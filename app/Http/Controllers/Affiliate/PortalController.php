<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

/**
 * Modul AFFILIATE (portal publik) — affiliate.mooda.id.
 * Untuk afiliator EKSTERNAL (bukan pengguna POS): landing, daftar, login, dashboard.
 * Auth pakai tabel users + role 'affiliate' (host affiliate.mooda.id ber-WAF penuh).
 */
class PortalController extends Controller
{
    /** Landing program afiliasi. */
    public function landing()
    {
        $commissionType  = config('affiliate.commission_type', 'flat');
        $commissionValue = (float) config('affiliate.commission_value', 50000);
        return view('affiliate.landing', compact('commissionType', 'commissionValue'));
    }

    /** Klik link referral -> set cookie -> arahkan ke pendaftaran tenant di mooda.id. */
    public function track($code)
    {
        $minutes = (int) config('affiliate.cookie_days', 30) * 24 * 60;
        $resp = redirect()->away('https://mooda.id/admin/register');
        // Cookie domain .mooda.id agar terbaca saat tenant mendaftar di mooda.id.
        return $resp->cookie(config('affiliate.cookie_name', 'mooda_ref'), $code, $minutes, '/', '.mooda.id');
    }

    public function showRegister()
    {
        return view('affiliate.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'username'          => $this->uniqueUsername($data['email']),
                'phone'             => $data['phone'] ?? null,
                'no_wa'             => $data['phone'] ?? null,
                'password'          => Hash::make($data['password']),
                'is_active'         => true,
                'email_verified_at' => now(),
                'tenant_id'         => null,
            ]);
            $user->assignRole('affiliate');

            Affiliate::create([
                'code'    => Affiliate::generateCode($data['name']),
                'name'    => $data['name'],
                'email'   => $data['email'],
                'phone'   => $data['phone'] ?? null,
                'type'    => 'external',
                'user_id' => $user->id,
                'status'  => 'pending', // menunggu persetujuan Superadmin
            ]);

            Auth::login($user);
        });

        return redirect()->route('affiliate.dashboard')
            ->with('status', 'Pendaftaran berhasil! Akun afiliator Anda menunggu persetujuan admin.');
    }

    public function showLogin()
    {
        return view('affiliate.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        // Portal ini KHUSUS afiliator (role 'affiliate'). Selain itu -> tolak.
        if (! Auth::user()->hasRole('affiliate')) {
            Auth::logout();
            return back()->withErrors(['email' => 'Akun ini bukan akun afiliator. Silakan masuk lewat mooda.id.'])->onlyInput('email');
        }
        if (Auth::user()->banned_at) {
            Auth::logout();
            return back()->withErrors(['email' => 'Akun Anda dibekukan.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        return redirect()->route('affiliate.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('affiliate.home');
    }

    public function dashboard()
    {
        if (! Auth::check()) {
            return redirect()->route('affiliate.login');
        }
        if (! Auth::user()->hasRole('affiliate')) {
            return redirect()->route('affiliate.home');
        }

        $user = Auth::user();
        $affiliate = Affiliate::where('user_id', $user->id)->first();

        // Belum punya profil afiliator (mis. user role affiliate tanpa row) -> buatkan minimal.
        if (! $affiliate) {
            $affiliate = Affiliate::create([
                'code' => Affiliate::generateCode($user->name), 'name' => $user->name,
                'email' => $user->email, 'type' => 'external', 'user_id' => $user->id, 'status' => 'pending',
            ]);
        }

        $referrals = $affiliate->referrals()->with('tenant')->orderByDesc('created_at')->get();
        $stats = [
            'total'      => $referrals->count(),
            'subscribed' => $referrals->where('status', 'subscribed')->count(),
            'earned'     => (float) $referrals->where('commission_status', 'paid')->sum('commission_amount'),
            'pending'    => (float) $referrals->where('commission_status', '!=', 'paid')->sum('commission_amount'),
        ];

        return view('affiliate.dashboard', compact('affiliate', 'referrals', 'stats'));
    }

    private function uniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '_') ?: 'affiliate';
        $u = $base;
        $i = 1;
        while (User::where('username', $u)->exists()) {
            $u = $base . $i++;
        }
        return $u;
    }
}
