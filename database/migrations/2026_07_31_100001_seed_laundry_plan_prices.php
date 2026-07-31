<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed harga paket LAUNDRY ke plan_settings & plan_promos (vertical = 'laundry'),
 * mengambil nilai dari config/plans.php agar satu sumber kebenaran.
 * Idempoten: memakai updateOrInsert, aman dijalankan berulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Unique constraint lama hanya per plan_key / (plan_key, months) sehingga paket
        // dgn key sama di vertical berbeda (mis. 'basic' F&B vs 'basic' Laundry) bentrok.
        // Sertakan kolom `vertical` agar tiap vertical punya barisnya sendiri.
        Schema::table('plan_settings', function (Blueprint $table) {
            $table->dropUnique('plan_settings_plan_key_unique');
            $table->unique(['plan_key', 'vertical']);
        });
        Schema::table('plan_promos', function (Blueprint $table) {
            $table->dropUnique('plan_promos_plan_key_months_unique');
            $table->unique(['plan_key', 'months', 'vertical']);
        });

        $plans = config('plans.verticals.laundry', []);
        $now   = now();

        foreach ($plans as $key => $plan) {
            // Paket konsultasi (customize) tak punya harga periode -> lewati.
            if (! empty($plan['contact'])) {
                continue;
            }

            $base = (int) ($plan['price'] ?? 0);
            DB::table('plan_settings')->updateOrInsert(
                ['plan_key' => $key, 'vertical' => 'laundry'],
                ['base_price' => $base, 'created_at' => $now, 'updated_at' => $now]
            );

            foreach (($plan['periods'] ?? []) as $per) {
                $months = (int) $per['months'];
                $ppm    = (int) $per['price_per_month'];
                $disc   = $base > 0 ? round((1 - $ppm / $base) * 100, 2) : 0;

                DB::table('plan_promos')->updateOrInsert(
                    ['plan_key' => $key, 'months' => $months, 'vertical' => 'laundry'],
                    [
                        'discount_percent' => $disc,
                        'promo_label'      => $per['label'] ?? ($months <= 1 ? 'Bulanan' : 'Promo ' . $months . ' Bulan'),
                        'is_active'        => $disc > 0,
                        'price_per_month'  => $ppm,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('plan_promos')->where('vertical', 'laundry')->delete();
        DB::table('plan_settings')->where('vertical', 'laundry')->delete();
    }
};
