<?php

namespace App\Http\Controllers\Backend\Fnb;

use App\Http\Controllers\Controller;
use App\Models\Fnb\Ingredient;
use App\Models\Fnb\MenuIngredient;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * RESEP menu (bahan + gramasi per porsi). Dikelola dari Data Master Menu.
 * Simpan = wipe & re-insert (sesuai desain), tidak mengubah tabel menus.
 */
class RecipeController extends Controller
{
    /** Daftar menu + status resep (sudah ada resep / belum) + perkiraan HPP standar. */
    public function index()
    {
        $menus = Menu::with(['menuIngredients.ingredient', 'category'])->orderBy('name')->get();

        return view('backend.fnb.recipes.index', [
            'menus'       => $menus,
            'ingredients' => Ingredient::orderBy('name')->get(),
        ]);
    }

    /** JSON resep 1 menu (untuk modal). */
    public function show(Menu $menu)
    {
        return response()->json([
            'menu_id' => $menu->id,
            'name'    => $menu->name,
            'lines'   => $menu->menuIngredients()->with('ingredient')->get()->map(fn ($l) => [
                'ingredient_id' => $l->ingredient_id,
                'name'          => $l->ingredient?->name,
                'unit'          => $l->ingredient?->unit,
                'quantity'      => (float) $l->quantity,
            ]),
        ]);
    }

    /** Simpan resep (wipe & re-insert). */
    public function store(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'lines'                 => ['nullable', 'array'],
            'lines.*.ingredient_id' => ['required', 'integer'],
            'lines.*.quantity'      => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($menu, $data) {
            MenuIngredient::where('menu_id', $menu->id)->delete();
            $seen = [];
            foreach (($data['lines'] ?? []) as $line) {
                $ingId = (int) $line['ingredient_id'];
                if (in_array($ingId, $seen, true)) {
                    continue; // hindari bahan dobel dalam satu resep
                }
                $seen[] = $ingId;
                MenuIngredient::create([
                    'menu_id'       => $menu->id,
                    'ingredient_id' => $ingId,
                    'quantity'      => (float) $line['quantity'],
                ]);
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Resep disimpan.']);
    }
}
