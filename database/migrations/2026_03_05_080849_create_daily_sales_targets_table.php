<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_sales_targets', function (Blueprint $table) {
            $table->id();
            // Keunikan per-tenant (tenant_id, date) ditetapkan setelah kolom tenant_id ada
            // — lihat migration add_per_tenant_unique_constraints.
            $table->date('date');
            $table->decimal('amount', 15, 2)->default(0); // Jumlah target Rupiah
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_sales_targets');
    }
};
