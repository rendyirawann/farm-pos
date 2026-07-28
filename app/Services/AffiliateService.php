<?php

namespace App\Services;

use App\Models\AffiliateSetting;
use App\Models\Referral;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

/**
 * Logika program affiliate: komisi otomatis saat langganan berbayar lunas,
 * dan cashback (potongan) untuk user yang daftar via referral.
 *
 * Aturan penting:
 *  - Komisi HANYA untuk plan berbayar Basic/Enterprise (bukan Starter/deposit, bukan Customize).
 *  - Komisi tercatat saat user BERLANGGANAN (bukan saat register pakai ref).
 *  - Affiliate harus 'active' (disetujui Superadmin).
 */
class AffiliateService
{
    /** Plan yang memenuhi syarat komisi & cashback. */
    public const ELIGIBLE_PLANS = ['basic', 'enterprise'];

    /** Referral aktif (affiliate 'active') untuk tenant tsb, atau null. */
    public static function eligibleReferral(?int $tenantId): ?Referral
    {
        if (! $tenantId) {
            return null;
        }
        $ref = Referral::where('tenant_id', $tenantId)->first();
        if (! $ref) {
            return null;
        }
        $aff = $ref->affiliate;
        return ($aff && $aff->status === 'active') ? $ref : null;
    }

    /** Persen cashback yang berlaku untuk (tenant, plan). 0 bila tak berlaku. */
    public static function cashbackPercentFor(?int $tenantId, string $planKey): float
    {
        if (! in_array($planKey, self::ELIGIBLE_PLANS, true)) {
            return 0.0;
        }
        if (! self::eligibleReferral($tenantId)) {
            return 0.0;
        }
        return (float) AffiliateSetting::current()->cashback_percent;
    }

    /** Nominal cashback (rupiah, dibulatkan) untuk potongan checkout. */
    public static function cashbackAmount(?int $tenantId, string $planKey, int $amount): int
    {
        $pct = self::cashbackPercentFor($tenantId, $planKey);
        return $pct > 0 ? (int) round($amount * $pct / 100) : 0;
    }

    /**
     * Catat komisi affiliate saat langganan berbayar LUNAS (one-time per referral).
     * Dipanggil dari webhook billing setelah subscription->status = 'paid'.
     * Non-fatal: dibungkus try/catch supaya tak pernah menggagalkan webhook.
     */
    public static function rewardOnSubscription(Subscription $sub): void
    {
        try {
            if (! in_array($sub->plan, self::ELIGIBLE_PLANS, true)) {
                return;
            }
            $ref = self::eligibleReferral($sub->tenant_id);
            if (! $ref) {
                return;
            }
            // One-time: kalau komisi sudah pernah tercatat (subscribed & bernilai), jangan dobel.
            if ($ref->status === 'subscribed' && (float) $ref->commission_amount > 0) {
                return;
            }

            $setting    = AffiliateSetting::current();
            $commission = $setting->commission_type === 'percent'
                ? round((float) $sub->amount * (float) $setting->commission_value / 100, 2)
                : (float) $setting->commission_value;

            $ref->status            = 'subscribed';
            $ref->subscribed_at     = $ref->subscribed_at ?? now();
            $ref->commission_amount = $commission;
            // 'pending' = sudah jadi hak affiliate & bisa diajukan withdraw (kalau > 0).
            $ref->commission_status = 'pending';
            $ref->save();
        } catch (\Throwable $e) {
            Log::warning('AffiliateService::rewardOnSubscription gagal: ' . $e->getMessage());
        }
    }
}
