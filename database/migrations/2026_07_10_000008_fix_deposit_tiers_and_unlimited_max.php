<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rapikan data deposit lama (mis. di server yang sudah pernah di-seed):
     *  - Hapus tier top-up bawaan lama Rp5.000 & Rp10.000. Kini paket hanya 25rb & 50rb.
     *  - Set batas maksimum saldo => tanpa batas (null). Cap rendah (mis. 70rb/100rb)
     *    membuat paket top-up otomatis ter-hide setelah aktivasi wajib 50rb (=62.500 saldo).
     *
     * Tier 25rb/50rb & setelan lain TIDAK disentuh. Idempoten (aman dijalankan ulang).
     */
    public function up(): void
    {
        if (Schema::hasTable('deposit_tiers')) {
            DB::table('deposit_tiers')->whereIn('amount', [5000, 10000])->delete();
        }

        if (Schema::hasTable('deposit_settings')) {
            // Hanya kolom max_points; nilai setelan lain dipertahankan.
            DB::table('deposit_settings')->update(['max_points' => null]);
        }
    }

    public function down(): void
    {
        // Tidak mengembalikan tier lama & tidak memaksa batas maksimum; biarkan apa adanya.
    }
};
