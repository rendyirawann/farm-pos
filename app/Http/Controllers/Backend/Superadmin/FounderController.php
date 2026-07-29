<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Founder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Kelola founder/tim untuk halaman "Tentang Kami" (nama, jabatan, bio, foto). Superadmin. */
class FounderController extends Controller
{
    public function index()
    {
        $founders = Founder::orderBy('sort_order')->orderBy('id')->get();
        return view('backend.superadmin.founders.index', compact('founders'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'founders'            => ['array'],
            'founders.*.name'     => ['required', 'string', 'max:120'],
            'founders.*.position' => ['required', 'string', 'max:120'],
            'founders.*.bio'      => ['nullable', 'string', 'max:600'],
            'founders.*.photo'    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        foreach ((array) $request->input('founders', []) as $id => $data) {
            $f = Founder::find($id);
            if (! $f) {
                continue;
            }
            $f->name     = $data['name'];
            $f->position = $data['position'];
            $f->bio      = $data['bio'] ?? null;

            if ($request->hasFile("founders.$id.photo")) {
                $path = $request->file("founders.$id.photo")->store('founders', 'public');
                if ($f->photo) {
                    Storage::disk('public')->delete($f->photo);
                }
                $f->photo = $path;
            }
            $f->save();
        }

        return back()->with('success', 'Profil founder berhasil disimpan.');
    }

    public function removePhoto($id)
    {
        $f = Founder::findOrFail($id);
        if ($f->photo) {
            Storage::disk('public')->delete($f->photo);
            $f->photo = null;
            $f->save();
        }
        return back()->with('success', 'Foto dihapus.');
    }
}
