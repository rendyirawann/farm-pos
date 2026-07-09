<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\DepositService;
use Illuminate\Console\Command;

/**
 * Sweep harian plan deposit:
 *  1. Hanguskan poin yang sudah lewat masa aktif (dormant > expiry_days).
 *  2. Kembalikan tenant ke mode deposit bila langganan bulanan sudah berakhir
 *     dan poin masih dapat dipakai.
 * Dijadwalkan harian (lihat routes/console.php).
 */
class DepositSweep extends Command
{
    protected $signature = 'deposit:sweep';

    protected $description = 'Hanguskan poin deposit dormant & kembalikan tenant bulanan-kedaluwarsa ke mode deposit';

    public function handle(DepositService $service): int
    {
        $now = now();

        // 1. Hanguskan poin dormant (masa aktif lewat).
        $expired = 0;
        Tenant::where('deposit_points', '>', 0)
            ->whereNotNull('deposit_expires_at')
            ->where('deposit_expires_at', '<', $now)
            ->chunkById(100, function ($tenants) use ($service, &$expired) {
                foreach ($tenants as $tenant) {
                    $service->expire($tenant);
                    $expired++;
                }
            });

        // 2. Kembalikan ke mode deposit bila langganan bulanan sudah berakhir & poin masih ada.
        $reverted = 0;
        Tenant::where('billing_mode', 'monthly')
            ->where('deposit_points', '>', 0)
            ->chunkById(100, function ($tenants) use ($service, &$reverted) {
                foreach ($tenants as $tenant) {
                    if ($service->maybeRevertToDeposit($tenant)) {
                        $reverted++;
                    }
                }
            });

        $this->info("Selesai. Poin hangus: {$expired}. Tenant kembali ke deposit: {$reverted}.");

        return self::SUCCESS;
    }
}
