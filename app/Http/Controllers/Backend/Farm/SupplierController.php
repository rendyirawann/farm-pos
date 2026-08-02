<?php

namespace App\Http\Controllers\Backend\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm\Supplier;
use Illuminate\Http\Request;

/** Master pemasok ayam. */
class SupplierController extends Controller
{
    public function index()
    {
        return view('backend.farm.suppliers.index', [
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Supplier::create($data);

        return back()->with('success', 'Supplier ditambahkan.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request));

        return back()->with('success', 'Supplier diperbarui.');
    }

    public function toggle(Supplier $supplier)
    {
        $supplier->update(['is_active' => ! $supplier->is_active]);

        return back()->with('success', 'Status supplier diubah.');
    }

    public function destroy(Supplier $supplier)
    {
        // Supplier yang sudah dipakai pembelian tidak dihapus — riwayat harga pokok
        // akan kehilangan asal-usulnya. Cukup dinonaktifkan.
        if ($supplier->stockIns()->exists()) {
            return back()->with('error', 'Supplier sudah dipakai pada pembelian — nonaktifkan saja, jangan dihapus.');
        }
        $supplier->delete();

        return back()->with('success', 'Supplier dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes'   => ['nullable', 'string', 'max:255'],
        ]);
    }
}
