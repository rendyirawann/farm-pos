<?php

use App\Http\Controllers\Blog\PublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subdomain BLOG — PUBLIK (blog.mooda.id)
|--------------------------------------------------------------------------
| Dilayani app yang sama via Octane. Hanya menampilkan artikel PUBLISHED.
| Route admin (kelola artikel) ada di routes/blog_admin.php.
| Catatan urutan: route spesifik didaftarkan SEBELUM '/{slug}' agar tidak
| tertangkap sebagai slug.
*/

Route::get('/', [PublicController::class, 'index'])->name('blog.home');
Route::get('/sitemap.xml', [PublicController::class, 'sitemap'])->name('blog.sitemap');
Route::get('/kategori/{slug}', [PublicController::class, 'category'])->name('blog.category');
Route::get('/{slug}', [PublicController::class, 'show'])->name('blog.show');
