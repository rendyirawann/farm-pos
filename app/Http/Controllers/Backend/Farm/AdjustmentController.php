<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Item;
use App\Models\Farm\StockAdjustment;
use App\Models\Farm\StockLot;
use App\Services\Farm\FarmStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * PENYESUAIAN STOK — ayam mati, susut bobot, rusak, atau koreksi hasil opname.
 * Tanpa jalur ini stok sistem tidak akan pernah cocok dengan fisik.
 */
class AdjustmentController extends Controller
{
    public function __construct(private FarmStockService $stock) {}

    public function index()
    {
        return view('backend.farm.adjustments.index', [
            'rows'    => StockAdjustment::with(['item', 'lot', 'user'])->orderByDesc('date')->orderByDesc('id')->paginate(30),
            'items'   => Item::where('is_active', true)->orderBy('name')->get(),
            'reasons' => StockAdjustment::REASONS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'      => ['required', 'date'],
            'item_id'   => ['required', 'integer'],
            'lot_id'    => ['nullable', 'integer'],
            'reason'    => ['required', 'in:' . implode(',', array_keys(StockAdjustment::REASONS))],
            'qty_ekor'  => ['nullable', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'notes'     => ['nullable', 'string', 'max:255'],
        ]);

        if ((int) ($data['qty_ekor'] ?? 0) <= 0 && (float) ($data['weight_kg'] ?? 0) <= 0) {
            return back()->withInput()->with('error', 'Isi jumlah ekor atau berat yang disesuaikan.');
        }

        $adj = StockAdjustment::create($data + [
            'ref_no'  => 'ADJ-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'user_id' => Auth::id(),
        ]);

        try {
            $dampak = $this->stock->applyAdjustment($adj);
        } catch (\Throwable $e) {
            $adj->delete();

            return back()->with('error', 'Gagal menyesuaikan: ' . $e->getMessage());
        }

        return back()->with('success', 'Penyesuaian tercatat. Dampak nilai: Rp ' . number_format($dampak, 0, ',', '.'));
    }

    /** Persetujuan supervisor/admin. */
    public function approve(StockAdjustment $adjustment)
    {
        $adjustment->update(['approved_by' => Auth::id(), 'approved_at' => now()]);

        return back()->with('success', 'Penyesuaian disetujui.');
    }

    /** Daftar lot suatu item (ajax) — untuk memilih lot tertentu saat menyesuaikan. */
    public function lots(Item $item)
    {
        return response()->json(
            StockLot::where('item_id', $item->id)->available()->fifo()->with('supplier')->get()
                ->map(fn (StockLot $l) => [
                    'id'    => $l->id,
                    'label' => sprintf('%s — sisa %d ekor / %s kg @ Rp%s',
                        $l->date->format('d/m/Y'),
                        $l->qty_ekor_left,
                        number_format((float) $l->weight_kg_left, 2),
                        number_format((float) $l->cost_per_kg, 0, ',', '.')),
                ])
        );
    }
}
