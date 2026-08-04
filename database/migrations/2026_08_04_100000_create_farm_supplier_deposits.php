<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DEPOSIT SUPPLIER — buku besar saldo, bukan kolom saldo.
 *
 * Owner mentransfer uang ke supplier lebih dulu; setiap pembelian memotong saldo,
 * dan realisasi (barang ternyata kurang/lebih) mengoreksinya. Saldo TIDAK disimpan
 * sebagai satu angka melainkan dihitung SUM(amount) dari buku besar ini, supaya
 * setiap perubahan punya jejak dan tidak bisa menyimpang tanpa alasan.
 *
 * Menggantikan konsep "piutang supplier" (farm_supplier_settlements + kolom
 * settled_amount pada realisasi) yang dihapus atas permintaan pemilik produk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_supplier_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('supplier_id')->index();
            $table->date('date')->index();

            // topup       : owner transfer uang ke supplier            (+)
            // purchase    : nota barang masuk memotong saldo            (-)
            // realization : koreksi karena barang kurang (+) / lebih (-)
            // manual      : koreksi manual dengan alasan tertulis      (+/-)
            $table->string('type', 20)->index();

            // BERTANDA: positif menambah saldo, negatif mengurangi.
            // Disimpan bertanda agar saldo cukup satu SUM tanpa percabangan.
            $table->decimal('amount', 15, 2);

            // Rujukan ke dokumen sumber supaya bisa ditelusuri & dibatalkan.
            $table->string('reference_type', 30)->nullable();   // stock_in | realization
            $table->unsignedBigInteger('reference_id')->nullable();

            // Bukti transfer — wajib secara praktik untuk type=topup.
            $table->string('proof_path')->nullable();

            $table->uuid('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'supplier_id', 'date'], 'farm_dep_saldo_idx');
            $table->index(['reference_type', 'reference_id'], 'farm_dep_ref_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_supplier_deposits');
    }
};
