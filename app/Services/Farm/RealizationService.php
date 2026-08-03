<?php

namespace App\Services\Farm;

use App\Models\Farm\StockIn;
use App\Models\Farm\StockInLine;
use App\Models\Farm\StockInRealization;
use App\Models\Farm\StockLot;
use App\Models\Farm\SupplierSettlement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * REALISASI barang masuk & PIUTANG SUPPLIER.
 *
 * Alur: barang datang dicatat sesuai surat jalan supplier. Saat ditimbang ulang
 * ternyata kurang (susut perjalanan, ada yang mati, atau timbangan supplier beda).
 * Kekurangan itu:
 *   1) mengurangi STOK ke angka nyata — lewat lot pembelian yang bersangkutan,
 *      bukan FIFO, karena yang kurang adalah barang dari lot ITU;
 *   2) menjadi PIUTANG SUPPLIER senilai selisih x harga satuan nota.
 *
 * Piutang tersebut kemudian bisa ditutup oleh pembelian berikutnya dari supplier
 * yang sama — dialokasikan ke realisasi TERTUA lebih dulu agar tidak ada yang
 * menggantung terlalu lama.
 */
class RealizationService
{
    /**
     * Catat realisasi pada satu baris nota pembelian.
     *
     * @throws RuntimeException bila kekurangan melebihi yang tercatat / stok lot habis
     */
    public function record(StockInLine $line, array $data): StockInRealization
    {
        return DB::transaction(function () use ($line, $data) {
            $ekorKurang = max(0, (int) ($data['qty_ekor_short'] ?? 0));
            $kgKurang   = max(0, round((float) ($data['weight_kg_short'] ?? 0), 2));

            if ($ekorKurang <= 0 && $kgKurang <= 0) {
                throw new RuntimeException('Isi kekurangan ekor atau kilogram.');
            }

            // Tidak boleh melebihi yang tercatat saat barang masuk — kalau melebihi,
            // berarti yang salah adalah pencatatan awalnya, bukan realisasinya.
            $sudah = StockInRealization::where('stock_in_line_id', $line->id)->get();
            $sisaEkor = (int) $line->qty_ekor - (int) $sudah->sum('qty_ekor_short');
            $sisaKg   = round((float) $line->weight_kg - (float) $sudah->sum('weight_kg_short'), 2);

            if ($ekorKurang > $sisaEkor) {
                throw new RuntimeException("Kekurangan ekor melebihi sisa yang bisa direalisasi ({$sisaEkor} ekor).");
            }
            if ($kgKurang > $sisaKg + 0.001) {
                throw new RuntimeException('Kekurangan kg melebihi sisa yang bisa direalisasi (' . number_format($sisaKg, 2, ',', '.') . ' kg).');
            }

            // Nilai kekurangan memakai DASAR HARGA nota, bukan menebak.
            $nilai = $line->price_basis === 'ekor'
                ? $ekorKurang * (float) $line->unit_price
                : $kgKurang * (float) $line->unit_price;

            // ---- Kurangi stok pada lot milik baris ini ----
            $lot = StockLot::where('stock_in_line_id', $line->id)->lockForUpdate()->first();
            if ($lot) {
                $lot->update([
                    'qty_ekor_left'  => max(0, (int) $lot->qty_ekor_left - $ekorKurang),
                    'weight_kg_left' => max(0, round((float) $lot->weight_kg_left - $kgKurang, 2)),
                    // Jumlah awal ikut dikoreksi supaya laporan stok tidak menampilkan
                    // barang yang sebenarnya tidak pernah ada.
                    'qty_ekor_initial'  => max(0, (int) $lot->qty_ekor_initial - $ekorKurang),
                    'weight_kg_initial' => max(0, round((float) $lot->weight_kg_initial - $kgKurang, 2)),
                ]);
            }

            $stockIn = $line->stockIn;

            return StockInRealization::create([
                'stock_in_id'      => $line->stock_in_id,
                'stock_in_line_id' => $line->id,
                'supplier_id'      => $stockIn?->supplier_id,
                'date'             => $data['date'] ?? now()->toDateString(),
                'reason'           => $data['reason'] ?? 'kurang_timbang',
                'qty_ekor_short'   => $ekorKurang,
                'weight_kg_short'  => $kgKurang,
                'value'            => round($nilai, 2),
                'settled_amount'   => 0,
                'status'           => 'open',
                'user_id'          => Auth::id(),
                'notes'            => $data['notes'] ?? null,
            ]);
        });
    }

