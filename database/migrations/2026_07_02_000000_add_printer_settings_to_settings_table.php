<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konfigurasi printer struk per toko:
 * - printer_method : auto | browser | qztray | webbluetooth | rawbt
 * - paper_width    : lebar kertas thermal dalam mm (58 / 80)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'printer_method')) {
                $table->string('printer_method')->default('auto')->after('tax_rate');
            }
            if (!Schema::hasColumn('settings', 'paper_width')) {
                $table->unsignedSmallInteger('paper_width')->default(58)->after('printer_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['printer_method', 'paper_width']);
        });
    }
};
