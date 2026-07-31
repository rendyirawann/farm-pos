<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fondasi multi-vertical (F&B / Laundry / Retail).
 * Kolom penanda industri di tenant + paket. Default 'fnb' -> perilaku existing TIDAK berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'vertical')) {
                $table->string('vertical', 20)->default('fnb')->after('category')->index();
            }
        });

        foreach (['plan_settings', 'plan_promos'] as $t) {
            if (Schema::hasTable($t) && ! Schema::hasColumn($t, 'vertical')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->string('vertical', 20)->default('fnb')->index();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'vertical')) {
                $table->dropColumn('vertical');
            }
        });
        foreach (['plan_settings', 'plan_promos'] as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'vertical')) {
                Schema::table($t, fn (Blueprint $table) => $table->dropColumn('vertical'));
            }
        }
    }
};
