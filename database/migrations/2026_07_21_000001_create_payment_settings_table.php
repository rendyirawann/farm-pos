<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Setelan payment gateway tingkat-platform (SATU baris).
 * `active_driver` = gateway yang aktif: midtrans | doku | tripay. Hanya 1 aktif.
 * Dikelola Superadmin (Payment -> Payment Gateway). Fallback bila kosong: config('billing.driver').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('active_driver')->default('midtrans'); // midtrans | doku | tripay
            $table->timestamps();
        });

        // Baris tunggal awal — ambil default dari .env (BILLING_DRIVER) bila ada.
        $default = config('billing.driver', 'midtrans');
        if (! in_array($default, ['midtrans', 'doku', 'tripay'], true)) {
            $default = 'midtrans';
        }
        DB::table('payment_settings')->insert([
            'active_driver' => $default,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
