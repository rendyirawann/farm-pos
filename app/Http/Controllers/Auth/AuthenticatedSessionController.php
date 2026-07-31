<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\SiteOption;
use Jenssegers\Agent\Agent;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login', [
            'maintenance' => SiteOption::get('maintenance_mode', '0') === '1',
            'maintenanceMessage' => SiteOption::get('maintenance_message')
                ?: 'Aplikasi sedang dalam pemeliharaan. Mohon maaf atas ketidaknyamanannya, silakan coba beberapa saat lagi.',
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        // 1. PROSES LOGIN & RATE LIMITER (PENTING!)
        // Kita panggil fungsi authenticate() dari LoginRequest.
        // Di sinilah logic deteksi Email/WA/Nama DAN logic Lockout (10s, 15s) berjalan.
        $request->authenticate();

        // 2. Regenerate Session (Keamanan standar)
        $request->session()->regenerate();

        // 3. Ambil Data User
        $user = Auth::user();

        // 4. Cek Status Banned (Double Check setelah login berhasil)
        if ($user->banned_at) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Respon jika banned
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun Anda telah dibekukan. Silakan hubungi admin.',
                ], 403);
            }
            throw ValidationException::withMessages([
                'email' => 'Akun Anda telah dibekukan.',
            ]);
        }

        // 4b. Mode Pemeliharaan: hanya Superadmin yang boleh masuk saat aktif.
        if (SiteOption::get('maintenance_mode', '0') === '1' && ! $user->isSuperadmin()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $msg = SiteOption::get('maintenance_message')
                ?: 'Aplikasi sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.';

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $msg,
                    'maintenance' => true,
                ], 503);
            }
            throw ValidationException::withMessages(['email' => $msg]);
        }

        // 4c. Isolasi VERTICAL: akun tenant hanya boleh login di subdomain vertical-nya
        //     (mis. tenant laundry HARUS di laundry.mooda.id, tenant F&B di mooda.id).
        //     Sesi tidak dibagi antar subdomain (SESSION_DOMAIN=null), jadi diblokir tegas
        //     dengan pesan + alamat yang benar. Superadmin dikecualikan (lintas-vertical).
        if (! $user->isSuperadmin()) {
            $hostVertical = \App\Verticals\VerticalRegistry::fromHost($request->getHost());
            $knownHosts   = array_map(
                fn ($m) => strtolower((string) ($m['host'] ?? '')),
                \App\Verticals\VerticalRegistry::all()
            );
            $tenant = $user->tenant;

            if ($tenant && in_array(strtolower($request->getHost()), $knownHosts, true)) {
                $tenantVertical = \App\Verticals\VerticalRegistry::normalize($tenant->vertical);
                if ($tenantVertical !== $hostVertical) {
                    $correctHost = \App\Verticals\VerticalRegistry::host($tenantVertical);
                    $label       = \App\Verticals\VerticalRegistry::label($tenantVertical);

                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    $msg = 'Akun ini terdaftar untuk ' . $label . '. Silakan masuk melalui '
                        . $correctHost . '/admin/login';

                    if ($request->expectsJson()) {
                        return response()->json([
                            'status'   => 'error',
                            'message'  => $msg,
                            'redirect' => 'https://' . $correctHost . '/admin/login',
                        ], 403);
                    }
                    throw ValidationException::withMessages(['email' => $msg]);
                }
            }
        }

        // 5. Update Data User (IP & Last Login)
        $user->update([
            'last_ip' => $request->ip(),
            'last_login' => now(),
        ]);

        // 6. Catat Activity Log (Spatie + Agent)
        $agent = new Agent;
        if (function_exists('activity')) {
            activity()
                ->useLog('login')
                ->causedBy($user)
                ->withProperties([
                    'ip' => $request->ip(),
                    'agent' => [
                        'browser' => $agent->browser() . ' ' . $agent->version($agent->browser()),
                        'os' => $agent->platform() . ' ' . $agent->version($agent->platform()),
                        'device' => $agent->device(),
                        'is_mobile' => $agent->isMobile(),
                        'is_desktop' => $agent->isDesktop(),
                        'raw' => $request->header('User-Agent'),
                    ],
                    'request' => [
                        'method' => $request->method(),
                        'url' => $request->fullUrl(),
                    ],
                ])
                ->log('Login berhasil');
        }

        // 7. Tujuan setelah login: Superadmin -> Platform Menu (semua menu platform),
        //    user lain -> dashboard seperti biasa.
        $target = $user->isSuperadmin() ? route('platform-menu.index') : route('dashboard');

        // Support JSON untuk Metronic
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil, mengalihkan...',
                'redirect' => $target
            ], 200);
        }

        // Redirect biasa
        return redirect()->intended($target);
    }


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $agent = new Agent;

        // Catat Log Logout
        if ($user && function_exists('activity')) {
            activity()
                ->useLog('logout')
                ->causedBy($user)
                ->withProperties([
                    'ip' => $request->ip(),
                    'agent' => [
                        'browser' => $agent->browser() . ' ' . $agent->version($agent->browser()),
                        'os' => $agent->platform() . ' ' . $agent->version($agent->platform()),
                        'device' => $agent->device(),
                        'is_mobile' => $agent->isMobile(),
                        'is_desktop' => $agent->isDesktop(),
                        'raw' => $request->header('User-Agent'),
                    ],
                    'request' => [
                        'method' => $request->method(),
                        'url' => $request->fullUrl(),
                    ],
                ])
                ->log('Logout berhasil');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
