<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pilihan nominal top-up deposit. Diedit oleh Superadmin.
     * amount = Rupiah dibayar, points = poin diterima (sudah termasuk bonus).
     */
    public function up(): void
    {
        Schema::create('deposit_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('points');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_tiers');
    }
};
