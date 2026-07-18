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
            // Host produksi utama (domain hidup): kunci ke APP_URL + https.
            $primaryHosts = ['mooda.id', 'www.mooda.id', 'blog.mooda.id', 'affiliate.mooda.id'];

            if (in_array($request->getHost(), $primaryHosts, true)) {
                URL::forceRootUrl(config('app.url'));
                URL::forceScheme('https');
            } else {
                // Host lain (akses via IP, atau alias server lama spt lama.mooda.id):
                // ikuti host permintaan apa adanya -> link tidak melompat ke mooda.id.
                URL::forceRootUrl($request->getSchemeAndHttpHost());
                URL::forceScheme($request->getScheme());
            }
        }

        return $next($request);
    }
}
