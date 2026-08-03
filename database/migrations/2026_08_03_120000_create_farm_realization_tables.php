<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REALISASI barang masuk + PIUTANG SUPPLIER.
 *
 * Beda tegas dengan Penyesuaian Stok:
 * - Penyesuaian  : kerugian KITA di gudang (mati/susut setelah barang diterima).
 *                  Tidak ada sangkut paut dengan supplier.
 * - Realisasi    : barang dari supplier ternyata KURANG saat ditimbang ulang.
 *                  Kekurangannya menjadi PIUTANG SUPPLIER — supplier berutang
 *                  kepada kita, dan bisa ditutup oleh pembelian berikutnya.
 *
 * Keduanya sama-sama menyesuaikan stok ke angka nyata.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- Status pembayaran KITA ke supplier ----------
        Schema::table('farm_stock_ins', function (Blueprint $table) {
            // unpaid | paid — apakah nota pembelian ini sudah kita bayar ke supplier.
            $table->string('payment_status', 12)->default('unpaid')->index()->after('total');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('payment_status');
            $table->date('paid_at')->nullable()->after('paid_amount');
            // Bagian nota yang ditutup memakai piutang supplier (bukan uang tunai).
            $table->decimal('credit_applied', 15, 2)->default(0)->after('paid_at');
        });

        // ---------- Realisasi: selisih antara klaim supplier dan hasil timbang ----------
        Schema::create('farm_stock_in_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('stock_in_id')->index();
            $table->foreignId('stock_in_line_id')->nullable()->index();
            $table->foreignId('supplier_id')->nullable()->index();
            $table->date('date')->index();
            // mati | susut | kurang_timbang | lainnya
            $table->string('reason', 30)->default('kurang_timbang');
            // Kekurangan dibanding yang tercatat saat barang masuk.
            $table->integer('qty_ekor_short')->default(0);
            $table->decimal('weight_kg_short', 12, 2)->default(0);
            // Nilai kekurangan = selisih x harga satuan nota.
            $table->decimal('value', 15, 2)->default(0);
            // Berapa dari nilai itu yang sudah ditutup pembelian berikutnya.
            $table->decimal('settled_amount', 15, 2)->default(0);
            $table->string('status', 12)->default('open')->index();   // open | settled
            $table->uuid('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ---------- Alokasi: pembelian baru menutup piutang supplier ----------
        Schema::create('farm_supplier_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('supplier_id')->index();
            // Realisasi mana yang ditutup, dan nota pembelian mana yang menutupinya.
            $table->foreignId('realization_id')->index();
            $table->foreignId('stock_in_id')->nullable()->index();
            $table->date('date')->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->uuid('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_supplier_settlements');
        Schema::dropIfExists('farm_stock_in_realizations');
        Schema::table('farm_stock_ins', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'paid_amount', 'paid_at', 'credit_applied']);
        });
    }
};
