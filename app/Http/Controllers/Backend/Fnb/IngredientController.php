<?php

namespace App\Http\Controllers\Backend\Fnb;

use App\Http\Controllers\Controller;
use App\Models\Fnb\Ingredient;
use Illuminate\Http\Request;

/** Data Master: Bahan Baku (modul HPP/Inventory — paket Customize). */
class IngredientController extends Controller
{
    public function index()
    {
        $ingredients = Ingredient::withSum('batches as stock', 'remaining_quantity')
            ->orderBy('name')->get();

        return view('backend.fnb.ingredients.index', [
            'ingredients' => $ingredients,
            'lowCount'    => $ingredients->filter(fn ($i) => (float) $i->minimum_stock > 0
                && (float) ($i->stock ?? 0) <= (float) $i->minimum_stock)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'unit'          => ['required', 'string', 'max:20'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
        ]);
        Ingredient::create($data + ['minimum_stock' => $data['minimum_stock'] ?? 0]);

        return back()->with('success', 'Bahan baku ditambahkan.');
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'unit'          => ['required', 'string', 'max:20'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
        ]);
        $ingredient->update($data + ['minimum_stock' => $data['minimum_stock'] ?? 0]);

        return back()->with('success', 'Bahan baku diperbarui.');
    }

    public function destroy(Ingredient $ingredient)
    {
        // Cegah hapus bila masih dipakai resep atau punya stok/riwayat.
        if ($ingredient->batches()->exists() || $ingredient->movements()->exists()) {
            return back()->with('error', 'Bahan sudah punya riwayat stok — tidak bisa dihapus.');
        }
        $ingredient->delete();

        return back()->with('success', 'Bahan baku dihapus.');
    }
}
