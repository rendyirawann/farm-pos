<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul AFFILIATE (program referral Mooda — GLOBAL, bukan per-tenant).
 * Afiliator bisa: 'external' (bukan pengguna POS → punya portal sendiri di
 * affiliate.mooda.id) atau 'tenant' (pelanggan POS, ditautkan ke tenant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();                 // kode referral unik
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('type')->default('external');      // external | tenant
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->uuid('user_id')->nullable();              // user login portal (external) / owner tenant
            $table->string('status')->default('pending');     // pending | active | suspended
            $table->text('payout_info')->nullable();          // info rekening/e-wallet utk payout
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
