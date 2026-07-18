<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferensi per-user: tampilkan / sembunyikan display pilihan meja di layar Kasir.
 * Di-toggle real-time via AJAX (KasirController::toggleTables).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('kasir_show_tables')->default(true)->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kasir_show_tables');
        });
    }
};
