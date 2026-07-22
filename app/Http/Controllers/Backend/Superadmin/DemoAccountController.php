<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DepositService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Akun Demo (Superadmin):
 *  - Generate 1 tenant demo + user OWNER & KASIR (kredensial acak) + saldo deposit Rp5.000,
 *    lalu tampilkan email/password agar bisa langsung diberikan.
 *  - Deposit Rp5.000 ke akun (tenant) yang dipilih.
 */
class DemoAccountController extends Controller
{
    /** Saldo deposit demo (Rupiah). */
    public const DEMO_DEPOSIT = 5000;

    private function guard(): void
    {
        abort_unless(Auth::check() && Auth::user()->isSuperadmin(), 403);
    }

    public function index()
    {
        $this->guard();

        // Daftar akun demo yang pernah dibuat.
        $demoTenants = Tenant::where('created_via', 'demo')
            ->with('owner:id,email,tenant_id')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        // Dropdown deposit: kecualikan tenant yang sedang berlangganan bulanan aktif
        // (agar tidak tak sengaja terkonversi ke mode deposit). Sama seperti Setelan Deposit.
        $tenants = Tenant::orderBy('name')->get()
            ->filter(fn ($t) => $t->isDepositMode() || ! $t->monthlyActive())
            ->values();

        return view('backend.superadmin.demo.index', [
            'demoTenants'  => $demoTenants,
            'tenants'      => $tenants,
            'demoDeposit'  => self::DEMO_DEPOSIT,
        ]);
    }

    /** Generate 1 tenant demo + owner + kasir + saldo Rp5.000. Tampilkan kredensial via flash. */
    public function generate()
    {
        $this->guard();

        $token     = $this->uniqueToken();
        $ownerPass = Str::password(10, true, true, false, false);
        $kasirPass = Str::password(10, true, true, false, false);

        $data = DB::transaction(function () use ($token, $ownerPass, $kasirPass) {
            $tenant = Tenant::create([
                'name'                => 'Demo ' . strtoupper($token),
                'slug'                => $this->uniqueSlug('demo-' . $token),
                'business_type'       => 'Cafe (Demo)',
                'category'            => 'cafe',
                'subscription_status' => 'inactive',
                'plan'                => null,
                'is_active'           => true,
                'created_via'         => 'demo',
            ]);

            $owner = User::create([
                'tenant_id'         => $tenant->id,
                'name'              => 'Owner Demo',
                'email'             => 'owner-' . $token . '@demo.mooda.id',
                'username'          => 'owner_' . $token,
                'password'          => Hash::make($ownerPass),
                'is_active'         => true,
                'email_verified_at' => now(),   // akun demo langsung aktif (email fiktif)
            ]);
            $owner->assignRole('owner');
            $tenant->update(['owner_id' => $owner->id]);

            $kasir = User::create([
                'tenant_id'         => $tenant->id,
                'name'              => 'Kasir Demo',
                'email'             => 'kasir-' . $token . '@demo.mooda.id',
                'username'          => 'kasir_' . $token,
                'password'          => Hash::make($kasirPass),
                'is_active'         => true,
                'email_verified_at' => now(),   // akun demo langsung aktif (email fiktif)
            ]);
            $kasir->assignRole('kasir');

            // Saldo awal demo Rp5.000 (auto -> mode deposit; tanpa enforce cap).
            app(DepositService::class)->manualCredit(
                $tenant,
                self::DEMO_DEPOSIT,
                self::DEMO_DEPOSIT,
                Auth::id(),
                'Saldo awal akun demo'
            );

            return compact('tenant', 'owner', 'kasir');
        });

        return redirect()->route('demo-accounts.index')->with('demo_created', [
            'tenant'         => $data['tenant']->name,
            'login_url'      => route('login'),
            'owner_email'    => $data['owner']->email,
            'owner_password' => $ownerPass,
            'kasir_email'    => $data['kasir']->email,
            'kasir_password' => $kasirPass,
            'deposit'        => self::DEMO_DEPOSIT,
        ]);
    }

    /** Deposit Rp5.000 ke tenant yang dipilih (mis. untuk demo / bonus). */
    public function deposit(Request $request)
    {
        $this->guard();

        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
        ]);

        $tenant = Tenant::findOrFail($data['tenant_id']);

        // Jangan konversi tenant yang sedang berlangganan bulanan aktif.
        if (! $tenant->isDepositMode() && $tenant->monthlyActive()) {
            return back()->with('error', 'Tenant "' . $tenant->name . '" sedang berlangganan bulanan aktif — tidak dideposit agar mode-nya tidak berubah.');
        }

        app(DepositService::class)->manualCredit(
            $tenant,
            self::DEMO_DEPOSIT,
            self::DEMO_DEPOSIT,
            Auth::id(),
            'Deposit Rp' . number_format(self::DEMO_DEPOSIT, 0, ',', '.') . ' oleh Superadmin'
        );

        return back()->with('success', 'Berhasil deposit Rp' . number_format(self::DEMO_DEPOSIT, 0, ',', '.') . ' ke "' . $tenant->name . '".');
    }

    /** Token acak yang belum dipakai di email demo. */
    private function uniqueToken(): string
    {
        do {
            $token = strtolower(Str::random(5));
        } while (User::where('email', 'owner-' . $token . '@demo.mooda.id')->exists()
            || User::where('email', 'kasir-' . $token . '@demo.mooda.id')->exists());

        return $token;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
