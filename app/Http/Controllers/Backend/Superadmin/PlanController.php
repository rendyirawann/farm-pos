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
    /** Paket berbayar (bukan 'contact'/customize) untuk sebuah vertical. */
    private function paidPlanKeys(string $vertical): array
    {
        return collect(Plan::all($vertical))
            ->reject(fn ($p) => ! empty($p['contact']))
            ->keys()->all();
    }

    public function index(Request $request)
    {
        $verticals = \App\Verticals\VerticalRegistry::enabled();
        $vertical  = \App\Verticals\VerticalRegistry::normalize($request->query('vertical'));
        if (! array_key_exists($vertical, $verticals)) {
            $vertical = array_key_first($verticals);
        }

        $data = [];
        foreach ($this->paidPlanKeys($vertical) as $key) {
            $setting = PlanSetting::firstOrCreate(
                ['plan_key' => $key, 'vertical' => $vertical],
                ['base_price' => (int) (Plan::get($key, $vertical)['price'] ?? 0)]
            );
            $data[$key] = [
                'name'    => Plan::name($key, $vertical),
                'setting' => $setting,
                'promos'  => PlanPromo::where('plan_key', $key)->where('vertical', $vertical)->orderBy('months')->get(),
            ];
        }

        return view('backend.superadmin.plans.index', compact('data', 'verticals', 'vertical'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'plans'    => ['required', 'array'],
            'vertical' => ['nullable', 'string'],
        ]);

        $vertical  = \App\Verticals\VerticalRegistry::normalize($request->input('vertical'));
        $validKeys = $this->paidPlanKeys($vertical);

        DB::transaction(function () use ($request, $vertical, $validKeys) {
            foreach ((array) $request->input('plans', []) as $key => $p) {
                if (! in_array($key, $validKeys, true)) {
                    continue;
                }
                $base = max(0, (int) ($p['base_price'] ?? 0));
                PlanSetting::updateOrCreate(
                    ['plan_key' => $key, 'vertical' => $vertical],
                    ['base_price' => $base]
                );

                foreach ((array) ($p['promos'] ?? []) as $months => $row) {
                    $months = (int) $months;
                    if ($months < 1) {
                        continue;
                    }
                    $disc   = min(100, max(0, (float) ($row['discount_percent'] ?? 0)));
                    $active = ! empty($row['is_active']);
                    $label  = trim((string) ($row['promo_label'] ?? '')) ?: null;
                    // Harga bisa diketik langsung (biar pas, mis. 169.000). Bila kosong, hitung dari diskon.
                    $typedPrice = isset($row['price_per_month']) && $row['price_per_month'] !== ''
                        ? max(0, (int) $row['price_per_month'])
                        : null;
                    // Toggle ON = diskon berlaku (harga turun): pakai harga ketikan, atau hitung dari diskon.
                    // OFF = harga penuh (harga dasar). Harga tak boleh melebihi harga dasar.
                    $ppm = ! $active
                        ? $base
                        : min($base, $typedPrice ?? (int) round($base * (1 - $disc / 100)));

                    PlanPromo::updateOrCreate(
                        ['plan_key' => $key, 'months' => $months, 'vertical' => $vertical],
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

        return redirect()->route('plan-settings.index', ['vertical' => $vertical])
            ->with('success', 'Setelan paket ' . \App\Verticals\VerticalRegistry::label($vertical) . ' berhasil disimpan.');
    }
}
