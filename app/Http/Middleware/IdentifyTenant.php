<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Menetapkan tenant aktif untuk request dari user yang login.
 * Superadmin sengaja TIDAK diberi tenant -> bisa melihat data semua tenant.
 */
class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (!$user->isSuperadmin() && $user->tenant_id) {
                app(TenantManager::class)->set((int) $user->tenant_id);
            }
        }

        return $next($request);
    }
}
