<?php

namespace App\Tenancy;

use App\Models\Tenant;

/**
 * Helper paket langganan. Membaca config/plans.php.
 */
class Plan
{
    public static function all(): array
    {
        return config('plans.plans', []);
    }

    public static function get(?string $key): ?array
    {
        if (!$key) {
            return null;
        }
        return config("plans.plans.$key");
    }

    public static function price(?string $key): int
    {
        return (int) (self::get($key)['price'] ?? 0);
    }

    /**
     * Daftar pilihan durasi langganan. Jika paket tidak mendefinisikan 'periods',
     * fallback ke 1 bulan memakai harga dasar.
     */
    public static function periods(?string $key): array
    {
        $plan = self::get($key);
        if (!$plan) {
            return [];
        }
        if (!empty($plan['periods'])) {
            return array_values($plan['periods']);
        }
        return [['months' => 1, 'price_per_month' => (int) ($plan['price'] ?? 0), 'label' => 'Bulanan']];
    }

    /**
     * Total harga (server-side, anti-manipulasi) untuk durasi tertentu.
     * Return null bila jumlah bulan tidak ditawarkan paket ini.
     */
    public static function periodAmount(?string $key, int $months): ?int
    {
        foreach (self::periods($key) as $p) {
            if ((int) $p['months'] === $months) {
                return (int) $p['price_per_month'] * $months;
            }
        }
        return null;
    }

    public static function name(?string $key): string
    {
        return self::get($key)['name'] ?? '-';
    }

    public static function modules(?string $key): array
    {
        return self::get($key)['modules'] ?? [];
    }

    /** Paket konsultasi (WhatsApp), bukan checkout Midtrans. */
    public static function isContact(?string $key): bool
    {
        return (bool) (self::get($key)['contact'] ?? false);
    }

    /** Nomor WhatsApp untuk paket konsultasi. */
    public static function wa(?string $key): ?string
    {
        return self::get($key)['wa'] ?? null;
    }

    public static function trialDays(): int
    {
        return (int) config('plans.trial_days', 14);
    }

    /**
     * Mode maintenance = pembelian/pendaftaran paket dinonaktifkan sementara.
     * Sumber tunggal: config('billing.purchase_enabled') (env BILLING_PURCHASE_ENABLED).
     * Dipakai landing page untuk menonaktifkan tombol "Pilih Starter" & "Daftar".
     */
    public static function maintenance(): bool
    {
        return ! (bool) config('billing.purchase_enabled', false);
    }

    public static function maintenanceText(): string
    {
        return (string) config('billing.maintenance_text', 'Available soon — Maintenance Midtrans Server');
    }

    /**
     * Apakah paket tenant mengizinkan modul tertentu.
     * Superadmin (tenant null) selalu boleh.
     */
    public static function tenantAllows(?Tenant $tenant, string $module): bool
    {
        if (!$tenant) {
            return true; // Superadmin / konteks tanpa tenant
        }

        // Trial diberi akses penuh (biar bisa coba semua fitur)
        if ($tenant->isOnTrial() && $tenant->hasActiveAccess()) {
            return true;
        }

        return in_array($module, self::modules($tenant->plan), true);
    }
}
