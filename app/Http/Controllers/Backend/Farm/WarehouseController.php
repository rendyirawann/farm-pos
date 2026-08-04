<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Item;
use App\Models\Farm\WarehouseSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * BUKA / TUTUP GUDANG — pengganti "shift" kasir.
 * Tidak ada modal & kembalian: yang dipertanggungjawabkan adalah STOK FISIK.
 */
class WarehouseController extends Controller
{
    public function index()
    {
        return view('backend.farm.warehouse.index', [
            'active'  => WarehouseSession::where('status', 'open')->latest('opened_at')->first(),
            'history' => WarehouseSession::with(['opener', 'closer'])->orderByDesc('opened_at')->limit(30)->get(),
            'items'   => Item::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function open(Request $request)
    {
        if (WarehouseSession::where('status', 'open')->exists()) {
            return back()->with('error', 'Masih ada sesi gudang yang terbuka. Tutup dulu sebelum membuka yang baru.');
        }

        WarehouseSession::create([
            'opened_by'     => Auth::id(),
            'opened_at'     => now(),
            'opening_stock' => $this->snapshot(),
            'status'        => 'open',
            'notes'         => $request->input('notes'),
        ]);

        return back()->with('success', 'Gudang dibuka. Stok awal tercatat.');
    }

    public function close(Request $request, WarehouseSession $session)
    {
        if (! $session->isOpen()) {
            return back()->with('error', 'Sesi ini sudah ditutup.');
        }

        $sistem = $this->snapshot();
        $fisik  = [];
        foreach ((array) $request->input('physical', []) as $itemId => $nilai) {
            $fisik[(int) $itemId] = [
                'ekor' => (int) ($nilai['ekor'] ?? 0),
                'kg'   => round((float) ($nilai['kg'] ?? 0), 2),
            ];
        }

        // Selisih = fisik - sistem. Dicatat apa adanya, tidak menimpa stok:
        // koreksi stok dilakukan sadar lewat Penyesuaian Stok.
        $selisih = [];
        foreach ($sistem as $itemId => $s) {
            $f = $fisik[$itemId] ?? ['ekor' => 0, 'kg' => 0];
            $selisih[$itemId] = [
                'nama' => $s['nama'],
                'ekor' => $f['ekor'] - $s['ekor'],
                'kg'   => round($f['kg'] - $s['kg'], 2),
            ];
        }

        $session->update([
            'closed_by'      => Auth::id(),
            'closed_at'      => now(),
            'closing_stock'  => $sistem,
            'physical_stock' => $fisik,
            'difference'     => $selisih,
            'status'         => 'closed',
            'notes'          => trim(($session->notes ? $session->notes . ' | ' : '') . (string) $request->input('notes')),
        ]);

        return back()->with('success', 'Gudang ditutup. Selisih tercatat — luruskan lewat Penyesuaian Stok bila perlu.');
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

    /** Potret stok sistem saat ini per item. */
    private function snapshot(): array
    {
        $out = [];
        foreach (Item::where('is_active', true)->get() as $item) {
            $s = $item->stock();
            $out[$item->id] = ['nama' => $item->name, 'ekor' => $s['ekor'], 'kg' => $s['kg']];
        }

        return $out;
    }
}
