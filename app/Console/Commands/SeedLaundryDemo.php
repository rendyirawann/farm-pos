<?php

namespace App\Console\Commands;

use App\Models\Laundry\LaundryCustomer;
use App\Models\Laundry\LaundryService;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Buat tenant DEMO laundry + user owner & kasir + layanan & pelanggan contoh.
 * Idempoten: aman dijalankan berulang (memakai firstOrCreate/updateOrCreate).
 *
 * Pakai: php artisan mooda:seed-laundry-demo
 */
class SeedLaundryDemo extends Command
{
    protected $signature = 'mooda:seed-laundry-demo';
    protected $description = 'Seed tenant demo LAUNDRY (owner + kasir + layanan + pelanggan contoh)';

    public function handle(): int
    {
        $tenant = DB::transaction(function () {
            /** @var Tenant $tenant */
            $tenant = Tenant::firstOrCreate(
                ['slug' => 'demo-laundry'],
                [
                    'name'                 => 'Demo Laundry Mooda',
                    'business_type'        => 'Laundry Kiloan',
                    'category'             => 'resto',       // sistem kas: pakai Shift kasir
                    'vertical'             => 'laundry',
                    'phone'                => '08123456780',
                    'email'                => 'demo@laundry.mooda.test',
                    'address'              => 'Jl. Contoh Laundry No. 1',
                    // Paket Bisnis + langganan panjang supaya semua modul laundry terbuka.
                    'plan'                 => 'bisnis',
                    'subscription_status'  => 'active',
                    'subscription_ends_at' => now()->addYears(50),
                    'billing_mode'         => 'monthly',
                    'is_active'            => true,
                    'created_via'          => 'demo',
                ]
            );

            // Pastikan atribut penting tetap benar bila tenant sudah ada sebelumnya.
            $tenant->update([
                'vertical'             => 'laundry',
                'plan'                 => 'bisnis',
                'subscription_status'  => 'active',
                'subscription_ends_at' => now()->addYears(50),
                'is_active'            => true,
            ]);

            // --- User OWNER & KASIR ---
            $accounts = [
                ['owner', 'Owner Demo Laundry', 'owner@demolaundry.test', 'owner12345'],
                ['kasir', 'Kasir Demo Laundry', 'kasir@demolaundry.test', 'kasir12345'],
            ];

            foreach ($accounts as [$role, $name, $email, $password]) {
                $user = User::withoutGlobalScopes()->where('email', $email)->first();
                if (! $user) {
                    $user = new User();
                    $user->email = $email;
                }
                $user->fill([
                    'tenant_id'         => $tenant->id,
                    'name'              => $name,
                    'username'          => str_replace(['@', '.'], ['_', '_'], $email),
                    'no_wa'             => $role === 'owner' ? '08123456781' : '08123456782',
                    'phone'             => $role === 'owner' ? '08123456781' : '08123456782',
                    'password'          => Hash::make($password),
                    'is_active'         => true,
                    'email_verified_at' => now(),   // demo: langsung aktif
                ]);
                $user->save();
                $user->syncRoles([$role]);

                if ($role === 'owner' && ! $tenant->owner_id) {
                    $tenant->update(['owner_id' => $user->id]);
                }
            }

            // --- Layanan contoh ---
            $services = [
                ['Cuci Kering Kiloan',   'kiloan', 'kg',    7000,  48, 1],
                ['Cuci Setrika Kiloan',  'kiloan', 'kg',    9000,  48, 2],
                ['Setrika Saja',         'kiloan', 'kg',    6000,  24, 3],
                ['Express 6 Jam',        'express','kg',    18000,  6, 4],
                ['Bed Cover',            'satuan', 'pcs',   25000, 72, 5],
                ['Jas / Blazer',         'satuan', 'pcs',   35000, 72, 6],
                ['Sepatu',               'satuan', 'pasang',30000, 72, 7],
            ];
            foreach ($services as [$name, $cat, $unit, $price, $hours, $sort]) {
                LaundryService::withoutGlobalScopes()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => $name],
                    [
                        'category'                 => $cat,
                        'unit'                     => $unit,
                        'price_per_unit'           => $price,
                        'estimated_duration_hours' => $hours,
                        'is_active'                => true,
                        'sort_order'               => $sort,
                    ]
                );
            }

            // --- Pelanggan contoh (satu VIP untuk uji diskon member) ---
            $customers = [
                ['Budi Santoso', '081200000001', 'reguler'],
                ['Siti Aminah',  '081200000002', 'vip'],
            ];
            foreach ($customers as [$name, $phone, $status]) {
                LaundryCustomer::withoutGlobalScopes()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'phone' => $phone],
                    ['name' => $name, 'member_status' => $status, 'loyalty_points' => $status === 'vip' ? 120 : 0]
                );
            }

            return $tenant;
        });

        $this->newLine();
        $this->info('Tenant demo laundry siap: ' . $tenant->name . ' (vertical=laundry, plan=bisnis)');
        $this->table(
            ['Peran', 'Email', 'Password', 'Login di'],
            [
                ['Owner', 'owner@demolaundry.test', 'owner12345', 'https://laundry.mooda.id/admin/login'],
                ['Kasir', 'kasir@demolaundry.test', 'kasir12345', 'https://laundry.mooda.id/admin/login'],
            ]
        );
        $this->line('Layanan contoh: ' . LaundryService::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count()
            . ' · Pelanggan contoh: ' . LaundryCustomer::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());

        return self::SUCCESS;
    }
}
