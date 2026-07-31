<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Laundry (vertical 'laundry'). Semua tabel tenant-scoped (tenant_id uuid).
 * Terpisah dari tabel F&B (menus/orders) supaya tidak saling mengganggu.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Layanan cuci (kiloan/satuan/express). Express = baris layanan tersendiri (harga lebih tinggi).
        Schema::create('laundry_services', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->index();
            $table->string('category')->nullable();               // mis. Cuci, Setrika, Express, Dry Clean
            $table->string('name');
            $table->string('unit', 10)->default('kg');            // kg | pcs | meter | pasang
            $table->decimal('price_per_unit', 15, 2)->default(0);
            $table->unsignedInteger('estimated_duration_hours')->default(48);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'is_active']);
        });

        // Pelanggan laundry.
        Schema::create('laundry_customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('member_status', 10)->default('regular'); // regular | vip
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'phone']);
        });

        // Nota / pesanan laundry.
        Schema::create('laundry_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->index();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('invoice_no')->unique();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->uuid('staff_id')->nullable()->index();          // user pembuat (kasir)
            $table->string('order_type', 20)->default('self_pickup'); // self_pickup | delivery
            $table->text('delivery_address')->nullable();
            $table->decimal('delivery_fee', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('payment_method', 20)->nullable();       // cash | tripay | ...
            $table->string('payment_status', 12)->default('unpaid'); // unpaid | paid | failed
            $table->decimal('dp_amount', 15, 2)->nullable();
            $table->decimal('cash_received', 15, 2)->nullable();
            $table->decimal('cash_change', 15, 2)->nullable();
            $table->string('order_status', 20)->default('diterima'); // pipeline laundry
            $table->text('special_instructions')->nullable();
            $table->timestamp('estimated_completed_at')->nullable();
            $table->timestamp('actual_completed_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'order_status']);
            $table->index(['tenant_id', 'created_at']);
        });

        // Item / potongan cucian per nota.
        Schema::create('laundry_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('service_name');                 // snapshot
            $table->string('unit', 10)->default('kg');      // snapshot
            $table->decimal('qty', 8, 2)->default(1);
            $table->decimal('price', 15, 2)->default(0);    // snapshot harga/unit
            $table->decimal('subtotal', 15, 2)->default(0); // qty * price
            $table->string('notes')->nullable();
            $table->text('item_condition')->nullable();     // diagnosis noda/kerusakan
            $table->string('status', 10)->default('entry'); // entry | process | done
            $table->timestamps();
        });

        // Riwayat status (audit trail alur cucian).
        Schema::create('laundry_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('status', 20)->index();
            $table->uuid('changed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laundry_status_logs');
        Schema::dropIfExists('laundry_order_items');
        Schema::dropIfExists('laundry_orders');
        Schema::dropIfExists('laundry_customers');
        Schema::dropIfExists('laundry_services');
    }
};
