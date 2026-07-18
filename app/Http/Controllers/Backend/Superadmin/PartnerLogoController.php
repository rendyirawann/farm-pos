<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\PartnerLogo;
use App\Models\SiteOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Kelola logo partner/tenant berlangganan (marquee landing) — Superadmin.
 */
class PartnerLogoController extends Controller
{
    private function guard(): void
    {
        abort_unless(Auth::check() && Auth::user()->isSuperadmin(), 403);
    }

    public function index()
    {
        $this->guard();
        return view('backend.superadmin.partner-logos.index', [
            'logos' => PartnerLogo::orderBy('sort_order')->orderBy('id')->get(),
            'limit' => (int) SiteOption::get('landing_partner_limit', 12),
        ]);
    }

    public function store(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:120'],
            'url'        => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image'      => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
        ], [
            'image.mimes' => 'Logo hanya boleh JPG, JPEG, atau PNG.',
            'image.max'   => 'Ukuran logo maksimal 1MB.',
        ]);

        $data['image'] = $this->processAndStore($request->file('image'), $request->boolean('remove_bg'), $request->boolean('grayscale'));
        $data['is_active'] = $request->boolean('is_active', true);
        PartnerLogo::create($data);

        return back()->with('success', 'Logo partner "' . $data['name'] . '" ditambahkan.');
    }

    public function update(Request $request, PartnerLogo $partnerLogo)
    {
        $this->guard();
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:120'],
            'url'        => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image'      => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
        ], [
            'image.mimes' => 'Logo hanya boleh JPG, JPEG, atau PNG.',
            'image.max'   => 'Ukuran logo maksimal 1MB.',
        ]);

        if ($request->hasFile('image')) {
            $this->deleteImage($partnerLogo->image);
            $data['image'] = $this->processAndStore($request->file('image'), $request->boolean('remove_bg'), $request->boolean('grayscale'));
        } else {
            unset($data['image']); // tidak ganti logo -> jangan sentuh kolom image (cegah null)
        }
        $data['is_active'] = $request->boolean('is_active', true);
        $partnerLogo->update($data);

        return back()->with('success', 'Logo partner "' . $partnerLogo->name . '" diperbarui.');
    }

    public function toggle(PartnerLogo $partnerLogo)
    {
        $this->guard();
        $partnerLogo->update(['is_active' => ! $partnerLogo->is_active]);
        return back()->with('success', 'Status "' . $partnerLogo->name . '" diubah.');
    }

    public function destroy(PartnerLogo $partnerLogo)
    {
        $this->guard();
        $name = $partnerLogo->name;
        $this->deleteImage($partnerLogo->image);
        $partnerLogo->delete();
        return back()->with('success', 'Logo partner "' . $name . '" dihapus.');
    }

    public function updateLimit(Request $request)
    {
        $this->guard();
        $request->validate(['limit' => ['required', 'integer', 'min:0', 'max:100']]);
        SiteOption::set('landing_partner_limit', (int) $request->input('limit'));
        return back()->with('success', 'Jumlah logo yang ditampilkan di landing diperbarui.');
    }

    /**
     * Proses logo: kompres (resize sisi terpanjang 600px + PNG lossless), opsi hapus background
     * & grayscale. Output selalu PNG (mendukung transparansi bila background dihapus).
     */
    private function processAndStore($file, bool $removeBg, bool $grayscale): string
    {
        $mime = (string) $file->getMimeType();
        $src = str_contains($mime, 'png')
            ? @imagecreatefrompng($file->getRealPath())
            : @imagecreatefromjpeg($file->getRealPath());
        if (! $src) {
            throw new \RuntimeException('Gagal membaca gambar.');
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $max = 600;
        $scale = min(1, $max / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $img = imagecreatetruecolor($nw, $nh);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefilledrectangle($img, 0, 0, $nw, $nh, imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagealphablending($img, true);
        imagecopyresampled($img, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        if ($removeBg) {
            $this->floodRemoveBg($img, $nw, $nh);
        }
        if ($grayscale) {
            imagefilter($img, IMG_FILTER_GRAYSCALE);
        }

        imagealphablending($img, false);
        imagesavealpha($img, true);
        ob_start();
        imagepng($img, null, 6); // PNG = kompresi lossless
        $data = (string) ob_get_clean();
        imagedestroy($img);

        $filename = Str::uuid() . '.png';
        Storage::disk('public')->put('partners/' . $filename, $data);
        return $filename;
    }

    /**
     * Hapus background otomatis: flood-fill dari tepi gambar; pixel yang mirip warna latar
     * dijadikan transparan. Optimal untuk logo berlatar polos/terang.
     */
    private function floodRemoveBg($img, int $w, int $h): void
    {
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);

        // Warna latar acuan = rata-rata 4 sudut.
        $rr = $gg = $bb = 0;
        foreach ([[0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1]] as [$cx, $cy]) {
            $c = imagecolorat($img, $cx, $cy);
            $rr += ($c >> 16) & 0xFF;
            $gg += ($c >> 8) & 0xFF;
            $bb += $c & 0xFF;
        }
        $refR = $rr / 4; $refG = $gg / 4; $refB = $bb / 4;
        $thr = 44 * 44; // toleransi kuadrat

        $total = $w * $h;
        $visited = array_fill(0, $total, false);
        $queue = [];
        for ($x = 0; $x < $w; $x++) { $queue[] = $x; $queue[] = ($h - 1) * $w + $x; }
        for ($y = 0; $y < $h; $y++) { $queue[] = $y * $w; $queue[] = $y * $w + ($w - 1); }

        $qi = 0;
        while ($qi < count($queue)) {
            $idx = $queue[$qi++];
            if ($idx < 0 || $idx >= $total || $visited[$idx]) {
                continue;
            }
            $visited[$idx] = true;
            $x = $idx % $w;
            $y = intdiv($idx, $w);
            $c = imagecolorat($img, $x, $y);
            $dr = (($c >> 16) & 0xFF) - $refR;
            $dg = (($c >> 8) & 0xFF) - $refG;
            $db = ($c & 0xFF) - $refB;
            if (($dr * $dr + $dg * $dg + $db * $db) > $thr) {
                continue; // bukan latar -> berhenti (batas objek)
            }
            imagesetpixel($img, $x, $y, $transparent);
            if ($x + 1 < $w) $queue[] = $idx + 1;
            if ($x - 1 >= 0) $queue[] = $idx - 1;
            if ($y + 1 < $h) $queue[] = $idx + $w;
            if ($y - 1 >= 0) $queue[] = $idx - $w;
        }
    }

    private function deleteImage(?string $image): void
    {
        if ($image && Storage::disk('public')->exists('partners/' . $image)) {
            Storage::disk('public')->delete('partners/' . $image);
        }
    }
}
