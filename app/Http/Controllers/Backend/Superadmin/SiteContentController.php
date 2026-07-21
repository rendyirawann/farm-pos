<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SiteOption;
use App\Support\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * CMS konten landing per-situs (mooda.id / blog / affiliate) — Superadmin.
 * Nilai disimpan di SiteOption dgn key "{site}.{field}". Field & default dari
 * config/site_content.php; kosong/sama-dengan-default -> pakai bawaan (baris SiteOption dihapus).
 */
class SiteContentController extends Controller
{
    private function guard(): void
    {
        abort_unless(Auth::check() && Auth::user()->isSuperadmin(), 403);
    }

    public function index(Request $request)
    {
        $this->guard();
        $sites = SiteContent::sites();
        $active = (string) $request->query('situs', '');
        if (! isset($sites[$active])) {
            $active = (string) array_key_first($sites);
        }

        return view('backend.superadmin.site-content.index', [
            'sites'  => $sites,
            'active' => $active,
            'values' => SiteOption::pluck('value', 'key')->all(),
        ]);
    }

    public function update(Request $request, string $site)
    {
        $this->guard();
        $sites = SiteContent::sites();
        abort_unless(isset($sites[$site]), 404);

        // Kumpulkan definisi field situs ini.
        $fields = [];
        foreach ($sites[$site]['groups'] as $group) {
            foreach ($group['fields'] as $f) {
                $fields[$f['key']] = $f;
            }
        }

        // Validasi upload gambar (jpg/jpeg/png, maks 1MB).
        $rules = [];
        $messages = [];
        foreach ($fields as $key => $f) {
            if (($f['type'] ?? 'text') === 'image') {
                $rules["images.$key"] = ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'];
                $messages["images.$key.image"] = 'Berkas "' . ($f['label'] ?? $key) . '" harus berupa gambar.';
                $messages["images.$key.mimes"] = 'Gambar "' . ($f['label'] ?? $key) . '" hanya boleh JPG, JPEG, atau PNG.';
                $messages["images.$key.max"]   = 'Ukuran gambar "' . ($f['label'] ?? $key) . '" maksimal 1MB.';
            }
        }
        // Validasi gambar ikon repeater (nested: rep_img[key][idx]).
        $rules['rep_img.*.*'] = ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'];
        $messages['rep_img.*.*.image'] = 'Gambar ikon harus berupa gambar.';
        $messages['rep_img.*.*.mimes'] = 'Gambar ikon hanya boleh JPG, JPEG, atau PNG.';
        $messages['rep_img.*.*.max']   = 'Ukuran gambar ikon maksimal 1MB.';

        $request->validate($rules, $messages);

        $texts     = (array) $request->input('fields', []);
        $removeImg = (array) $request->input('remove_image', []);

        foreach ($fields as $key => $f) {
            $type   = $f['type'] ?? 'text';
            $optKey = "$site.$key";
            $default = (string) ($f['default'] ?? '');

            if ($type === 'image') {
                // Hapus gambar kustom -> kembali ke bawaan.
                if (! empty($removeImg[$key])) {
                    $this->deleteImage(SiteOption::get($optKey));
                    SiteOption::where('key', $optKey)->delete();
                    continue;
                }
                // Upload baru.
                if ($request->hasFile("images.$key")) {
                    $this->deleteImage(SiteOption::get($optKey));
                    $path = $request->file("images.$key")->store("site/$site", 'public');
                    SiteOption::set($optKey, $path);
                }
                continue;
            }

            // Teks / textarea.
            if (! array_key_exists($key, $texts)) {
                continue;
            }
            $val = trim((string) $texts[$key]);
            // Kosong atau sama dengan bawaan -> pakai bawaan (hapus baris).
            if ($val === '' || $val === trim($default)) {
                SiteOption::where('key', $optKey)->delete();
            } else {
                SiteOption::set($optKey, $val);
            }
        }

        // ===== Seksi REPEATER (Fitur, Kenapa, dst) =====
        $this->saveRepeaters($request, $site, $sites[$site]['repeaters'] ?? []);

        SiteContent::flush();

        return redirect()
            ->route('site-content.index', ['situs' => $site])
            ->with('success', 'Konten "' . ($sites[$site]['label'] ?? $site) . '" berhasil disimpan.');
    }

    /** Simpan seksi repeater: parse baris + upload gambar per item + JSON ke SiteOption. */
    private function saveRepeaters(Request $request, string $site, array $repeaters): void
    {
        $repInput = (array) $request->input('rep', []);
        foreach ($repeaters as $rkey => $rdef) {
            $optKey  = "$site.$rkey";
            $rows    = (array) ($repInput[$rkey] ?? []);
            $default = config("site_repeaters.sites.$site.$rkey.default", []);

            // Gambar lama (untuk hapus file yatim setelah simpan).
            $oldImages = [];
            foreach (SiteContent::repeater($site, $rkey) as $oi) {
                if (! empty($oi['image'])) {
                    $oldImages[] = $oi['image'];
                }
            }

            $items = [];
            $usedImages = [];
            foreach ($rows as $i => $row) {
                $title = trim((string) ($row['title'] ?? ''));
                $desc  = trim((string) ($row['desc'] ?? ''));
                $icon  = trim((string) ($row['icon'] ?? ''));
                $color = (string) ($row['color'] ?? 'indigo');
                $image = $row['image_existing'] ?? null;

                if (! empty($row['remove_image'])) {
                    $image = null;
                }
                if ($request->hasFile("rep_img.$rkey.$i")) {
                    $image = $request->file("rep_img.$rkey.$i")->store("site/$site", 'public');
                }

                // Lewati baris kosong total.
                if ($title === '' && $desc === '' && $icon === '' && empty($image)) {
                    continue;
                }
                if (! empty($image)) {
                    $usedImages[] = $image;
                }
                $items[] = ['icon' => $icon, 'image' => $image, 'color' => $color, 'title' => $title, 'desc' => $desc];
            }

            // Kosong atau identik default -> pakai bawaan (hapus baris).
            if (empty($items) || $items === $default) {
                SiteOption::where('key', $optKey)->delete();
            } else {
                SiteOption::set($optKey, json_encode($items, JSON_UNESCAPED_UNICODE));
            }

            // Bersihkan file gambar lama yang tak lagi dipakai.
            foreach ($oldImages as $img) {
                if (! in_array($img, $usedImages, true)) {
                    $this->deleteImage($img);
                }
            }
        }
    }

    private function deleteImage(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'assets/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
