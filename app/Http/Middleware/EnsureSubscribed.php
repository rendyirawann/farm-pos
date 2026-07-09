<?php

namespace App\Http\Middleware;

use App\Services\DepositService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Mengunci fitur sistem jika tenant belum berlangganan / masa aktif habis.
 * Superadmin selalu lolos. User tanpa langganan tetap bisa login & buka halaman
 * billing/dashboard (yang tidak memakai middleware ini), tapi tidak fitur operasional.
 */
class EnsureSubscribed
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Platform owner bebas akses
        if ($user->isSuperadmin()) {
            return $next($request);
        }

        $tenant = $user->tenant;

        // Bila langganan bulanan sudah berakhir tapi masih punya poin deposit yang
        // dapat dipakai, kembalikan otomatis ke mode deposit (poin aktif lagi).
        if ($tenant) {
            app(DepositService::class)->maybeRevertToDeposit($tenant);
        }

        if (!$tenant || !$tenant->hasActiveAccess()) {
            $message = 'Langganan Anda belum aktif atau sudah berakhir. Silakan berlangganan untuk memakai fitur ini.';

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 402);
            }

            return redirect()->route('billing.index')->with('warning', $message);
        }

        return $next($request);
    }
}
