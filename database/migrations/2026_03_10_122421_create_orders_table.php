<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_no')->unique();
            $table->integer('queue_number')->nullable(); // Nomor antrian harian per-tenant (untuk struk & panggil nama)
            $table->string('customer_name')->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0); // Pajak Resto 10%
            $table->decimal('grand_total', 15, 2)->default(0);

            // Pembayaran: hanya Tunai (cash) atau QRIS — tanpa payment gateway
            $table->string('payment_method')->nullable(); // 'cash' | 'qris'
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->decimal('cash_received', 15, 2)->nullable(); // Uang diterima (khusus tunai)
            $table->decimal('change_amount', 15, 2)->nullable(); // Kembalian (khusus tunai)

            // Status Pesanan (Untuk Kitchen Display)
            $table->enum('order_status', ['pending', 'cooking', 'served', 'completed'])->default('pending');
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->integer('qty');
            $table->decimal('price', 15, 2); // Harga satuan (menu + add-ons) saat transaksi
            $table->decimal('subtotal', 15, 2);
            $table->json('addons')->nullable(); // Snapshot add-ons terpilih: [{"name":"Extra Keju","price":5000}]
            $table->string('notes')->nullable(); // Contoh: "Jangan pakai daun bawang"
            $table->enum('status', ['pending', 'cooking', 'done'])->default('pending'); // Status per item masakan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
        Schema::dropIfExists('orders');
    }
};
