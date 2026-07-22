<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Grandfather: semua akun LAMA dianggap sudah AKTIF (email terverifikasi) agar tidak
 * terkunci saat verifikasi email diberlakukan. Hanya akun BARU (daftar setelah ini)
 * yang wajib klik link aktivasi.
 *
 * Juga menjamin kolom email_verified_at ada (defensif; tabel users standar sudah punya).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'email_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            });
        }

        DB::table('users')->whereNull('email_verified_at')->update([
            'email_verified_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Tidak dikembalikan (menandai verified tidak merusak apa pun).
    }
};
