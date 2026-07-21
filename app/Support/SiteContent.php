<?php

namespace App\Support;

use App\Models\SiteOption;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * CMS konten landing per-situs (mooda.id / blog / affiliate).
 * Nilai disimpan di SiteOption dgn key ber-namespace "{site}.{field}".
 * Blade memanggil helper sc()/sc_img() dengan DEFAULT inline -> halaman tak pernah kosong.
 */
class SiteContent
{
    /** Peta seluruh SiteOption (cache Redis; di-forget saat admin menyimpan). */
    protected static function map(): array
    {
        return Cache::remember('site_options_map', 3600, function () {
            return SiteOption::pluck('value', 'key')->all();
        });
    }

    public static function flush(): void
    {
        Cache::forget('site_options_map');
    }

    protected static function raw(string $site, string $key): ?string
    {
        $val = static::map()["{$site}.{$key}"] ?? null;
        return ($val === null || $val === '') ? null : $val;
    }

    /** Teks: nilai tersimpan bila ada, selain itu default (literal di blade). */
    public static function text(string $site, string $key, string $default = ''): string
    {
        return static::raw($site, $key) ?? $default;
    }

    /**
     * URL gambar. Nilai tersimpan = path di disk 'public' (mis. "site/landing/hero.png").
     * Default = path aset publik (mis. "assets/media/logos/mooda-logo.png").
     */
    public static function image(string $site, string $key, string $default = ''): string
    {
        $val = static::raw($site, $key);
        if ($val) {
            return Storage::disk('public')->url($val);
        }
        return $default !== '' ? asset($default) : '';
    }

    /** Registry field per situs (field tetap + repeater) untuk form admin. */
    public static function sites(): array
    {
        $sites = config('site_content.sites', []);
        $reps  = config('site_repeaters.sites', []);
        foreach ($sites as $k => &$s) {
            $s['repeaters'] = $reps[$k] ?? [];
        }
        return $sites;
    }

    /** Data seksi repeater: JSON tersimpan bila ada, selain itu default dari config. */
    public static function repeater(string $site, string $key): array
    {
        $val = static::raw($site, $key);
        if ($val) {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return config("site_repeaters.sites.$site.$key.default", []);
    }

    /** Registry ikon (key => [label, svg]). */
    public static function icons(): array
    {
        return config('site_icons', []);
    }

    /** Render <svg> ikon berdasarkan key; string kosong bila key tak dikenal. */
    public static function iconSvg(string $key, string $class = 'h-6 w-6', float $stroke = 1.7): string
    {
        $ic = config("site_icons.$key");
        if (! $ic) {
            return '';
        }
        return '<svg class="' . e($class) . '" fill="none" stroke="currentColor" stroke-width="' . $stroke
            . '" viewBox="0 0 24 24">' . $ic['svg'] . '</svg>';
    }

    /** Peta warna preset (key => [label, grad, hex]). */
    public static function colors(): array
    {
        return config('site_repeaters.colors', []);
    }

    public static function color(string $key): array
    {
        $c = config('site_repeaters.colors', []);
        return $c[$key] ?? (reset($c) ?: ['grad' => 'from-indigo-500 to-blue-500', 'hex' => '#4f46e5', 'label' => 'Indigo']);
    }

    /** URL gambar item repeater (path di disk public). */
    public static function itemImage(?string $path): string
    {
        return $path ? Storage::disk('public')->url($path) : '';
    }
}
