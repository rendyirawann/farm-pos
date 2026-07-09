<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Kategori bisnis: 'resto' | 'cafe' | 'umkm'. NULL = normal (perilaku shift seperti biasa).
            // UMKM = tanpa shift per-sesi; pakai "Kas Harian" (1x/hari).
            $table->string('category', 20)->nullable()->after('business_type');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
