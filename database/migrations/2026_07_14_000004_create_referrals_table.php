<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pemakaian kode referral: satu baris per tenant yang mendaftar lewat kode afiliator.
 * Komisi ONE-TIME dihitung saat tenant referral pertama kali berlangganan (subscribed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('tenant_name')->nullable();               // snapshot nama tenant
            $table->string('status')->default('signup');             // signup | subscribed
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->string('commission_status')->default('pending'); // pending | approved | paid
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('paid_at')->nullable();                // waktu payout ke afiliator
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
