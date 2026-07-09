<?php

namespace App\Services;

use App\Models\DepositTransaction;
use App\Models\Tenant;
use App\Tenancy\DepositConfig;
use Illuminate\Support\Facades\DB;

/**
 * Semua mutasi poin deposit + peralihan mode langganan. Aman-race (lockForUpdate).
 */
class DepositService
{
    /**
     * Kreditkan poin (top-up). Menolak bila saldo akhir melewati batas maksimum.
     *
     * @throws \RuntimeException bila melewati batas maksimum.
     */
    public function credit(
        Tenant $tenant,
        int $points,
        ?int $cashAmount = null,
        ?string $reference = null,
        ?string $userId = null,
        string $description = 'Top-up deposit',
        bool $enforceCap = true
    ): Tenant {
        return DB::transaction(function () use ($tenant, $points, $cashAmount, $reference, $userId, $description, $enforceCap) {
            /** @var Tenant $t */
            $t = Tenant::whereKey($tenant->getKey())->lockForUpdate()->firstOrFail();

            $max = DepositConfig::maxPoints(); // null = tanpa batas
            $newBalance = (float) $t->deposit_points + $points;
            // Cap ditegakkan saat CHECKOUT (sebelum bayar). Saat settlement webhook
            // ($enforceCap=false) poin tetap dikreditkan karena pembayaran sudah terjadi.
            if ($enforceCap && $max !== null && $newBalance > $max) {
                throw new \RuntimeException(
                    'Top-up ditolak: saldo akan menjadi Rp' . number_format($newBalance, 0, ',', '.') .
                    ', melebihi batas maksimum poin Rp' . number_format($max, 0, ',', '.') . '.'
                );
            }

            $t->deposit_points     = $newBalance;
            $t->deposit_expires_at = now()->addDays(DepositConfig::expiryDays());
            $t->save();

            DepositTransaction::create([
                'tenant_id'     => $t->id,
                'user_id'       => $userId,
                'type'          => 'topup',
                'points'        => $points,
                'balance_after' => $t->deposit_points,
                'cash_amount'   => $cashAmount,
                'description'   => $description,
                'reference'     => $reference,
            ]);

            return $t;
        });
    }

    /**
     * Potong poin untuk 1 transaksi (pesanan diselesaikan). Reset masa aktif.
     *
     * @throws \RuntimeException bila poin tidak cukup.
     */
    public function deduct(
        Tenant $tenant,
        ?int $points = null,
        string $type = 'usage',
        ?string $reference = null,
        ?string $userId = null,
        string $description = 'Biaya transaksi'
    ): Tenant {
        $points ??= DepositConfig::feePerTransaction();

        return DB::transaction(function () use ($tenant, $points, $type, $reference, $userId, $description) {
            /** @var Tenant $t */
            $t = Tenant::whereKey($tenant->getKey())->lockForUpdate()->firstOrFail();

            if ((float) $t->deposit_points < $points) {
                throw new \RuntimeException(
                    'Poin deposit tidak cukup (sisa Rp' . number_format($t->deposit_points, 0, ',', '.') . ').'
                );
            }

            $t->deposit_points      = (float) $t->deposit_points - $points;
            $t->deposit_last_used_at = now();
            $t->deposit_expires_at  = now()->addDays(DepositConfig::expiryDays());
            $t->save();

            DepositTransaction::create([
                'tenant_id'     => $t->id,
                'user_id'       => $userId,
                'type'          => $type,
                'points'        => -1 * abs($points),
                'balance_after' => $t->deposit_points,
                'description'   => $description,
                'reference'     => $reference,
            ]);

            return $t;
        });
    }

