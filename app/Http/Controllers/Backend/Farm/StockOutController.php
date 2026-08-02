<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Agent;
use App\Models\Farm\Item;
use App\Models\Farm\StockOut;
use App\Models\Farm\StockOutLine;
use App\Services\Farm\EggCostService;
use App\Services\Farm\FarmStockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * STOCK OUT — penjualan ke agen. Harga pokok diambil FIFO dari lot pembelian,
 * sehingga laba per nota memakai harga beli yang benar-benar terpakai.
 */
class StockOutController extends Controller
{
    public function __construct(
        private FarmStockService $stock,
        private EggCostService $eggCost,
    ) {}

    public function index(Request $request)
    {
        $from = $request->filled('from') ? Carbon::parse($request->from) : Carbon::now()->startOfMonth();
        $to   = $request->filled('to') ? Carbon::parse($request->to) : Carbon::now();

        $q = StockOut::with(['agent', 'lines.item'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);

        if ($request->input('status') === 'unpaid') {
            $q->where('payment_status', 'unpaid');
        }

        $rows = $q->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString();

        $rekap = StockOut::whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COALESCE(SUM(total_sale),0) jual, COALESCE(SUM(total_cost),0) modal, COALESCE(SUM(gross_profit),0) laba')
            ->first();

        return view('backend.farm.stock_out.index', [
            'rows'  => $rows,
            'from'  => $from->format('Y-m-d'),
            'to'    => $to->format('Y-m-d'),
            'rekap' => $rekap,
            'status' => $request->input('status'),
        ]);
    }

    public function create()
    {
        $items = Item::where('is_active', true)->orderBy('name')->get()
            ->map(function (Item $i) {
                $s = $i->stock();
                $i->stok_ekor = $s['ekor'];
                $i->stok_kg   = $s['kg'];

                return $i;
            });

        return view('backend.farm.stock_out.create', [
            'agents' => Agent::where('is_active', true)->orderBy('name')->get(),
            'items'  => $items,
            'hppTelur' => $this->eggCost->costPerButir(),
        ]);
    }

    /**
     * Pratinjau harga pokok (ajax) — dipanggil saat kasir mengetik jumlah,
     * supaya margin terlihat SEBELUM nota disimpan.
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'item_id'   => ['required', 'integer'],
            'qty_ekor'  => ['nullable', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
        ]);

        $item = Item::findOrFail($data['item_id']);

        // Telur: harga pokok dari biaya operasional, bukan dari lot.
        if ($item->is_produced) {
            $butir = (int) ($data['qty_ekor'] ?? 0);
            $hpp = $this->eggCost->costFor($butir);

            return response()->json([
                'cost' => $hpp,
                'lots' => [],
                'catatan' => 'Harga pokok telur dihitung otomatis dari biaya operasional bulan ini.',
                'kurang_kg' => 0, 'kurang_ekor' => 0,
            ]);
        }

        return response()->json(
            $this->stock->previewCost($item->id, (float) ($data['weight_kg'] ?? 0), (int) ($data['qty_ekor'] ?? 0))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'                => ['required', 'date'],
            'agent_id'            => ['nullable', 'integer'],
            'payment_status'      => ['required', 'in:paid,unpaid'],
            'due_date'            => ['nullable', 'date'],
            'notes'               => ['nullable', 'string', 'max:255'],
            'lines'               => ['required', 'array', 'min:1'],
            'lines.*.item_id'     => ['required', 'integer'],
            'lines.*.qty_ekor'    => ['nullable', 'integer', 'min:0'],
            'lines.*.weight_kg'   => ['nullable', 'numeric', 'min:0'],
            'lines.*.price_basis' => ['required', 'in:kg,ekor,butir'],
            'lines.*.unit_price'  => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $out = DB::transaction(function () use ($data) {
                $out = StockOut::create([
                    'invoice_no'     => $this->generateInvoiceNo(),
                    'date'           => $data['date'],
                    'agent_id'       => $data['agent_id'] ?: null,
                    'user_id'        => Auth::id(),
                    'payment_status' => $data['payment_status'],
                    'due_date'       => $data['payment_status'] === 'unpaid' ? ($data['due_date'] ?? null) : null,
                    'paid_amount'    => 0,
                    'notes'          => $data['notes'] ?? null,
                ]);

                $totalJual = $totalModal = 0.0;

                foreach ($data['lines'] as $row) {
                    $ekor = (int) ($row['qty_ekor'] ?? 0);
                    $kg   = round((float) ($row['weight_kg'] ?? 0), 2);
                    if ($ekor <= 0 && $kg <= 0) {
                        continue;
                    }

                    $item  = Item::findOrFail((int) $row['item_id']);
                    $harga = (float) $row['unit_price'];
                    $basis = $row['price_basis'];

                    $subtotal = match ($basis) {
                        'ekor', 'butir' => $harga * $ekor,
                        default         => $harga * $kg,
                    };

                    $line = StockOutLine::create([
                        'stock_out_id' => $out->id,
                        'item_id'      => $item->id,
                        'qty_ekor'     => $ekor,
                        'weight_kg'    => $kg,
                        'price_basis'  => $basis,
                        'unit_price'   => $harga,
                        'subtotal'     => round($subtotal, 2),
                    ]);

                    if ($item->is_produced) {
                        // Telur: HPP dari biaya operasional, dibekukan ke baris ini supaya
                        // laporan lama tidak berubah saat biaya bulan berjalan bertambah.
                        $cost = $this->eggCost->costFor($ekor, Carbon::parse($data['date']));
                    } else {
                        $hasil = $this->stock->consumeFifo($item->id, $kg, $ekor);
                        $cost  = $hasil['cost'];
                        $this->stock->recordUsages($line, $hasil['usages']);
                    }

                    $line->update(['cost' => $cost, 'profit' => round($subtotal - $cost, 2)]);

                    $totalJual  += $subtotal;
                    $totalModal += $cost;
                }

                if ($out->lines()->count() === 0) {
                    throw new \RuntimeException('Tidak ada baris barang yang diisi.');
                }

                $out->update([
                    'total_sale'   => round($totalJual, 2),
                    'total_cost'   => round($totalModal, 2),
                    'gross_profit' => round($totalJual - $totalModal, 2),
                    'paid_amount'  => $data['payment_status'] === 'paid' ? round($totalJual, 2) : 0,
                    'paid_at'      => $data['payment_status'] === 'paid' ? $data['date'] : null,
                ]);

                return $out->fresh(['lines.item', 'agent']);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }

        $pesan = 'Penjualan tersimpan. Nota siap dicetak.';
        if ($this->stock->warnings) {
            $pesan .= ' ⚠ ' . implode(' ', $this->stock->warnings);
        }

        return redirect()->route('farm.stock-out.show', $out->id)
            ->with('success', $pesan)
            ->with('autoprint', true);
    }

    public function show(StockOut $stockOut)
    {
        $stockOut->load(['lines.item', 'lines.lotUsages.lot.supplier', 'agent', 'user', 'payments']);

        return view('backend.farm.stock_out.show', ['row' => $stockOut]);
    }

    public function receipt(StockOut $stockOut)
    {
        $stockOut->load(['lines.item', 'agent']);

        return response()->json([
            'title'       => 'NOTA PENJUALAN',
            'invoice_no'  => $stockOut->invoice_no,
            'datetime'    => $stockOut->date->format('d/m/Y'),
            'party'       => $stockOut->agent?->name ?? 'Umum',
            'party_label' => 'Agen',
            'items'       => $stockOut->lines->map(fn ($l) => [
                'name'      => $l->item?->name ?? '-',
                'qty_ekor'  => (int) $l->qty_ekor,
                'weight_kg' => (float) $l->weight_kg,
                'basis'     => $l->price_basis,
                'price'     => (float) $l->unit_price,
                'subtotal'  => (float) $l->subtotal,
            ]),
            'total'          => (float) $stockOut->total_sale,
            'payment_status' => $stockOut->payment_status,
            'due_date'       => $stockOut->due_date?->format('d/m/Y'),
            'notes'          => $stockOut->notes,
        ]);
    }

    /** Nomor nota jual: JUAL-YYYYMMDD-XXXXXX */
    private function generateInvoiceNo(): string
    {
        do {
            $no = 'JUAL-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (StockOut::withoutGlobalScopes()->where('invoice_no', $no)->exists());

        return $no;
    }
}
