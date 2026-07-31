<?php

namespace App\Services\Fnb;

use App\Models\Fnb\Ingredient;
use App\Models\Fnb\IngredientBatch;
use App\Models\Fnb\MenuIngredient;
use App\Models\Fnb\StockMovement;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * MESIN STOK & HPP (F&B) — FIFO/FEFO per lot.
 *
 * Prinsip (mengacu dokumen analisa):
 *  - Stok TIDAK disimpan sebagai satu angka; stok = SUM(remaining_quantity) semua lot.
 *  - Pengurasan memakai FEFO: lot paling dekat kadaluarsa dulu, tie-break FIFO (entry_date, id).
 *  - HPP = biaya konsumsi NYATA = Σ(porsi dikuras tiap lot × harga beli lot itu).
 *  - Idempoten: order_details.is_stock_deducted menjaga stok dipotong TEPAT SEKALI.
 *  - Stok kurang tidak memblokir penjualan; kekurangan dicatat sebagai gerakan
 *    'sales_deduction_out_of_stock' dengan cost 0 (HPP jadi understated) + dilaporkan
 *    ke pemanggil sebagai peringatan.
 */
class StockService
{
    /** Kekurangan stok pada operasi terakhir: [ ['ingredient' => nama, 'short' => qty] ]. */
    public array $shortages = [];

    /**
     * Kuras 1 bahan sejumlah $quantity mengikuti FEFO. Mengembalikan total biaya (COGS).
     *
     * @param  int|null  $preferBatchId  lot yang dipilih manual dari layar dapur (opsional)
     */
    public function deductIngredient(
        int $ingredientId,
        float $quantity,
        ?int $orderDetailId = null,
        ?int $preferBatchId = null,
        string $reason = 'sales_deduction',
        ?string $reference = null
    ): float {
        if ($quantity <= 0) {
            return 0.0;
        }

        $totalCost = 0.0;
        $remaining = $quantity;

        // Lot pilihan manual didahulukan, sisanya lanjut FEFO.
        $batches = collect();
        if ($preferBatchId) {
            $picked = IngredientBatch::where('ingredient_id', $ingredientId)
                ->where('id', $preferBatchId)->where('remaining_quantity', '>', 0)->first();
            if ($picked) {
                $batches->push($picked);
            }
        }
        $batches = $batches->merge(
            IngredientBatch::where('ingredient_id', $ingredientId)
                ->when($preferBatchId, fn ($q) => $q->where('id', '!=', $preferBatchId))
                ->fefo()->get()
        );

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }
            $take = min((float) $batch->remaining_quantity, $remaining);
            if ($take <= 0) {
                continue;
            }

            $batch->remaining_quantity = (float) $batch->remaining_quantity - $take;
            $batch->save();

            $cost = round($take * (float) $batch->buy_price, 2);
            $totalCost += $cost;
            $remaining -= $take;

