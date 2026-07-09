<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscription;
use App\Tenancy\Plan;
use Spatie\Permission\Models\Role;

/**
 * Seeder tenant "Terra Coffee" (bisnis pelanggan yang memakai aplikasi Mooda) beserta akunnya:
 * - Owner  (langganan Starter aktif 6 bulan)
 * - Admin
 * - Kasir Pagi & Kasir Sore
 *
 * Idempoten: aman dijalankan berulang (firstOrCreate berdasarkan slug/email/order id).
 * Jalankan sendiri:  php artisan db:seed --class=Database\\Seeders\\TerraCoffeeSeeder
 */
class TerraCoffeeSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Langganan Starter 6 bulan (promo): total dihitung dari config paket.
        $months  = 6;
        $amount  = Plan::periodAmount('basic', $months) ?? 894000; // 6 x Rp149.000
        $startAt = now();
        $endsAt  = now()->addMonthsNoOverflow($months);

        // ============================================================
        // 1. Tenant Terra Coffee (langganan Starter aktif s/d 6 bulan ke depan)
        // ============================================================
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'terra-coffee'],
            [
                'name'                 => 'Terra Coffee',
                'business_type'        => 'Cafe',
                'phone'                => '081200000001',
                'email'                => 'halo@terracoffee.id',
                'address'              => 'Lubuk Pakam, Deli Serdang, Sumatera Utara',
                'plan'                 => 'basic',
                'subscription_status'  => 'active',
                'trial_ends_at'        => null,
                'subscription_ends_at' => $endsAt,
                'is_active'            => true,
            ]
        );

        // Pastikan status langganan tetap sinkron walau tenant sudah ada.
        $tenant->update([
            'plan'                 => 'basic',
            'subscription_status'  => 'active',
            'subscription_ends_at' => $endsAt,
            'is_active'            => true,
        ]);

        // ============================================================
        // 2. Akun-akun tenant Terra Coffee
        //    [role, nama, email, username, password]
        // ============================================================
        $accounts = [
            ['owner', 'Owner Terra Coffee', 'owner@terracoffee.id',     'owner_terra',      'owner12345'],
            ['admin', 'Admin Terra Coffee', 'admin@terracoffee.id',     'admin_terra',      'admin12345'],
            ['kasir', 'Kasir Pagi',         'kasirpagi@terracoffee.id', 'kasir_pagi_terra', 'kasir12345'],
            ['kasir', 'Kasir Sore',         'kasirsore@terracoffee.id', 'kasir_sore_terra', 'kasir12345'],
        ];

        $ownerUser = null;

        foreach ($accounts as [$roleName, $name, $email, $username, $password]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'tenant_id'         => $tenant->id,
                    'name'              => $name,
                    'username'          => $username,
                    'password'          => Hash::make($password),
                    'no_wa'             => '081200000001',
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ]
            );

            if (!$user->tenant_id) {
                $user->update(['tenant_id' => $tenant->id]);
            }

            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            if (!$user->hasRole($roleName)) {
                $user->assignRole($role);
            }

            if ($roleName === 'owner') {
                $ownerUser = $user;
            }
        }

        // 3. Set pemilik tenant
        if ($ownerUser) {
            $tenant->update(['owner_id' => $ownerUser->id]);
        }

        // ============================================================
        // 4. Riwayat langganan Starter 6 bulan (LUNAS)
        //    billing_period = jumlah bulan (konsisten dgn BillingController).
        // ============================================================
        Subscription::firstOrCreate(
            ['midtrans_order_id' => 'TERRA-SEED-STARTER-6M'],
            [
                'tenant_id'      => $tenant->id,
                'plan'           => 'basic',
                'amount'         => $amount,
                'billing_period' => (string) $months,
                'status'         => 'paid',
                'payment_type'   => 'seeder',
                'starts_at'      => $startAt,
                'ends_at'        => $endsAt,
                'paid_at'        => $startAt,
            ]
        );

        $this->command->info('Tenant "Terra Coffee" + akun siap (langganan Starter 6 bulan, aktif s/d ' . $endsAt->format('d M Y') . '):');
        $this->command->info('  owner@terracoffee.id     / owner12345  (owner, langganan 6 bln)');
        $this->command->info('  admin@terracoffee.id     / admin12345  (admin)');
        $this->command->info('  kasirpagi@terracoffee.id / kasir12345  (kasir - pagi)');
        $this->command->info('  kasirsore@terracoffee.id / kasir12345  (kasir - sore)');
    }
}
