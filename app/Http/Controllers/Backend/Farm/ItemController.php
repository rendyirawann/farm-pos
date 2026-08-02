<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Item;
use Illuminate\Http\Request;

/** Master objek dagang: ayam potong, ayam petelur, telur. */
class ItemController extends Controller
{
    public function index()
    {
        $items = Item::orderBy('category')->orderBy('name')->get();

        return view('backend.farm.items.index', [
            'items'      => $items,
            'categories' => Item::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        // Telur tidak dibeli dari supplier -> ditandai sebagai hasil produksi,
        // harga pokoknya dihitung dari biaya operasional, bukan dari lot pembelian.
        $data['is_produced'] = $data['category'] === 'telur';
        Item::create($data);

        return back()->with('success', 'Item ditambahkan.');
    }

    public function update(Request $request, Item $item)
    {
        $data = $this->validated($request);
        $data['is_produced'] = $data['category'] === 'telur';
        $item->update($data);

        return back()->with('success', 'Item diperbarui.');
    }

    public function toggle(Item $item)
    {
        $item->update(['is_active' => ! $item->is_active]);

        return back()->with('success', 'Status item diubah.');
    }

    public function destroy(Item $item)
    {
        if ($item->lots()->exists()) {
            return back()->with('error', 'Item sudah punya riwayat stok — nonaktifkan saja, jangan dihapus.');
        }
        $item->delete();

        return back()->with('success', 'Item dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category'     => ['required', 'in:' . implode(',', array_keys(Item::CATEGORIES))],
            'name'         => ['required', 'string', 'max:100'],
            'primary_unit' => ['required', 'in:kg,ekor,butir'],
            'min_stock_kg' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