            StockMovement::create([
                'ingredient_id'       => $ingredientId,
                'ingredient_batch_id' => $batch->id,
                'order_detail_id'     => $orderDetailId,
                'type'                => 'out',
                'quantity'            => $take,
                'cost_total'          => $cost,
                'reason'              => $reason,
                'reference'           => $reference,
            ]);
        }

        // Stok kurang: catat kekurangan (biaya 0) supaya kartu stok tetap jujur.
        if ($remaining > 0.0001) {
            StockMovement::create([
                'ingredient_id'   => $ingredientId,
                'order_detail_id' => $orderDetailId,
                'type'            => 'out',
                'quantity'        => $remaining,
                'cost_total'      => 0,
                'reason'          => 'sales_deduction_out_of_stock',
                'reference'       => $reference,
            ]);
            $this->shortages[] = [
                'ingredient' => Ingredient::find($ingredientId)?->name ?? ('#' . $ingredientId),
                'short'      => round($remaining, 2),
            ];
        }

        return round($totalCost, 2);
    }

    /**
     * Potong stok seluruh bahan resep untuk 1 baris pesanan, lalu simpan HPP-nya.
     * Aman dipanggil berulang (dilindungi is_stock_deducted).
     *
     * @param  array  $batchSelections  [ingredient_id => batch_id] pilihan manual dari dapur
     * @return float  HPP baris pesanan (0 bila menu tanpa resep / sudah pernah dipotong)
     */
    public function deductMenuStock(OrderDetail $detail, array $batchSelections = []): float
    {
        if ($detail->is_stock_deducted) {
            return (float) $detail->hpp;
        }

        $recipe = MenuIngredient::where('menu_id', $detail->menu_id)->get();

        // Menu tanpa resep: tandai selesai agar tak dicek berulang; HPP tetap 0.
        if ($recipe->isEmpty()) {
            $detail->forceFill(['is_stock_deducted' => true])->save();
            return 0.0;
        }

        $this->shortages = [];

        $hpp = DB::transaction(function () use ($detail, $recipe, $batchSelections) {
            // Kunci baris + cek ulang di dalam transaksi -> anti balapan (dapur diklik 2x).
            $fresh = OrderDetail::whereKey($detail->getKey())->lockForUpdate()->first();
            if (! $fresh || $fresh->is_stock_deducted) {
                return (float) ($fresh->hpp ?? 0);
            }

            $qty   = max(1, (int) $fresh->qty);
            $total = 0.0;

            foreach ($recipe as $line) {
                $need = (float) $line->quantity * $qty;
                $total += $this->deductIngredient(
                    (int) $line->ingredient_id,
                    $need,
                    (int) $fresh->id,
                    isset($batchSelections[$line->ingredient_id]) ? (int) $batchSelections[$line->ingredient_id] : null,
                    'sales_deduction',
                    'order_detail#' . $fresh->id
                );
            }

            $total = round($total, 2);
            $fresh->forceFill(['hpp' => $total, 'is_stock_deducted' => true])->save();

            return $total;
        });

        return (float) $hpp;
    }

    /**
     * Tambah stok = buat lot baru (pembelian/restock). Harga beli diisi TOTAL,
     * harga satuan dihitung = total / qty (sesuai desain).
     */
    public function addBatch(array $data): IngredientBatch
    {
        $qty   = (float) $data['quantity'];
        $total = (float) ($data['buy_price_total'] ?? 0);
        $unit  = $qty > 0 ? round($total / $qty, 2) : 0;

        return DB::transaction(function () use ($data, $qty, $total, $unit) {
            $batch = IngredientBatch::create([
                'ingredient_id'      => $data['ingredient_id'],
                'supplier_id'        => $data['supplier_id'] ?? null,
                'initial_quantity'   => $qty,
                'remaining_quantity' => $qty,
                'buy_price'          => $unit,
                'buy_price_total'    => $total,
                'entry_date'         => $data['entry_date'] ?? now()->toDateString(),
                'expiry_date'        => $data['expiry_date'] ?? null,
            ]);

            StockMovement::create([
                'ingredient_id'       => $batch->ingredient_id,
                'ingredient_batch_id' => $batch->id,
                'type'                => 'in',
                'quantity'            => $qty,
                'cost_total'          => $total,
                'reason'              => 'purchase',
                'reference'           => $data['reference'] ?? null,
            ]);

            return $batch;
        });
    }

    /**
     * Stok keluar manual (rusak/tumpah/buang/koreksi) — tetap FEFO agar nilai stok akurat.
     * Mengembalikan biaya bahan yang keluar.
     */
    public function adjustOut(int $ingredientId, float $qty, string $reason = 'waste', ?string $reference = null): float
    {
        return DB::transaction(fn () => $this->deductIngredient($ingredientId, $qty, null, null, $reason, $reference));
    }

    /**
     * Koreksi hasil opname: selisih positif -> lot baru (harga = harga lot terakhir),
     * selisih negatif -> kuras FEFO. Keduanya dicatat reason 'stock_opname'.
     */
    public function applyOpnameDifference(int $ingredientId, float $difference, ?string $reference = null): void
    {
        if (abs($difference) < 0.0001) {
            return;
        }

        if ($difference > 0) {
            $lastPrice = (float) (IngredientBatch::where('ingredient_id', $ingredientId)
                ->orderByDesc('id')->value('buy_price') ?? 0);

            $batch = IngredientBatch::create([
                'ingredient_id'      => $ingredientId,
                'initial_quantity'   => $difference,
                'remaining_quantity' => $difference,
                'buy_price'          => $lastPrice,
                'buy_price_total'    => round($difference * $lastPrice, 2),
                'entry_date'         => now()->toDateString(),
            ]);

            StockMovement::create([
                'ingredient_id'       => $ingredientId,
                'ingredient_batch_id' => $batch->id,
                'type'                => 'in',
                'quantity'            => $difference,
                'cost_total'          => round($difference * $lastPrice, 2),
                'reason'              => 'stock_opname',
                'reference'           => $reference,
            ]);
            return;
        }

        $this->deductIngredient($ingredientId, abs($difference), null, null, 'stock_opname', $reference);
    }
}
