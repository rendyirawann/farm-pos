<?php

namespace App\Services\Farm;

use App\Models\Farm\StockIn;
use App\Models\Farm\StockInRealization;
use App\Models\Farm\StockInRealizationLine;
use App\Models\Farm\StockLot;
use App\Models\Farm\StockOutLotUsage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * REALISASI barang masuk — hasil timbang ulang saat barang benar-benar diterima.
 *
 * Aturan yang dipegang:
 *   1. SATU NOTA = SATU REALISASI. Tidak ada tumpukan baris; koreksi berikutnya
 *      hanya lewat batal + catat ulang.
 *   2. Yang diminta ke petugas adalah ANGKA NYATA ("diterima berapa ekor / berapa
 *      kg"), bukan selisih dan bukan pilihan kurang/lebih. Arah uang dihitung
 *      sistem, sebab menyuruh petugas menyimpulkan arah adalah sumber salah tanda.
 *   3. Selisih menyesuaikan SALDO DEPOSIT supplier (tidak ada lagi piutang supplier):
 *      barang kurang -> saldo naik, barang lebih -> saldo turun.
 *   4. Barang lebih TIDAK membuat lot baru — lot nota itu yang disesuaikan ke angka
 *      nyata, dan keterangan "lebih x kg" tetap tersimpan pada baris realisasi.
 *   5. Karena (4) mengubah isi & harga pokok lot, realisasi hanya boleh selama lot
 *      BELUM terpakai (belum terjual, belum kena penyesuaian). Kalau sudah terpakai,
 *      harga pokok yang sudah dibukukan akan berubah retroaktif — itu ditolak.
 */
class RealizationService
{
    public function __construct(private DepositService $deposit) {}

    /**
     * Catat realisasi satu nota sekaligus.
     *
     * @param  array  $data  ['date','reason','notes','lines' => [line_id => ['qty_ekor','weight_kg']]]
     *
     * @throws RuntimeException
     */
    public function record(StockIn $stockIn, array $data): StockInRealization
    {
        return DB::transaction(function () use ($stockIn, $data) {
            if (StockInRealization::where('stock_in_id', $stockIn->id)->exists()) {
                throw new RuntimeException('Nota ini sudah punya realisasi. Batalkan dulu bila ingin mengubahnya.');
            }

            $notaLines = $stockIn->lines()->with('item')->get();
            if ($notaLines->isEmpty()) {
                throw new RuntimeException('Nota ini tidak punya baris barang.');
            }

            $header = StockInRealization::create([
                'stock_in_id' => $stockIn->id,
                'supplier_id' => $stockIn->supplier_id,
                'date'        => $data['date'] ?? now()->toDateString(),
                'reason'      => $data['reason'] ?? 'kurang_timbang',
                'user_id'     => Auth::id(),
                'notes'       => $data['notes'] ?? null,
            ]);

            $totalNilai = 0.0;
            $totalEkor  = 0;
            $totalKg    = 0.0;
            $adaSelisih = false;

            foreach ($notaLines as $line) {
                $isi = $data['lines'][$line->id] ?? null;

                // Baris yang dibiarkan kosong berarti diterima sesuai nota.
                $nyataEkor = $this->angka($isi['qty_ekor'] ?? null) === null
                    ? (int) $line->qty_ekor
                    : max(0, (int) $isi['qty_ekor']);
                $nyataKg = $this->angka($isi['weight_kg'] ?? null) === null
                    ? round((float) $line->weight_kg, 2)
                    : max(0, round((float) $isi['weight_kg'], 2));

                $deltaEkor = $nyataEkor - (int) $line->qty_ekor;
                $deltaKg   = round($nyataKg - (float) $line->weight_kg, 2);

                // Nilai selisih memakai DASAR HARGA nota — kalau nota dihargai per
                // ekor, selisih kg tidak boleh dinilai per kg (dan sebaliknya).
                // Tanda dibalik supaya positif = saldo supplier NAIK (barang kurang).
                $nilai = $line->price_basis === 'ekor'
                    ? -1 * $deltaEkor * (float) $line->unit_price
                    : -1 * $deltaKg * (float) $line->unit_price;

                $lot = StockLot::where('stock_in_line_id', $line->id)->lockForUpdate()->first();

                if ($deltaEkor !== 0 || abs($deltaKg) >= 0.005) {
                    $adaSelisih = true;
                    $this->pastikanLotBelumTerpakai($lot, $line->item?->name ?? 'barang ini');
                    $this->sesuaikanLot($lot, $nyataEkor, $nyataKg, $line);
                }

                StockInRealizationLine::create([
                    'realization_id'     => $header->id,
                    'stock_in_line_id'   => $line->id,
                    'lot_id'             => $lot?->id,
                    'nota_qty_ekor'      => (int) $line->qty_ekor,
                    'nota_weight_kg'     => round((float) $line->weight_kg, 2),
                    'received_qty_ekor'  => $nyataEkor,
                    'received_weight_kg' => $nyataKg,
                    'delta_qty_ekor'     => $deltaEkor,
                    'delta_weight_kg'    => $deltaKg,
                    'price_basis'        => $line->price_basis,
                    'unit_price'         => (float) $line->unit_price,
                    'value'              => round($nilai, 2),
                ]);

                $totalNilai += $nilai;
                $totalEkor  += $deltaEkor;
                $totalKg    += $deltaKg;
            }

            if (! $adaSelisih) {
                throw new RuntimeException('Semua barang sudah sesuai nota — tidak ada yang perlu direalisasi.');
            }

            $header->update([
                'delta_qty_ekor'  => $totalEkor,
                'delta_weight_kg' => round($totalKg, 2),
                'value'           => round($totalNilai, 2),
            ]);

            // Selisihnya menyesuaikan saldo deposit supplier.
            if ($stockIn->supplier_id) {
                $this->deposit->adjustForRealization(
                    $header->id,
                    $stockIn->supplier_id,
                    $header->date,
                    round($totalNilai, 2),
                    'Realisasi nota ' . $stockIn->invoice_no
                );
            }

            return $header->fresh('lines');
        });
    }

    /**
     * Batalkan realisasi: lot dikembalikan ke angka nota dan koreksi saldo dibalik.
     * Baris buku besar lama tidak dihapus — dibukukan baris balik agar ada jejaknya.
     */
    public function revert(StockInRealization $r): void
    {
        DB::transaction(function () use ($r) {
            foreach ($r->lines()->with('line.item')->get() as $rl) {
                $lot = $rl->lot_id ? StockLot::whereKey($rl->lot_id)->lockForUpdate()->first() : null;

                if ($lot && ! $rl->isSesuai()) {
                    $this->pastikanLotBelumTerpakai($lot, $rl->line?->item?->name ?? 'barang ini');
                    $this->sesuaikanLot($lot, (int) $rl->nota_qty_ekor, (float) $rl->nota_weight_kg, $rl->line);
                }

                $rl->delete();
            }

            $this->deposit->reverseByReference('realization', $r->id, 'Realisasi dibatalkan');
            $r->delete();
        });
    }

    /**
     * Lot yang isinya sudah dipakai tidak boleh diubah: harga pokok penjualan yang
     * sudah dibukukan akan berubah retroaktif dan laba periode lalu ikut bergeser.
     */
    private function pastikanLotBelumTerpakai(?StockLot $lot, string $namaBarang): void
    {
        if (! $lot) {
            return;
        }

        $terjual = StockOutLotUsage::where('lot_id', $lot->id)->exists();
        $susut   = DB::table('farm_adjustment_lot_usages')->where('lot_id', $lot->id)->exists();

        if ($terjual || $susut) {
            throw new RuntimeException(
                'Stok "' . $namaBarang . '" dari nota ini sudah dipakai (terjual atau kena penyesuaian), '
                . 'jadi angkanya tidak bisa diubah lagi. Realisasi harus dicatat sebelum barang keluar. '
                . 'Untuk selisih yang baru ketahuan sekarang, pakai menu Penyesuaian Stok.'
            );
        }
    }

    /** Setel lot ke angka NYATA (absolut) beserta harga pokoknya. */
    private function sesuaikanLot(?StockLot $lot, int $ekor, float $kg, $line): void
    {
        if (! $lot || ! $line) {
            return;
        }

        // Subtotal nyata memakai dasar harga nota — inilah nilai lot sesungguhnya.
        $subtotal = $line->price_basis === 'ekor'
            ? $ekor * (float) $line->unit_price
            : $kg * (float) $line->unit_price;

        $lot->update([
            'qty_ekor_initial'  => $ekor,
            'weight_kg_initial' => round($kg, 2),
            'qty_ekor_left'     => $ekor,
            'weight_kg_left'    => round($kg, 2),
            'cost_per_kg'       => $kg > 0 ? round($subtotal / $kg, 2) : 0,
            'cost_per_ekor'     => $ekor > 0 ? round($subtotal / $ekor, 2) : 0,
        ]);
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

    /** Nilai kosong ("" / null) dibedakan dari angka 0 yang memang diisi petugas. */
    private function angka($v): ?string
    {
        return ($v === null || $v === '') ? null : (string) $v;
    }
}
