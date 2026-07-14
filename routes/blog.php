<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subdomain BLOG — blog.mooda.id
|--------------------------------------------------------------------------
| Dilayani oleh app yang sama via Octane (bukan stack terpisah).
| Sementara: halaman "segera hadir". Modul blog menyusul
| (posts, kategori/tag, admin CRUD, halaman publik list+detail, SEO/sitemap).
*/

Route::get('/', fn () => view('subdomain.coming-soon', [
    'brand'     => 'Blog Mooda',
    'tagline'   => 'Tips, panduan, & cerita seputar bisnis kuliner dan sistem kasir.',
    'icon'      => '📝',
    'subdomain' => 'blog.mooda.id',
]))->name('blog.home');
