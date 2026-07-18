<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Key-value opsi situs GLOBAL (bukan per-tenant), dikelola Superadmin.
 * Mis. landing_partner_limit = jumlah logo partner ditampilkan di landing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_options', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_options');
    }
};
