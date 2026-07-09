<?php

namespace Database\Seeders;

use App\Models\DepositTopup;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DepositService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * 5 tenant plan DEPOSIT (owner saja), masing-masing deposit awal Rp5.000.
 * Login owner: owner@<slug tanpa strip>.id / owner12345
 */
class DepositTenantsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        /** @var DepositService $service */
        $service       = app(DepositService::class);
        $depositAmount = 5000;
        // Saldo awal akun demo = Rp5.000 (poin flat, tanpa bonus tier).
        $depositPoints = 5000;

        // Akun demo generik. Login owner: ownerN@demo.id / owner12345
        $tenants = [
            ['Tenant Demo 1', 'tenant-demo-1', 'owner1@demo.id', 'owner_demo_1', 'Cafe',       'Alamat Demo 1', '081200000001'],
            ['Tenant Demo 2', 'tenant-demo-2', 'owner2@demo.id', 'owner_demo_2', 'Restaurant', 'Alamat Demo 2', '081200000002'],
            ['Tenant Demo 3', 'tenant-demo-3', 'owner3@demo.id', 'owner_demo_3', 'Warung',     'Alamat Demo 3', '081200000003'],
            ['Tenant Demo 4', 'tenant-demo-4', 'owner4@demo.id', 'owner_demo_4', 'Cafe',       'Alamat Demo 4', '081200000004'],
            ['Tenant Demo 5', 'tenant-demo-5', 'owner5@demo.id', 'owner_demo_5', 'Restaurant', 'Alamat Demo 5', '081200000005'],
        ];

        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);

        foreach ($tenants as [$name, $slug, $email, $username, $type, $address, $phone]) {
            $flat = str_replace('-', '', $slug);

            $tenant = Tenant::firstOrCreate(
                ['slug' => $slug],
                [
                    'name'                 => $name,
                    'business_type'        => $type,
                    'phone'                => $phone,
                    'email'                => 'halo@' . $flat . '.id',
                    'address'              => $address,
                    'plan'                 => null,
                    'billing_mode'         => 'deposit',
                    'subscription_status'  => 'inactive',
                    'trial_ends_at'        => null,
                    'subscription_ends_at' => null,
                    'is_active'            => true,
                    'deposit_points'       => 0,
                ]
            );

            // Pastikan konsisten (bila tenant sudah ada dari run sebelumnya).
            $tenant->update([
                'billing_mode'        => 'deposit',
                'subscription_status' => 'inactive',
                'is_active'           => true,
            ]);

            // Owner (satu-satunya akun untuk tiap tenant sesuai permintaan).
            $owner = User::firstOrCreate(
                ['email' => $email],
                [
                    'tenant_id'         => $tenant->id,
                    'name'              => 'Owner ' . $name,
                    'username'          => $username,
                    'password'          => Hash::make('owner12345'),
                    'no_wa'             => $phone,
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ]
            );
            if (!$owner->tenant_id) {
                $owner->update(['tenant_id' => $tenant->id]);
            }
            if (!$owner->hasRole('owner')) {
                $owner->assignRole($ownerRole);
            }
            $tenant->update(['owner_id' => $owner->id]);

            // Deposit awal Rp5.000 -> poin (idempoten via order id unik).
            $orderId = 'DEP-SEED-' . strtoupper($flat);
            if (! DepositTopup::where('midtrans_order_id', $orderId)->exists()) {
                DepositTopup::create([
                    'tenant_id'         => $tenant->id,
                    'amount'            => $depositAmount,
                    'points'            => $depositPoints,
                    'status'            => 'paid',
                    'midtrans_order_id' => $orderId,
                    'payment_type'      => 'seeder',
                    'paid_at'           => now(),
                ]);

                $service->credit(
                    $tenant->fresh(),
                    $depositPoints,
                    $depositAmount,
                    $orderId,
                    $owner->id,
                    'Deposit awal Rp' . number_format($depositAmount, 0, ',', '.') . ' (seeder)'
                );
            }
        }

        $this->command->info('5 tenant deposit + owner siap. Login: owner1@demo.id ... owner5@demo.id / owner12345.');
        $this->command->info('Saldo poin awal tiap owner: Rp' . number_format($depositPoints, 0, ',', '.') . ' dari deposit Rp' . number_format($depositAmount, 0, ',', '.') . '.');
    }
}
