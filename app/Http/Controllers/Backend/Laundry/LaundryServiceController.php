<?php

namespace App\Http\Controllers\Backend\Laundry;

use App\Http\Controllers\Controller;
use App\Models\Laundry\LaundryService;
use Illuminate\Http\Request;

/**
 * Kelola layanan laundry (kiloan/satuan/express). Data master, tenant-scoped.
 */
class LaundryServiceController extends Controller
{
    public function index()
    {
        return view('backend.laundry.services.index', [
            'services' => LaundryService::orderBy('sort_order')->orderBy('name')->get(),
            'units'    => LaundryService::UNITS,
        ]);
    }

    public function store(Request $request)
    {
        LaundryService::create($this->validated($request));
        return back()->with('success', 'Layanan ditambahkan.');
    }

    public function update(Request $request, LaundryService $service)
    {
        $service->update($this->validated($request));
        return back()->with('success', 'Layanan "' . $service->name . '" diperbarui.');
    }

    public function toggle(LaundryService $service)
    {
        $service->update(['is_active' => ! $service->is_active]);
        return back()->with('success', 'Status "' . $service->name . '" diubah.');
    }

    public function destroy(LaundryService $service)
    {
        $name = $service->name;
        $service->delete();
        return back()->with('success', 'Layanan "' . $name . '" dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'                     => ['required', 'string', 'max:100'],
            'category'                 => ['nullable', 'string', 'max:60'],
            'unit'                     => ['required', 'in:' . implode(',', array_keys(LaundryService::UNITS))],
            'price_per_unit'           => ['required', 'numeric', 'min:0'],
            'estimated_duration_hours' => ['required', 'integer', 'min:1', 'max:2160'],
            'sort_order'               => ['nullable', 'integer', 'min:0'],
        ]) + ['is_active' => (bool) $request->boolean('is_active', true)];
    }
}
