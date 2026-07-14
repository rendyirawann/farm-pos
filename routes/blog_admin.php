<?php

use App\Http\Controllers\Blog\Admin\CategoryController;
use App\Http\Controllers\Blog\Admin\PostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modul BLOG — ADMIN (host utama, /admin/blog*)
|--------------------------------------------------------------------------
| Di-mount di web.php dgn middleware ['auth','forbid-banned-user','can:blog.manage'].
| Khusus Superadmin (permission blog.manage). File terpisah agar mudah dikelola.
| Endpoint DataTables (GET) didaftarkan SEBELUM Route::resource agar tidak
| bentrok dengan {post}/{category}.
*/

// Artikel
Route::get('/admin/blog/data', [PostController::class, 'getDataPosts'])->name('blog.admin.posts.data');
Route::resource('/admin/blog', PostController::class)
    ->parameters(['blog' => 'post'])
    ->names('blog.admin.posts')
    ->only(['index', 'store', 'show', 'edit', 'update', 'destroy']);

// Kategori
Route::get('/admin/blog-categories/data', [CategoryController::class, 'getData'])->name('blog.admin.categories.data');
Route::resource('/admin/blog-categories', CategoryController::class)
    ->parameters(['blog-categories' => 'category'])
    ->names('blog.admin.categories')
    ->only(['index', 'store', 'update', 'destroy']);
