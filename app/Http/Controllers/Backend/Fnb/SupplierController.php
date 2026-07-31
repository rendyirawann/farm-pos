<?php

namespace App\Http\Controllers\Backend\Fnb;

use App\Http\Controllers\Controller;
use App\Models\Fnb\Supplier;
use Illuminate\Http\Request;

/** Data Master: Supplier bahan (modul HPP/Inventory — paket Customize). */
class SupplierController extends Controller
{
    public function index()
    {
        return view('backend.fnb.suppliers.index', [
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Supplier::create($this->rules($request));

        return back()->with('success', 'Supplier ditambahkan.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->rules($request));

        return back()->with('success', 'Supplier diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return back()->with('success', 'Supplier dihapus.');
    }

    private function rules(Request $request): array
    {
        return $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'address'        => ['nullable', 'string', 'max:500'],
        ]);
    }
}
