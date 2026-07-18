<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency key untuk pesanan kasir.
 *
 * Kunci transaksi dari klien (dibuat sekali per checkout & stabil saat retry / sinkron offline).
 * Mencegah pesanan dobel saat jaringan lambat: bila request sudah masuk server tapi respons
 * hilang, percobaan berikutnya dengan client_txn_id yang sama akan mengembalikan pesanan yang
 * SUDAH ada, bukan membuat pesanan baru. Unique parsial per-tenant = jaring pengaman anti-balapan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('client_txn_id', 64)->nullable()->after('uuid');
        });

        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS orders_tenant_client_txn_unique '
            . 'ON orders (tenant_id, client_txn_id) WHERE client_txn_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS orders_tenant_client_txn_unique');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('client_txn_id');
        });
    }
};
