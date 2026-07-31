<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard route modul per-vertical. Hanya tenant dengan vertical yang cocok yang boleh akses.
 * Superadmin (tanpa tenant) selalu boleh. Pemakaian: ->middleware('vertical:laundry').
 */
class EnsureVertical
{
    public function handle(Request $request, Closure $next, string $vertical): Response
    {
        $user = $request->user();

        // Superadmin lintas-vertical.
        if ($user && $user->isSuperadmin()) {
            return $next($request);
        }

        $tenant = $user?->tenant;
        abort_unless($tenant && $tenant->vertical() === $vertical, 404);

        return $next($request);
    }
}