    /** Batalkan realisasi: stok dikembalikan ke lot, alokasi penutupan ikut dibatalkan. */
    public function revert(StockInRealization $r): void
    {
        DB::transaction(function () use ($r) {
            if ($r->settlements()->exists()) {
                throw new RuntimeException('Realisasi ini sudah ditutup pembelian lain — batalkan penutupannya dulu.');
            }

            $lot = StockLot::where('stock_in_line_id', $r->stock_in_line_id)->lockForUpdate()->first();
            if ($lot) {
                $lot->update([
                    'qty_ekor_left'     => (int) $lot->qty_ekor_left + (int) $r->qty_ekor_short,
                    'weight_kg_left'    => round((float) $lot->weight_kg_left + (float) $r->weight_kg_short, 2),
                    'qty_ekor_initial'  => (int) $lot->qty_ekor_initial + (int) $r->qty_ekor_short,
                    'weight_kg_initial' => round((float) $lot->weight_kg_initial + (float) $r->weight_kg_short, 2),
                ]);
            }

            $r->delete();
        });
    }

    /**
     * Pakai nota pembelian BARU untuk menutup piutang supplier.
     * Dialokasikan ke realisasi tertua lebih dulu, maksimal sebesar nilai nota.
     *
     * @return array{terpakai: float, sisa_piutang: float, lunas: bool, rincian: array}
     */
    public function applyCredit(StockIn $stockIn, ?float $batas = null): array
    {
        return DB::transaction(function () use ($stockIn, $batas) {
            if (! $stockIn->supplier_id) {
                return ['terpakai' => 0.0, 'sisa_piutang' => 0.0, 'lunas' => false, 'rincian' => []];
            }

            // Yang bisa ditutup paling banyak sebesar nilai nota ini yang belum tertutup.
            $ruang = $batas !== null
                ? min($batas, $stockIn->remainingToPay())
                : $stockIn->remainingToPay();

            if ($ruang <= 0.01) {
                return ['terpakai' => 0.0, 'sisa_piutang' => $this->outstanding($stockIn->supplier_id),
                        'lunas' => false, 'rincian' => []];
            }

            $terpakai = 0.0;
            $rincian = [];

            $daftar = StockInRealization::where('supplier_id', $stockIn->supplier_id)
                ->whereColumn('settled_amount', '<', 'value')
                ->orderBy('date')->orderBy('id')
                ->lockForUpdate()->get();

            foreach ($daftar as $r) {
                if ($ruang <= 0.01) {
                    break;
                }
                $ambil = min($r->remaining(), $ruang);
                if ($ambil <= 0.01) {
                    continue;
                }

                SupplierSettlement::create([
                    'supplier_id'    => $stockIn->supplier_id,
                    'realization_id' => $r->id,
                    'stock_in_id'    => $stockIn->id,
                    'date'           => $stockIn->date,
                    'amount'         => round($ambil, 2),
                    'user_id'        => Auth::id(),
                    'notes'          => 'Ditutup nota pembelian ' . $stockIn->invoice_no,
                ]);

                $baru = round((float) $r->settled_amount + $ambil, 2);
                $r->update([
                    'settled_amount' => $baru,
                    'status'         => $baru >= (float) $r->value - 0.01 ? 'settled' : 'open',
                ]);

                $rincian[] = [
                    'realisasi' => $r->id,
                    'tanggal'   => $r->date->format('d/m/Y'),
                    'jumlah'    => round($ambil, 2),
                ];

                $terpakai += $ambil;
                $ruang    -= $ambil;
            }

            if ($terpakai > 0) {
                $stockIn->update(['credit_applied' => round((float) $stockIn->credit_applied + $terpakai, 2)]);
                $this->syncPaymentStatus($stockIn->fresh());
            }

            return [
                'terpakai'     => round($terpakai, 2),
                'sisa_piutang' => $this->outstanding($stockIn->supplier_id),
                'lunas'        => $stockIn->fresh()->remainingToPay() <= 0.01,
                'rincian'      => $rincian,
            ];
        });
    }

    /** Batalkan seluruh penutupan piutang oleh satu nota pembelian. */
    public function revokeCredit(StockIn $stockIn): void
    {
        DB::transaction(function () use ($stockIn) {
            foreach ($stockIn->settlements()->get() as $s) {
                $r = $s->realization;
                if ($r) {
                    $baru = max(0, round((float) $r->settled_amount - (float) $s->amount, 2));
                    $r->update(['settled_amount' => $baru, 'status' => $baru >= (float) $r->value - 0.01 ? 'settled' : 'open']);
                }
                $s->delete();
            }
            $stockIn->update(['credit_applied' => 0]);
            $this->syncPaymentStatus($stockIn->fresh());
        });
    }

    /** Status lunas mengikuti sisa yang harus dibayar; tidak diisi manual agar tidak melenceng. */
    public function syncPaymentStatus(StockIn $stockIn): void
    {
        $lunas = $stockIn->remainingToPay() <= 0.01;
        $stockIn->update([
            'payment_status' => $lunas ? 'paid' : 'unpaid',
            'paid_at'        => $lunas ? ($stockIn->paid_at ?? now()->toDateString()) : null,
        ]);
    }

    public function outstanding(int $supplierId): float
    {
        return (float) StockInRealization::where('supplier_id', $supplierId)
            ->selectRaw('COALESCE(SUM(value - settled_amount), 0) as sisa')
            ->value('sisa');
    }
}
