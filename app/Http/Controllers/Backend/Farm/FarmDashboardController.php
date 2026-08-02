<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Farm\Agent;
use App\Models\Farm\EggProduction;
use App\Models\Farm\Item;
use App\Models\Farm\StockAdjustment;
use App\Models\Farm\StockIn;
use App\Models\Farm\StockOut;
use App\Models\Farm\Supplier;
use App\Models\Farm\WarehouseSession;
use App\Services\Farm\EggCostService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard MOODA FARM — bukan dashboard restoran.
 * Yang dipantau: stok (ekor & kg), pembelian, penjualan, laba kotor, piutang.
 */
class FarmDashboardController extends Controller
{
    public function __construct(private EggCostService $eggCost) {}

    public function index(Request $request)
    {
        // Periode: harian (default) atau bulanan — sama polanya dengan dashboard F&B.
        $range = $request->input('range') === 'month' ? 'month' : 'day';

        $selectedDate = (string) $request->input('date', Carbon::now()->format('Y-m-d'));
        try {
            $dayAnchor = Carbon::createFromFormat('Y-m-d', $selectedDate)->startOfDay();
        } catch (\Throwable $e) {
            $dayAnchor = Carbon::now()->startOfDay();
        }
        $selectedDate = $dayAnchor->format('Y-m-d');

        $selectedMonth = (string) $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $monthAnchor = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $monthAnchor = Carbon::now()->startOfMonth();
        }
        $selectedMonth = $monthAnchor->format('Y-m');

        if ($range === 'month') {
            $start = $monthAnchor->copy();
            $end   = $monthAnchor->copy()->endOfMonth();
            $periodLabel = $monthAnchor->locale('id')->translatedFormat('F Y');
        } else {
            $start = $dayAnchor->copy();
            $end   = $dayAnchor->copy()->endOfDay();
            $periodLabel = $dayAnchor->locale('id')->translatedFormat('l, d F Y');
        }

        $monthOptions = [];
        for ($c = Carbon::now()->startOfMonth(), $i = 0; $i < 12; $i++, $c->subMonth()) {
            $monthOptions[] = ['value' => $c->format('Y-m'), 'label' => $c->locale('id')->translatedFormat('F Y')];
        }

        $d1 = $start->toDateString();
        $d2 = $end->toDateString();

        // ---------- STOK SAAT INI (tidak tergantung periode) ----------
        $items = Item::where('is_active', true)->get();
        $stokAyamEkor = 0; $stokAyamKg = 0.0; $stokTelurButir = 0; $nilaiPersediaan = 0.0;
        $stokPerItem = [];

        foreach ($items as $item) {
            $s = $item->stock();
            $nilai = $item->stockValue();
            // Telur disimpan pada besaran "ekor" (= butir) dengan cost_per_ekor.
            if ($item->category === 'telur') {
                $stokTelurButir += $s['ekor'];
                // Telur dinilai dengan HPP BERJALAN, bukan nilai lot yang beku saat produksi.
                // Biaya pakan sering baru dicatat setelah telur dipanen; kalau memakai nilai
                // lot, persediaan telur akan terlihat Rp0 selamanya.
                $nilai = $s['ekor'] * $this->eggCost->costPerButir($start);
            } else {
                $stokAyamEkor += $s['ekor'];
                $stokAyamKg   += $s['kg'];
            }
            $nilaiPersediaan += $nilai;
            $stokPerItem[] = [
                'nama'     => $item->name,
                'kategori' => $item->categoryLabel(),
                'ekor'     => $s['ekor'],
                'kg'       => $s['kg'],
                'nilai'    => round($nilai, 2),
                'menipis'  => (float) $item->min_stock_kg > 0 && $s['kg'] < (float) $item->min_stock_kg,
            ];
        }

