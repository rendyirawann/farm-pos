<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_settings', function (Blueprint $table) {
            // Ambang peringatan: saldo <= nilai ini => peringatan merah "segera top up".
            $table->unsignedInteger('warning_threshold')->default(10000)->after('min_deposit');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_settings', function (Blueprint $table) {
            $table->dropColumn('warning_threshold');
        });
    }
};
