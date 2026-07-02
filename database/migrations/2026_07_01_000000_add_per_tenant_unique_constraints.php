<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menegakkan keunikan PER-TENANT (bukan global) agar tidak ada konflik antar-tenant:
 * - settings: 1 baris pengaturan per tenant.
 * - daily_sales_targets: 1 target per (tenant, tanggal).
 *
 * Dijalankan setelah kolom tenant_id ditambahkan (add_tenant_id_to_tables).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unique('tenant_id', 'settings_tenant_id_unique');
        });

        Schema::table('daily_sales_targets', function (Blueprint $table) {
            $table->unique(['tenant_id', 'date'], 'daily_sales_targets_tenant_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_tenant_id_unique');
        });

        Schema::table('daily_sales_targets', function (Blueprint $table) {
            $table->dropUnique('daily_sales_targets_tenant_date_unique');
        });
    }
};
