<?php

use App\Http\Controllers\Backend\Affiliate\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Modul AFFILIATE — ADMIN (Superadmin, mooda.id/admin/affiliates*)
|--------------------------------------------------------------------------
| Di-mount di web.php dgn middleware ['auth','forbid-banned-user','can:affiliate.manage'].
*/

Route::get('/admin/affiliates/data', [AdminController::class, 'getData'])->name('affiliates.data');
Route::get('/admin/affiliates/{id}/referrals', [AdminController::class, 'referrals'])->name('affiliates.referrals');
Route::post('/admin/affiliates/{id}/status', [AdminController::class, 'updateStatus'])->name('affiliates.status');
Route::post('/admin/referrals/{id}/pay', [AdminController::class, 'payReferral'])->name('affiliates.pay');
Route::delete('/admin/affiliates/{id}', [AdminController::class, 'destroy'])->name('affiliates.destroy');
Route::post('/admin/affiliates', [AdminController::class, 'store'])->name('affiliates.store');
Route::get('/admin/affiliates', [AdminController::class, 'index'])->name('affiliates.index');
