<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Root URL mengikuti host permintaan (per-request, aman untuk Octane).
 *
 * - Host utama (mooda.id / *.mooda.id): paksa ke APP_URL + https (perilaku lama).
 * - Host lain, mis. AKSES LANGSUNG VIA IP (server lama yang sudah tak dipetakan ke domain):
 *   ikuti host permintaan supaya link/aset/login TIDAK melompat ke mooda.id.
 *
 * Menggantikan URL::forceRootUrl statis di AppServiceProvider yang selalu mengunci ke mooda.id.
 */
class DynamicUrlRoot
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.env') === 'production') {
            $host = strtolower($request->getHost());

            // Semua host mooda.id & subdomainnya (mooda.id, laundry.mooda.id, blog, affiliate, ...):
            // root URL MENGIKUTI host permintaan + https. Ini penting agar redirect login,
            // halaman error (419/403), dan link lain TIDAK melompat ke mooda.id saat user
            // sedang berada di subdomain vertical (mis. laundry.mooda.id).
            if ($host === 'mooda.id' || str_ends_with($host, '.mooda.id')) {
                URL::forceRootUrl('https://' . $host);
                URL::forceScheme('https');
            } else {
                // Host lain (akses via IP / alias server lama): ikuti apa adanya.
                URL::forceRootUrl($request->getSchemeAndHttpHost());
                URL::forceScheme($request->getScheme());
            }
        }

        return $next($request);
    }
}
