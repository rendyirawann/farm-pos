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
    public function create(): View
    {
        return view('auth.register');
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
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'email'         => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request) {
            $tenant = Tenant::create([
                'name'                => $request->business_name,
                'slug'                => $this->uniqueSlug($request->business_name),
                'business_type'       => $request->business_type,
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

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        // Arahkan ke halaman billing: wajib berlangganan dulu sebelum memakai fitur.
        return redirect()->route('billing.index')
            ->with('warning', 'Akun & data bisnis Anda berhasil dibuat. Silakan pilih paket & lakukan pembayaran untuk mengaktifkan sistem.');
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
