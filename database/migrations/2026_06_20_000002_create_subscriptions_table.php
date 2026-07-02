<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->enum('plan', ['starter', 'customize']);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('billing_period')->default('monthly');

            // pending -> menunggu pembayaran, paid -> lunas/aktif, failed, expired, cancelled
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'cancelled'])->default('pending');

            // Midtrans
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->text('snap_token')->nullable();
            $table->string('payment_type')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
