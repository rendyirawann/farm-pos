<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;

/**
 * Halaman unduhan aplikasi tablet (APK) untuk tenant yang berlangganan aktif.
 * APK adalah pembungkus (WebView) yang tetap terhubung ke server via internet.
 */
class DownloadAppController extends Controller
{
    private function localApkPath(): string
    {
        return public_path('downloads/stakko-pos.apk');
    }

    /** Sumber unduhan: file lokal jika ada, atau URL eksternal dari config. */
    private function apkUrl(): ?string
    {
        if (File::exists($this->localApkPath())) {
            return route('download-app.apk');
        }
        return config('stakko.mobile_apk_url') ?: null;
    }

    public function index()
    {
        $apkUrl  = $this->apkUrl();
        $version = config('stakko.mobile_version', '1.0.0');
        $available = (bool) $apkUrl;

        return view('backend.download_app.index', compact('apkUrl', 'version', 'available'));
    }

    /** Unduh file APK lokal. */
    public function apk()
    {
        $path = $this->localApkPath();
        abort_unless(File::exists($path), 404, 'File APK belum tersedia.');

        return response()->download($path, 'stakko-pos.apk', [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }
}
