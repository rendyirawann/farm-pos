<?php

namespace App\Services\Farm;

use App\Models\Farm\Item;
use App\Models\Farm\StockAdjustment;
use App\Models\Farm\StockLot;
use App\Models\Farm\StockOutLine;
use App\Models\Farm\StockOutLotUsage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Mesin stok Mooda Stok (modul peternakan).
 *
 * Dua besaran dipakai berdampingan: EKOR dan KG. Pengambilan FIFO memakai KG
 * sebagai patokan utama (karena harga umumnya per kg), dan jumlah ekor ikut
 * berkurang secara proporsional terhadap lot yang terpakai. Menyimpan keduanya
 * membuat susut bobot terlihat: 100 ekor/200kg masuk, 100 ekor/195kg keluar.
 */
class FarmStockService
{
    /** Peringatan non-fatal dari proses terakhir (mis. stok kurang). */
    public array $warnings = [];

    /**
     * Ambil stok item secara FIFO.
     *
     * @return array{cost: float, usages: array<int, array{lot_id:int, qty_ekor:int, weight_kg:float, cost:float}>}
     */
    public function consumeFifo(int $itemId, float $weightKg, int $qtyEkor): array
    {
        $sisaKg   = round($weightKg, 2);
        $sisaEkor = $qtyEkor;
        $totalCost = 0.0;
        $usages = [];

        $lots = StockLot::where('item_id', $itemId)->available()->fifo()->lockForUpdate()->get();

        foreach ($lots as $lot) {
            if ($sisaKg <= 0 && $sisaEkor <= 0) {
                break;
            }

            $ambilKg   = min($sisaKg, (float) $lot->weight_kg_left);
            $ambilEkor = min($sisaEkor, (int) $lot->qty_ekor_left);

            // Lot yang sudah kosong di kedua besaran dilewati.
            if ($ambilKg <= 0 && $ambilEkor <= 0) {
                continue;
            }

            // Biaya dihitung dari kg bila ada bobotnya, kalau tidak jatuh ke per ekor
            // (mis. penjualan yang benar-benar dihitung per ekor).
            $cost = $ambilKg > 0
                ? $ambilKg * (float) $lot->cost_per_kg
                : $ambilEkor * (float) $lot->cost_per_ekor;

            $lot->update([
                'weight_kg_left' => round((float) $lot->weight_kg_left - $ambilKg, 2),
                'qty_ekor_left'  => (int) $lot->qty_ekor_left - $ambilEkor,
            ]);

            $usages[] = [
                'lot_id'    => $lot->id,
                'qty_ekor'  => $ambilEkor,
                'weight_kg' => round($ambilKg, 2),
                'cost'      => round($cost, 2),
            ];

            $totalCost += $cost;
            $sisaKg    = round($sisaKg - $ambilKg, 2);
            $sisaEkor -= $ambilEkor;
        }

        // Stok kurang: transaksi TIDAK digagalkan (barang fisik memang sudah keluar),
        // tapi kekurangan dicatat agar terlihat dan bisa diluruskan lewat penyesuaian.
        if ($sisaKg > 0.001 || $sisaEkor > 0) {
            $item = Item::find($itemId);
            $this->warnings[] = sprintf(
                'Stok %s kurang %s kg / %d ekor — bagian ini tercatat berharga pokok 0. Luruskan lewat Penyesuaian Stok.',
                $item->name ?? "#{$itemId}",
                number_format(max(0, $sisaKg), 2),
                max(0, $sisaEkor)
            );
        }

        return ['cost' => round($totalCost, 2), 'usages' => $usages];
    }

    /** Catat pemakaian lot untuk satu baris penjualan. */
    public function recordUsages(StockOutLine $line, array $usages): void
    {
        foreach ($usages as $u) {
            StockOutLotUsage::create([
                'stock_out_line_id' => $line->id,
                'lot_id'    => $u['lot_id'],
                'qty_ekor'  => $u['qty_ekor'],
                'weight_kg' => $u['weight_kg'],
                'cost'      => $u['cost'],
            ]);
        }
    }

    /**
     * Kembalikan stok ke lot asalnya (dipakai saat nota penjualan dibatalkan/dihapus).
     * Mengembalikan ke lot yang sama, bukan membuat lot baru, supaya urutan FIFO utuh.
     */
    public function restoreFromStockOut(StockOutLine $line): void
    {
        foreach ($line->lotUsages()->get() as $u) {
            $lot = StockLot::find($u->lot_id);
            if ($lot) {
                $lot->update([
                    'weight_kg_left' => round((float) $lot->weight_kg_left + (float) $u->weight_kg, 2),
                    'qty_ekor_left'  => (int) $lot->qty_ekor_left + (int) $u->qty_ekor,
                ]);
            }
            $u->delete();
        }
    }

