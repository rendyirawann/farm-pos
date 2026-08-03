<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto bon dari supplier untuk nota pembelian.
 *
 * Bon fisik dari supplier adalah satu-satunya bukti harga beli; memotretnya
 * membuat harga pokok bisa diverifikasi belakangan tanpa mencari kertasnya.
 * Beberapa berkas diperbolehkan (bon sering lebih dari selembar), disimpan
 * sebagai JSON daftar path agar tidak perlu tabel tambahan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_stock_ins', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('farm_stock_ins', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};
