<?php

namespace App\Support;

use App\Models\PaymentSetting;
use App\Services\Tripay\Tripay;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Sumber tunggal "driver payment aktif" untuk checkout langganan & top-up deposit.
 *
 * Prioritas: nilai dari DB (payment_settings, dikelola Superadmin) -> fallback config('billing.driver').
 * Hanya SATU driver aktif pada satu waktu.
 */
class Billing
{
    /** Driver yang didukung + label tampil. */
    public const DRIVERS = [
        'midtrans' => 'Midtrans (Snap)',
        'doku'     => 'DOKU (Virtual Account)',
        'tripay'   => 'Tripay',
    ];

    /** Driver payment yang sedang aktif (midtrans|doku|tripay). */
    public static function driver(): string
    {
        $driver = self::storedDriver() ?: (string) config('billing.driver', 'midtrans');

        return array_key_exists($driver, self::DRIVERS) ? $driver : 'midtrans';
    }

    /** Baca driver tersimpan di DB (null bila belum ada / tabel belum dimigrasi). */
    public static function storedDriver(): ?string
    {
        try {
            // Cache singkat agar tidak query tiap request (Octane). Di-flush saat update.
            return Cache::remember('payment_active_driver', 300, function () {
                return optional(PaymentSetting::query()->first())->active_driver;
            });
        } catch (Throwable $e) {
            // Tabel belum ada / DB error -> jatuh ke fallback config.
            return null;
        }
    }

    /** Set driver aktif (dipakai Superadmin). Hanya nilai valid diterima. */
    public static function setDriver(string $driver): void
    {
        if (! array_key_exists($driver, self::DRIVERS)) {
            return;
        }

        $row = PaymentSetting::query()->first();
        if ($row) {
            $row->update(['active_driver' => $driver]);
        } else {
            PaymentSetting::create(['active_driver' => $driver]);
        }

        Cache::forget('payment_active_driver');
    }

    /** Apakah kredensial driver tsb sudah terisi (untuk peringatan di panel). */
    public static function isConfigured(string $driver): bool
    {
        return match ($driver) {
            'midtrans' => filled(config('services.midtrans.server_key')) && filled(config('services.midtrans.client_key')),
            'doku'     => filled(config('services.doku.client_id')) && filled(config('services.doku.secret_key')) && filled(config('services.doku.private_key')),
            'tripay'   => (new Tripay())->isConfigured(),
            default    => false,
        };
    }
}
