<?php

namespace App\Http\Controllers\Backend\Fnb;

use App\Http\Controllers\Controller;
use App\Models\Fnb\Ingredient;
use App\Models\Fnb\StockOpname;
use App\Models\Fnb\StockOpnameDetail;
use App\Services\Fnb\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Stok opname: bandingkan stok sistem vs fisik, lalu sesuaikan (FEFO). */
class StockOpnameController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index()
    {
        return view('backend.fnb.opname.index', [
            'ingredients' => Ingredient::withSum('batches as stock', 'remaining_quantity')->orderBy('name')->get(),
            'history'     => StockOpname::with('details.ingredient')->orderByDesc('id')->limit(20)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'                   => ['required', 'date'],
            'notes'                  => ['nullable', 'string', 'max:500'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.ingredient_id'  => ['required', 'integer'],
            'items.*.physical_qty'   => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            $opname = StockOpname::create([
                'user_id' => Auth::id(),
                'date'    => $data['date'],
                'notes'   => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $row) {
                $ing = Ingredient::find($row['ingredient_id']);
                if (! $ing) {
                    continue;
                }
                $system   = $ing->currentStock();
                $physical = (float) $row['physical_qty'];
                $diff     = round($physical - $system, 2);

                StockOpnameDetail::create([
                    'stock_opname_id' => $opname->id,
                    'ingredient_id'   => $ing->id,
                    'system_qty'      => $system,
                    'physical_qty'    => $physical,
                    'difference'      => $diff,
                ]);

                // Sesuaikan stok agar sistem = fisik.
                $this->stock->applyOpnameDifference($ing->id, $diff, 'opname#' . $opname->id);
            }
        });

        return back()->with('success', 'Stok opname disimpan & stok disesuaikan.');
    }
}