        // ---------- TRANSAKSI PERIODE ----------
        $beli = (float) StockIn::whereBetween('date', [$d1, $d2])->sum('total');
        $jualRow = StockOut::whereBetween('date', [$d1, $d2])
            ->selectRaw('COALESCE(SUM(total_sale),0) jual, COALESCE(SUM(total_cost),0) modal,
                         COALESCE(SUM(gross_profit),0) laba, COUNT(*) nota')
            ->first();

        $biaya = (float) Expense::whereBetween('date', [$d1, $d2])->sum('amount');
        $telurProduksi = (int) EggProduction::whereBetween('date', [$d1, $d2])->sum(DB::raw('qty_butir - qty_broken'));
        $kerugian = (float) StockAdjustment::whereBetween('date', [$d1, $d2])
            ->where('reason', '!=', 'koreksi_tambah')->sum('cost_impact');

        $labaKotor = (float) $jualRow->laba;
        $labaBersih = $labaKotor - $biaya - $kerugian;

        $summary = [
            'stok_ayam_ekor'   => $stokAyamEkor,
            'stok_ayam_kg'     => round($stokAyamKg, 2),
            'stok_telur'       => $stokTelurButir,
            'nilai_persediaan' => round($nilaiPersediaan, 2),
            'pembelian'        => $beli,
            'penjualan'        => (float) $jualRow->jual,
            'modal_terjual'    => (float) $jualRow->modal,
            'laba_kotor'       => $labaKotor,
            'laba_bersih'      => $labaBersih,
            'jumlah_nota'      => (int) $jualRow->nota,
            'margin_persen'    => (float) $jualRow->jual > 0 ? round($labaKotor / (float) $jualRow->jual * 100, 1) : 0,
            'biaya'            => $biaya,
            'kerugian'         => $kerugian,
            'telur_produksi'   => $telurProduksi,
            'hpp_telur'        => $this->eggCost->costPerButir($start),
        ];

        // ---------- PIUTANG ----------
        $piutang = (float) StockOut::where('payment_status', 'unpaid')
            ->selectRaw('COALESCE(SUM(total_sale - paid_amount),0) s')->value('s');
        $piutangTempo = (float) StockOut::where('payment_status', 'unpaid')
            ->whereNotNull('due_date')->whereDate('due_date', '<', now())
            ->selectRaw('COALESCE(SUM(total_sale - paid_amount),0) s')->value('s');

        // ---------- GRAFIK: pembelian vs penjualan ----------
        $kategori = []; $seriBeli = []; $seriJual = []; $seriLaba = [];

        if ($range === 'day') {
            // Harian: 14 hari terakhir sampai tanggal terpilih, supaya ada konteks tren.
            for ($d = $start->copy()->subDays(13); $d->lte($start); $d->addDay()) {
                $tgl = $d->toDateString();
                $kategori[] = $d->format('d/m');
                $seriBeli[] = (int) StockIn::whereDate('date', $tgl)->sum('total');
                $row = StockOut::whereDate('date', $tgl)
                    ->selectRaw('COALESCE(SUM(total_sale),0) j, COALESCE(SUM(gross_profit),0) l')->first();
                $seriJual[] = (int) $row->j;
                $seriLaba[] = (int) $row->l;
            }
        } else {
            $akhir = $end->lt(Carbon::now()) ? $end->copy() : Carbon::now();
            for ($d = $start->copy(); $d->lte($akhir); $d->addDay()) {
                $tgl = $d->toDateString();
                $kategori[] = $d->format('d');
                $seriBeli[] = (int) StockIn::whereDate('date', $tgl)->sum('total');
                $row = StockOut::whereDate('date', $tgl)
                    ->selectRaw('COALESCE(SUM(total_sale),0) j, COALESCE(SUM(gross_profit),0) l')->first();
                $seriJual[] = (int) $row->j;
                $seriLaba[] = (int) $row->l;
            }
        }

        // ---------- PANDUAN SETUP (khas peternakan, bukan setup kasir) ----------
        $onboarding = [
            'show'  => ! (bool) session('farm_setup_hidden'),
            'steps' => [
                ['judul' => 'Tambah Supplier', 'ket' => 'Pemasok ayam Anda',
                 'url' => route('farm.suppliers.index'), 'selesai' => Supplier::exists()],
                ['judul' => 'Tambah Agen', 'ket' => 'Pembeli/agen langganan',
                 'url' => route('farm.agents.index'), 'selesai' => Agent::exists()],
                ['judul' => 'Tambah Item', 'ket' => 'Ayam potong, ayam petelur, telur',
                 'url' => route('farm.items.index'), 'selesai' => Item::exists()],
                ['judul' => 'Catat Pembelian Pertama', 'ket' => 'Stock In dari supplier',
                 'url' => route('farm.stock-in.create'), 'selesai' => StockIn::exists()],
            ],
        ];
        $onboarding['selesai'] = collect($onboarding['steps'])->where('selesai', true)->count();
        $onboarding['total']   = count($onboarding['steps']);

        return view('backend.farm.dashboard', [
            'summary'   => $summary,
            'stokPerItem' => $stokPerItem,
            'piutang'   => $piutang,
            'piutangTempo' => $piutangTempo,
            'chart'     => ['categories' => $kategori, 'beli' => $seriBeli, 'jual' => $seriJual, 'laba' => $seriLaba],
            'range'     => $range,
            'selectedDate'  => $selectedDate,
            'selectedMonth' => $selectedMonth,
            'monthOptions'  => $monthOptions,
            'periodLabel'   => $periodLabel,
            'onboarding'    => $onboarding,
            'sesiGudang'    => WarehouseSession::where('status', 'open')->latest('opened_at')->first(),
            'notaTerakhir'  => StockOut::with('agent')->orderByDesc('id')->limit(6)->get(),
        ]);
    }
}
