<?php

namespace App\Http\Controllers\Backend\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\DepositSetting;
use App\Models\DepositTier;
use App\Tenancy\DepositConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepositSettingController extends Controller
{
    /** Halaman setelan plan deposit (Superadmin). */
    public function index()
    {
        $settings = DepositSetting::firstOrCreate([], [
            'max_points'          => (int) config('deposit.max_points', 50000),
            'fee_per_transaction' => (int) config('deposit.fee_per_transaction', 150),
            'expiry_days'         => (int) config('deposit.expiry_days', 60),
            'min_deposit'         => (int) config('deposit.min_deposit', 5000),
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

        return view('backend.superadmin.deposit.index', compact('settings', 'tiers'));
    }

    /** Simpan setelan + sinkron daftar tier. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'max_points'          => ['required', 'integer', 'min:0'],
            'fee_per_transaction' => ['required', 'integer', 'min:0'],
            'expiry_days'         => ['required', 'integer', 'min:1', 'max:3650'],
            'min_deposit'         => ['required', 'integer', 'min:0'],
            'tiers'               => ['required', 'array', 'min:1'],
            'tiers.*.amount'      => ['required', 'integer', 'min:1'],
            'tiers.*.points'      => ['required', 'integer', 'min:1'],
            'tiers.*.is_active'   => ['nullable'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $settings = DepositSetting::firstOrCreate([]);
            $settings->update([
                'max_points'          => $data['max_points'],
                'fee_per_transaction' => $data['fee_per_transaction'],
                'expiry_days'         => $data['expiry_days'],
                'min_deposit'         => $data['min_deposit'],
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
}
