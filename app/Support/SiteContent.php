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

    /** Registry field per situs (dari config/site_content.php) untuk form admin. */
    public static function sites(): array
    {
        return config('site_content.sites', []);
    }
}
