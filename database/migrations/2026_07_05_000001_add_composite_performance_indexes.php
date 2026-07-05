<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes untuk pola query multi-tenant tersibuk:
 *  - orders: "pesanan LUNAS dalam rentang tanggal" per tenant (dashboard, laporan
 *    penjualan, omzet sidebar) -> (tenant_id, payment_status, created_at)
 *  - order_details: agregasi penjualan per menu per tenant (laporan item, top produk)
 *    -> (tenant_id, menu_id)
 * Index single-column lama (tenant_id, created_at, payment_status, dst) tetap ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['tenant_id', 'payment_status', 'created_at'], 'orders_tenant_paid_created_idx');
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->index(['tenant_id', 'menu_id'], 'order_details_tenant_menu_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_tenant_paid_created_idx');
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropIndex('order_details_tenant_menu_idx');
        });
    }
};
