<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat tenant demo untuk testing & menampung data lama
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo-resto'],
            [
                'name'                 => 'Demo Resto Mooda',
                'business_type'        => 'Restoran',
                'phone'                => '08123456789',
                'email'                => 'demo@mooda.test',
                'address'              => 'Jl. Contoh No. 1, Indonesia',
                'plan'                 => 'starter',
                'subscription_status'  => 'active',
                'trial_ends_at'        => now()->addDays(14),
                'subscription_ends_at' => now()->addYear(),
                'is_active'            => true,
            ]
        );

        // 2. Backfill: pindahkan semua data lama (tenant_id NULL) ke tenant demo.
        //    'users' ditangani di UserSeeder (Superadmin harus tetap tanpa tenant).
        $tables = [
            'categories', 'menus', 'menu_addons', 'orders', 'order_details',
            'promos', 'shifts', 'settings', 'daily_sales_targets',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasColumn($table, 'tenant_id')) {
                DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);
            }
        }

        $this->command->info("Tenant demo siap (slug: demo-resto, id: {$tenant->id}). Data lama dipindahkan ke tenant ini.");
    }
}
