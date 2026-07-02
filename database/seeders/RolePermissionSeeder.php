<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ================================================
        // 1. CREATE ALL PERMISSIONS
        // ================================================

        // --- Navigation / Page Access ---
        $navPermissions = [
            'view_dashboard',
            'view_kasir',
            'view_kitchen',
            'view_data_master',
            'view_report',
            'view_resources',
            'view_help',
            'view_billing',   // Langganan (untuk owner/admin tenant)
            'view_tenants',   // Manajemen tenant (khusus Superadmin)
        ];

        // --- Granular User Management ---
        $userPermissions = [
            'user.show',
            'user.create',
            'user.edit',
            'user.delete',
            'user.massdelete',
            'user.ban',
        ];

        // --- Granular Role Management ---
        $rolePermissions = [
            'role.show',
            'role.create',
            'role.edit',
            'role.delete',
            'role.massdelete',
        ];

        // --- Granular Data Master (menu, kategori, promo) ---
        $masterPermissions = [
            'category.show', 'category.create', 'category.edit', 'category.delete',
            'menu.show', 'menu.create', 'menu.edit', 'menu.delete',
            'promo.show', 'promo.create', 'promo.edit', 'promo.delete',
        ];

        // --- Granular Report ---
        $reportPermissions = [
            'report.sales',
            'report.items',
        ];

        // --- Kontrol Order / Penjualan (aksi sensitif, khusus owner) ---
        $orderPermissions = [
            'order.delete',   // hapus pesanan (berjalan/selesai)
            'sales.clear',    // reset/kosongkan penjualan hari ini
            'sales.target',   // set/ubah target penjualan hari ini
        ];

        // Create all permissions
        $allPermissions = array_merge(
            $navPermissions, $userPermissions, $rolePermissions,
            $masterPermissions, $reportPermissions, $orderPermissions
        );

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ================================================
        // 2. CREATE ROLES (if they don't exist)
        // ================================================
        $roleSuperadmin = Role::firstOrCreate(['name' => 'Superadmin']);
        $roleOwner      = Role::firstOrCreate(['name' => 'owner']);
        $roleAdmin      = Role::firstOrCreate(['name' => 'admin']);
        $roleKasir      = Role::firstOrCreate(['name' => 'kasir']);
        $roleKitchen    = Role::firstOrCreate(['name' => 'kitchen']);

        // ================================================
        // 3. ASSIGN PERMISSIONS TO ROLES
        // ================================================

        // SUPERADMIN — gets everything implicitly via Gate::before in AppServiceProvider
        // But we still assign explicitly for completeness
        $roleSuperadmin->syncPermissions(Permission::all());

        // OWNER — Pemilik tenant. Kontrol penuh DALAM tenant-nya (termasuk kelola staf & billing),
        // tetapi TIDAK punya 'view_tenants' (itu khusus Superadmin platform).
        $ownerPermissions = [
            'view_dashboard', 'view_kasir', 'view_kitchen',
            'view_data_master', 'view_report', 'view_help',
            'view_resources', 'view_billing',
            'user.show', 'user.create', 'user.edit', 'user.delete', 'user.massdelete', 'user.ban',
            // Catatan: role.* (kelola Hak Akses) TIDAK diberikan ke owner.
            // Role di sistem ini bersifat global lintas-tenant, jadi hanya Superadmin yang boleh mengelola.
            'category.show', 'category.create', 'category.edit', 'category.delete',
            'menu.show', 'menu.create', 'menu.edit', 'menu.delete',
            'promo.show', 'promo.create', 'promo.edit', 'promo.delete',
            'report.sales', 'report.items',
            // Aksi sensitif khusus owner (admin/kasir TIDAK diberi).
            'order.delete', 'sales.clear', 'sales.target',
        ];
        $roleOwner->syncPermissions($ownerPermissions);

        // ADMIN — All except Resources (User/Role Management) & Billing
        $adminPermissions = [
            'view_dashboard',
            'view_kasir',
            'view_kitchen',
            'view_data_master',
            'view_report',
            'view_help',
            'category.show', 'category.create', 'category.edit', 'category.delete',
            'menu.show', 'menu.create', 'menu.edit', 'menu.delete',
            'promo.show', 'promo.create', 'promo.edit', 'promo.delete',
            'report.sales', 'report.items',
        ];
        $roleAdmin->syncPermissions($adminPermissions);

        // KASIR — Dashboard, Kasir, Kitchen, Report
        $kasirPermissions = [
            'view_dashboard',
            'view_kasir',
            'view_kitchen',
            'view_report',
            'report.sales',
            'report.items',
        ];
        $roleKasir->syncPermissions($kasirPermissions);

        // KITCHEN — Dashboard, Kitchen only
        $kitchenPermissions = [
            'view_dashboard',
            'view_kitchen',
        ];
        $roleKitchen->syncPermissions($kitchenPermissions);
    }
}
