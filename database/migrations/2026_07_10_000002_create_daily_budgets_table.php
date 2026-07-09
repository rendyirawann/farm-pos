<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anggaran pengeluaran harian ("modal/anggaran pengeluaran"). Per-tenant, per-tanggal.
     * Diisi saat buka shift pertama hari itu; dipakai untuk widget budget & peringatan.
     */
    public function up(): void
    {
        Schema::create('daily_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->date('date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_budgets');
    }
};
