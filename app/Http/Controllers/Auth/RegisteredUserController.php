<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        // Pra-isi kode referral dari ?ref= atau cookie (bila datang lewat link referral).
        $ref = $request->query('ref') ?: $request->cookie(config('affiliate.cookie_name', 'mooda_ref'));
        return view('auth.register', ['ref' => $ref]);
    }

    /**
     * Handle an incoming registration request.
     * Membuat 1 TENANT baru + user OWNER. Status langganan = inactive (terkunci)
     * sampai owner menyelesaikan pembayaran di halaman billing (Midtrans).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'category'      => ['nullable', 'in:resto,cafe,umkm'],
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'email'         => [
                'required', 'string', 'lowercase', 'email:rfc', 'max:255',
                // Anti-inject + rapi: hanya huruf/angka dengan pemisah tunggal titik/strip/underscore
                // (tanpa simbol lain, tanpa titik berurutan, tanpa titik di awal/akhir).
                'regex:/^[a-z0-9]+([._-][a-z0-9]+)*@[a-z0-9]+([.-][a-z0-9]+)*\.[a-z]{2,}$/',
                'unique:' . User::class,
                function ($attribute, $value, $fail) {
                    if (substr_count(Str::before($value, '@'), '.') > 2) {
                        $fail('Email jangan pakai terlalu banyak titik (maksimal 2 sebelum tanda @).');
                    }
                },
            ],
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.regex' => 'Format email tidak valid. Gunakan huruf/angka dengan pemisah titik/strip/underscore tunggal, tanpa simbol lain.',
        ]);

        $user = DB::transaction(function () use ($request) {
            $categoryLabels = ['resto' => 'Restoran', 'cafe' => 'Cafe', 'umkm' => 'UMKM'];
            $tenant = Tenant::create([
                'name'                => $request->business_name,
                'slug'                => $this->uniqueSlug($request->business_name),
                'business_type'       => $request->business_type ?: ($categoryLabels[$request->category] ?? null),
                'category'            => $request->category,
                'phone'               => $request->phone,
                'email'               => $request->email,
                // Terkunci sampai berlangganan (sesuai kebutuhan: wajib langganan dulu)
                'subscription_status' => 'inactive',
                'plan'                => null,
                'trial_ends_at'       => null,
                'is_active'           => true,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $request->name,
                'email'     => $request->email,
                'username'  => $this->uniqueUsername($request->email),
                'no_wa'     => $request->phone,
                'phone'     => $request->phone,
                'password'  => Hash::make($request->password),
                'is_active' => true,
            ]);

            $user->assignRole('owner');
            $tenant->update(['owner_id' => $user->id]);

            // Catat referral bila tenant daftar lewat kode afiliator (cookie mooda_ref).
            $this->attachReferral($tenant, $request);

            return $user;
        });

        // Kirim email verifikasi (link aktivasi) versi branded — dikirim eksplisit (anti dobel).
        $user->sendEmailVerificationNotification();

        Auth::login($user);

        // Wajib aktivasi via link email dulu. Setelah aktif: otomatis jadi Starter + saldo Rp2.000.
        return redirect()->route('verification.notice')
            ->with('status', 'Akun berhasil dibuat! Kami sudah mengirim link aktivasi ke email Anda (' . $user->email . '). Klik link tersebut untuk mengaktifkan akun & dapat saldo Starter Rp2.000.');
    }

    /** Catat pemakaian kode referral (cookie mooda_ref) oleh tenant yang baru daftar. */
    private function attachReferral(Tenant $tenant, Request $request): void
    {
        try {
            // Prioritas: kode yang diketik/terisi di form; fallback ke cookie link referral.
            $code = trim((string) ($request->input('ref') ?: $request->cookie(config('affiliate.cookie_name', 'mooda_ref'))));
            if ($code === '') {
                return;
            }
            $affiliate = \App\Models\Affiliate::whereRaw('UPPER(code) = ?', [strtoupper($code)])->first();
            if (! $affiliate) {
                return;
            }
            \App\Models\Referral::firstOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'affiliate_id' => $affiliate->id,
                    'tenant_name'  => $tenant->name,
                    'status'       => 'signup',
                ]
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('attachReferral gagal: ' . $e->getMessage());
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tenant';
        $slug = $base;
        $i = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function uniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '_') ?: 'user';
        $username = $base;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $i++;
        }
        return $username;
    }
}
