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
