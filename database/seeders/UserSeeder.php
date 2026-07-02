<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Tenant;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $tenant = Tenant::where('slug', 'demo-resto')->first();
        if (!$tenant) {
            $this->command->warn('Tenant demo belum ada. Jalankan TenantSeeder dulu.');
            return;
        }

        // 1. Backfill user lama (selain Superadmin) -> masuk ke tenant demo
        $superadminIds = User::role('Superadmin')->pluck('id')->all();
        User::whereNull('tenant_id')
            ->whereNotIn('id', $superadminIds)
            ->update(['tenant_id' => $tenant->id]);

        // 2. Akun testing untuk tenant demo
        $accounts = [
            ['owner',   'Owner Demo',   'owner@demo.test',   'owner',   'owner12345'],
            ['admin',   'Admin Demo',   'admin@demo.test',   'admin',   'admin12345'],
            ['kasir',   'Kasir Demo',   'kasir@demo.test',   'kasir',   'kasir12345'],
            ['kitchen', 'Kitchen Demo', 'kitchen@demo.test', 'kitchen', 'kitchen12345'],
        ];

        $ownerUser = null;

        foreach ($accounts as [$username, $name, $email, $roleName, $password]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'tenant_id'         => $tenant->id,
                    'name'              => $name,
                    'username'          => $username . '_demo',
                    'password'          => Hash::make($password),
                    'no_wa'             => '08120000000',
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ]
            );

            // pastikan tenant_id terpasang walau user sudah ada
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

        // 3. Set owner tenant demo
        if ($ownerUser) {
            $tenant->update(['owner_id' => $ownerUser->id]);
        }

        $this->command->info('Akun testing tenant demo dibuat:');
        $this->command->info('  owner@demo.test   / owner12345');
        $this->command->info('  admin@demo.test   / admin12345');
        $this->command->info('  kasir@demo.test   / kasir12345');
        $this->command->info('  kitchen@demo.test / kitchen12345');
    }
}
