<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kelola FAQ / Q&A landing (platform-wide, Superadmin). Tampil di section FAQ mooda.id.
 */
class FaqController extends Controller
{
    private function guard(): void
    {
        abort_unless(Auth::check() && Auth::user()->isSuperadmin(), 403);
    }

    private function rules(): array
    {
        return [
            'question'   => ['required', 'string', 'max:500'],
            'answer'     => ['required', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function index()
    {
        $this->guard();

        return view('backend.superadmin.faqs.index', [
            'faqs' => Faq::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    /** Simpan urutan baru (drag & drop). Body: order = [id, id, ...] sesuai posisi. */
    public function reorder(Request $request)
    {
        $this->guard();
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('order', []))));
        foreach ($ids as $i => $id) {
            Faq::where('id', $id)->update(['sort_order' => $i + 1]);
        }

        return response()->json(['status' => 'success', 'count' => count($ids)]);
    }

    public function store(Request $request)
    {
        $this->guard();
        $data = $request->validate($this->rules());
        $data['sort_order'] = (int) ($data['sort_order'] ?? (Faq::max('sort_order') + 1));
        $data['is_active'] = $request->boolean('is_active', true);
        Faq::create($data);

        return back()->with('success', 'FAQ ditambahkan.');
    }

    public function update(Request $request, Faq $faq)
    {
        $this->guard();
        $data = $request->validate($this->rules());
        $data['sort_order'] = (int) ($data['sort_order'] ?? $faq->sort_order);
        $data['is_active'] = $request->boolean('is_active', true);
        $faq->update($data);

        return back()->with('success', 'FAQ diperbarui.');
    }

    public function toggle(Faq $faq)
    {
        $this->guard();
        $faq->update(['is_active' => ! $faq->is_active]);

        return back()->with('success', 'Status FAQ diubah.');
    }

    public function destroy(Faq $faq)
    {
        $this->guard();
        $faq->delete();

        return back()->with('success', 'FAQ dihapus.');
    }
}
