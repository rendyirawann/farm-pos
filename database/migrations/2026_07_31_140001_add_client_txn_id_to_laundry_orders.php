<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci idempoten untuk sinkron nota laundry OFFLINE.
 * Klien membuat client_txn_id (unik per nota) saat menyimpan lokal; server memakainya
 * untuk menolak nota yang sudah pernah masuk -> tidak ada nota ganda meski sinkron diulang
 * atau request sempat sampai lalu koneksi terputus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laundry_orders', function (Blueprint $table) {
            $table->string('client_txn_id', 64)->nullable()->unique()->after('invoice_no');
        });
    }

    public function down(): void
    {
        Schema::table('laundry_orders', function (Blueprint $table) {
            $table->dropUnique(['client_txn_id']);
            $table->dropColumn('client_txn_id');
        });
    }
};
