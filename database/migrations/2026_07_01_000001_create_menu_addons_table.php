<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add-ons (tambahan) per menu — mis. "Extra Keju", "Level Pedas", dengan harga sendiri.
 * Dipilih kasir saat menambah menu ke keranjang; snapshot-nya disimpan di order_details.addons.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_addons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_addons');
    }
};
