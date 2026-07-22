<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Channel pembayaran Tripay — GLOBAL, dikelola Superadmin (mirip DOKU VA).
 * `code` = kode channel Tripay (mis. QRIS, BRIVA). Customer memilih dari channel yang aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tripay_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');              // Nama tampil, mis. "QRIS" / "BRI Virtual Account"
            $table->string('code');              // Kode channel Tripay, mis. QRIS / BRIVA
            $table->string('group')->nullable(); // Grup, mis. "Virtual Account" / "E-Wallet" / "QRIS"
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tripay_channels');
    }
};
