<?php

namespace App\Http\Middleware;

use App\Models\SiteOption;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mode Pemeliharaan (Maintenance) — dikontrol Superadmin via SiteOption.
 *
 * Saat AKTIF:
 *  - Superadmin tetap bisa akses penuh (agar bisa mematikan mode ini).
 *  - User lain yang sudah login -> ditampilkan halaman kunci "Maintenance"
 *    dengan tombol OK yang otomatis logout, lalu di halaman login muncul
 *    pop-up maintenance yang tidak bisa ditutup.
 *
 * Saat NONAKTIF: semua akses normal.
 */
class MaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (SiteOption::get('maintenance_mode', '0') === '1') {
            $user = $request->user();

            if ($user && ! $user->isSuperadmin()) {
                $message = SiteOption::get('maintenance_message')
                    ?: 'Aplikasi sedang dalam pemeliharaan. Mohon maaf atas ketidaknyamanannya, silakan coba beberapa saat lagi.';

                return response()->view('maintenance-lock', [
                    'message' => $message,
                ], 503);
            }
        }

        return $next($request);
    }
}
