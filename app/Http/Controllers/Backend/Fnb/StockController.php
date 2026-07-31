<?php

namespace App\Http\Controllers\Backend\Fnb;

use App\Http\Controllers\Controller;
use App\Models\Fnb\Ingredient;
use App\Models\Fnb\IngredientBatch;
use App\Models\Fnb\StockMovement;
use App\Models\Fnb\Supplier;
use App\Services\Fnb\StockService;
use Illuminate\Http\Request;

/**
 * INVENTORY: pembelian (buat lot), stok keluar manual (waste/koreksi), dan kartu stok.
 * Stok tidak disimpan sebagai satu angka — dihitung dari sisa lot (FIFO/FEFO).
 */
class StockController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    /** Ringkasan stok per bahan + daftar lot. */
    public function index()
    {
        $ingredients = Ingredient::withSum('batches as stock', 'remaining_quantity')->orderBy('name')->get();

        $batches = IngredientBatch::with(['ingredient', 'supplier'])
            ->where('remaining_quantity', '>', 0)
            ->orderByRaw('expiry_date ASC NULLS LAST')->orderBy('entry_date')->limit(200)->get();

        $stockValue = (float) IngredientBatch::selectRaw('COALESCE(SUM(remaining_quantity * buy_price),0) v')->value('v');

        return view('backend.fnb.stock.index', [
            'ingredients' => $ingredients,
            'batches'     => $batches,
            'suppliers'   => Supplier::orderBy('name')->get(),
            'stockValue'  => $stockValue,
        ]);
    }

    /** Pembelian/restock -> lot baru (harga satuan = total / qty). */
    public function purchase(Request $request)
    {
        $data = $request->validate([
            'ingredient_id'   => ['required', 'integer'],
            'quantity'        => ['required', 'numeric', 'min:0.01'],
            'buy_price_total' => ['required', 'numeric', 'min:0'],
            'supplier_id'     => ['nullable', 'integer'],
            'entry_date'      => ['nullable', 'date'],
            'expiry_date'     => ['nullable', 'date', 'after_or_equal:entry_date'],
            'reference'       => ['nullable', 'string', 'max:120'],
        ]);

        $this->stock->addBatch($data);

        return back()->with('success', 'Stok masuk dicatat (lot baru dibuat).');
    }

    /** Stok keluar manual: rusak/tumpah/buang (waste) atau koreksi. */
    public function issue(Request $request)
    {
        $data = $request->validate([
            'ingredient_id' => ['required', 'integer'],
            'quantity'      => ['required', 'numeric', 'min:0.01'],
            'reason'        => ['required', 'in:waste,adjustment'],
            'reference'     => ['nullable', 'string', 'max:120'],
        ]);

        $cost = $this->stock->adjustOut(
            (int) $data['ingredient_id'],
            (float) $data['quantity'],
            $data['reason'],
            $data['reference'] ?? null
        );

        $msg = 'Stok keluar dicatat. Nilai bahan keluar: Rp ' . number_format($cost, 0, ',', '.') . '.';
        if (! empty($this->stock->shortages)) {
            $msg .= ' Perhatian: stok tidak cukup, kekurangan tercatat tanpa biaya.';
        }

        return back()->with('success', $msg);
    }

    /** Kartu stok (ledger) per bahan. */
    public function card(Request $request)
    {
        $ingredientId = $request->integer('ingredient_id') ?: Ingredient::orderBy('name')->value('id');

        $movements = StockMovement::with(['batch'])
            ->when($ingredientId, fn ($q) => $q->where('ingredient_id', $ingredientId))
            ->orderByDesc('id')->limit(300)->get();

        return view('backend.fnb.stock.card', [
            'ingredients'  => Ingredient::orderBy('name')->get(),
            'ingredientId' => $ingredientId,
            'movements'    => $movements,
        ]);
    }
}
