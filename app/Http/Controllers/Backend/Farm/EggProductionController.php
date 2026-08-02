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

        $rows = EggProduction::with('item')
            ->whereBetween('date', [$bulan->toDateString(), $bulan->copy()->endOfMonth()->toDateString()])
            ->orderByDesc('date')->orderByDesc('id')
            ->get();

        return view('backend.farm.eggs.index', [
            'rows'    => $rows,
            'bulan'   => $bulan->format('Y-m'),
            'rincian' => $this->eggCost->breakdown($bulan, $bulan->copy()->endOfMonth()),
            'items'   => Item::where('is_active', true)->where('category', 'telur')->orderBy('name')->get(),
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
                StockLot::create([
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
                ]);
            }
        });

        return back()->with('success', 'Produksi telur dicatat & masuk stok.');
    }

    public function destroy(EggProduction $eggProduction)
    {
        $eggProduction->delete();

        return back()->with('success', 'Catatan produksi dihapus. Periksa stok telur bila sudah terlanjur terjual.');
    }
}
