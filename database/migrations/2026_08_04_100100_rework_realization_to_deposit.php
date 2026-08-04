<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Realisasi dua arah + hapus konsep "piutang supplier".
 *
 * Atas permintaan pemilik produk, istilah utang/piutang supplier dihapus. Selisih
 * antara nota dan barang nyata kini langsung menyesuaikan SALDO DEPOSIT supplier,
 * jadi tabel alokasi penutupan (farm_supplier_settlements) dan kolom
 * settled_amount/status pada realisasi tidak lagi punya arti.
 *
 * Realisasi kini punya arah: 'kurang' (barang lebih sedikit dari nota) atau
 * 'lebih' (barang lebih banyak). Besarannya tetap positif; arah yang menentukan
 * tanda pada buku besar deposit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_stock_in_realizations', function (Blueprint $table) {
            $table->string('direction', 10)->default('kurang')->after('reason')->index();
        });

        // Data lama (bila ada) semuanya berarah 'kurang'.
        \Illuminate\Support\Facades\DB::table('farm_stock_in_realizations')->update(['direction' => 'kurang']);

        Schema::table('farm_stock_in_realizations', function (Blueprint $table) {
            $table->dropColumn(['settled_amount', 'status']);
        });

        Schema::dropIfExists('farm_supplier_settlements');

        // credit_applied pada nota juga kehilangan artinya: pemotongan kini lewat deposit.
        Schema::table('farm_stock_ins', function (Blueprint $table) {
            $table->dropColumn('credit_applied');
        });
    }

    public function down(): void
    {
        Schema::table('farm_stock_in_realizations', function (Blueprint $table) {
            $table->dropColumn('direction');
            $table->decimal('settled_amount', 15, 2)->default(0);
            $table->string('status', 12)->default('open')->index();
        });
        Schema::table('farm_stock_ins', function (Blueprint $table) {
            $table->decimal('credit_applied', 15, 2)->default(0);
        });
        Schema::create('farm_supplier_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('supplier_id')->index();
            $table->foreignId('realization_id')->index();
            $table->foreignId('stock_in_id')->nullable()->index();
            $table->date('date')->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->uuid('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
};
