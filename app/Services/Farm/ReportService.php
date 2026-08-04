<?php

namespace App\Services\Farm;

use App\Models\Farm\Agent;
use App\Models\Farm\Item;
use App\Models\Farm\StockIn;
use App\Models\Farm\StockLot;
use App\Models\Farm\StockOut;
use App\Models\Farm\Supplier;
use App\Models\Farm\SupplierDeposit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * MESIN LAPORAN — semua angka laporan dihitung di sini, satu tempat.
 *
 * Setiap laporan mengembalikan bentuk yang sama:
 *
 *   [
 *     'judul'    => 'LAPORAN PEMBELIAN',
 *     'ringkas'  => [ ['label' => ..., 'nilai' => ..., 'jenis' => 'rp|kg|ekor|num|txt'], ... ],
 *     'blok'     => [ ['judul' => ..., 'kolom' => [...], 'baris' => [...], 'total' => [...]], ... ],
 *     'catatan'  => 'kalimat penutup',
 *   ]
 *
 * Dengan bentuk seragam ini, tampilan layar dan berkas PDF memakai SATU perender
 * yang sama — sehingga angka di layar dan di PDF mustahil berbeda, dan menambah
 * laporan baru tidak perlu menyentuh tata letak sama sekali.
 */
class ReportService
{
    /** Daftar laporan yang tersedia beserta filter yang relevan untuk masing-masing. */
    public const JENIS = [
        'ringkasan' => [
            'nama'   => 'Ringkasan Usaha',
            'ikon'   => 'ki-chart-simple',
            'untuk'  => 'Satu halaman: pembelian, penjualan, laba, susut, nilai stok, uang di supplier, dan piutang agen.',
            'filter' => ['periode'],
        ],
        'pembelian' => [
            'nama'   => 'Pembelian per Supplier',
            'ikon'   => 'ki-entrance-left',
            'untuk'  => 'Rincian nota barang masuk, koreksi realisasi, dan rekap belanja tiap supplier.',
            'filter' => ['periode', 'supplier'],
        ],
        'penjualan' => [
            'nama'   => 'Penjualan & Laba',
            'ikon'   => 'ki-entrance-right',
            'untuk'  => 'Nota barang keluar dengan harga pokok, laba, dan margin — per agen atau seluruhnya.',
            'filter' => ['periode', 'agen'],
        ],
        'kartu-stok' => [
            'nama'   => 'Kartu Stok',
            'ikon'   => 'ki-book',
            'untuk'  => 'Mutasi tiap barang: saldo awal, masuk, keluar, susut, saldo akhir.',
            'filter' => ['periode', 'item'],
        ],
        'stok-supplier' => [
            'nama'   => 'Stok & HPP per Supplier',
            'ikon'   => 'ki-package',
            'untuk'  => 'Sisa stok yang masih ada beserta nilai persediaan dan harga pokoknya.',
            'filter' => ['supplier'],
        ],
        'deposit' => [
            'nama'   => 'Deposit Supplier',
            'ikon'   => 'ki-wallet',
            'untuk'  => 'Mutasi uang di tiap supplier: setoran, potongan pembelian, koreksi realisasi, saldo akhir.',
            'filter' => ['periode', 'supplier'],
        ],
        'piutang' => [
            'nama'   => 'Piutang Agen',
            'ikon'   => 'ki-dollar',
            'untuk'  => 'Nota yang belum lunas dikelompokkan menurut umur tunggakan.',
            'filter' => ['agen'],
        ],
        'susut' => [
            'nama'   => 'Susut & Penyesuaian',
            'ikon'   => 'ki-arrows-circle',
            'untuk'  => 'Ayam mati, susut bobot, dan koreksi opname beserta nilai kerugiannya.',
            'filter' => ['periode', 'item'],
        ],
    ];

    /** Pilihan periode siap pakai. Nilainya dihitung saat dipakai, bukan disimpan. */
    public const PERIODE = [
        'hari-ini'     => 'Hari ini',
        'kemarin'      => 'Kemarin',
        'minggu-ini'   => 'Minggu ini',
        'minggu-lalu'  => 'Minggu lalu',
        'bulan-ini'    => 'Bulan ini',
        'bulan-lalu'   => 'Bulan lalu',
        '30-hari'      => '30 hari terakhir',
        'tahun-ini'    => 'Tahun ini',
        'custom'       => 'Pilih tanggal sendiri',
    ];

    /**
     * Terjemahkan pilihan periode menjadi tanggal mulai & selesai.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}  [dari, sampai, label]
     */
    public function rentang(string $periode, ?string $dari = null, ?string $sampai = null): array
    {
        $hariIni = Carbon::today();

        [$a, $b] = match ($periode) {
            'kemarin'     => [$hariIni->copy()->subDay(), $hariIni->copy()->subDay()],
            'minggu-ini'  => [$hariIni->copy()->startOfWeek(), $hariIni->copy()->endOfWeek()],
            'minggu-lalu' => [$hariIni->copy()->subWeek()->startOfWeek(), $hariIni->copy()->subWeek()->endOfWeek()],
            'bulan-ini'   => [$hariIni->copy()->startOfMonth(), $hariIni->copy()->endOfMonth()],
            'bulan-lalu'  => [$hariIni->copy()->subMonthNoOverflow()->startOfMonth(),
                              $hariIni->copy()->subMonthNoOverflow()->endOfMonth()],
            '30-hari'     => [$hariIni->copy()->subDays(29), $hariIni->copy()],
            'tahun-ini'   => [$hariIni->copy()->startOfYear(), $hariIni->copy()->endOfYear()],
            'custom'      => [
                $dari ? Carbon::parse($dari) : $hariIni->copy()->startOfMonth(),
                $sampai ? Carbon::parse($sampai) : $hariIni->copy(),
            ],
            default       => [$hariIni->copy(), $hariIni->copy()],   // hari-ini
        };

        // Tanggal terbalik diluruskan, bukan ditolak: yang dimaksud sudah jelas.
        if ($a->gt($b)) {
            [$a, $b] = [$b, $a];
        }

        return [$a->startOfDay(), $b->endOfDay(), $this->labelRentang($a, $b)];
    }

