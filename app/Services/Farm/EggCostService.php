<?php

namespace App\Services\Farm;

use App\Models\Expense;
use App\Models\Farm\EggProduction;
use Carbon\Carbon;

/**
 * Harga pokok telur — DIHITUNG OTOMATIS, tidak diisi manual.
 *
 * Telur tidak dibeli dari supplier, jadi tidak punya harga beli. Kalau dipaksa
 * masuk lewat Stock In dengan harga 0, marginnya akan terlihat 100% — angka yang
 * menyesatkan untuk mengambil keputusan.
 *
 * Rumusnya: biaya operasional periode (pakan, obat, tenaga, dsb. dari modul
 * Pengeluaran) dibagi jumlah butir bersih yang diproduksi pada periode yang sama.
 *
 *      HPP per butir = Σ pengeluaran bulan berjalan ÷ Σ butir bersih bulan berjalan
 *
 * Dipakai bulanan karena biaya pakan datang bergelombang (beli sekali untuk
 * beberapa minggu), sehingga hitungan harian akan naik-turun liar dan tidak berguna.
 */
class EggCostService
{
    /** Harga pokok satu butir pada tanggal tertentu (memakai bulan tanggal itu). */
    public function costPerButir(?Carbon $date = null): float
    {
        $date  = $date ? $date->copy() : Carbon::today();
        $start = $date->copy()->startOfMonth();
        $end   = $date->copy()->endOfMonth();

        $rincian = $this->breakdown($start, $end);

        return $rincian['cost_per_butir'];
    }

    /**
     * Rincian perhitungan, dipakai layar laporan supaya angkanya bisa ditelusuri
     * (bukan angka ajaib).
     *
     * @return array{biaya: float, butir: int, cost_per_butir: float, periode: string}
     */
    public function breakdown(Carbon $start, Carbon $end): array
    {
        $biaya = (float) Expense::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $produksi = EggProduction::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(qty_butir),0) as butir, COALESCE(SUM(qty_broken),0) as pecah')
            ->first();

        $butirBersih = max(0, (int) ($produksi->butir ?? 0) - (int) ($produksi->pecah ?? 0));

        return [
            'biaya'          => round($biaya, 2),
            'butir'          => $butirBersih,
            'butir_kotor'    => (int) ($produksi->butir ?? 0),
            'butir_pecah'    => (int) ($produksi->pecah ?? 0),
            'cost_per_butir' => $butirBersih > 0 ? round($biaya / $butirBersih, 2) : 0.0,
            'periode'        => $start->translatedFormat('F Y'),
        ];
    }

    /**
     * Harga pokok untuk sejumlah butir. Dipakai saat telur dijual: nilainya
     * dibekukan ke baris penjualan supaya laporan lama tidak berubah ketika
     * biaya bulan berjalan bertambah.
     */
    public function costFor(int $qtyButir, ?Carbon $date = null): float
    {
        return round($qtyButir * $this->costPerButir($date), 2);
    }
}
