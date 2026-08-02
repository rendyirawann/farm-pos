<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MOODA FARM — inventori & perdagangan ternak.
 *
 * Semua tabel diawali `farm_` karena nama generik seperti `suppliers` dan
 * `stock_movements` sudah dipakai modul HPP F&B di database yang sama.
 *
 * Prinsip kunci: setiap transaksi menyimpan EKOR dan KG sekaligus. Menyimpan satu
 * satuan saja membuat susut bobot tidak terlihat (100 ekor/200kg masuk,
 * 100 ekor/195kg keluar -> 5 kg hilang tanpa jejak).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- MASTER ----------
        Schema::create('farm_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('farm_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            // Batas piutang; 0 = tanpa batas.
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('term_days')->default(0);   // tempo baku (hari)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('farm_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            // ayam_potong | ayam_petelur | telur
            $table->string('category', 30)->index();
            $table->string('name');
            // Satuan yang ditampilkan lebih dulu: kg | ekor | butir
            $table->string('primary_unit', 10)->default('kg');
            // Telur tidak dibeli dari supplier -> HPP dari biaya operasional, bukan lot.
            $table->boolean('is_produced')->default(false);
            $table->decimal('min_stock_kg', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ---------- STOCK IN (pembelian) ----------
        Schema::create('farm_stock_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('invoice_no', 40)->unique();
            $table->date('date')->index();
            $table->foreignId('supplier_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('farm_stock_in_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('stock_in_id')->index();
            $table->foreignId('item_id');
            $table->integer('qty_ekor')->default(0);
            $table->decimal('weight_kg', 12, 2)->default(0);
            // Dasar harga yang dipakai saat transaksi: kg | ekor
            $table->string('price_basis', 10)->default('kg');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();
        });

        // ---------- LOT (dasar FIFO) ----------
        Schema::create('farm_stock_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('item_id')->index();
            $table->foreignId('stock_in_line_id')->nullable();
            $table->foreignId('supplier_id')->nullable();
            $table->date('date')->index();
            $table->integer('qty_ekor_initial')->default(0);
            $table->decimal('weight_kg_initial', 12, 2)->default(0);
            $table->integer('qty_ekor_left')->default(0);
            $table->decimal('weight_kg_left', 12, 2)->default(0);
            // Dua-duanya disimpan supaya HPP bisa dihitung baik per kg maupun per ekor.
            $table->decimal('cost_per_kg', 15, 2)->default(0);
            $table->decimal('cost_per_ekor', 15, 2)->default(0);
            $table->timestamps();

            // Urutan FIFO: tanggal terlama dulu, lalu id.
            $table->index(['tenant_id', 'item_id', 'date', 'id'], 'farm_lot_fifo_idx');
        });

        // ---------- STOCK OUT (penjualan ke agen) ----------
        Schema::create('farm_stock_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('invoice_no', 40)->unique();
            $table->date('date')->index();
            $table->foreignId('agent_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->decimal('total_sale', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);   // HPP dari lot terpakai
            $table->decimal('gross_profit', 15, 2)->default(0);
            // unpaid | paid
            $table->string('payment_status', 12)->default('unpaid')->index();
            $table->date('due_date')->nullable()->index();
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('farm_stock_out_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('stock_out_id')->index();
            $table->foreignId('item_id');
            $table->integer('qty_ekor')->default(0);
            $table->decimal('weight_kg', 12, 2)->default(0);
            $table->string('price_basis', 10)->default('kg');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0);          // HPP baris ini
            $table->decimal('profit', 15, 2)->default(0);
            $table->timestamps();
        });

        // Jejak lot mana yang terpakai — supaya HPP bisa ditelusuri, bukan angka rata-rata.
        Schema::create('farm_stock_out_lot_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('stock_out_line_id')->index();
            $table->foreignId('lot_id')->index();
            $table->integer('qty_ekor')->default(0);
            $table->decimal('weight_kg', 12, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->timestamps();
        });

        // ---------- PRODUKSI TELUR ----------
        Schema::create('farm_egg_productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->date('date')->index();
            $table->string('coop', 50)->nullable();               // kandang
            $table->foreignId('item_id')->nullable();             // item telur
            $table->integer('qty_butir')->default(0);
            $table->integer('qty_broken')->default(0);            // telur pecah
            $table->uuid('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ---------- PENYESUAIAN STOK ----------
        Schema::create('farm_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('ref_no', 40)->unique();
            $table->date('date')->index();
            $table->foreignId('item_id')->index();
            $table->foreignId('lot_id')->nullable();
            // mati | susut | rusak | koreksi_opname | koreksi_tambah
            $table->string('reason', 30)->index();
            // Negatif = stok berkurang, positif = bertambah (koreksi).
            $table->integer('qty_ekor')->default(0);
            $table->decimal('weight_kg', 12, 2)->default(0);
            $table->decimal('cost_impact', 15, 2)->default(0);    // nilai kerugian
            $table->uuid('user_id')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ---------- PEMBAYARAN PIUTANG AGEN ----------
        Schema::create('farm_agent_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('agent_id')->index();
            $table->foreignId('stock_out_id')->nullable()->index();
            $table->date('date')->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('method', 20)->default('cash');        // cash | transfer
            $table->uuid('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ---------- BUKA / TUTUP GUDANG ----------
        Schema::create('farm_warehouse_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->uuid('opened_by')->nullable();
            $table->uuid('closed_by')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            // Snapshot stok sistem saat buka & tutup + hasil hitung fisik.
            $table->json('opening_stock')->nullable();
            $table->json('closing_stock')->nullable();
            $table->json('physical_stock')->nullable();
            $table->json('difference')->nullable();
            $table->string('status', 12)->default('open')->index();  // open | closed
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'farm_warehouse_sessions', 'farm_agent_payments', 'farm_stock_adjustments',
            'farm_egg_productions', 'farm_stock_out_lot_usages', 'farm_stock_out_lines',
            'farm_stock_outs', 'farm_stock_lots', 'farm_stock_in_lines', 'farm_stock_ins',
            'farm_items', 'farm_agents', 'farm_suppliers',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
