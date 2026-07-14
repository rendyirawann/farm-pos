<?php

namespace App\Http\Controllers\Blog\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog\Category;
use App\Models\Blog\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;
use Yajra\DataTables\Facades\DataTables;

/**
 * Modul BLOG (admin) — CRUD artikel. Khusus Superadmin (can:blog.manage).
 * Konvensi mengikuti modul Master: DataTables server-side + modal AJAX + response JSON.
 * body disanitasi (mews/purifier) sebelum simpan karena tampil ke publik.
 */
class PostController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get();
        return view('backend.blog.posts.index', compact('categories'));
    }

    /** Sumber DataTables server-side. */
    public function getDataPosts(Request $request)
    {
        if ($request->ajax()) {
            $data = Post::with('category')->orderByDesc('created_at')->select('blog_posts.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('title', function ($row) {
                    $img = $row->cover
                        ? asset('storage/blog/' . $row->cover)
                        : asset('assets/media/svg/files/blank-image.svg');
                    return '<div class="d-flex align-items-center">'
                        . '<img src="' . $img . '" class="rounded me-3" style="width:44px;height:44px;object-fit:cover;">'
                        . '<div><span class="fw-bold text-gray-800 d-block">' . e($row->title) . '</span>'
                        . '<span class="text-muted fs-8">/' . e($row->slug) . '</span></div></div>';
                })
                ->addColumn('category', fn ($row) => $row->category
                    ? '<span class="badge badge-light-primary">' . e($row->category->name) . '</span>'
                    : '<span class="text-muted">-</span>')
                ->addColumn('status', fn ($row) => $row->status === 'published'
                    ? '<span class="badge badge-light-success">Terbit</span>'
                    : '<span class="badge badge-light-warning">Draf</span>')
                ->addColumn('published_at', fn ($row) => $row->published_at
                    ? Carbon::parse($row->published_at)->locale('id')->translatedFormat('d M Y, H:i')
                    : '<span class="text-muted">-</span>')
                ->addColumn('action', function ($row) {
                    $blogLink = $row->status === 'published'
                        ? '<a href="' . route('blog.show', $row->slug) . '" target="_blank" class="btn btn-sm btn-icon btn-light" title="Buka di blog"><i class="ki-outline ki-exit-right-corner fs-4"></i></a>'
                        : '';
                    return '<div class="d-flex justify-content-end gap-1">'
                        . '<button class="btn btn-sm btn-icon btn-light btn-view-detail" data-id="' . $row->id . '" title="Detail"><i class="ki-outline ki-eye fs-4"></i></button>'
                        . $blogLink
                        . '<button class="btn btn-sm btn-icon btn-light-primary btn-edit" data-id="' . $row->id . '" title="Ubah"><i class="ki-outline ki-pencil fs-4"></i></button>'
                        . '<button class="btn btn-sm btn-icon btn-light-danger btn-delete" data-id="' . $row->id . '" data-name="' . e($row->title) . '" title="Hapus"><i class="ki-outline ki-trash fs-4"></i></button>'
                        . '</div>';
                })
                ->rawColumns(['title', 'category', 'status', 'published_at', 'action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $request->merge(['slug' => $this->uniqueSlug($request->title)]);

        $validator = Validator::make($request->all(), $this->rules(), $this->messages());
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            $data = $this->payload($request);
            $data['user_id']      = Auth::id();
            $data['published_at'] = $request->status === 'published' ? now() : null;

            if ($request->hasFile('cover')) {
                $data['cover'] = $this->storeCover($request->file('cover'));
            }

            $post = Post::create($data);

            activity()->useLog('blog')->causedBy(Auth::user())->performedOn($post)
                ->withProperties(['title' => $post->title, 'status' => $post->status])
                ->log('Menambah artikel blog: ' . $post->title);

            DB::commit();
            return response()->json(['success' => 'Artikel berhasil ditambahkan.', 'judul' => 'Berhasil'], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menyimpan: ' . $e->getMessage(), 'judul' => 'Gagal'], 500);
        }
    }

    /** Partial form edit (di-inject ke modal). */
    public function edit($id)
    {
        $post = Post::findOrFail($id);
        $categories = Category::orderBy('name')->get();
        $html = view('backend.blog.posts.edit', compact('post', 'categories'))->render();
        return response()->json(['html' => $html]);
    }

    /** Partial detail read-only. */
    public function show($id)
    {
        $post = Post::with(['category', 'author'])->findOrFail($id);
        $html = view('backend.blog.posts.show', compact('post'))->render();
        return response()->json(['html' => $html]);
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $request->merge(['slug' => $this->uniqueSlug($request->title, $post->id)]);

        $validator = Validator::make($request->all(), $this->rules($post->id), $this->messages());
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            $data = $this->payload($request);
            // Set published_at saat pertama kali terbit; pertahankan tanggal aslinya bila sudah pernah.
            $data['published_at'] = $request->status === 'published'
                ? ($post->published_at ?? now())
                : $post->published_at;

            if ($request->hasFile('cover')) {
                $this->deleteCover($post->cover);
                $data['cover'] = $this->storeCover($request->file('cover'));
            }

            $post->update($data);

            activity()->useLog('blog')->causedBy(Auth::user())->performedOn($post)
                ->withProperties(['title' => $post->title, 'status' => $post->status])
                ->log('Mengubah artikel blog: ' . $post->title);

            DB::commit();
            return response()->json(['success' => 'Artikel berhasil diperbarui.', 'judul' => 'Berhasil']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal memperbarui: ' . $e->getMessage(), 'judul' => 'Gagal'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $post = Post::findOrFail($id);
            $title = $post->title;
            $this->deleteCover($post->cover);
            $post->delete();

            activity()->useLog('blog')->causedBy(Auth::user())
                ->withProperties(['title' => $title])->log('Menghapus artikel blog: ' . $title);

            DB::commit();
            return response()->json(['success' => 'Artikel berhasil dihapus.', 'judul' => 'Berhasil']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menghapus: ' . $e->getMessage(), 'judul' => 'Gagal'], 500);
        }
    }

    // ================= Helpers =================

    private function rules($ignoreId = null): array
    {
        return [
            'title'            => 'required|string|max:255',
            'slug'             => 'required|string|unique:blog_posts,slug' . ($ignoreId ? ',' . $ignoreId : ''),
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'excerpt'          => 'nullable|string|max:500',
            'body'             => 'nullable|string',
            'cover'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status'           => 'required|in:draft,published',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];
    }

    private function messages(): array
    {
        return [
            'title.required'  => 'Judul artikel wajib diisi.',
            'slug.unique'     => 'Slug artikel sudah dipakai.',
            'status.required' => 'Status wajib dipilih.',
            'status.in'       => 'Status tidak valid.',
            'cover.image'     => 'Cover harus berupa gambar.',
            'cover.mimes'     => 'Cover harus jpeg, png, jpg, atau webp.',
            'cover.max'       => 'Ukuran cover maksimal 2MB.',
        ];
    }

    /** Field umum store/update (body disanitasi). */
    private function payload(Request $request): array
    {
        return [
            'blog_category_id' => $request->blog_category_id ?: null,
            'title'            => $request->title,
            'slug'             => $request->slug,
            'excerpt'          => $request->excerpt,
            'body'             => $request->filled('body') ? Purifier::clean($request->body, 'blog') : null,
            'status'           => $request->status,
            'meta_title'       => $request->meta_title,
            'meta_description' => $request->meta_description,
        ];
    }

    private function storeCover($file): string
    {
        $filename = 'post-' . Str::uuid() . '.' . $file->getClientOriginalExtension();
        Storage::disk('public')->putFileAs('blog', $file, $filename);
        return $filename;
    }

    private function deleteCover(?string $cover): void
    {
        if ($cover && Storage::disk('public')->exists('blog/' . $cover)) {
            Storage::disk('public')->delete('blog/' . $cover);
        }
    }

    /** Slug unik otomatis (title -> slug, tambah sufiks -2, -3, ... bila bentrok). */
    private function uniqueSlug(?string $title, $ignoreId = null): string
    {
        $base = Str::slug((string) $title) ?: 'artikel';
        $slug = $base;
        $i = 2;
        while (Post::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}
