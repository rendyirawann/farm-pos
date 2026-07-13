<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tandai "pesanan salah" (void) pada order yang SUDAH SELESAI.
     * - voided_at: waktu pesanan ditandai salah (NULL = sah/normal).
     * - voided_by: user (uuid) yang menandai (audit).
     * Pesanan ber-voided_at TIDAK dihitung ke omzet/penjualan/kas laci,
     * tetapi TETAP tersimpan & tampil di laporan dengan penanda "SALAH".
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('order_status');
            $table->uuid('voided_by')->nullable()->after('voided_at');
            $table->index('voided_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['voided_at']);
            $table->dropColumn(['voided_at', 'voided_by']);
        });
    }
};
