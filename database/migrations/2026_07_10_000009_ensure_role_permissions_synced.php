<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Pastikan seluruh permission (termasuk 'view_expense') ada & ter-assign ke role.
     * Diperlukan pada environment yang kode-nya sudah ter-deploy tetapi
     * RolePermissionSeeder belum dijalankan ulang -> menu "Pengeluaran" (dan aksi
     * order.delete / sales.* pada owner) tidak muncul.
     *
     * ADITIF: givePermissionTo = sync tanpa detach -> hanya MENAMBAH yang kurang,
     * tidak pernah menghapus permission role apa pun (aman untuk kustomisasi manual).
     * Idempoten. Mengikuti daftar yang sama dengan RolePermissionSeeder.
     */
    public function up(): void
    {
        $registrar = app()[PermissionRegistrar::class];
        $registrar->forgetCachedPermissions();
        $guard = config('auth.defaults.guard', 'web');

        $all = [
            'view_dashboard', 'view_kasir', 'view_kitchen', 'view_data_master',
            'view_report', 'view_expense', 'view_resources', 'view_help',
            'view_billing', 'view_tenants',
            'user.show', 'user.create', 'user.edit', 'user.delete', 'user.massdelete', 'user.ban',
            'role.show', 'role.create', 'role.edit', 'role.delete', 'role.massdelete',
            'category.show', 'category.create', 'category.edit', 'category.delete',
            'menu.show', 'menu.create', 'menu.edit', 'menu.delete',
            'promo.show', 'promo.create', 'promo.edit', 'promo.delete',
            'report.sales', 'report.items',
            'order.delete', 'sales.clear', 'sales.target',
        ];
        foreach ($all as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => $guard]);
        }

        // Kosongkan cache lagi agar grant di bawah "melihat" permission yang baru dibuat.
        $registrar->forgetCachedPermissions();

        $grant = function (string $roleName, $perms) use ($guard) {
            $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();
            if ($role) {
                $role->givePermissionTo($perms);
            }
        };

        // Superadmin: semua (juga otomatis via Gate::before, assign eksplisit utk kelengkapan).
        if ($sa = Role::where('name', 'Superadmin')->where('guard_name', $guard)->first()) {
            $sa->givePermissionTo(Permission::where('guard_name', $guard)->get());
        }

        $grant('owner', [
            'view_dashboard', 'view_kasir', 'view_kitchen',
            'view_data_master', 'view_report', 'view_expense', 'view_help',
            'view_resources', 'view_billing',
            'user.show', 'user.create', 'user.edit', 'user.delete', 'user.massdelete', 'user.ban',
            'category.show', 'category.create', 'category.edit', 'category.delete',
            'menu.show', 'menu.create', 'menu.edit', 'menu.delete',
            'promo.show', 'promo.create', 'promo.edit', 'promo.delete',
            'report.sales', 'report.items',
            'order.delete', 'sales.clear', 'sales.target',
        ]);

        $grant('admin', [
            'view_dashboard', 'view_kasir', 'view_kitchen',
            'view_data_master', 'view_report', 'view_expense', 'view_help',
            'category.show', 'category.create', 'category.edit', 'category.delete',
            'menu.show', 'menu.create', 'menu.edit', 'menu.delete',
            'promo.show', 'promo.create', 'promo.edit', 'promo.delete',
            'report.sales', 'report.items',
        ]);

        $grant('kasir', [
            'view_dashboard', 'view_kasir', 'view_kitchen', 'view_report',
            'view_expense', 'report.sales', 'report.items',
        ]);

        $grant('kitchen', [
            'view_dashboard', 'view_kitchen',
        ]);

        $registrar->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Aditif; tidak mencabut permission apa pun.
    }
};
