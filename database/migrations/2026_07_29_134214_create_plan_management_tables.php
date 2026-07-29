<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Harga dasar per paket (harga 1 bulan). Diatur Superadmin.
        Schema::create('plan_settings', function (Blueprint $table) {
            $table->id();
            $table->string('plan_key')->unique();     // basic | enterprise
            $table->integer('base_price')->default(0);
            $table->timestamps();
        });

        // Promo/diskon per (paket, durasi bulan).
        Schema::create('plan_promos', function (Blueprint $table) {
            $table->id();
            $table->string('plan_key');
            $table->unsignedSmallInteger('months');
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->string('promo_label')->nullable();
            $table->boolean('is_active')->default(false);   // toggle promo (diskon + badge)
            $table->integer('price_per_month')->default(0); // harga efektif (sumber kebenaran harga)
            $table->timestamps();
            $table->unique(['plan_key', 'months']);
        });

        // Seed dari config/plans.php agar harga saat ini TETAP PERSIS jadi nilai awal.
        $plans = config('plans.plans', []);
        foreach (['basic', 'enterprise'] as $key) {
            $plan = $plans[$key] ?? null;
            if (! $plan) {
                continue;
            }
            $base = (int) ($plan['price'] ?? 0);
            DB::table('plan_settings')->updateOrInsert(
                ['plan_key' => $key],
                ['base_price' => $base, 'created_at' => now(), 'updated_at' => now()]
            );

            foreach (($plan['periods'] ?? []) as $per) {
                $months = (int) $per['months'];
                $ppm    = (int) $per['price_per_month'];
                $disc   = $base > 0 ? round((1 - $ppm / $base) * 100, 2) : 0;
                DB::table('plan_promos')->updateOrInsert(
                    ['plan_key' => $key, 'months' => $months],
                    [
                        'discount_percent' => $disc,
                        'promo_label'      => $per['label'] ?? ($months <= 1 ? 'Bulanan' : 'Promo ' . $months . ' Bulan'),
                        'is_active'        => $disc > 0,           // durasi berdiskon -> promo aktif
                        'price_per_month'  => $ppm,                // harga saat ini PERSIS
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_promos');
        Schema::dropIfExists('plan_settings');
    }
};
