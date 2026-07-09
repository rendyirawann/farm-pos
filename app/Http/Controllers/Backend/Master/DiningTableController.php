<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DiningTableController extends Controller
{
    public function index()
    {
        return view('backend.master.tables.index');
    }

    public function getData(Request $request)
    {
        $data = DiningTable::orderBy('sort_order')->orderBy('name')->select('dining_tables.*');

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', fn ($row) => '<span class="fw-bold text-gray-800">' . e($row->name) . '</span>')
            ->addColumn('area', fn ($row) => e($row->area ?: '-'))
            ->addColumn('capacity', fn ($row) => $row->capacity ? ($row->capacity . ' org') : '-')
            ->addColumn('status', fn ($row) => $row->is_active
                ? '<span class="badge badge-light-success">Aktif</span>'
                : '<span class="badge badge-light-dark">Nonaktif</span>')
            ->addColumn('action', function ($row) {
                $d = htmlspecialchars(json_encode([
                    'id' => $row->id, 'name' => $row->name, 'area' => $row->area,
                    'capacity' => $row->capacity, 'is_active' => (bool) $row->is_active, 'sort_order' => $row->sort_order,
                ]), ENT_QUOTES, 'UTF-8');

                return '<div class="d-flex justify-content-end gap-2">'
                    . '<button class="btn btn-sm btn-icon btn-light-primary btn-edit-table" data-row="' . $d . '"><i class="ki-outline ki-pencil fs-4"></i></button>'
                    . '<button class="btn btn-sm btn-icon btn-light-danger btn-del-table" data-id="' . $row->id . '"><i class="ki-outline ki-trash fs-4"></i></button>'
                    . '</div>';
            })
            ->rawColumns(['name', 'status', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        try {
            DiningTable::create($data);
            return response()->json(['success' => true, 'message' => 'Meja berhasil ditambahkan.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $data = $this->validated($request);

        try {
            DiningTable::findOrFail($id)->update($data);
            return response()->json(['success' => true, 'message' => 'Meja berhasil diperbarui.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui.'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DiningTable::findOrFail($id)->delete();
            return response()->json(['success' => true, 'message' => 'Meja dihapus.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus.'], 500);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'       => 'required|string|max:40',
            'area'       => 'nullable|string|max:60',
            'capacity'   => 'nullable|integer|min:1|max:999',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active'  => 'nullable',
        ]) + ['is_active' => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN)];
    }
}
