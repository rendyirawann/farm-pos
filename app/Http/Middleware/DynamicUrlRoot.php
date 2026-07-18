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
            $host = $request->getHost();
            $isPrimary = $host === 'mooda.id' || str_ends_with($host, '.mooda.id');

            if ($isPrimary) {
                URL::forceRootUrl(config('app.url'));
                URL::forceScheme('https');
            } else {
                // Akses via IP / host lain -> ikuti permintaan apa adanya.
                URL::forceRootUrl($request->getSchemeAndHttpHost());
                URL::forceScheme($request->getScheme());
            }
        }

        return $next($request);
    }
}