    /**
     * Terapkan penyesuaian stok (mati / susut / rusak / koreksi).
     * Pengurangan mengikuti FIFO bila lot tidak ditentukan.
     */
    public function applyAdjustment(StockAdjustment $adj): float
    {
        return DB::transaction(function () use ($adj) {
            $kg   = abs((float) $adj->weight_kg);
            $ekor = abs((int) $adj->qty_ekor);

            // Koreksi TAMBAH: buat lot baru berharga pokok rata-rata item saat ini,
            // supaya barang temuan tidak masuk dengan nilai 0.
            if ($adj->isAddition()) {
                $avg = $this->averageCostPerKg($adj->item_id);
                StockLot::create([
                    'item_id'           => $adj->item_id,
                    'stock_in_line_id'  => null,
                    'supplier_id'       => null,
                    'date'              => $adj->date,
                    'qty_ekor_initial'  => $ekor,
                    'weight_kg_initial' => $kg,
                    'qty_ekor_left'     => $ekor,
                    'weight_kg_left'    => $kg,
                    'cost_per_kg'       => $avg,
                    'cost_per_ekor'     => $ekor > 0 ? round($kg * $avg / $ekor, 2) : 0,
                ]);

                $dampak = round($kg * $avg, 2);
                $adj->update(['cost_impact' => $dampak]);

                return $dampak;
            }

            // Pengurangan: dari lot tertentu bila dipilih, kalau tidak ikuti FIFO.
            if ($adj->lot_id) {
                $lot = StockLot::whereKey($adj->lot_id)->lockForUpdate()->first();
                if (! $lot) {
                    throw new RuntimeException('Lot tidak ditemukan.');
                }
                $ambilKg   = min($kg, (float) $lot->weight_kg_left);
                $ambilEkor = min($ekor, (int) $lot->qty_ekor_left);
                $lot->update([
                    'weight_kg_left' => round((float) $lot->weight_kg_left - $ambilKg, 2),
                    'qty_ekor_left'  => (int) $lot->qty_ekor_left - $ambilEkor,
                ]);
                $dampak = round($ambilKg > 0 ? $ambilKg * (float) $lot->cost_per_kg
                                             : $ambilEkor * (float) $lot->cost_per_ekor, 2);

                $pemakaian = [[
                    'lot_id' => $lot->id, 'qty_ekor' => $ambilEkor,
                    'weight_kg' => round($ambilKg, 2), 'cost' => $dampak,
                ]];
            } else {
                $hasil  = $this->consumeFifo($adj->item_id, $kg, $ekor);
                $dampak = $hasil['cost'];
                $pemakaian = $hasil['usages'];
            }

            // Jejak lot yang susut. Tanpa ini, kerugian penyesuaian tidak bisa
            // dibebankan ke supplier mana pun sehingga HPP per supplier di menu
            // Gudang jadi angka gantung.
            $this->recordAdjustmentUsages($adj, $pemakaian);

            $adj->update(['cost_impact' => $dampak]);

            return $dampak;
        });
    }

