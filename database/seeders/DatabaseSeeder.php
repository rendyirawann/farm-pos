<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuAddon;

class DatabaseSeeder extends Seeder
{
    // Catatan: JANGAN pakai WithoutModelEvents — model User/Menu memakai HasUuids yang
    // meng-generate UUID lewat event 'creating'. Muting event membuat UUID null → insert gagal.

    public function run(): void
    {
        // 1. Role, permission & Superadmin (Superadmin tetap tanpa tenant)
        $this->call([
            RolePermissionSeeder::class,
            SuperAdminSeeder::class,
        ]);

        // 2. Sample data master (hanya jika DB masih kosong, agar tidak menduplikasi data lama).
        //    tenant_id dibiarkan NULL di sini, lalu di-backfill oleh TenantSeeder ke tenant demo.
        if (Menu::count() === 0) {
            $catFood = Category::create(['name' => 'Main Course', 'slug' => 'main-course']);
            $catBeverage = Category::create(['name' => 'Beverages', 'slug' => 'beverages']);

            $nasgor = Menu::create([
                'category_id' => $catFood->id,
                'name' => 'Nasi Goreng Spesial',
                'description' => 'Nasi goreng dengan telur, ayam, dan kerupuk',
                'price' => 25000,
                'is_available' => true,
            ]);

            Menu::create([
                'category_id' => $catBeverage->id,
                'name' => 'Ice Americano',
                'description' => 'Kopi hitam dingin tanpa gula',
                'price' => 18000,
                'is_available' => true,
            ]);

            // Contoh add-ons untuk menu
            MenuAddon::create(['menu_id' => $nasgor->id, 'name' => 'Extra Telur',   'price' => 5000]);
            MenuAddon::create(['menu_id' => $nasgor->id, 'name' => 'Extra Kerupuk', 'price' => 3000]);
        }

        // 3. Tenant demo + backfill data lama, lalu akun testing per-tenant
        $this->call([
            TenantSeeder::class,
            UserSeeder::class,
        ]);

        // 4. Tenant "Terra Coffee" + akun (owner langganan 6 bln, admin, kasir pagi & sore)
        $this->call([
            TerraCoffeeSeeder::class,
        ]);

        // 5. Plan deposit: setelan/tier default + 5 tenant deposit (owner saja, deposit Rp5.000)
        $this->call([
            DepositTierSeeder::class,
            DepositTenantsSeeder::class,
        ]);
    }
}
