<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Meja (manajemen meja dinamis) — fitur paket Enterprise ke atas.
     * Basic memakai pilihan meja statis 1..25 di kasir; Enterprise memakai daftar ini.
     */
    public function up(): void
    {
        Schema::create('dining_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('name', 40);              // label meja: "1", "A1", "VIP 2"
            $table->string('area', 60)->nullable();  // area/lantai (opsional)
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dining_tables');
    }
};
