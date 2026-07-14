<?php

namespace App\Http\Controllers\Blog\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

/**
 * Modul BLOG (admin) — CRUD kategori artikel. Khusus Superadmin (can:blog.manage).
 */
class CategoryController extends Controller
{
    public function index()
    {
        return view('backend.blog.categories.index');
    }

    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = Category::withCount('posts')->orderBy('name')->select('blog_categories.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('posts_count', fn ($row) => '<span class="badge badge-light-info">' . $row->posts_count . ' artikel</span>')
                ->addColumn('action', fn ($row) => '<div class="d-flex justify-content-end gap-1">'
                    . '<button class="btn btn-sm btn-icon btn-light-primary btn-edit" data-id="' . $row->id . '" data-name="' . e($row->name) . '"><i class="ki-outline ki-pencil fs-4"></i></button>'
                    . '<button class="btn btn-sm btn-icon btn-light-danger btn-delete" data-id="' . $row->id . '" data-name="' . e($row->name) . '"><i class="ki-outline ki-trash fs-4"></i></button>'
                    . '</div>')
                ->rawColumns(['posts_count', 'action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->merge(['slug' => $this->uniqueSlug($request->name)]);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:blog_categories,slug',
        ], ['name.required' => 'Nama kategori wajib diisi.', 'slug.unique' => 'Kategori ini sudah ada.']);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();
            $cat = Category::create(['name' => $request->name, 'slug' => $request->slug]);
            activity()->useLog('blog')->causedBy(Auth::user())->performedOn($cat)
                ->log('Menambah kategori blog: ' . $cat->name);
            DB::commit();
            return response()->json(['success' => 'Kategori berhasil ditambahkan.', 'judul' => 'Berhasil'], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menyimpan: ' . $e->getMessage(), 'judul' => 'Gagal'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $cat = Category::findOrFail($id);
        $request->merge(['slug' => $this->uniqueSlug($request->name, $cat->id)]);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:blog_categories,slug,' . $cat->id,
        ], ['name.required' => 'Nama kategori wajib diisi.', 'slug.unique' => 'Kategori ini sudah ada.']);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();
            $cat->update(['name' => $request->name, 'slug' => $request->slug]);
            activity()->useLog('blog')->causedBy(Auth::user())->performedOn($cat)
                ->log('Mengubah kategori blog: ' . $cat->name);
            DB::commit();
            return response()->json(['success' => 'Kategori berhasil diperbarui.', 'judul' => 'Berhasil']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal memperbarui: ' . $e->getMessage(), 'judul' => 'Gagal'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $cat = Category::findOrFail($id);
            $name = $cat->name;
            $cat->delete(); // post.blog_category_id -> null (nullOnDelete)
            activity()->useLog('blog')->causedBy(Auth::user())->log('Menghapus kategori blog: ' . $name);
            DB::commit();
            return response()->json(['success' => 'Kategori berhasil dihapus.', 'judul' => 'Berhasil']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menghapus: ' . $e->getMessage(), 'judul' => 'Gagal'], 500);
        }
    }

    private function uniqueSlug(?string $name, $ignoreId = null): string
    {
        $base = Str::slug((string) $name) ?: 'kategori';
        $slug = $base;
        $i = 2;
        while (Category::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}
