<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Setelan plan deposit tingkat-platform (satu baris). Diedit oleh Superadmin.
     */
    public function up(): void
    {
        Schema::create('deposit_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('max_points')->default(70000);
            $table->unsignedInteger('fee_per_transaction')->default(169);
            $table->unsignedSmallInteger('expiry_days')->default(60);
            $table->unsignedInteger('min_deposit')->default(5000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_settings');
    }
};
