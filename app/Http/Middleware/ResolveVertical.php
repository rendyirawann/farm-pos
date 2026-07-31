<?php

namespace App\Http\Middleware;

use App\Verticals\VerticalRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tentukan vertical aktif dari HOST (mis. laundry.mooda.id -> 'laundry') dan simpan di
 * config('app.vertical'). Setelah login: bila vertical tenant != vertical host DAN host
 * saat ini adalah host vertical yang dikenal -> redirect ke subdomain yang benar.
 *
 * AMAN untuk F&B: pada mooda.id, tenant fnb -> host fnb -> tidak ada redirect. Redirect hanya
 * terjadi bila tenant salah subdomain. Di host tak dikenal (localhost) tak pernah redirect.
 */
class ResolveVertical
{
    public function handle(Request $request, Closure $next): Response
    {
        $host     = strtolower($request->getHost());
        $vertical = VerticalRegistry::fromHost($host);
        config(['app.vertical' => $vertical]);

        // Host ini benar-benar host vertical yang dikonfigurasi? (bukan localhost/staging)
        $knownHosts = array_map(
            fn ($m) => strtolower((string) ($m['host'] ?? '')),
            VerticalRegistry::all()
        );
        $isKnownHost = in_array($host, $knownHosts, true);

        if ($isKnownHost && $request->isMethod('GET') && ! $request->ajax()) {
            $user = $request->user();
            if ($user && ! $user->isSuperadmin()) {
                $tenant = $user->tenant;
                if ($tenant) {
                    $tv = $tenant->vertical();
                    $correctHost = VerticalRegistry::host($tv);
                    if ($tv !== $vertical && $correctHost && strtolower($correctHost) !== $host) {
                        return redirect()->away($request->getScheme() . '://' . $correctHost . $request->getRequestUri());
                    }
                }
            }
        }

        return $next($request);
    }
}
