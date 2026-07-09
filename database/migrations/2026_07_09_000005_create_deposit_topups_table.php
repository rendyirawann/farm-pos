<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan pembelian top-up (alur Midtrans), mirip tabel subscriptions.
     *   amount = Rupiah dibayar, points = poin diterima (termasuk bonus).
     *   Poin baru dikreditkan saat status menjadi 'paid' (via webhook).
     */
    public function up(): void
    {
        Schema::create('deposit_topups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'cancelled'])->default('pending');
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->text('snap_token')->nullable();
            $table->string('payment_type')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_topups');
    }
};
