<?php

namespace App\Tenancy;

use App\Models\DepositSetting;
use App\Models\DepositTier;
use Illuminate\Support\Collection;

/**
 * Titik akses tunggal untuk setelan plan deposit. Membaca dari DB
 * (deposit_settings & deposit_tiers) dengan fallback ke config/deposit.php
 * bila baris DB belum ada. Semua nilai bisa diubah Superadmin.
 */
class DepositConfig
{
    /** Apakah fitur plan deposit ditawarkan. */
    public static function enabled(): bool
    {
        return (bool) config('deposit.enabled', true);
    }

    /** Baris setelan (unsaved default bila belum ada di DB). */
    public static function settings(): DepositSetting
    {
        return DepositSetting::query()->first() ?? new DepositSetting([
            'max_points'          => (int) config('deposit.max_points', 70000),
            'fee_per_transaction' => (int) config('deposit.fee_per_transaction', 150),
            'expiry_days'         => (int) config('deposit.expiry_days', 60),
            'min_deposit'         => (int) config('deposit.min_deposit', 5000),
            'initial_topup'       => (int) config('deposit.initial_topup', 50000),
            'manual_wa'           => config('deposit.manual_wa'),
            'manual_bank'         => config('deposit.manual_bank'),
        ]);
    }

    /**
     * Batas maksimum saldo poin. NULL = tanpa batas (unlimited) — bila Superadmin
     * mengosongkan nilai (atau 0). Kode pemanggil harus memperlakukan null = tak terbatas.
     */
    public static function maxPoints(): ?int
    {
        $v = self::settings()->max_points;

        return ($v === null || (int) $v <= 0) ? null : (int) $v;
    }

    /** Nominal top-up WAJIB pertama kali (aktivasi plan deposit akun baru). */
    public static function initialTopup(): int
    {
        return (int) self::settings()->initial_topup;
    }

    /** Nomor WA & info rekening untuk top-up manual. */
    public static function manualWa(): ?string
    {
        return self::settings()->manual_wa ?: config('deposit.manual_wa');
    }

    public static function manualBank(): ?string
    {
        return self::settings()->manual_bank ?: config('deposit.manual_bank');
    }

    public static function feePerTransaction(): int
    {
        return (int) self::settings()->fee_per_transaction;
    }

    public static function expiryDays(): int
    {
        return (int) self::settings()->expiry_days;
    }

    public static function minDeposit(): int
    {
        return (int) self::settings()->min_deposit;
    }

    /**
     * Tier top-up aktif, urut. Tiap item: object{amount:int, points:int}.
     * Fallback ke config bila tabel kosong.
     */
    public static function tiers(): Collection
    {
        $rows = DepositTier::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('amount')
            ->get();

        if ($rows->isEmpty()) {
            return collect(config('deposit.tiers', []))->map(fn ($t) => (object) [
                'amount' => (int) $t['amount'],
                'points' => (int) $t['points'],
            ]);
        }

        return $rows->map(fn ($r) => (object) [
            'amount' => (int) $r->amount,
            'points' => (int) $r->points,
        ])->values();
    }

    /** Poin yang didapat untuk nominal tertentu, atau null bila tier tak ada. */
    public static function pointsFor(int $amount): ?int
    {
        $tier = self::tiers()->firstWhere('amount', $amount);

        return $tier ? (int) $tier->points : null;
    }

    /**
     * Poin untuk top-up: bila nominal cocok tier aktif -> poin tier (termasuk bonus);
     * bila tidak (nominal custom) -> 1:1 (poin = nominal). Null bila nominal <= 0.
     */
    public static function pointsForTopup(int $amount): ?int
    {
        if ($amount <= 0) {
            return null;
        }

        return self::pointsFor($amount) ?? $amount;
    }
}