    /** Simpan rincian lot mana saja yang berkurang karena satu penyesuaian. */
    private function recordAdjustmentUsages(StockAdjustment $adj, array $usages): void
    {
        DB::table('farm_adjustment_lot_usages')->where('adjustment_id', $adj->id)->delete();

        foreach ($usages as $u) {
            if ($u['weight_kg'] <= 0 && $u['qty_ekor'] <= 0) {
                continue;
            }
            DB::table('farm_adjustment_lot_usages')->insert([
                'tenant_id'     => $adj->tenant_id,
                'adjustment_id' => $adj->id,
                'lot_id'        => $u['lot_id'],
                'qty_ekor'      => $u['qty_ekor'],
                'weight_kg'     => $u['weight_kg'],
                'cost'          => $u['cost'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    /** Harga pokok rata-rata per kg dari lot yang masih tersisa. */
    public function averageCostPerKg(int $itemId): float
    {
        $row = StockLot::where('item_id', $itemId)
            ->selectRaw('COALESCE(SUM(weight_kg_left * cost_per_kg),0) as nilai, COALESCE(SUM(weight_kg_left),0) as kg')
            ->first();

        $kg = (float) ($row->kg ?? 0);

        return $kg > 0 ? round((float) $row->nilai / $kg, 2) : 0.0;
    }

    /**
     * Pratinjau harga pokok TANPA mengubah stok — dipakai layar Stock Out agar
     * margin terlihat saat kasir masih mengetik harga jual.
     */
    public function previewCost(int $itemId, float $weightKg, int $qtyEkor): array
    {
        $sisaKg = round($weightKg, 2);
        $sisaEkor = $qtyEkor;
        $total = 0.0;
        $lots = [];

        foreach (StockLot::where('item_id', $itemId)->available()->fifo()->get() as $lot) {
            if ($sisaKg <= 0 && $sisaEkor <= 0) {
                break;
            }
            $ambilKg   = min($sisaKg, (float) $lot->weight_kg_left);
            $ambilEkor = min($sisaEkor, (int) $lot->qty_ekor_left);
            if ($ambilKg <= 0 && $ambilEkor <= 0) {
                continue;
            }
            $cost = $ambilKg > 0 ? $ambilKg * (float) $lot->cost_per_kg
                                 : $ambilEkor * (float) $lot->cost_per_ekor;
            $total += $cost;
            $lots[] = [
                'lot_id'      => $lot->id,
                'tanggal'     => optional($lot->date)->format('d/m/Y'),
                'supplier_id' => $lot->supplier_id,
                'supplier'    => $lot->supplier?->name,
                'weight_kg'   => round($ambilKg, 2),
                'qty_ekor'    => $ambilEkor,
                'cost_per_kg' => (float) $lot->cost_per_kg,
            ];
            $sisaKg = round($sisaKg - $ambilKg, 2);
            $sisaEkor -= $ambilEkor;
        }

        // Harga pokok TERTIMBANG atas seluruh lot yang akan terpakai. Kalau hanya lot
        // pertama yang ditampilkan, penjual bisa yakin marginnya Rp2.000 padahal
        // rata-rata sebenarnya jauh lebih tinggi.
        $terpakaiKg   = round(array_sum(array_column($lots, 'weight_kg')), 2);
        $terpakaiEkor = (int) array_sum(array_column($lots, 'qty_ekor'));

        return [
            'cost'        => round($total, 2),
            'lots'        => $lots,
            'kurang_kg'   => max(0, $sisaKg),
            'kurang_ekor' => max(0, $sisaEkor),
            'hpp_per_kg'  => $terpakaiKg > 0.001 ? round($total / $terpakaiKg, 2) : null,
            'hpp_per_ekor' => $terpakaiEkor > 0 ? round($total / $terpakaiEkor, 2) : null,
            'terpakai_kg'   => $terpakaiKg,
            'terpakai_ekor' => $terpakaiEkor,
            // Acuan harga beli terakhir diambil dari supplier lot yang akan dipakai
            // FIFO — bukan lintas supplier, karena harga tiap supplier bisa jauh beda
            // (ayam kecil vs besar) dan salah acuan justru menekan harga jual.
            'acuan' => $this->hargaBeliTerakhir($itemId, $lots[0]['supplier_id'] ?? null),
        ];
    }

    /**
     * Harga beli terakhir untuk (item, supplier) — sudah termasuk koreksi realisasi,
     * karena angkanya diambil dari lot, bukan dari baris nota mentah.
     *
     * @return array{state:string, harga_kg:?float, harga_ekor:?float, tanggal:?string, supplier:?string, umur_hari:?int}
     */
    public function hargaBeliTerakhir(int $itemId, ?int $supplierId): array
    {
        $kosong = ['state' => 'none', 'harga_kg' => null, 'harga_ekor' => null,
            'tanggal' => null, 'supplier' => null, 'umur_hari' => null];

        if (! $supplierId) {
            // Telur / barang produksi sendiri tidak punya harga beli — acuan pembelian
            // tidak boleh dikarang 0, itu membuat peringatan jadi sampah.
            return $kosong;
        }

        $lot = StockLot::with('supplier')
            ->where('item_id', $itemId)
            ->where('supplier_id', $supplierId)
            ->where('source', 'purchase')
            ->where(fn ($q) => $q->where('weight_kg_initial', '>', 0)->orWhere('qty_ekor_initial', '>', 0))
            ->orderByDesc('date')->orderByDesc('id')
            ->first();

        if (! $lot) {
            return $kosong;
        }

        $umur = (int) $lot->date->diffInDays(now());

        return [
            // Lebih dari 14 hari: harga ayam hidup berubah cepat, acuan sudah basi
            // dan tidak layak dipakai menyarankan harga.
            'state'      => $umur > 14 ? 'stale' : 'ok',
            'harga_kg'   => (float) $lot->cost_per_kg > 0 ? (float) $lot->cost_per_kg : null,
            'harga_ekor' => (float) $lot->cost_per_ekor > 0 ? (float) $lot->cost_per_ekor : null,
            'tanggal'    => $lot->date->format('d/m/Y'),
            'supplier'   => $lot->supplier?->name,
            'umur_hari'  => $umur,
        ];
    }
}
