<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Nomor meja (opsional). Untuk plan Basic = pilihan statis 1..25;
            // Enterprise nanti bisa pakai manajemen meja dinamis.
            $table->string('table_no', 20)->nullable()->after('customer_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('table_no');
        });
    }
};
