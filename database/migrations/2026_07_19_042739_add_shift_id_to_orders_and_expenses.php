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
        // Kolom NULLABLE (aditif) -> aman, tidak mengubah nilai keuangan apa pun.
        // Menautkan order & pengeluaran ke shift kasir tempat transaksi/uang-laci itu terjadi,
        // menggantikan atribusi via created_at yang dobel-hitung antar-shift & salah utk backdate.
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->index();
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shift_id');
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('shift_id');
        });
    }
};