    private function labelRentang(Carbon $a, Carbon $b): string
    {
        $fa = $a->locale('id');
        $fb = $b->locale('id');

        if ($a->isSameDay($b)) {
            return $fa->translatedFormat('l, d F Y');
        }
        if ($a->format('Y-m') === $b->format('Y-m')) {
            return $fa->translatedFormat('d') . ' – ' . $fb->translatedFormat('d F Y');
        }

        return $fa->translatedFormat('d M Y') . ' – ' . $fb->translatedFormat('d M Y');
    }

    /* ===================================================================
       1. RINGKASAN USAHA
       =================================================================== */
    public function ringkasan(Carbon $a, Carbon $b): array
    {
        $tglA = $a->toDateString();
        $tglB = $b->toDateString();

        $beli = StockIn::whereBetween('date', [$tglA, $tglB])
            ->selectRaw('COUNT(*) n, COALESCE(SUM(total),0) total')->first();

        $jual = StockOut::whereBetween('date', [$tglA, $tglB])
            ->selectRaw('COUNT(*) n, COALESCE(SUM(total_sale),0) jual,
                         COALESCE(SUM(total_cost),0) modal, COALESCE(SUM(gross_profit),0) laba')->first();

        // Berat yang benar-benar bergerak — angka yang paling dipegang orang kandang.
        $kgMasuk = (float) DB::table('farm_stock_lots')
            ->whereBetween('date', [$tglA, $tglB])->where('source', 'purchase')
            ->sum('weight_kg_initial');
        $kgKeluar = (float) DB::table('farm_stock_out_lines as l')
            ->join('farm_stock_outs as o', 'o.id', '=', 'l.stock_out_id')
            ->whereBetween('o.date', [$tglA, $tglB])->sum('l.weight_kg');

        $susut = DB::table('farm_stock_adjustments')
            ->whereBetween('date', [$tglA, $tglB])
            ->where('reason', '!=', 'koreksi_tambah')
            ->selectRaw('COUNT(*) n, COALESCE(SUM(weight_kg),0) kg, COALESCE(SUM(qty_ekor),0) ekor,
                         COALESCE(SUM(cost_impact),0) nilai')->first();

        $stok = DB::table('farm_stock_lots')
            ->selectRaw('COALESCE(SUM(weight_kg_left),0) kg, COALESCE(SUM(qty_ekor_left),0) ekor,
                         COALESCE(SUM(weight_kg_left * cost_per_kg),0) nilai')->first();

        $deposit = (float) SupplierDeposit::sum('amount');
        $depositMinus = Supplier::all()->filter(fn ($s) => $s->depositBalance() < -0.01);

        $piutang = StockOut::where('payment_status', 'unpaid')
            ->selectRaw('COUNT(*) n, COALESCE(SUM(total_sale - paid_amount),0) sisa')->first();

        $marginPersen = (float) $jual->jual > 0 ? (float) $jual->laba / (float) $jual->jual * 100 : 0;

        return [
            'judul'   => 'RINGKASAN USAHA',
            'ringkas' => [
                ['label' => 'Pembelian', 'nilai' => (float) $beli->total, 'jenis' => 'rp',
                    'ket' => $beli->n . ' nota · ' . $this->angka($kgMasuk, 2) . ' kg masuk'],
                ['label' => 'Penjualan', 'nilai' => (float) $jual->jual, 'jenis' => 'rp',
                    'ket' => $jual->n . ' nota · ' . $this->angka($kgKeluar, 2) . ' kg keluar'],
                ['label' => 'Laba Kotor', 'nilai' => (float) $jual->laba, 'jenis' => 'rp',
                    'ket' => 'margin ' . number_format($marginPersen, 1, ',', '.') . '%'],
                ['label' => 'Nilai Susut', 'nilai' => (float) $susut->nilai, 'jenis' => 'rp',
                    'ket' => $susut->n . ' penyesuaian · ' . $this->angka((float) $susut->kg, 2) . ' kg'],
            ],
            'blok' => [
                [
                    'judul' => 'Perhitungan Laba Periode Ini',
                    'kolom' => [
                        ['label' => 'Uraian', 'align' => 'left'],
                        ['label' => 'Jumlah', 'align' => 'right', 'lebar' => '22%'],
                        ['label' => 'Keterangan', 'align' => 'left', 'lebar' => '38%'],
                    ],
                    'baris' => [
                        ['Penjualan ke agen', $this->rp($jual->jual), 'Nilai seluruh nota barang keluar.'],
                        ['Harga pokok penjualan (FIFO)', '(' . $this->rp($jual->modal) . ')',
                            'Harga beli nyata barang yang terjual, diambil dari lot tertua lebih dulu.'],
                        ['LABA KOTOR', $this->rp($jual->laba), 'Penjualan dikurangi harga pokok.'],
                        ['Susut & penyesuaian gudang', '(' . $this->rp($susut->nilai) . ')',
                            'Ayam mati / susut bobot — beban kita sendiri, bukan tanggungan supplier.'],
                        ['LABA SETELAH SUSUT', $this->rp((float) $jual->laba - (float) $susut->nilai),
                            'Belum dikurangi biaya operasional (pakan, gaji, listrik, transport).'],
                    ],
                    'tebal' => [2, 4],   // baris yang dicetak tebal
                ],
                [
                    'judul' => 'Posisi Terkini (per ' . $b->locale('id')->translatedFormat('d F Y') . ')',
                    'kolom' => [
                        ['label' => 'Uraian', 'align' => 'left'],
                        ['label' => 'Jumlah', 'align' => 'right', 'lebar' => '22%'],
                        ['label' => 'Keterangan', 'align' => 'left', 'lebar' => '38%'],
                    ],
                    'baris' => [
                        ['Sisa stok', $this->angka((float) $stok->kg, 2) . ' kg / ' . $this->angka((int) $stok->ekor) . ' ekor',
                            'Jumlah fisik yang seharusnya ada di gudang saat ini.'],
                        ['Nilai persediaan', $this->rp($stok->nilai), 'Sisa stok dikali harga pokoknya.'],
                        ['Saldo deposit di supplier', $this->rp($deposit),
                            $depositMinus->count()
                                ? $depositMinus->count() . ' supplier bersaldo minus — kita belum bayar'
                                : 'Uang kita yang masih dipegang supplier.'],
                        ['Piutang agen belum lunas', $this->rp($piutang->sisa), $piutang->n . ' nota belum lunas.'],
                    ],
                ],
            ],
            'catatan' => 'Laba kotor belum memperhitungkan biaya operasional. Nilai persediaan dan saldo '
                . 'deposit adalah posisi SAAT LAPORAN DIBUAT, bukan posisi akhir periode.',
        ];
    }

    /* ===================================================================
       2. PEMBELIAN PER SUPPLIER
       =================================================================== */
    public function pembelian(Carbon $a, Carbon $b, ?int $supplierId): array
    {
        $q = StockIn::with(['supplier', 'lines.item', 'realization'])
            ->whereBetween('date', [$a->toDateString(), $b->toDateString()]);
        if ($supplierId) {
            $q->where('supplier_id', $supplierId);
        }
        $nota = $q->orderBy('date')->orderBy('id')->get();

        $baris = [];
        $totalNota = $totalReal = 0.0;
        $totalKg = 0.0;
        $totalEkor = 0;

        foreach ($nota as $n) {
            $kg   = (float) $n->lines->sum('weight_kg');
            $ekor = (int) $n->lines->sum('qty_ekor');
            $koreksi = (float) ($n->realization->value ?? 0);

            $baris[] = [
                $n->date->format('d/m/y'),
                $n->invoice_no,
                $n->supplier?->name ?? '—',
                $n->lines->map(fn ($l) => $l->item?->name)->filter()->unique()->implode(', '),
                $this->angka($ekor),
                $this->angka($kg, 2),
                $this->rp($n->total),
                $koreksi != 0 ? ($koreksi > 0 ? '+' : '−') . $this->rp(abs($koreksi)) : '—',
                $this->rp($n->netTotal()),
                $n->isPaid() ? 'Lunas' : 'Belum',
            ];

            $totalNota  += (float) $n->total;
            $totalReal  += $koreksi;
            $totalKg    += $kg;
            $totalEkor  += $ekor;
        }

        // Rekap per supplier: yang paling sering ditanya owner adalah
        // "supplier mana yang paling banyak saya beli, dan berapa harganya per kg".
        $rekap = [];
        foreach ($nota->groupBy('supplier_id') as $grup) {
            $nama = $grup->first()->supplier?->name ?? 'Tanpa Supplier';
            $kg   = (float) $grup->sum(fn ($n) => $n->lines->sum('weight_kg'));
            $ekor = (int) $grup->sum(fn ($n) => $n->lines->sum('qty_ekor'));
            $nilai = (float) $grup->sum(fn ($n) => $n->netTotal());
            $rekap[] = [
                $nama,
                count($grup),
                $this->angka($ekor),
                $this->angka($kg, 2),
                $kg > 0 ? $this->rp($nilai / $kg) : '—',
                $this->rp($nilai),
            ];
        }
        usort($rekap, fn ($x, $y) => strcmp($x[0], $y[0]));

        $netto = $totalNota - $totalReal;

        return [
            'judul'    => 'LAPORAN PEMBELIAN',
            'subjudul' => $supplierId ? 'Supplier: ' . (Supplier::find($supplierId)->name ?? '—') : 'Seluruh supplier',
            'ringkas'  => [
                ['label' => 'Jumlah Nota', 'nilai' => $nota->count(), 'jenis' => 'num'],
                ['label' => 'Berat Masuk', 'nilai' => $totalKg, 'jenis' => 'kg', 'ket' => $this->angka($totalEkor) . ' ekor'],
                ['label' => 'Nilai Nota', 'nilai' => $totalNota, 'jenis' => 'rp'],
                ['label' => 'Nilai Bersih', 'nilai' => $netto, 'jenis' => 'rp', 'ket' => 'setelah koreksi realisasi'],
            ],
            'blok' => [
                [
                    'judul' => 'Rekap per Supplier',
                    'kolom' => [
                        ['label' => 'Supplier', 'align' => 'left'],
                        ['label' => 'Nota', 'align' => 'center', 'lebar' => '8%'],
                        ['label' => 'Ekor', 'align' => 'right', 'lebar' => '10%'],
                        ['label' => 'Berat (kg)', 'align' => 'right', 'lebar' => '13%'],
                        ['label' => 'Rata-rata/kg', 'align' => 'right', 'lebar' => '15%'],
                        ['label' => 'Nilai Bersih', 'align' => 'right', 'lebar' => '17%'],
                    ],
                    'baris' => $rekap,
                    'total' => ['TOTAL', $nota->count(), $this->angka($totalEkor), $this->angka($totalKg, 2),
                        $totalKg > 0 ? $this->rp($netto / $totalKg) : '—', $this->rp($netto)],
                ],
                [
                    'judul' => 'Rincian Nota',
                    'kolom' => [
                        ['label' => 'Tgl', 'align' => 'left', 'lebar' => '6%'],
                        ['label' => 'No. Nota', 'align' => 'left', 'lebar' => '14%'],
                        ['label' => 'Supplier', 'align' => 'left', 'lebar' => '15%'],
                        ['label' => 'Barang', 'align' => 'left'],
                        ['label' => 'Ekor', 'align' => 'right', 'lebar' => '7%'],
                        ['label' => 'Kg', 'align' => 'right', 'lebar' => '9%'],
                        ['label' => 'Nilai Nota', 'align' => 'right', 'lebar' => '12%'],
                        ['label' => 'Realisasi', 'align' => 'right', 'lebar' => '10%'],
                        ['label' => 'Bersih', 'align' => 'right', 'lebar' => '12%'],
                        ['label' => 'Bayar', 'align' => 'center', 'lebar' => '7%'],
                    ],
                    'baris' => $baris,
                    'total' => ['', '', '', 'TOTAL', $this->angka($totalEkor), $this->angka($totalKg, 2),
                        $this->rp($totalNota),
                        ($totalReal >= 0 ? '+' : '−') . $this->rp(abs($totalReal)),
                        $this->rp($netto), ''],
                ],
            ],
            'catatan' => 'Kolom "Realisasi" adalah koreksi setelah barang ditimbang ulang. Tanda + berarti '
                . 'barang kurang dari nota sehingga saldo deposit supplier bertambah; tanda − berarti barang lebih.',
        ];
    }

    /* ===================================================================
       3. PENJUALAN & LABA
       =================================================================== */
    public function penjualan(Carbon $a, Carbon $b, ?int $agenId): array
    {
        $q = StockOut::with(['agent', 'lines.item'])
            ->whereBetween('date', [$a->toDateString(), $b->toDateString()]);
        if ($agenId) {
            $q->where('agent_id', $agenId);
        }
        $nota = $q->orderBy('date')->orderBy('id')->get();

        $baris = [];
        $tJual = $tModal = 0.0;
        $tKg = 0.0;
        $tEkor = 0;

        foreach ($nota as $n) {
            $kg   = (float) $n->lines->sum('weight_kg');
            $ekor = (int) $n->lines->sum('qty_ekor');
            $margin = (float) $n->total_sale > 0 ? (float) $n->gross_profit / (float) $n->total_sale * 100 : 0;

            $baris[] = [
                $n->date->format('d/m/y'),
                $n->invoice_no,
                $n->agent?->name ?? '—',
                $this->angka($ekor),
                $this->angka($kg, 2),
                $kg > 0 ? $this->rp((float) $n->total_sale / $kg) : '—',
                $this->rp($n->total_sale),
                $this->rp($n->total_cost),
                $this->rp($n->gross_profit),
                number_format($margin, 1, ',', '.') . '%',
                $n->isPaid() ? 'Lunas' : 'Belum',
            ];

            $tJual  += (float) $n->total_sale;
            $tModal += (float) $n->total_cost;
            $tKg    += $kg;
            $tEkor  += $ekor;
        }

        // Rekap per agen — untuk melihat pelanggan mana yang paling menguntungkan,
        // bukan sekadar yang paling banyak membeli.
        $rekap = [];
        foreach ($nota->groupBy('agent_id') as $grup) {
            $jual  = (float) $grup->sum('total_sale');
            $modal = (float) $grup->sum('total_cost');
            $kg    = (float) $grup->sum(fn ($n) => $n->lines->sum('weight_kg'));
            $rekap[] = [
                $grup->first()->agent?->name ?? 'Tanpa Agen',
                count($grup),
                $this->angka($kg, 2),
                $kg > 0 ? $this->rp($jual / $kg) : '—',
                $this->rp($jual),
                $this->rp($jual - $modal),
                $jual > 0 ? number_format(($jual - $modal) / $jual * 100, 1, ',', '.') . '%' : '—',
            ];
        }
        usort($rekap, fn ($x, $y) => strcmp($x[0], $y[0]));

        $laba = $tJual - $tModal;

        return [
            'judul'    => 'LAPORAN PENJUALAN & LABA',
            'subjudul' => $agenId ? 'Agen: ' . (Agent::find($agenId)->name ?? '—') : 'Seluruh agen',
            'ringkas'  => [
                ['label' => 'Jumlah Nota', 'nilai' => $nota->count(), 'jenis' => 'num'],
                ['label' => 'Berat Terjual', 'nilai' => $tKg, 'jenis' => 'kg', 'ket' => $this->angka($tEkor) . ' ekor'],
                ['label' => 'Penjualan', 'nilai' => $tJual, 'jenis' => 'rp',
                    'ket' => $tKg > 0 ? 'rata-rata ' . $this->rp($tJual / $tKg) . '/kg' : ''],
                ['label' => 'Laba Kotor', 'nilai' => $laba, 'jenis' => 'rp',
                    'ket' => $tJual > 0 ? 'margin ' . number_format($laba / $tJual * 100, 1, ',', '.') . '%' : ''],
            ],
            'blok' => [
                [
                    'judul' => 'Rekap per Agen',
                    'kolom' => [
                        ['label' => 'Agen', 'align' => 'left'],
                        ['label' => 'Nota', 'align' => 'center', 'lebar' => '8%'],
                        ['label' => 'Berat (kg)', 'align' => 'right', 'lebar' => '13%'],
                        ['label' => 'Harga Rata²/kg', 'align' => 'right', 'lebar' => '15%'],
                        ['label' => 'Penjualan', 'align' => 'right', 'lebar' => '16%'],
                        ['label' => 'Laba', 'align' => 'right', 'lebar' => '15%'],
                        ['label' => 'Margin', 'align' => 'right', 'lebar' => '9%'],
                    ],
                    'baris' => $rekap,
                    'total' => ['TOTAL', $nota->count(), $this->angka($tKg, 2),
                        $tKg > 0 ? $this->rp($tJual / $tKg) : '—', $this->rp($tJual), $this->rp($laba),
                        $tJual > 0 ? number_format($laba / $tJual * 100, 1, ',', '.') . '%' : '—'],
                ],
                [
                    'judul' => 'Rincian Nota',
                    'kolom' => [
                        ['label' => 'Tgl', 'align' => 'left', 'lebar' => '6%'],
                        ['label' => 'No. Nota', 'align' => 'left', 'lebar' => '13%'],
                        ['label' => 'Agen', 'align' => 'left'],
                        ['label' => 'Ekor', 'align' => 'right', 'lebar' => '6%'],
                        ['label' => 'Kg', 'align' => 'right', 'lebar' => '8%'],
                        ['label' => 'Harga/kg', 'align' => 'right', 'lebar' => '10%'],
                        ['label' => 'Penjualan', 'align' => 'right', 'lebar' => '12%'],
                        ['label' => 'HPP', 'align' => 'right', 'lebar' => '11%'],
                        ['label' => 'Laba', 'align' => 'right', 'lebar' => '11%'],
                        ['label' => 'Margin', 'align' => 'right', 'lebar' => '7%'],
                        ['label' => 'Bayar', 'align' => 'center', 'lebar' => '6%'],
                    ],
                    'baris' => $baris,
                    'total' => ['', '', 'TOTAL', $this->angka($tEkor), $this->angka($tKg, 2), '',
                        $this->rp($tJual), $this->rp($tModal), $this->rp($laba),
                        $tJual > 0 ? number_format($laba / $tJual * 100, 1, ',', '.') . '%' : '—', ''],
                ],
            ],
            'catatan' => 'HPP dihitung FIFO: barang yang paling dulu masuk dianggap paling dulu keluar, '
                . 'sehingga laba mengikuti harga beli yang sebenarnya — bukan harga rata-rata.',
        ];
    }

    /* ===================================================================
       4. KARTU STOK
       =================================================================== */
    public function kartuStok(Carbon $a, Carbon $b, ?int $itemId): array
    {
        $items = Item::where('is_active', true)
            ->when($itemId, fn ($q) => $q->whereKey($itemId))
            ->orderBy('name')->get();

        $blok = [];
        $tAwal = $tMasuk = $tKeluar = $tSusut = $tAkhir = 0.0;

        foreach ($items as $item) {
            $masukSblm  = $this->masukKg($item->id, null, $a->copy()->subDay());
            $keluarSblm = $this->keluarKg($item->id, null, $a->copy()->subDay());
            $susutSblm  = $this->susutKg($item->id, null, $a->copy()->subDay());
            $awal = round($masukSblm - $keluarSblm - $susutSblm, 2);

            $masuk  = $this->masukKg($item->id, $a, $b);
            $keluar = $this->keluarKg($item->id, $a, $b);
            $susut  = $this->susutKg($item->id, $a, $b);
            $akhir  = round($awal + $masuk - $keluar - $susut, 2);

            // Barang tanpa mutasi dan tanpa saldo tidak perlu memenuhi halaman.
            if (abs($awal) < 0.01 && abs($masuk) < 0.01 && abs($keluar) < 0.01 && abs($susut) < 0.01) {
                continue;
            }

            $blok[] = [$item->name, $awal, $masuk, $keluar, $susut, $akhir];
            $tAwal += $awal; $tMasuk += $masuk; $tKeluar += $keluar; $tSusut += $susut; $tAkhir += $akhir;
        }

        $baris = array_map(fn ($r) => [
            $r[0],
            $this->angka($r[1], 2),
            $this->angka($r[2], 2),
            $this->angka($r[3], 2),
            $this->angka($r[4], 2),
            $this->angka($r[5], 2),
        ], $blok);

        return [
            'judul'    => 'KARTU STOK',
            'subjudul' => $itemId ? 'Barang: ' . (Item::find($itemId)->name ?? '—') : 'Seluruh barang',
            'ringkas'  => [
                ['label' => 'Saldo Awal', 'nilai' => $tAwal, 'jenis' => 'kg'],
                ['label' => 'Masuk', 'nilai' => $tMasuk, 'jenis' => 'kg'],
                ['label' => 'Keluar', 'nilai' => $tKeluar, 'jenis' => 'kg'],
                ['label' => 'Saldo Akhir', 'nilai' => $tAkhir, 'jenis' => 'kg', 'ket' => 'susut ' . $this->angka($tSusut, 2) . ' kg'],
            ],
            'blok' => [[
                'judul' => 'Mutasi Barang (kilogram)',
                'kolom' => [
                    ['label' => 'Barang', 'align' => 'left'],
                    ['label' => 'Saldo Awal', 'align' => 'right', 'lebar' => '15%'],
                    ['label' => 'Masuk', 'align' => 'right', 'lebar' => '15%'],
                    ['label' => 'Keluar', 'align' => 'right', 'lebar' => '15%'],
                    ['label' => 'Susut', 'align' => 'right', 'lebar' => '15%'],
                    ['label' => 'Saldo Akhir', 'align' => 'right', 'lebar' => '15%'],
                ],
                'baris' => $baris,
                'total' => ['TOTAL', $this->angka($tAwal, 2), $this->angka($tMasuk, 2),
                    $this->angka($tKeluar, 2), $this->angka($tSusut, 2), $this->angka($tAkhir, 2)],
            ]],
            'catatan' => 'Saldo akhir = saldo awal + masuk − keluar − susut. Bila saldo akhir berbeda dengan '
                . 'hitungan fisik di gudang, luruskan lewat menu Penyesuaian Stok agar keduanya kembali sama.',
        ];
    }

    private function masukKg(int $itemId, ?Carbon $a, Carbon $b): float
    {
        return (float) DB::table('farm_stock_lots')
            ->where('item_id', $itemId)
            ->when($a, fn ($q) => $q->where('date', '>=', $a->toDateString()))
            ->where('date', '<=', $b->toDateString())
            ->sum('weight_kg_initial');
    }

    private function keluarKg(int $itemId, ?Carbon $a, Carbon $b): float
    {
        return (float) DB::table('farm_stock_out_lines as l')
            ->join('farm_stock_outs as o', 'o.id', '=', 'l.stock_out_id')
            ->where('l.item_id', $itemId)
            ->when($a, fn ($q) => $q->where('o.date', '>=', $a->toDateString()))
            ->where('o.date', '<=', $b->toDateString())
            ->sum('l.weight_kg');
    }

    private function susutKg(int $itemId, ?Carbon $a, Carbon $b): float
    {
        return (float) DB::table('farm_stock_adjustments')
            ->where('item_id', $itemId)
            ->where('reason', '!=', 'koreksi_tambah')
            ->when($a, fn ($q) => $q->where('date', '>=', $a->toDateString()))
            ->where('date', '<=', $b->toDateString())
            ->sum('weight_kg');
    }

    /* ===================================================================
       5. STOK & HPP PER SUPPLIER (potret saat ini)
       =================================================================== */
    public function stokSupplier(?int $supplierId): array
    {
        $lots = StockLot::with(['item', 'supplier'])
            ->where(fn ($q) => $q->where('weight_kg_left', '>', 0)->orWhere('qty_ekor_left', '>', 0))
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->orderBy('date')->orderBy('id')->get();

        $baris = [];
        $tKg = $tNilai = 0.0;
        $tEkor = 0;

        foreach ($lots as $lot) {
            $kg    = (float) $lot->weight_kg_left;
            $ekor  = (int) $lot->qty_ekor_left;
            $nilai = $kg > 0 ? $kg * (float) $lot->cost_per_kg : $ekor * (float) $lot->cost_per_ekor;

            $baris[] = [
                $lot->supplier?->name ?? ($lot->source === 'production' ? 'Produksi Sendiri' : 'Tanpa Supplier'),
                $lot->item?->name ?? '—',
                $lot->date->format('d/m/y'),
                $this->angka($ekor),
                $this->angka($kg, 2),
                $kg > 0 ? $this->rp($lot->cost_per_kg) : '—',
                $this->rp($nilai),
            ];

            $tKg += $kg; $tEkor += $ekor; $tNilai += $nilai;
        }

        return [
            'judul'    => 'STOK & HARGA POKOK PER SUPPLIER',
            'subjudul' => $supplierId ? 'Supplier: ' . (Supplier::find($supplierId)->name ?? '—') : 'Seluruh supplier',
            'ringkas'  => [
                ['label' => 'Jumlah Lot', 'nilai' => count($baris), 'jenis' => 'num'],
                ['label' => 'Sisa Stok', 'nilai' => $tKg, 'jenis' => 'kg', 'ket' => $this->angka($tEkor) . ' ekor'],
                ['label' => 'Nilai Persediaan', 'nilai' => $tNilai, 'jenis' => 'rp'],
                ['label' => 'HPP Rata-rata', 'nilai' => $tKg > 0 ? $tNilai / $tKg : 0, 'jenis' => 'rp', 'ket' => 'per kg'],
            ],
            'blok' => [[
                'judul' => 'Rincian Lot — urut FIFO (paling atas dipakai lebih dulu)',
                'kolom' => [
                    ['label' => 'Supplier', 'align' => 'left'],
                    ['label' => 'Barang', 'align' => 'left', 'lebar' => '18%'],
                    ['label' => 'Tgl Masuk', 'align' => 'left', 'lebar' => '11%'],
                    ['label' => 'Sisa Ekor', 'align' => 'right', 'lebar' => '11%'],
                    ['label' => 'Sisa Kg', 'align' => 'right', 'lebar' => '12%'],
                    ['label' => 'HPP/kg', 'align' => 'right', 'lebar' => '13%'],
                    ['label' => 'Nilai', 'align' => 'right', 'lebar' => '15%'],
                ],
                'baris' => $baris,
                'total' => ['TOTAL', '', '', $this->angka($tEkor), $this->angka($tKg, 2),
                    $tKg > 0 ? $this->rp($tNilai / $tKg) : '—', $this->rp($tNilai)],
            ]],
            'catatan' => 'Potret saat laporan dibuat, bukan posisi akhir periode. Nilai persediaan memakai '
                . 'harga beli nyata tiap lot — sudah termasuk koreksi realisasi.',
        ];
    }

    /* ===================================================================
       6. DEPOSIT SUPPLIER
       =================================================================== */
    public function deposit(Carbon $a, Carbon $b, ?int $supplierId): array
    {
        $suppliers = Supplier::when($supplierId, fn ($q) => $q->whereKey($supplierId))
            ->orderBy('name')->get();

        $baris = [];
        $tAwal = $tSetor = $tBeli = $tKoreksi = $tAkhir = 0.0;

        foreach ($suppliers as $s) {
            $awal = (float) SupplierDeposit::where('supplier_id', $s->id)
                ->where('date', '<', $a->toDateString())->sum('amount');

            $per = SupplierDeposit::where('supplier_id', $s->id)
                ->whereBetween('date', [$a->toDateString(), $b->toDateString()])
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN type = 'topup' THEN amount ELSE 0 END),0) setor,
                    COALESCE(SUM(CASE WHEN type = 'purchase' THEN amount ELSE 0 END),0) beli,
                    COALESCE(SUM(CASE WHEN type IN ('realization','manual') THEN amount ELSE 0 END),0) koreksi
                ")->first();

            $setor   = (float) $per->setor;
            $beli    = (float) $per->beli;
            $koreksi = (float) $per->koreksi;
            $akhir   = round($awal + $setor + $beli + $koreksi, 2);

            // Supplier yang benar-benar tidak bergerak dan tidak bersaldo dilewati.
            if (abs($awal) < 0.01 && abs($setor) < 0.01 && abs($beli) < 0.01 && abs($koreksi) < 0.01) {
                continue;
            }

            $baris[] = [
                $s->name,
                $this->rp($awal),
                $this->rp($setor),
                $this->rp(abs($beli)),
                ($koreksi >= 0 ? '+' : '−') . $this->rp(abs($koreksi)),
                $this->rp($akhir),
                $akhir < -0.01 ? 'Belum bayar' : '—',
            ];

            $tAwal += $awal; $tSetor += $setor; $tBeli += $beli; $tKoreksi += $koreksi; $tAkhir += $akhir;
        }

        return [
            'judul'    => 'LAPORAN DEPOSIT SUPPLIER',
            'subjudul' => $supplierId ? 'Supplier: ' . (Supplier::find($supplierId)->name ?? '—') : 'Seluruh supplier',
            'ringkas'  => [
                ['label' => 'Saldo Awal', 'nilai' => $tAwal, 'jenis' => 'rp'],
                ['label' => 'Setoran', 'nilai' => $tSetor, 'jenis' => 'rp'],
                ['label' => 'Terpakai Pembelian', 'nilai' => abs($tBeli), 'jenis' => 'rp'],
                ['label' => 'Saldo Akhir', 'nilai' => $tAkhir, 'jenis' => 'rp'],
            ],
            'blok' => [[
                'judul' => 'Mutasi Saldo per Supplier',
                'kolom' => [
                    ['label' => 'Supplier', 'align' => 'left'],
                    ['label' => 'Saldo Awal', 'align' => 'right', 'lebar' => '14%'],
                    ['label' => 'Setoran', 'align' => 'right', 'lebar' => '14%'],
                    ['label' => 'Terpakai', 'align' => 'right', 'lebar' => '14%'],
                    ['label' => 'Koreksi', 'align' => 'right', 'lebar' => '13%'],
                    ['label' => 'Saldo Akhir', 'align' => 'right', 'lebar' => '15%'],
                    ['label' => 'Status', 'align' => 'center', 'lebar' => '11%'],
                ],
                'baris' => $baris,
                'total' => ['TOTAL', $this->rp($tAwal), $this->rp($tSetor), $this->rp(abs($tBeli)),
                    ($tKoreksi >= 0 ? '+' : '−') . $this->rp(abs($tKoreksi)), $this->rp($tAkhir), ''],
            ]],
            'catatan' => 'Saldo akhir = saldo awal + setoran − terpakai pembelian ± koreksi realisasi. '
                . 'Saldo minus berarti barang sudah masuk tetapi uangnya belum kita transfer.',
        ];
    }

    /* ===================================================================
       7. PIUTANG AGEN (umur tunggakan)
       =================================================================== */
    public function piutang(?int $agenId): array
    {
        $nota = StockOut::with('agent')->where('payment_status', 'unpaid')
            ->when($agenId, fn ($q) => $q->where('agent_id', $agenId))
            ->orderBy('due_date')->orderBy('date')->get();

        $hariIni = Carbon::today();
        $ember = ['Belum jatuh tempo' => 0.0, '1–7 hari' => 0.0, '8–30 hari' => 0.0, 'Lebih dari 30 hari' => 0.0];
        $baris = [];
        $total = 0.0;

        foreach ($nota as $n) {
            $sisa = $n->remaining();
            if ($sisa <= 0.01) {
                continue;
            }

            $umur = $n->due_date ? $n->due_date->diffInDays($hariIni, false) : null;
            $kel  = match (true) {
                $umur === null || $umur <= 0 => 'Belum jatuh tempo',
                $umur <= 7                   => '1–7 hari',
                $umur <= 30                  => '8–30 hari',
                default                      => 'Lebih dari 30 hari',
            };
            $ember[$kel] += $sisa;

            $baris[] = [
                $n->date->format('d/m/y'),
                $n->invoice_no,
                $n->agent?->name ?? '—',
                $n->due_date?->format('d/m/y') ?? '—',
                $umur !== null && $umur > 0 ? $umur . ' hari' : '—',
                $this->rp($n->total_sale),
                $this->rp($n->paid_amount),
                $this->rp($sisa),
                $kel,
            ];
            $total += $sisa;
        }

        $emberBaris = [];
        foreach ($ember as $k => $v) {
            $emberBaris[] = [$k, $this->rp($v), $total > 0 ? number_format($v / $total * 100, 1, ',', '.') . '%' : '—'];
        }

        return [
            'judul'    => 'LAPORAN PIUTANG AGEN',
            'subjudul' => $agenId ? 'Agen: ' . (Agent::find($agenId)->name ?? '—') : 'Seluruh agen',
            'ringkas'  => [
                ['label' => 'Nota Belum Lunas', 'nilai' => count($baris), 'jenis' => 'num'],
                ['label' => 'Total Piutang', 'nilai' => $total, 'jenis' => 'rp'],
                ['label' => 'Lewat Jatuh Tempo', 'nilai' => $ember['1–7 hari'] + $ember['8–30 hari'] + $ember['Lebih dari 30 hari'], 'jenis' => 'rp'],
                ['label' => 'Lebih dari 30 Hari', 'nilai' => $ember['Lebih dari 30 hari'], 'jenis' => 'rp', 'ket' => 'paling perlu ditagih'],
            ],
            'blok' => [
                [
                    'judul' => 'Umur Piutang',
                    'kolom' => [
                        ['label' => 'Kelompok Umur', 'align' => 'left'],
                        ['label' => 'Jumlah', 'align' => 'right', 'lebar' => '25%'],
                        ['label' => 'Porsi', 'align' => 'right', 'lebar' => '15%'],
                    ],
                    'baris' => $emberBaris,
                    'total' => ['TOTAL', $this->rp($total), '100%'],
                ],
                [
                    'judul' => 'Rincian Nota Belum Lunas',
                    'kolom' => [
                        ['label' => 'Tgl', 'align' => 'left', 'lebar' => '7%'],
                        ['label' => 'No. Nota', 'align' => 'left', 'lebar' => '15%'],
                        ['label' => 'Agen', 'align' => 'left'],
                        ['label' => 'Jatuh Tempo', 'align' => 'left', 'lebar' => '10%'],
                        ['label' => 'Telat', 'align' => 'right', 'lebar' => '8%'],
                        ['label' => 'Nilai Nota', 'align' => 'right', 'lebar' => '12%'],
                        ['label' => 'Dibayar', 'align' => 'right', 'lebar' => '12%'],
                        ['label' => 'Sisa', 'align' => 'right', 'lebar' => '12%'],
                        ['label' => 'Kelompok', 'align' => 'left', 'lebar' => '13%'],
                    ],
                    'baris' => $baris,
                    'total' => ['', '', '', '', 'TOTAL', '', '', $this->rp($total), ''],
                ],
            ],
            'catatan' => 'Posisi per ' . $hariIni->locale('id')->translatedFormat('d F Y')
                . '. Piutang lewat 30 hari sebaiknya ditagih lebih dulu — makin lama menggantung, makin sulit tertagih.',
        ];
    }

    /* ===================================================================
       8. SUSUT & PENYESUAIAN
       =================================================================== */
    public function susut(Carbon $a, Carbon $b, ?int $itemId): array
    {
        $rows = DB::table('farm_stock_adjustments as a')
            ->leftJoin('farm_items as i', 'i.id', '=', 'a.item_id')
            ->whereBetween('a.date', [$a->toDateString(), $b->toDateString()])
            ->when($itemId, fn ($q) => $q->where('a.item_id', $itemId))
            ->orderBy('a.date')->orderBy('a.id')
            ->get(['a.date', 'a.ref_no', 'a.reason', 'a.qty_ekor', 'a.weight_kg', 'a.cost_impact',
                'a.notes', 'i.name as item']);

        $alasan = \App\Models\Farm\StockAdjustment::REASONS;
        $baris = [];
        $tEkor = 0; $tKg = 0.0; $tNilai = 0.0;
        $perAlasan = [];

        foreach ($rows as $r) {
            $baris[] = [
                Carbon::parse($r->date)->format('d/m/y'),
                $r->ref_no,
                $r->item ?? '—',
                $alasan[$r->reason] ?? $r->reason,
                $this->angka((int) $r->qty_ekor),
                $this->angka((float) $r->weight_kg, 2),
                $this->rp($r->cost_impact),
                mb_strimwidth((string) $r->notes, 0, 40, '…'),
            ];

            $tEkor += (int) $r->qty_ekor;
            $tKg   += (float) $r->weight_kg;
            $tNilai += (float) $r->cost_impact;

            $k = $alasan[$r->reason] ?? $r->reason;
            $perAlasan[$k] ??= ['n' => 0, 'ekor' => 0, 'kg' => 0.0, 'nilai' => 0.0];
            $perAlasan[$k]['n']++;
            $perAlasan[$k]['ekor'] += (int) $r->qty_ekor;
            $perAlasan[$k]['kg']   += (float) $r->weight_kg;
            $perAlasan[$k]['nilai'] += (float) $r->cost_impact;
        }

        $rekap = [];
        foreach ($perAlasan as $k => $v) {
            $rekap[] = [$k, $v['n'], $this->angka($v['ekor']), $this->angka($v['kg'], 2), $this->rp($v['nilai']),
                $tNilai > 0 ? number_format($v['nilai'] / $tNilai * 100, 1, ',', '.') . '%' : '—'];
        }

        return [
            'judul'    => 'LAPORAN SUSUT & PENYESUAIAN',
            'subjudul' => $itemId ? 'Barang: ' . (Item::find($itemId)->name ?? '—') : 'Seluruh barang',
            'ringkas'  => [
                ['label' => 'Jumlah Kejadian', 'nilai' => count($baris), 'jenis' => 'num'],
                ['label' => 'Ekor', 'nilai' => $tEkor, 'jenis' => 'ekor'],
                ['label' => 'Berat', 'nilai' => $tKg, 'jenis' => 'kg'],
                ['label' => 'Nilai Kerugian', 'nilai' => $tNilai, 'jenis' => 'rp'],
            ],
            'blok' => [
                [
                    'judul' => 'Rekap per Sebab',
                    'kolom' => [
                        ['label' => 'Sebab', 'align' => 'left'],
                        ['label' => 'Kejadian', 'align' => 'center', 'lebar' => '11%'],
                        ['label' => 'Ekor', 'align' => 'right', 'lebar' => '12%'],
                        ['label' => 'Berat (kg)', 'align' => 'right', 'lebar' => '14%'],
                        ['label' => 'Nilai', 'align' => 'right', 'lebar' => '18%'],
                        ['label' => 'Porsi', 'align' => 'right', 'lebar' => '10%'],
                    ],
                    'baris' => $rekap,
                    'total' => ['TOTAL', count($baris), $this->angka($tEkor), $this->angka($tKg, 2), $this->rp($tNilai), '100%'],
                ],
                [
                    'judul' => 'Rincian Kejadian',
                    'kolom' => [
                        ['label' => 'Tgl', 'align' => 'left', 'lebar' => '7%'],
                        ['label' => 'No. Ref', 'align' => 'left', 'lebar' => '15%'],
                        ['label' => 'Barang', 'align' => 'left', 'lebar' => '15%'],
                        ['label' => 'Sebab', 'align' => 'left', 'lebar' => '14%'],
                        ['label' => 'Ekor', 'align' => 'right', 'lebar' => '8%'],
                        ['label' => 'Kg', 'align' => 'right', 'lebar' => '9%'],
                        ['label' => 'Nilai', 'align' => 'right', 'lebar' => '12%'],
                        ['label' => 'Catatan', 'align' => 'left'],
                    ],
                    'baris' => $baris,
                    'total' => ['', '', '', 'TOTAL', $this->angka($tEkor), $this->angka($tKg, 2), $this->rp($tNilai), ''],
                ],
            ],
            'catatan' => 'Penyesuaian adalah kerugian gudang sendiri (ayam mati, susut bobot) dan TIDAK '
                . 'mempengaruhi saldo supplier. Selisih yang berasal dari nota supplier dicatat lewat Realisasi.',
        ];
    }

    /* ---------------- pembantu ---------------- */

    public function rp($n): string
    {
        return 'Rp ' . number_format((float) $n, 0, ',', '.');
    }

    public function angka($n, int $desimal = 0): string
    {
        return number_format((float) $n, $desimal, ',', '.');
    }
}
