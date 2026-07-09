<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\DepositSetting;
use App\Models\DepositTier;
use App\Models\DepositTransaction;
use App\Models\Tenant;
use App\Services\DepositService;
use App\Tenancy\DepositConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepositSettingController extends Controller
{
    /** Halaman setelan plan deposit (Superadmin). */
    public function index()
    {
        $settings = DepositSetting::firstOrCreate([], [
            'max_points'          => (int) config('deposit.max_points', 70000),
            'fee_per_transaction' => (int) config('deposit.fee_per_transaction', 169),
            'expiry_days'         => (int) config('deposit.expiry_days', 60),
            'min_deposit'         => (int) config('deposit.min_deposit', 5000),
            'initial_topup'       => (int) config('deposit.initial_topup', 50000),
            'manual_wa'           => config('deposit.manual_wa'),
            'manual_bank'         => config('deposit.manual_bank'),
        ]);

        $tiers = DepositTier::orderBy('sort_order')->orderBy('amount')->get();

        // Bila belum ada tier di DB, tampilkan default dari config sebagai baris awal.
        if ($tiers->isEmpty()) {
            $tiers = collect(config('deposit.tiers', []))->map(fn ($t, $i) => new DepositTier([
                'amount'     => (int) $t['amount'],
                'points'     => (int) $t['points'],
                'sort_order' => $i,
                'is_active'  => true,
            ]));
        }

        // Tenant mode deposit (untuk top-up manual) + riwayat top-up manual terbaru.
        $tenants = Tenant::where('billing_mode', 'deposit')->orderBy('name')->get();
        $recentManual = DepositTransaction::where('reference', 'manual')
            ->with('tenant:id,name')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        // Paket aktif untuk dropdown top-up manual (bukan nominal bebas).
        $activeTiers = DepositConfig::tiers();

        return view('backend.superadmin.deposit.index', compact('settings', 'tiers', 'tenants', 'recentManual', 'activeTiers'));
    }

    /** Simpan setelan + sinkron daftar tier. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'max_points'          => ['nullable', 'integer', 'min:0'], // kosong/0 = tanpa batas
            'fee_per_transaction' => ['required', 'integer', 'min:0'],
            'expiry_days'         => ['required', 'integer', 'min:1', 'max:3650'],
            'min_deposit'         => ['required', 'integer', 'min:0'],
            'initial_topup'       => ['required', 'integer', 'min:1'],
            'manual_wa'           => ['nullable', 'string', 'max:32'],
            'manual_bank'         => ['nullable', 'string', 'max:255'],
            'tiers'               => ['required', 'array', 'min:1'],
            'tiers.*.amount'      => ['required', 'integer', 'min:1'],
            'tiers.*.points'      => ['required', 'integer', 'min:1'],
            'tiers.*.is_active'   => ['nullable'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $settings = DepositSetting::firstOrCreate([]);
            // Kosong atau 0 => null (tanpa batas).
            $maxPoints = (isset($data['max_points']) && (int) $data['max_points'] > 0) ? (int) $data['max_points'] : null;
            $settings->update([
                'max_points'          => $maxPoints,
                'fee_per_transaction' => $data['fee_per_transaction'],
                'expiry_days'         => $data['expiry_days'],
                'min_deposit'         => $data['min_deposit'],
                'initial_topup'       => $data['initial_topup'],
                'manual_wa'           => $data['manual_wa'] ?? null,
                'manual_bank'         => $data['manual_bank'] ?? null,
            ]);

            // Sinkron tier: hapus semua lalu buat ulang dari input (daftar kecil).
            DepositTier::query()->delete();
            foreach (array_values($data['tiers']) as $i => $tier) {
                DepositTier::create([
                    'amount'     => (int) $tier['amount'],
                    'points'     => (int) $tier['points'],
                    'sort_order' => $i,
                    'is_active'  => filter_var($tier['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ]);
            }
        });

        return redirect()->route('deposit-settings.index')
            ->with('success', 'Setelan plan deposit berhasil disimpan.');
    }

    /**
     * Top-up manual poin ke sebuah tenant (mis. setelah transfer bank + konfirmasi WA).
     * Tercatat di riwayat poin tenant + activity log. Batas maksimum tidak ditegakkan.
     */
    public function manualTopup(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'amount'    => ['required', 'integer'],   // nominal PAKET yang dipilih (bukan nominal bebas)
            'note'      => ['nullable', 'string', 'max:255'],
        ]);

        // Poin diambil dari paket/tier aktif — server-side, anti-manipulasi.
        $points = DepositConfig::pointsFor((int) $data['amount']);
        if ($points === null) {
            return redirect()->route('deposit-settings.index')
                ->with('error', 'Paket top-up tidak valid. Silakan pilih paket yang tersedia.');
        }

        $tenant = Tenant::findOrFail($data['tenant_id']);

        app(DepositService::class)->manualCredit(
            $tenant,
            (int) $points,
            (int) $data['amount'],
            auth()->id(),
            $data['note'] ?? ''
        );

        return redirect()->route('deposit-settings.index')->with(
            'success',
            'Top-up manual berhasil: +' . number_format($points, 0, ',', '.') . ' poin (paket Rp'
                . number_format($data['amount'], 0, ',', '.') . ') ke ' . $tenant->name . '.'
        );
    }
}
