<?php

use App\Support\SiteContent;

if (! function_exists('sc')) {
    /** Teks konten situs (CMS landing). $default = literal saat ini (fallback). */
    function sc(string $site, string $key, string $default = ''): string
    {
        return SiteContent::text($site, $key, $default);
    }
}

if (! function_exists('sc_img')) {
    /** URL gambar konten situs (CMS landing). $default = path aset publik fallback. */
    function sc_img(string $site, string $key, string $default = ''): string
    {
        return SiteContent::image($site, $key, $default);
    }
}
