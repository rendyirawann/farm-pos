<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah index pada kolom status yang sering difilter namun belum terindeks.
     * - orders.order_status: dipakai di Kasir/Kitchen (whereIn order_status)
     * - order_details.status: dipakai di Kitchen
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('order_status');
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->index('status');
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['order_status']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['order_id', 'status']);
        });
    }
};
