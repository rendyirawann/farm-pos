<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MODUL HPP · INVENTORY (FIFO/FEFO) · RESEP — untuk vertical F&B.
 * Mengacu dokumen "HPP-Inventory-FIFO-Analisa-Implementasi.pdf" (Tahap 1).
 *
 * Sifat: SEPENUHNYA ADITIF. Tabel baru + 2 kolom baru di order_details dengan default aman
 * (hpp=0, is_stock_deducted=false) sehingga alur F&B yang sudah berjalan tidak berubah.
 * Semua tabel tenant-scoped (ikut konvensi BelongsToTenant).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== Supplier (pemasok bahan) =====
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'name']);
        });

        // ===== Bahan baku (master) — 1 satuan per bahan, tanpa konversi (v1) =====
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('unit', 20)->default('gram');   // gram / ml / pcs
            $table->decimal('minimum_stock', 15, 2)->default(0); // ambang "stok menipis"
            $table->timestamps();
            $table->index(['tenant_id', 'name']);
        });

        // ===== Lot/batch stok — JANTUNG FIFO/FEFO. Stok = SUM(remaining_quantity) =====
        Schema::create('ingredient_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('initial_quantity', 15, 2)->default(0);
            $table->decimal('remaining_quantity', 15, 2)->default(0); // dikuras saat produksi
            $table->decimal('buy_price', 15, 2)->default(0);          // harga per satuan = total / qty
            $table->decimal('buy_price_total', 15, 2)->default(0);
            $table->date('entry_date')->nullable();                   // tie-break FIFO
            $table->date('expiry_date')->nullable();                  // kunci FEFO
            $table->timestamps();
            // Urutan pengurasan: expiry (FEFO) lalu entry_date (FIFO).
            $table->index(['tenant_id', 'ingredient_id', 'expiry_date', 'entry_date'], 'ing_batch_fefo_idx');
        });

        // ===== Resep: bahan + gramasi per 1 porsi menu =====
        Schema::create('menu_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['menu_id', 'ingredient_id']);
        });

        // ===== Kartu stok / ledger semua gerakan (in/out) + COGS gerakan =====
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignId('ingredient_batch_id')->nullable()->constrained('ingredient_batches')->nullOnDelete();
            $table->foreignId('order_detail_id')->nullable()->constrained('order_details')->nullOnDelete();
            $table->string('type', 10);            // in | out
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('cost_total', 15, 2)->default(0); // qty x buy_price lot
            $table->string('reason', 40)->nullable();         // purchase|sales_deduction|stock_opname|adjustment|waste
            $table->string('reference')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'ingredient_id', 'created_at']);
            $table->index('order_detail_id');
        });

        // ===== Stok opname (sistem vs fisik) =====
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->uuid('user_id')->nullable();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_opname_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('system_qty', 15, 2)->default(0);
            $table->decimal('physical_qty', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->default(0);
            $table->timestamps();
        });

        // ===== order_details: snapshot HPP + penjaga idempoten potong stok =====
        Schema::table('order_details', function (Blueprint $table) {
            $table->decimal('hpp', 15, 2)->default(0)->after('subtotal');
            $table->boolean('is_stock_deducted')->default(false)->after('hpp');
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['hpp', 'is_stock_deducted']);
        });
        Schema::dropIfExists('stock_opname_details');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('menu_ingredients');
        Schema::dropIfExists('ingredient_batches');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('suppliers');
    }
};
