<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\EggProduction;
use App\Models\Farm\Item;
use App\Models\Farm\StockLot;
use App\Services\Farm\EggCostService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * PRODUKSI TELUR harian. Telur tidak dibeli, jadi tidak lewat Stock In —
 * harga pokoknya dihitung otomatis dari biaya operasional (EggCostService).
 */
class EggProductionController extends Controller
{
    public function __construct(private EggCostService $eggCost) {}

    public function index(Request $request)
    {
        $bulan = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $rows = EggProduction::with(['item', 'lot'])
            ->whereBetween('date', [$bulan->toDateString(), $bulan->copy()->endOfMonth()->toDateString()])
            ->orderByDesc('date')->orderByDesc('id')
            ->get();

        // Sisa stok telur diambil dari LOT, bukan dari selisih catatan produksi:
        // lot itulah yang benar-benar berkurang saat telur terjual.
        $stok = DB::table('farm_stock_lots as l')
            ->join('farm_items as i', 'i.id', '=', 'l.item_id')
            ->where('i.is_produced', true)
            ->selectRaw('COALESCE(SUM(l.qty_ekor_left),0) sisa,
                         COALESCE(SUM(l.qty_ekor_initial),0) masuk,
                         COALESCE(SUM(l.qty_ekor_left * l.cost_per_ekor),0) nilai')
            ->first();

        return view('backend.farm.eggs.index', [
            'rows'    => $rows,
            'bulan'   => $bulan->format('Y-m'),
            'rincian' => $this->eggCost->breakdown($bulan, $bulan->copy()->endOfMonth()),
            'items'   => Item::where('is_active', true)->where('category', 'telur')->orderBy('name')->get(),
            'stok'    => [
                'sisa'    => (int) $stok->sisa,
                'masuk'   => (int) $stok->masuk,
                'terjual' => max(0, (int) $stok->masuk - (int) $stok->sisa),
                'nilai'   => round((float) $stok->nilai, 2),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'       => ['required', 'date'],
            'item_id'    => ['required', 'integer'],
            'coop'       => ['nullable', 'string', 'max:50'],
            'qty_butir'  => ['required', 'integer', 'min:1'],
            'qty_broken' => ['nullable', 'integer', 'min:0'],
            'notes'      => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data) {
            $prod = EggProduction::create($data + ['user_id' => Auth::id()]);

            // Telur layak jual masuk stok sebagai lot. Harga pokoknya memakai HPP
            // otomatis periode ini; kalau biaya belum dicatat, nilainya 0 dan akan
            // ikut terkoreksi begitu pengeluaran diinput.
            $bersih = $prod->netButir();
            if ($bersih > 0) {
                $hppSatuan = $this->eggCost->costPerButir(Carbon::parse($data['date']));
                $lot = StockLot::create([
                    'item_id'           => $prod->item_id,
                    'stock_in_line_id'  => null,
                    'supplier_id'       => null,
                    'date'              => $prod->date,
                    'qty_ekor_initial'  => $bersih,     // untuk telur, "ekor" = butir
                    'weight_kg_initial' => 0,
                    'qty_ekor_left'     => $bersih,
                    'weight_kg_left'    => 0,
                    'cost_per_kg'       => 0,
                    'cost_per_ekor'     => $hppSatuan,
                    'source'            => 'production',
                ]);

                // Rujukan disimpan supaya sisa telur per catatan bisa ditampilkan
                // dan penghapusan bisa memeriksa apakah telurnya sudah terjual.
                $prod->update(['lot_id' => $lot->id]);
            }
        });

        return back()->with('success', 'Produksi telur dicatat & masuk stok.');
    }

    public function destroy(EggProduction $eggProduction)
    {
        $lot = $eggProduction->lot;

        // Telur yang sudah terjual tidak boleh dihapus catatannya: harga pokok pada
        // nota penjualan mengambil dari lot ini, dan menghapusnya membuat laba nota
        // lama berubah tanpa jejak.
        if ($lot) {
            $terjual = (int) $lot->qty_ekor_initial - (int) $lot->qty_ekor_left;
            if ($terjual > 0) {
                return back()->with('error', sprintf(
                    'Tidak bisa dihapus — %d butir dari catatan ini sudah terjual. '
                    . 'Untuk mengoreksi jumlah, pakai menu Penyesuaian Stok.', $terjual
                ));
            }
        }

        DB::transaction(function () use ($eggProduction, $lot) {
            $eggProduction->delete();
            $lot?->delete();      // lot masih utuh (belum terjual), aman dibuang
        });

        return back()->with('success', 'Catatan produksi & stoknya dihapus.');
    }

    /** Rincian satu catatan produksi: dipakai ke mana saja butirnya. */
    public function detail(EggProduction $eggProduction)
    {
        $lot = $eggProduction->lot;

        $pemakaian = $lot
            ? DB::table('farm_stock_out_lot_usages as u')
                ->join('farm_stock_out_lines as l', 'l.id', '=', 'u.stock_out_line_id')
                ->join('farm_stock_outs as o', 'o.id', '=', 'l.stock_out_id')
                ->leftJoin('farm_agents as a', 'a.id', '=', 'o.agent_id')
                ->where('u.lot_id', $lot->id)
                ->orderBy('o.date')->orderBy('o.id')
                ->get(['o.date', 'o.invoice_no', 'a.name as agen', 'u.qty_ekor', 'u.cost',
                    'l.unit_price', 'o.id as stock_out_id'])
            : collect();

        $susut = $lot
            ? DB::table('farm_adjustment_lot_usages as au')
                ->join('farm_stock_adjustments as adj', 'adj.id', '=', 'au.adjustment_id')
                ->where('au.lot_id', $lot->id)
                ->orderBy('adj.date')
                ->get(['adj.date', 'adj.ref_no', 'adj.reason', 'au.qty_ekor', 'au.cost'])
            : collect();

        return response()->json([
            'tanggal'   => $eggProduction->date->locale('id')->translatedFormat('d F Y'),
            'kandang'   => $eggProduction->coop ?: '—',
            'item'      => $eggProduction->item?->name ?? '—',
            'butir'     => (int) $eggProduction->qty_butir,
            'pecah'     => (int) $eggProduction->qty_broken,
            'bersih'    => $eggProduction->netButir(),
            'hpp'       => $lot ? (float) $lot->cost_per_ekor : 0,
            'sisa'      => $lot ? (int) $lot->qty_ekor_left : 0,
            'terjual'   => $lot ? max(0, (int) $lot->qty_ekor_initial - (int) $lot->qty_ekor_left) : 0,
            'catatan'   => $eggProduction->notes,
            'penjualan' => $pemakaian->map(fn ($r) => [
                'tanggal' => Carbon::parse($r->date)->format('d/m/Y'),
                'nota'    => $r->invoice_no,
                'agen'    => $r->agen ?: 'Ecer / umum',
                'butir'   => (int) $r->qty_ekor,
                'harga'   => (float) $r->unit_price,
                'hpp'     => (float) $r->cost,
                'url'     => route('farm.stock-out.show', $r->stock_out_id),
            ]),
            'penyesuaian' => $susut->map(fn ($r) => [
                'tanggal' => Carbon::parse($r->date)->format('d/m/Y'),
                'ref'     => $r->ref_no,
                'sebab'   => \App\Models\Farm\StockAdjustment::REASONS[$r->reason] ?? $r->reason,
                'butir'   => (int) $r->qty_ekor,
                'nilai'   => (float) $r->cost,
            ]),
        ]);
    }
}
