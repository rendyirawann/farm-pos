<?php

namespace Database\Seeders;

use App\Models\DepositSetting;
use App\Models\DepositTier;
use Illuminate\Database\Seeder;

/**
 * Isi setelan + tier deposit default (dari config/deposit.php) ke DB agar bisa
 * diedit Superadmin. Idempoten.
 */
class DepositTierSeeder extends Seeder
{
    public function run(): void
    {
        DepositSetting::firstOrCreate([], [
            'max_points'          => config('deposit.max_points'), // null = tanpa batas
            'fee_per_transaction' => (int) config('deposit.fee_per_transaction', 169),
            'expiry_days'         => (int) config('deposit.expiry_days', 10),
            'min_deposit'         => (int) config('deposit.min_deposit', 25000),
            'initial_topup'       => (int) config('deposit.initial_topup', 50000),
            'manual_wa'           => config('deposit.manual_wa'),
            'manual_bank'         => config('deposit.manual_bank'),
        ]);

        if (DepositTier::count() === 0) {
            foreach (config('deposit.tiers', []) as $i => $tier) {
                DepositTier::create([
                    'amount'     => (int) $tier['amount'],
                    'points'     => (int) $tier['points'],
                    'sort_order' => $i,
                    'is_active'  => true,
                ]);
            }
        }

        $this->command->info('Setelan & tier deposit default siap.');
    }
}
