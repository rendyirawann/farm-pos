<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buku besar (ledger) mutasi poin — immutable, satu baris per mutasi.
     *   type: topup (kredit dari top-up), usage (potong transaksi),
     *         expiry (hangus), adjustment (koreksi Superadmin), refund.
     *   points: delta bertanda (+ menambah, - mengurangi).
     *   balance_after: saldo setelah mutasi (audit).
     *   cash_amount: Rupiah dibayar (khusus topup).
     */
    public function up(): void
    {
        Schema::create('deposit_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->uuid('user_id')->nullable();
            $table->enum('type', ['topup', 'usage', 'expiry', 'adjustment', 'refund']);
            $table->decimal('points', 15, 2);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->decimal('cash_amount', 15, 2)->nullable();
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_transactions');
    }
};
