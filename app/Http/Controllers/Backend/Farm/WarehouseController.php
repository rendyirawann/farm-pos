<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Item;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * GUDANG — tampilan stok saja (baca-saja).
 *
 * Buka/tutup gudang sudah dihapus: satu-satunya jalur koreksi stok adalah
 * Penyesuaian Stok, sehingga tidak ada dua sumber angka yang bisa berbeda.
 */
class WarehouseController extends Controller
{
    /** Pilihan periode untuk kolom Masuk/Keluar/Susut. Stok sekarang selalu realtime. */
    public const PERIODE = [
        'hari-ini'    => 'hari ini',
        'kemarin'     => 'kemarin',
        '7-hari'      => '7 hari terakhir',
        'bulan-ini'   => 'bulan ini',
        'bulan-lalu'  => 'bulan lalu',
    ];

    /**
     * GUDANG — hanya untuk dilihat.
     *
     * Buka/tutup gudang dihapus: penutupan gudang dulu meminta hitung fisik dan
     * menghasilkan catatan selisih tersendiri, padahal koreksi stok yang sah
     * hanya lewat Penyesuaian Stok. Dua jalur untuk hal yang sama membuat
     * angkanya bisa berbeda tanpa ada yang bisa dipercaya.
     */
    public function index(Request $request)
    {
        $periode = $request->input('periode', 'hari-ini');
        if (! isset(self::PERIODE[$periode])) {
            $periode = 'hari-ini';
        }
        [$a, $b] = $this->rentang($periode);

        $rows = [];
        $total = ['masuk_kg' => 0.0, 'masuk_ekor' => 0, 'keluar_kg' => 0.0, 'keluar_ekor' => 0,
            'susut_kg' => 0.0, 'susut_ekor' => 0, 'sisa_kg' => 0.0, 'sisa_ekor' => 0, 'nilai' => 0.0,
            'tanpa_stok' => 0.0];

        foreach (Item::where('is_active', true)->orderBy('name')->get() as $item) {
            $masuk = DB::table('farm_stock_lots')->where('item_id', $item->id)
                ->whereBetween('date', [$a, $b])
                ->selectRaw('COALESCE(SUM(weight_kg_initial),0) kg, COALESCE(SUM(qty_ekor_initial),0) ekor')
                ->first();

            // Yang keluar diambil dari PEMAKAIAN LOT, bukan dari angka nota. Bila nota
            // menjual lebih banyak daripada stok yang ada, hanya sebatas stok itu yang
            // benar-benar keluar — memakai angka nota membuat halaman ini tidak cocok
            // dengan Kartu Stok maupun dengan sisa lot.
            $keluar = DB::table('farm_stock_out_lot_usages as u')
                ->join('farm_stock_out_lines as l', 'l.id', '=', 'u.stock_out_line_id')
                ->join('farm_stock_outs as o', 'o.id', '=', 'l.stock_out_id')
                ->where('l.item_id', $item->id)->whereBetween('o.date', [$a, $b])
                ->selectRaw('COALESCE(SUM(u.weight_kg),0) kg, COALESCE(SUM(u.qty_ekor),0) ekor')
                ->first();

            $nota = DB::table('farm_stock_out_lines as l')
                ->join('farm_stock_outs as o', 'o.id', '=', 'l.stock_out_id')
                ->where('l.item_id', $item->id)->whereBetween('o.date', [$a, $b])
                ->selectRaw('COALESCE(SUM(l.weight_kg),0) kg')
                ->first();
            $tanpaStok = max(0, round((float) $nota->kg - (float) $keluar->kg, 2));

            $susut = DB::table('farm_adjustment_lot_usages as au')
                ->join('farm_stock_adjustments as adj', 'adj.id', '=', 'au.adjustment_id')
                ->where('adj.item_id', $item->id)
                ->where('adj.reason', '!=', 'koreksi_tambah')->whereBetween('adj.date', [$a, $b])
                ->selectRaw('COALESCE(SUM(au.weight_kg),0) kg, COALESCE(SUM(au.qty_ekor),0) ekor')
                ->first();

            // Sisa & nilainya diambil dari lot: itulah kebenaran stok saat ini,
            // bukan hasil penjumlahan ulang mutasi yang bisa melenceng.
            $sisa = DB::table('farm_stock_lots')->where('item_id', $item->id)
                ->selectRaw('COALESCE(SUM(weight_kg_left),0) kg, COALESCE(SUM(qty_ekor_left),0) ekor,
                             COALESCE(SUM(CASE WHEN weight_kg_left > 0 THEN weight_kg_left * cost_per_kg
                                               ELSE qty_ekor_left * cost_per_ekor END),0) nilai,
                             COUNT(CASE WHEN weight_kg_left > 0 OR qty_ekor_left > 0 THEN 1 END) lot')
                ->first();

            $baris = [
                'nama'        => $item->name,
                'produksi'    => (bool) $item->is_produced,
                'lot'         => (int) $sisa->lot,
                'masuk_kg'    => round((float) $masuk->kg, 2),
                'masuk_ekor'  => (int) $masuk->ekor,
                'keluar_kg'   => round((float) $keluar->kg, 2),
                'keluar_ekor' => (int) $keluar->ekor,
                'susut_kg'    => round((float) $susut->kg, 2),
                'susut_ekor'  => (int) $susut->ekor,
                'tanpa_stok'  => $tanpaStok,
                'sisa_kg'     => round((float) $sisa->kg, 2),
                'sisa_ekor'   => (int) $sisa->ekor,
                'nilai'       => round((float) $sisa->nilai, 2),
            ];

            // Barang tanpa stok dan tanpa mutasi pada periode ini tidak perlu tampil.
            if ($baris['sisa_kg'] <= 0.001 && $baris['sisa_ekor'] <= 0
                && $baris['masuk_kg'] <= 0.001 && $baris['masuk_ekor'] <= 0
                && $baris['keluar_kg'] <= 0.001 && $baris['keluar_ekor'] <= 0) {
                continue;
            }

            $rows[] = $baris;
            foreach (['masuk_kg', 'masuk_ekor', 'keluar_kg', 'keluar_ekor', 'susut_kg', 'susut_ekor',
                      'sisa_kg', 'sisa_ekor', 'nilai', 'tanpa_stok'] as $k) {
                $total[$k] += $baris[$k];
            }
        }

        foreach (['masuk_kg', 'keluar_kg', 'susut_kg', 'sisa_kg', 'nilai', 'tanpa_stok'] as $k) {
            $total[$k] = round($total[$k], 2);
        }

        return view('backend.farm.warehouse.index', [
            'rows'          => $rows,
            'total'         => $total,
            'periode'       => $periode,
            'daftarPeriode' => self::PERIODE,
            'labelPeriode'  => self::PERIODE[$periode],
        ]);
    }

    /** @return array{0:string,1:string} tanggal mulai & selesai (Y-m-d) */
    private function rentang(string $periode): array
    {
        $h = Carbon::today();

        return match ($periode) {
            'kemarin'    => [$h->copy()->subDay()->toDateString(), $h->copy()->subDay()->toDateString()],
            '7-hari'     => [$h->copy()->subDays(6)->toDateString(), $h->toDateString()],
            'bulan-ini'  => [$h->copy()->startOfMonth()->toDateString(), $h->copy()->endOfMonth()->toDateString()],
            'bulan-lalu' => [$h->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                             $h->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            default      => [$h->toDateString(), $h->toDateString()],
        };
    }

    /**
     * STOK PER SUPPLIER — hanya untuk dilihat, tidak mengubah apa pun.
     *
     * Angkanya dibangun dari LOT, bukan dari agregat nota, karena lot adalah satu-
     * satunya tempat yang sudah memuat koreksi realisasi, penjualan FIFO, dan susut
     * penyesuaian sekaligus.
     */
    public function stock()
    {
        $baris = $this->lotRows();

        // Dikelompokkan per supplier lalu per item, supaya satu supplier tampil
        // sebagai satu blok dengan rincian barangnya.
        $perSupplier = [];
        foreach ($baris as $r) {
            $kunci = $r['supplier_id'] ?? 0;
            $perSupplier[$kunci] ??= [
                'supplier_id' => $r['supplier_id'],
                'nama'        => $r['supplier_nama'],
                'items'       => [],
                'sisa_kg'     => 0.0, 'sisa_ekor' => 0, 'nilai_sisa' => 0.0,
                'masuk'       => 0.0, 'terjual' => 0.0, 'susut' => 0.0, 'realisasi' => 0.0,
            ];

            $s = &$perSupplier[$kunci];
            $s['items'][$r['item_id']] ??= ['nama' => $r['item_nama'], 'sisa_kg' => 0.0,
                'sisa_ekor' => 0, 'nilai_sisa' => 0.0, 'lot' => 0];

            $it = &$s['items'][$r['item_id']];
            $it['sisa_kg']    += $r['sisa_kg'];
            $it['sisa_ekor']  += $r['sisa_ekor'];
            $it['nilai_sisa'] += $r['nilai_sisa'];
            $it['lot']++;
            unset($it);

            $s['sisa_kg']    += $r['sisa_kg'];
            $s['sisa_ekor']  += $r['sisa_ekor'];
            $s['nilai_sisa'] += $r['nilai_sisa'];
            $s['masuk']      += $r['nilai_masuk'];
            $s['terjual']    += $r['nilai_terjual'];
            $s['susut']      += $r['nilai_susut'];
            $s['realisasi']  += $r['nilai_realisasi'];
            unset($s);
        }

        // Yang masih ada stoknya diletakkan di atas — itu yang dipakai sehari-hari.
        uasort($perSupplier, function ($a, $b) {
            return ($b['sisa_kg'] <=> $a['sisa_kg']) ?: strcmp($a['nama'], $b['nama']);
        });

        return view('backend.farm.warehouse.stock', [
            'rows'  => $perSupplier,
            'total' => [
                'sisa_kg'    => round(array_sum(array_column($perSupplier, 'sisa_kg')), 2),
                'nilai_sisa' => round(array_sum(array_column($perSupplier, 'nilai_sisa')), 2),
            ],
        ]);
    }

    /** Rincian HPP satu supplier: dari barang masuk, realisasi, barang keluar, penyesuaian. */
    public function stockDetail(Request $request, $supplier)
    {
        $supplierId = (int) $supplier;
        $baris = array_values(array_filter(
            $this->lotRows(),
            fn ($r) => (int) ($r['supplier_id'] ?? 0) === $supplierId
        ));

        if (! $baris) {
            return redirect()->route('farm.warehouse.stock')
                ->with('error', 'Belum ada lot stok untuk supplier ini.');
        }

        $nama = $baris[0]['supplier_nama'];

        $ringkas = [
            'masuk'     => round(array_sum(array_column($baris, 'nilai_masuk')), 2),
            'realisasi' => round(array_sum(array_column($baris, 'nilai_realisasi')), 2),
            'terjual'   => round(array_sum(array_column($baris, 'nilai_terjual')), 2),
            'susut'     => round(array_sum(array_column($baris, 'nilai_susut')), 2),
            'sisa'      => round(array_sum(array_column($baris, 'nilai_sisa')), 2),
            'sisa_kg'   => round(array_sum(array_column($baris, 'sisa_kg')), 2),
            'sisa_ekor' => (int) array_sum(array_column($baris, 'sisa_ekor')),
        ];

        // Selisih ini harus 0. Kalau tidak, ada mutasi yang tidak tercatat —
        // lebih baik terlihat sebagai baris daripada disembunyikan.
        $ringkas['selisih'] = round(
            $ringkas['masuk'] - $ringkas['terjual'] - $ringkas['susut'] - $ringkas['sisa'], 2
        );

        return view('backend.farm.warehouse.stock_detail', [
            'supplierId' => $supplierId,
            'nama'       => $nama,
            'lots'       => $baris,
            'ringkas'    => $ringkas,
        ]);
    }

    /**
     * Satu baris per lot beserta seluruh mutasinya.
     * Dihitung sekali lalu dipakai bersama oleh daftar & halaman rincian.
     */
    private function lotRows(): array
    {
        $lots = \App\Models\Farm\StockLot::with(['item', 'supplier'])
            ->orderBy('date')->orderBy('id')->get();

        if ($lots->isEmpty()) {
            return [];
        }

        $ids = $lots->pluck('id')->all();

        // Nilai penjualan & penyesuaian diambil dari baris pemakaian, bukan dihitung
        // ulang dari harga sekarang — harga pokok yang sudah dibukukan harus tetap.
        $terjual = \Illuminate\Support\Facades\DB::table('farm_stock_out_lot_usages')
            ->whereIn('lot_id', $ids)
            ->selectRaw('lot_id, COALESCE(SUM(cost),0) nilai, COALESCE(SUM(weight_kg),0) kg, COALESCE(SUM(qty_ekor),0) ekor')
            ->groupBy('lot_id')->get()->keyBy('lot_id');

        $susut = \Illuminate\Support\Facades\DB::table('farm_adjustment_lot_usages')
            ->whereIn('lot_id', $ids)
            ->selectRaw('lot_id, COALESCE(SUM(cost),0) nilai, COALESCE(SUM(weight_kg),0) kg, COALESCE(SUM(qty_ekor),0) ekor')
            ->groupBy('lot_id')->get()->keyBy('lot_id');

        $realisasi = \App\Models\Farm\StockInRealizationLine::whereIn('lot_id', $ids)
            ->get()->keyBy('lot_id');

        $out = [];
        foreach ($lots as $lot) {
            $rl = $realisasi[$lot->id] ?? null;
            $tj = $terjual[$lot->id] ?? null;
            $ss = $susut[$lot->id] ?? null;

            // Nilai lot = isi awal (yang sudah disetel ke angka nyata bila ada
            // realisasi) dikali harga pokoknya.
            $nilaiMasuk = (float) $lot->weight_kg_initial > 0
                ? round((float) $lot->weight_kg_initial * (float) $lot->cost_per_kg, 2)
                : round((int) $lot->qty_ekor_initial * (float) $lot->cost_per_ekor, 2);

            $nilaiSisa = (float) $lot->weight_kg_left > 0
                ? round((float) $lot->weight_kg_left * (float) $lot->cost_per_kg, 2)
                : round((int) $lot->qty_ekor_left * (float) $lot->cost_per_ekor, 2);

            $out[] = [
                'lot_id'        => $lot->id,
                'tanggal'       => $lot->date,
                'supplier_id'   => $lot->supplier_id,
                'supplier_nama' => $lot->supplier?->name
                    ?? ($lot->source === 'production' ? 'Produksi Sendiri' : 'Tanpa Supplier'),
                'item_id'       => $lot->item_id,
                'item_nama'     => $lot->item?->name ?? '-',
                'sumber'        => $lot->source,

                'awal_ekor'     => (int) $lot->qty_ekor_initial,
                'awal_kg'       => round((float) $lot->weight_kg_initial, 2),
                'sisa_ekor'     => (int) $lot->qty_ekor_left,
                'sisa_kg'       => round((float) $lot->weight_kg_left, 2),
                'hpp_kg'        => (float) $lot->cost_per_kg,
                'hpp_ekor'      => (float) $lot->cost_per_ekor,

                'nilai_masuk'     => $nilaiMasuk,
                'nilai_sisa'      => $nilaiSisa,
                'nilai_terjual'   => round((float) ($tj->nilai ?? 0), 2),
                'terjual_kg'      => round((float) ($tj->kg ?? 0), 2),
                'terjual_ekor'    => (int) ($tj->ekor ?? 0),
                'nilai_susut'     => round((float) ($ss->nilai ?? 0), 2),
                'susut_kg'        => round((float) ($ss->kg ?? 0), 2),
                'susut_ekor'      => (int) ($ss->ekor ?? 0),

                // Bertanda: positif = barang kurang dari nota (saldo supplier naik).
                'nilai_realisasi' => round((float) ($rl->value ?? 0), 2),
                'realisasi_label' => $rl ? $rl->deltaLabel() : null,
                'nota_kg'         => $rl ? round((float) $rl->nota_weight_kg, 2) : null,
                'nota_ekor'       => $rl ? (int) $rl->nota_qty_ekor : null,
            ];
        }

        return $out;
    }

}