    /** Hanguskan seluruh sisa poin (dipanggil sweep harian). */
    public function expire(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            /** @var Tenant $t */
            $t = Tenant::whereKey($tenant->getKey())->lockForUpdate()->firstOrFail();

            $balance = (float) $t->deposit_points;
            if ($balance <= 0) {
                return $t;
            }

            $t->deposit_points     = 0;
            $t->deposit_expires_at = null;
            $t->save();

            DepositTransaction::create([
                'tenant_id'     => $t->id,
                'type'          => 'expiry',
                'points'        => -1 * $balance,
                'balance_after' => 0,
                'description'   => 'Poin hangus (lebih dari ' . DepositConfig::expiryDays() . ' hari tidak dipakai)',
            ]);

            return $t;
        });
    }

    /**
     * Beralih ke plan DEPOSIT. Langganan bulanan (jika ada) HANGUS.
     * Poin tetap tersimpan & bisa dipakai lagi.
     */
    public function switchToDeposit(Tenant $tenant): Tenant
    {
        $tenant->billing_mode         = 'deposit';
        $tenant->subscription_status  = 'inactive';
        $tenant->subscription_ends_at = null;
        // Mulai jam masa-aktif poin bila belum pernah diset.
        if ($tenant->deposit_points > 0 && $tenant->deposit_expires_at === null) {
            $tenant->deposit_expires_at = now()->addDays(DepositConfig::expiryDays());
        }
        $tenant->save();

        return $tenant;
    }

    /**
     * Tandai tenant memakai plan BULANAN (poin dibekukan, tetap tersimpan).
     * Dipanggil saat aktivasi langganan bulanan berhasil.
     */
    public function switchToMonthly(Tenant $tenant): Tenant
    {
        $tenant->billing_mode = 'monthly';
        $tenant->save();

        return $tenant;
    }

    /**
     * Kembalikan ke mode deposit bila langganan bulanan sudah berakhir dan
     * poin masih dapat dipakai. Return true bila ada perubahan.
     */
    public function maybeRevertToDeposit(Tenant $tenant): bool
    {
        if ($tenant->billing_mode !== 'monthly') {
            return false;
        }
        if ($tenant->monthlyActive()) {
            return false;
        }
        if (! $tenant->hasUsableDepositPoints()) {
            return false;
        }

        $tenant->billing_mode = 'deposit';
        $tenant->save();

        return true;
    }

    /** Apakah top-up nominal ini muat tanpa melewati batas maksimum (true bila tanpa batas). */
    public function canTopUp(Tenant $tenant, int $tierPoints): bool
    {
        $max = DepositConfig::maxPoints();
        if ($max === null) {
            return true;
        }

        return ((float) $tenant->deposit_points + $tierPoints) <= $max;
    }

    /**
     * Akun deposit yang BELUM pernah top-up sukses -> wajib top-up awal Rp{initial}.
     * Akun demo (punya ledger topup dari seeder) otomatis dikecualikan.
     */
    public function needsInitialTopup(Tenant $tenant): bool
    {
        if (! $tenant->isDepositMode()) {
            return false;
        }

        return ! DepositTransaction::where('tenant_id', $tenant->id)
            ->whereIn('type', ['topup', 'adjustment'])
            ->where('points', '>', 0)
            ->exists();
    }

    /**
     * Top-up manual oleh Superadmin (mis. transfer bank + konfirmasi WA). Karena
     * pembayaran sudah diterima di luar sistem, batas maksimum TIDAK ditegakkan.
     */
    public function manualCredit(Tenant $tenant, int $points, ?int $cashAmount, ?string $adminUserId, string $note = ''): Tenant
    {
        $desc = 'Top-up manual (transfer bank) oleh Superadmin' . ($note !== '' ? ' — ' . $note : '');

        return $this->credit($tenant, $points, $cashAmount, 'manual', $adminUserId, $desc, false);
    }

    /**
     * Ringkasan pilihan tier untuk halaman deposit: mana yang muat & rekomendasi.
     */
    public function tierOptions(Tenant $tenant): array
    {
        $balance = (float) $tenant->deposit_points;
        $max     = DepositConfig::maxPoints();

        $options = [];
        $recommended = null;
        foreach (DepositConfig::tiers() as $tier) {
            $resulting = $balance + $tier->points;
            $fits      = $max === null ? true : ($resulting <= $max);
            $options[] = [
                'amount'            => $tier->amount,
                'points'            => $tier->points,
                'bonus'             => $tier->points - $tier->amount,
                'resulting_balance' => $resulting,
                'fits'              => $fits,
            ];
            if ($fits) {
                $recommended = $tier->amount; // tier terbesar yang muat
            }
        }

        return [
            'options'     => $options,
            'recommended' => $recommended,
            'any_fits'    => $recommended !== null,
            'max'         => $max,
            'balance'     => $balance,
        ];
    }
}
