<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Security headers ringan (tidak terlalu ketat) agar:
 * - Tetap aman dari clickjacking/defacement via iframe & MIME-sniffing.
 * - Tidak memblok aset Metronic (inline script/CSS, CDN) -> kompatibel semua browser
 *   termasuk Android Chrome & iOS Safari, dan aman saat di-deploy ke server.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Tidak menimpa header yang sudah diset
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options'        => 'SAMEORIGIN',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
            'X-XSS-Protection'       => '1; mode=block',
            'Permissions-Policy'     => 'geolocation=(self), microphone=(), payment=(self)',
        ];

        foreach ($headers as $key => $value) {
            if (!$response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        // HSTS hanya saat HTTPS (aman untuk produksi, tidak mengganggu lokal http)
        if ($request->secure() && !$response->headers->has('Strict-Transport-Security')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
