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
            'view_expense',   // Pengeluaran (owner/admin/kasir)
            'view_resources',
            'view_help',
            'view_billing',   // Langganan (untuk owner/admin tenant)
            'view_tenants',   // Manajemen tenant (khusus Superadmin)
            'blog.manage',    // Kelola blog marketing (khusus Superadmin, bukan per-tenant)
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
            'order.delete',   // hapus pesanan (khusus tab "Sedang Diproses" — OWNER)
            'order.void',     // tandai pesanan SALAH di tab "Selesai" (OWNER + KASIR) — keluar dari omzet & kas
            'sales.clear',    // reset/kosongkan penjualan hari ini
            'sales.target',   // set/ubah target penjualan hari ini
            'shift.operate',  // buka/tutup shift (KASIR — shift miliknya sendiri)
            'shift.reopen',   // buka kembali shift yg tak sengaja ditutup (OWNER/ADMIN — koreksi)
        ];

        // --- Affiliate (program referral) ---
        $affiliatePermissions = [
            'affiliate.manage', // kelola program afiliasi (khusus Superadmin, di admin mooda.id)
            'affiliate.portal', // akses portal afiliator (role 'affiliate' eksternal di affiliate.mooda.id)
            'affiliate.refer',  // owner tenant gabung + lihat dashboard afiliasi dari dalam POS
        ];

        // Create all permissions
        $allPermissions = array_merge(
            $navPermissions, $userPermissions, $rolePermissions,
            $masterPermissions, $reportPermissions, $orderPermissions,
            $affiliatePermissions
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
        $roleAffiliate  = Role::firstOrCreate(['name' => 'affiliate']); // afiliator eksternal (portal)

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
            'view_data_master', 'view_report', 'view_expense', 'view_help',
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
            // Tandai pesanan salah di tab Selesai (owner juga boleh).
            'order.void',
            // Gabung program affiliate + lihat dashboard referral dari dalam POS.
            'affiliate.refer',
            // Owner LIHAT-SAJA shift (tak buka/tutup), tapi boleh membuka kembali shift kasir
            // yang tak sengaja ditutup (koreksi supervisor).
            'shift.reopen',
        ];
        $roleOwner->syncPermissions($ownerPermissions);

        // ADMIN — All except Resources (User/Role Management) & Billing
        $adminPermissions = [
            'view_dashboard',
            'view_kasir',
            'view_kitchen',
            'view_data_master',
            'view_report',
            'view_expense',
            'view_help',
            'category.show', 'category.create', 'category.edit', 'category.delete',
            'menu.show', 'menu.create', 'menu.edit', 'menu.delete',
            'promo.show', 'promo.create', 'promo.edit', 'promo.delete',
            'report.sales', 'report.items',
            // Admin LIHAT-SAJA shift (tak buka/tutup), tapi boleh membuka kembali shift kasir.
            'shift.reopen',
        ];
        $roleAdmin->syncPermissions($adminPermissions);

        // KASIR — Dashboard, Kasir, Kitchen, Report
        $kasirPermissions = [
            'view_dashboard',
            'view_kasir',
            'view_kitchen',
            'view_report',
            'view_expense',
            'report.sales',
            'report.items',
            // Kasir MENGOPERASIKAN shift-nya sendiri (buka/tutup). TIDAK boleh reopen.
            'shift.operate',
            // Kasir boleh menandai pesanan SALAH di tab Selesai (bukan hapus).
            'order.void',
        ];
        $roleKasir->syncPermissions($kasirPermissions);

        // KITCHEN — Dashboard, Kitchen only
        $kitchenPermissions = [
            'view_dashboard',
            'view_kitchen',
        ];
        $roleKitchen->syncPermissions($kitchenPermissions);

        // AFFILIATE — afiliator eksternal (portal affiliate.mooda.id). Hanya akses portal sendiri.
        $roleAffiliate->syncPermissions(['affiliate.portal']);
    }
}
