<?php

namespace App\Http\Controllers\Backend\Fnb;

use App\Http\Controllers\Controller;
use App\Models\Fnb\Ingredient;
use App\Models\Fnb\IngredientBatch;
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
    /** Halaman daftar resep. Isi tabel diambil via ajax (DataTables server-side) di data(). */
    public function index()
    {
        return view('backend.fnb.recipes.index', [
            'ingredients' => Ingredient::orderBy('name')->get(),
        ]);
    }

    /**
     * Data tabel resep untuk DataTables server-side (paging, search, sort).
     * Perkiraan HPP dihitung per halaman saja (bukan seluruh menu) supaya ringan.
     */
    public function data(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 5);
        $length = $length < 1 ? 5 : min($length, 100);   // -1 (tampil semua) tidak diizinkan
        $search = trim((string) $request->input('search.value', ''));

        // Kolom yang boleh diurutkan: 0=menu, 1=kategori. Sisanya nilai turunan.
        $sortable = [0 => 'menus.name', 1 => 'categories.name'];
        $colIdx   = (int) $request->input('order.0.column', 0);
        $sortCol  = $sortable[$colIdx] ?? 'menus.name';
        $sortDir  = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $base = Menu::query()
            ->leftJoin('categories', 'categories.id', '=', 'menus.category_id')
            ->select('menus.*');

        $total = (clone $base)->count();

        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('menus.name', 'ilike', "%{$search}%")
                    ->orWhere('categories.name', 'ilike', "%{$search}%");
            });
        }

        $filtered = (clone $base)->count();

        $menus = $base->with(['menuIngredients.ingredient', 'category'])
            ->orderBy($sortCol, $sortDir)
            ->skip($start)->take($length)
            ->get();

        // Harga lot TERBARU per bahan — satu query untuk semua bahan di halaman ini (hindari N+1).
        $ingredientIds = $menus->pluck('menuIngredients')->flatten()->pluck('ingredient_id')->unique()->all();
        $latestPrice   = collect();
        if ($ingredientIds) {
            $latestPrice = IngredientBatch::whereIn('ingredient_id', $ingredientIds)
                ->orderBy('ingredient_id')->orderByDesc('id')
                ->get(['id', 'ingredient_id', 'buy_price'])
                ->groupBy('ingredient_id')
                ->map(fn ($g) => (float) $g->first()->buy_price);
        }

        $rows = $menus->map(function (Menu $m) use ($latestPrice) {
            $est    = 0.0;
            $recipe = [];
            foreach ($m->menuIngredients as $l) {
                $qty  = (float) $l->quantity;
                $est += $qty * (float) ($latestPrice[$l->ingredient_id] ?? 0);
                $recipe[] = [
                    'name' => $l->ingredient?->name,
                    'qty'  => rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.'),
                    'unit' => $l->ingredient?->unit,
                ];
            }

            return [
                'id'       => $m->id,
                'menu'     => $m->name,
                'price'    => (float) ($m->price ?? 0),
                'category' => $m->category?->name ?? '-',
                'recipe'   => $recipe,
                'hpp_est'  => round($est, 2),
            ];
        });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows,
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
