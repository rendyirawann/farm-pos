<?php

namespace App\Http\Middleware;

use App\Tenancy\Plan;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gating fitur berdasarkan paket langganan tenant.
 * Pemakaian: ->middleware('plan:promo') / 'plan:qr_order' / 'plan:report_items' dst.
 */
class EnsurePlanFeature
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        $user = Auth::user();

        if ($user && $user->isSuperadmin()) {
            return $next($request);
        }

        $tenant = $user?->tenant;

        if (!Plan::tenantAllows($tenant, $feature)) {
            $message = 'Fitur ini tidak tersedia pada paket langganan Anda. Upgrade paket untuk membukanya.';

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
