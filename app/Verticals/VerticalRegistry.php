<?php

namespace App\Verticals;

/**
 * Registry vertical (F&B / Laundry / Retail). Sumber tunggal metadata vertical + resolusi host.
 *
 * Prinsip: kode BERSAMA tidak boleh `if ($vertical === 'laundry')`. Percabangan perilaku
 * dilakukan lewat pemisahan folder modul (Backend/Laundry/*) + registry ini.
 */
class VerticalRegistry
{
    /** Semua vertical terdaftar (termasuk yang disabled). */
    public static function all(): array
    {
        return (array) config('verticals.list', []);
    }

    /** Vertical yang aktif dipakai (enabled !== false). */
    public static function enabled(): array
    {
        return array_filter(self::all(), fn ($v) => ($v['enabled'] ?? true) !== false);
    }

    public static function default(): string
    {
        return (string) config('verticals.default', 'fnb');
    }

    public static function exists(?string $vertical): bool
    {
        return $vertical !== null && array_key_exists($vertical, self::all());
    }

    /** Normalisasi: nilai valid & enabled, else default. */
    public static function normalize(?string $vertical): string
    {
        return (self::exists($vertical) && (self::all()[$vertical]['enabled'] ?? true) !== false)
            ? $vertical
            : self::default();
    }

    public static function meta(?string $vertical): array
    {
        return self::all()[self::normalize($vertical)] ?? [];
    }

    public static function label(?string $vertical): string
    {
        return self::meta($vertical)['label'] ?? ucfirst((string) $vertical);
    }

    public static function name(?string $vertical): string
    {
        return self::meta($vertical)['name'] ?? 'Mooda';
    }

    public static function host(?string $vertical): ?string
    {
        return self::meta($vertical)['host'] ?? null;
    }

    /** Tentukan vertical dari host request (mis. laundry.mooda.id -> 'laundry'). */
    public static function fromHost(?string $host): string
    {
        $host = strtolower((string) $host);
        foreach (self::all() as $key => $meta) {
            $vhost = strtolower((string) ($meta['host'] ?? ''));
            if ($vhost !== '' && $host === $vhost) {
                return $key;
            }
        }
        // Subdomain cocok (laundry.<domain>) walau domain utama beda (staging, dsb).
        foreach (self::all() as $key => $meta) {
            if ($key !== self::default() && str_starts_with($host, $key . '.')) {
                return $key;
            }
        }
        return self::default();
    }

    /** Vertical aktif untuk request saat ini (di-set oleh middleware ResolveVertical). */
    public static function current(): string
    {
        return self::normalize(config('app.vertical', self::default()));
    }
}
