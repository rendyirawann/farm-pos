<?php

namespace App\Listeners;

use App\Models\DepositTransaction;
use App\Services\DepositService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;

/**
 * Saat email diverifikasi (akun aktif): jadikan tenant mode DEPOSIT (Starter)
 * + kredit saldo bonus aktivasi Rp2.000. Idempoten (sekali per tenant).
 */
class GrantStarterOnVerified
{
    /** Bonus saldo saat aktivasi (Rupiah). */
    public const ACTIVATION_BONUS = 2000;

    public function __construct(private DepositService $deposit)
    {
    }

    public function handle(Verified $event): void
    {
        $user   = $event->user;
        $tenant = $user->tenant ?? null;
        if (! $tenant) {
            return;
        }

        // Idempoten: jangan kredit dua kali (cek ledger bonus aktivasi).
        $already = DepositTransaction::where('tenant_id', $tenant->id)
            ->where('reference', 'activation-bonus')
            ->exists();
        if ($already) {
            return;
        }

        try {
            $this->deposit->switchToDeposit($tenant);
            $this->deposit->credit(
                $tenant,
                self::ACTIVATION_BONUS,
                self::ACTIVATION_BONUS,
                'activation-bonus',
                $user->id,
                'Bonus aktivasi akun (Starter)',
                false // tanpa enforce cap (bonus)
            );
        } catch (\Throwable $e) {
            Log::warning('GrantStarterOnVerified gagal: ' . $e->getMessage());
        }
    }
}
