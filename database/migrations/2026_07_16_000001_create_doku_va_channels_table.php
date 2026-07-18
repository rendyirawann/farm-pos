<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Channel Virtual Account DOKU (SNAP) — dikelola Superadmin.
 * GLOBAL (bukan per-tenant): konfigurasi pembayaran tingkat platform.
 * Nilai partner_service_id = Merchant BIN penuh dari dashboard DOKU per bank.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doku_va_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // Nama tampil, mis. "Bank BRI"
            $table->string('channel');              // Konstanta DOKU, mis. VIRTUAL_ACCOUNT_BRI
            $table->string('partner_service_id');   // = Merchant BIN penuh (dikirim sbg partnerServiceId)
            $table->string('prefix_customer')->nullable(); // Info dari dashboard (Prefix Customer No)
            $table->string('environment')->default('production'); // sandbox | production
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['channel', 'environment']);
            $table->index(['environment', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doku_va_channels');
    }
};
