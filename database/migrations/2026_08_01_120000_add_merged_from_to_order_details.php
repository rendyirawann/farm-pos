<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak asal item setelah MERGE TABLE.
 * NULL = item memang milik nota ini; berisi nomor antrian nota sumber (mis. "7")
 * agar detail pesanan & struk tetap bisa memperlihatkan item mana dari nota mana.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->string('merged_from', 20)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn('merged_from');
        });
    }
};
