<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Carbon\Carbon;

/**
 * Menandai tenant yang masa trial / langganannya sudah lewat menjadi 'expired'.
 * Dijadwalkan harian (lihat routes/console.php).
 */
class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Tandai trial/langganan tenant yang sudah kedaluwarsa menjadi expired';

    public function handle(): int
    {
        $now = Carbon::now();

        $expiredTrial = Tenant::where('subscription_status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', $now)
            ->update(['subscription_status' => 'expired']);

        $expiredActive = Tenant::where('subscription_status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', $now)
            ->update(['subscription_status' => 'expired']);

        $total = $expiredTrial + $expiredActive;
        $this->info("Selesai. Trial kedaluwarsa: {$expiredTrial}, Langganan kedaluwarsa: {$expiredActive} (total {$total}).");

        return self::SUCCESS;
    }
}
