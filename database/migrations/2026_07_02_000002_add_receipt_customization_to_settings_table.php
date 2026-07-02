<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kustomisasi struk per toko:
 * - receipt_header       : teks tambahan di bawah nama toko (mis. cabang/tagline)
 * - receipt_footer       : teks kaki struk (ucapan terima kasih, IG, dll) — bisa multi-baris
 * - receipt_show_address : tampilkan alamat toko di struk
 * - receipt_show_phone   : tampilkan no. telepon di struk
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'receipt_header')) {
                $table->string('receipt_header')->nullable()->after('paper_width');
            }
            if (!Schema::hasColumn('settings', 'receipt_footer')) {
                $table->text('receipt_footer')->nullable()->default('Terima kasih atas kunjungan Anda!')->after('receipt_header');
            }
            if (!Schema::hasColumn('settings', 'receipt_show_address')) {
                $table->boolean('receipt_show_address')->default(true)->after('receipt_footer');
            }
            if (!Schema::hasColumn('settings', 'receipt_show_phone')) {
                $table->boolean('receipt_show_phone')->default(true)->after('receipt_show_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['receipt_header', 'receipt_footer', 'receipt_show_address', 'receipt_show_phone']);
        });
    }
};
