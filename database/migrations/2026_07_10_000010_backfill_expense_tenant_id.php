<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Selamatkan expense "yatim" (tenant_id NULL): tetapkan ke tenant pencatatnya
     * (dari users.tenant_id via user_id). Ini terjadi bila pengeluaran direkam sambil
     * bertindak sebagai Superadmin -> IdentifyTenant melewati Superadmin, TenantManager
     * null, sehingga tenant_id tidak terisi dan baris jadi tak tampil di daftar tenant.
     *
     * Idempoten: hanya menyentuh baris tenant_id NULL yang user-nya punya tenant_id.
     */
    public function up(): void
    {
        if (! Schema::hasTable('expenses') || ! Schema::hasTable('users')) {
            return;
        }

        DB::statement("
            UPDATE expenses e
            SET tenant_id = u.tenant_id
            FROM users u
            WHERE e.user_id = u.id
              AND e.tenant_id IS NULL
              AND u.tenant_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        // Tidak dikembalikan; pemulihan tenant_id bersifat perbaikan data.
    }
};
