<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\PlanPromo;
use App\Models\PlanSetting;
use App\Tenancy\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Setelan Paket langganan (Superadmin): harga dasar per paket + promo/diskon per durasi.
 * Harga efektif = harga dasar x (1 - diskon%) bila promo aktif; harga penuh bila nonaktif.
 * Mengubah data di sini otomatis dipakai landing & halaman billing (via Plan::periods()).
 */
class PlanController extends Controller
{
    /** Paket bulanan yang dikelola. */
    private const PLANS = ['basic', 'enterprise'];

    public function index()
    {
        $data = [];
        foreach (self::PLANS as $key) {
            $setting = PlanSetting::firstOrCreate(
                ['plan_key' => $key],
                ['base_price' => (int) (config("plans.plans.$key.price") ?? 0)]
            );
            $data[$key] = [
                'name'    => Plan::name($key),
                'setting' => $setting,
                'promos'  => PlanPromo::where('plan_key', $key)->orderBy('months')->get(),
            ];
        }
        return view('backend.superadmin.plans.index', compact('data'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'plans' => ['required', 'array'],
        ]);

        DB::transaction(function () use ($request) {
            foreach ((array) $request->input('plans', []) as $key => $p) {
                if (! in_array($key, self::PLANS, true)) {
                    continue;
                }
                $base = max(0, (int) ($p['base_price'] ?? 0));
                PlanSetting::updateOrCreate(['plan_key' => $key], ['base_price' => $base]);

                foreach ((array) ($p['promos'] ?? []) as $months => $row) {
                    $months = (int) $months;
                    if ($months < 1) {
                        continue;
                    }
                    $disc   = min(100, max(0, (float) ($row['discount_percent'] ?? 0)));
                    $active = ! empty($row['is_active']);
                    $label  = trim((string) ($row['promo_label'] ?? '')) ?: null;
                    // Toggle ON = diskon berlaku (harga turun); OFF = harga penuh.
                    $ppm = $active ? (int) round($base * (1 - $disc / 100)) : $base;

                    PlanPromo::updateOrCreate(
                        ['plan_key' => $key, 'months' => $months],
                        [
                            'discount_percent' => $disc,
                            'promo_label'      => $label,
                            'is_active'        => $active,
                            'price_per_month'  => $ppm,
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Setelan paket berhasil disimpan.');
    }
}
