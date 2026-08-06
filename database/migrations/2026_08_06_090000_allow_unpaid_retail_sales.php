<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PENJUALAN ECER BOLEH BELUM LUNAS.
 *
 * Sebelumnya hutang hanya boleh atas nama agen, karena piutang tanpa nama tidak
 * bisa ditagih ke siapa pun. Pembeli ecer yang berhutang tetap perlu dicatat,
 * jadi notanya sekarang menyimpan NAMA PEMBELI sendiri — itulah pengganti nama
 * agen di daftar piutang.
 *
 * Pembayarannya juga dicatat di tabel yang sama dengan pembayaran agen, sehingga
 * kolom agent_id di sana harus boleh kosong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_stock_outs', function (Blueprint $t) {
            $t->string('customer_name', 100)->nullable()->after('agent_id');
        });

        DB::statement('ALTER TABLE farm_agent_payments ALTER COLUMN agent_id DROP NOT NULL');
    }

    public function down(): void
    {
        Schema::table('farm_stock_outs', function (Blueprint $t) {
            $t->dropColumn('customer_name');
        });

        // Baris tanpa agen tidak bisa dikembalikan ke NOT NULL; dibuang lebih dulu.
        DB::table('farm_agent_payments')->whereNull('agent_id')->delete();
        DB::statement('ALTER TABLE farm_agent_payments ALTER COLUMN agent_id SET NOT NULL');
    }
};
