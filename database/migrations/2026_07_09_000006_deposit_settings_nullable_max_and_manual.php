<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_settings', function (Blueprint $table) {
            // max_points boleh NULL = tanpa batas (unlimited) bila Superadmin mengosongkan.
            $table->unsignedBigInteger('max_points')->nullable()->default(70000)->change();

            // Nominal top-up WAJIB pertama kali (aktivasi plan deposit akun baru).
            $table->unsignedInteger('initial_topup')->default(50000)->after('min_deposit');

            // Info top-up manual (ditampilkan ke tenant): nomor WA & rekening bank.
            $table->string('manual_wa')->nullable()->after('initial_topup');
            $table->string('manual_bank')->nullable()->after('manual_wa');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_settings', function (Blueprint $table) {
            $table->dropColumn(['initial_topup', 'manual_wa', 'manual_bank']);
            $table->unsignedBigInteger('max_points')->default(70000)->change();
        });
    }
};
