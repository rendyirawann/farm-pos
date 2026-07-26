<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kelola tautan sosial media footer landing (platform-wide, Superadmin).
 * Ikon terdeteksi otomatis dari URL.
 */
class SocialLinkController extends Controller
{
    private function guard(): void
    {
        abort_unless(Auth::check() && Auth::user()->isSuperadmin(), 403);
    }

    public function index()
    {
        $this->guard();

        return view('backend.superadmin.social-links.index', [
            'links' => SocialLink::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'url'        => ['required', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], ['url.url' => 'URL tidak valid. Contoh: https://www.instagram.com/akun-anda']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? (SocialLink::max('sort_order') + 1));
        $data['is_active'] = $request->boolean('is_active', true);
        SocialLink::create($data);

        return back()->with('success', 'Tautan sosial media ditambahkan.');
    }

    public function update(Request $request, SocialLink $social)
    {
        $this->guard();
        $data = $request->validate([
            'url'        => ['required', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], ['url.url' => 'URL tidak valid.']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? $social->sort_order);
        $data['is_active'] = $request->boolean('is_active', true);
        $social->update($data);

        return back()->with('success', 'Tautan sosial media diperbarui.');
    }

    public function toggle(SocialLink $social)
    {
        $this->guard();
        $social->update(['is_active' => ! $social->is_active]);

        return back()->with('success', 'Status tautan diubah.');
    }

    public function destroy(SocialLink $social)
    {
        $this->guard();
        $social->delete();

        return back()->with('success', 'Tautan sosial media dihapus.');
    }
}
