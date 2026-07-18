<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logo partner/tenant yang sudah berlangganan — tampil marquee di landing page.
 * GLOBAL, dikelola Superadmin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_logos', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // Nama tenant (muncul saat hover)
            $table->string('image');                // File logo (storage/app/public/partners)
            $table->string('url')->nullable();      // Link opsional saat diklik
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_logos');
    }
};
