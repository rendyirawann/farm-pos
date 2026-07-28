<?php

use App\Http\Controllers\Affiliate\PortalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subdomain AFFILIATE — PORTAL PUBLIK (affiliate.mooda.id)
|--------------------------------------------------------------------------
| Untuk afiliator EKSTERNAL (bukan pengguna POS): landing, daftar, login, dashboard.
| Manajemen afiliator (Superadmin) ada di mooda.id/admin (routes/affiliate_admin.php).
*/

// Panel admin TIDAK dilayani di host affiliate -> alihkan ke domain utama mooda.id.
Route::any('admin/{any?}', fn () => redirect()->away('https://mooda.id/' . request()->path()))->where('any', '.*');

// Link tracking referral: set cookie lalu arahkan ke pendaftaran tenant di mooda.id.
Route::get('/r/{code}', [PortalController::class, 'track'])->name('affiliate.track');

// Publik
Route::get('/', [PortalController::class, 'landing'])->name('affiliate.home');
Route::get('/daftar', [PortalController::class, 'showRegister'])->name('affiliate.register');
Route::post('/daftar', [PortalController::class, 'register'])->name('affiliate.register.post');
Route::get('/masuk', [PortalController::class, 'showLogin'])->name('affiliate.login');
Route::post('/masuk', [PortalController::class, 'login'])->name('affiliate.login.post');
Route::post('/keluar', [PortalController::class, 'logout'])->name('affiliate.logout');

// Lupa / reset kata sandi affiliate.
Route::get('/lupa-password', [PortalController::class, 'showForgotPassword'])->name('affiliate.password.request');
Route::post('/lupa-password', [PortalController::class, 'sendResetLink'])->name('affiliate.password.email');
Route::get('/reset-password/{token}', [PortalController::class, 'showResetPassword'])->name('affiliate.password.reset');
Route::post('/reset-password', [PortalController::class, 'resetPassword'])->name('affiliate.password.update');

// Dashboard afiliator (guard auth + role 'affiliate' di controller).
Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('affiliate.dashboard');
Route::get('/dashboard/link', [PortalController::class, 'linkPage'])->name('affiliate.link');
Route::get('/dashboard/referral', [PortalController::class, 'referralsPage'])->name('affiliate.referrals');
Route::get('/dashboard/komisi', [PortalController::class, 'komisiPage'])->name('affiliate.komisi');
Route::get('/dashboard/withdraw', [PortalController::class, 'withdrawPage'])->name('affiliate.withdraw');
Route::post('/dashboard/withdraw', [PortalController::class, 'withdrawSubmit'])->name('affiliate.withdraw.submit');
