<?php

namespace App\Http\Controllers\Backend\Laundry;

use App\Http\Controllers\Controller;
use App\Models\Laundry\LaundryCustomer;
use Illuminate\Http\Request;

/**
 * Kelola pelanggan laundry (+ status member VIP). Data master, tenant-scoped.
 */
class LaundryCustomerController extends Controller
{
    public function index()
    {
        return view('backend.laundry.customers.index', [
            'customers' => LaundryCustomer::orderBy('name')->paginate(25),
        ]);
    }

    public function store(Request $request)
    {
        LaundryCustomer::create($this->validated($request));
        return back()->with('success', 'Pelanggan ditambahkan.');
    }

    public function update(Request $request, LaundryCustomer $customer)
    {
        $customer->update($this->validated($request));
        return back()->with('success', 'Pelanggan "' . $customer->name . '" diperbarui.');
    }

    public function destroy(LaundryCustomer $customer)
    {
        $name = $customer->name;
        $customer->delete();
        return back()->with('success', 'Pelanggan "' . $name . '" dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'email'         => ['nullable', 'email', 'max:100'],
            'address'       => ['nullable', 'string', 'max:255'],
            'member_status' => ['required', 'in:regular,vip'],
        ]);
    }
}
