<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Item;
use App\Models\Farm\StockIn;
use App\Models\Farm\StockInLine;
use App\Models\Farm\StockLot;
use App\Models\Farm\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * STOCK IN — pembelian ayam dari supplier.
 * Setiap baris pembelian menjadi SATU LOT; lot inilah yang nanti diambil FIFO saat dijual.
 */
class StockInController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->filled('from') ? Carbon::parse($request->from) : Carbon::now()->startOfMonth();
        $to   = $request->filled('to') ? Carbon::parse($request->to) : Carbon::now();

        $rows = StockIn::with(['supplier', 'lines.item'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('backend.farm.stock_in.index', [
            'rows' => $rows,
            'from' => $from->format('Y-m-d'),
            'to'   => $to->format('Y-m-d'),
            'total' => (float) StockIn::whereBetween('date', [$from->toDateString(), $to->toDateString()])->sum('total'),
        ]);
    }

    public function create()
    {
        return view('backend.farm.stock_in.create', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            // Telur tidak dibeli dari supplier -> tidak muncul di form pembelian.
            'items'     => Item::where('is_active', true)->where('is_produced', false)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'                  => ['required', 'date'],
            'supplier_id'           => ['nullable', 'integer'],
            'notes'                 => ['nullable', 'string', 'max:255'],
            'lines'                 => ['required', 'array', 'min:1'],
            'lines.*.item_id'       => ['required', 'integer'],
            'lines.*.qty_ekor'      => ['nullable', 'integer', 'min:0'],
            'lines.*.weight_kg'     => ['nullable', 'numeric', 'min:0'],
            'lines.*.price_basis'   => ['required', 'in:kg,ekor'],
            'lines.*.unit_price'    => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $stockIn = DB::transaction(function () use ($data) {
                $in = StockIn::create([
                    'invoice_no'  => $this->generateInvoiceNo(),
                    'date'        => $data['date'],
                    'supplier_id' => $data['supplier_id'] ?: null,
                    'user_id'     => Auth::id(),
                    'notes'       => $data['notes'] ?? null,
                    'total'       => 0,
                ]);

                $total = 0.0;

                foreach ($data['lines'] as $row) {
                    $ekor = (int) ($row['qty_ekor'] ?? 0);
                    $kg   = round((float) ($row['weight_kg'] ?? 0), 2);
                    $harga = (float) $row['unit_price'];

                    if ($ekor <= 0 && $kg <= 0) {
                        continue;   // baris kosong dilewati
                    }

                    // Subtotal mengikuti dasar harga yang dipilih kasir.
                    $subtotal = $row['price_basis'] === 'ekor' ? $harga * $ekor : $harga * $kg;

                    $line = StockInLine::create([
                        'stock_in_id' => $in->id,
                        'item_id'     => (int) $row['item_id'],
                        'qty_ekor'    => $ekor,
                        'weight_kg'   => $kg,
                        'price_basis' => $row['price_basis'],
                        'unit_price'  => $harga,
                        'subtotal'    => round($subtotal, 2),
                    ]);

                    // Harga pokok disimpan dalam DUA besaran supaya penjualan bisa
                    // dihitung baik per kg maupun per ekor tanpa menebak-nebak.
                    StockLot::create([
                        'item_id'           => $line->item_id,
                        'stock_in_line_id'  => $line->id,
                        'supplier_id'       => $in->supplier_id,
                        'date'              => $in->date,
                        'qty_ekor_initial'  => $ekor,
                        'weight_kg_initial' => $kg,
                        'qty_ekor_left'     => $ekor,
                        'weight_kg_left'    => $kg,
                        'cost_per_kg'       => $kg > 0 ? round($subtotal / $kg, 2) : 0,
                        'cost_per_ekor'     => $ekor > 0 ? round($subtotal / $ekor, 2) : 0,
                    ]);

                    $total += $subtotal;
                }

                if ($in->lines()->count() === 0) {
                    throw new \RuntimeException('Tidak ada baris barang yang diisi.');
                }

                $in->update(['total' => round($total, 2)]);

                return $in->fresh(['lines.item', 'supplier']);
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }

        return redirect()
            ->route('farm.stock-in.show', $stockIn->id)
            ->with('success', 'Pembelian tersimpan. Nota siap dicetak.')
            ->with('autoprint', true);
    }

    public function show(StockIn $stockIn)
    {
        $stockIn->load(['lines.item', 'supplier', 'user']);

        return view('backend.farm.stock_in.show', ['row' => $stockIn]);
    }

    /** Payload nota untuk mesin cetak (MoodaPrint). */
    public function receipt(StockIn $stockIn)
    {
        $stockIn->load(['lines.item', 'supplier']);

        return response()->json([
            'title'    => 'NOTA PEMBELIAN',
            'invoice_no' => $stockIn->invoice_no,
            'datetime' => $stockIn->date->format('d/m/Y'),
            'party'    => $stockIn->supplier?->name ?? 'Tanpa Supplier',
            'party_label' => 'Supplier',
            'items'    => $stockIn->lines->map(fn ($l) => [
                'name'      => $l->item?->name ?? '-',
                'qty_ekor'  => (int) $l->qty_ekor,
                'weight_kg' => (float) $l->weight_kg,
                'basis'     => $l->price_basis,
                'price'     => (float) $l->unit_price,
                'subtotal'  => (float) $l->subtotal,
            ]),
            'total'    => (float) $stockIn->total,
            'notes'    => $stockIn->notes,
        ]);
    }

    /** Nomor nota beli: BELI-YYYYMMDD-XXXXXX */
    private function generateInvoiceNo(): string
    {
        do {
            $no = 'BELI-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (StockIn::withoutGlobalScopes()->where('invoice_no', $no)->exists());

        return $no;
    }
}
