<?php

use App\Http\Controllers\Backend\Farm\AdjustmentController;
use App\Http\Controllers\Backend\Farm\AgentController;
use App\Http\Controllers\Backend\Farm\DepositController;
use App\Http\Controllers\Backend\Farm\EggProductionController;
use App\Http\Controllers\Backend\Farm\FarmDashboardController;
use App\Http\Controllers\Backend\Farm\ItemController;
use App\Http\Controllers\Backend\Farm\ReceivableController;
use App\Http\Controllers\Backend\Farm\ReportController;
use App\Http\Controllers\Backend\Farm\StockInController;
use App\Http\Controllers\Backend\Farm\StockOutController;
use App\Http\Controllers\Backend\Farm\SupplierController;
use App\Http\Controllers\Backend\Farm\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MODUL FARM (vertical 'farm') — inventori & perdagangan ternak
|--------------------------------------------------------------------------
| Dimuat dari routes/web.php di dalam grup auth + vertical:farm.
| Tidak menyentuh modul F&B/laundry sama sekali.
*/

Route::get('/admin/farm', [FarmDashboardController::class, 'index'])->name('farm.dashboard');
Route::post('/admin/farm/onboarding-toggle', [FarmDashboardController::class, 'toggleOnboarding'])->name('farm.onboarding-toggle');

// ---------- MASTER ----------
Route::get('/admin/farm/suppliers', [SupplierController::class, 'index'])->name('farm.suppliers.index');
Route::post('/admin/farm/suppliers', [SupplierController::class, 'store'])->name('farm.suppliers.store');
Route::put('/admin/farm/suppliers/{supplier}', [SupplierController::class, 'update'])->name('farm.suppliers.update');
Route::post('/admin/farm/suppliers/{supplier}/toggle', [SupplierController::class, 'toggle'])->name('farm.suppliers.toggle');
Route::delete('/admin/farm/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('farm.suppliers.destroy');

Route::get('/admin/farm/agents', [AgentController::class, 'index'])->name('farm.agents.index');
Route::post('/admin/farm/agents', [AgentController::class, 'store'])->name('farm.agents.store');
Route::put('/admin/farm/agents/{agent}', [AgentController::class, 'update'])->name('farm.agents.update');
Route::post('/admin/farm/agents/{agent}/toggle', [AgentController::class, 'toggle'])->name('farm.agents.toggle');
Route::delete('/admin/farm/agents/{agent}', [AgentController::class, 'destroy'])->name('farm.agents.destroy');

Route::get('/admin/farm/items', [ItemController::class, 'index'])->name('farm.items.index');
Route::post('/admin/farm/items', [ItemController::class, 'store'])->name('farm.items.store');
Route::put('/admin/farm/items/{item}', [ItemController::class, 'update'])->name('farm.items.update');
Route::post('/admin/farm/items/{item}/toggle', [ItemController::class, 'toggle'])->name('farm.items.toggle');
Route::delete('/admin/farm/items/{item}', [ItemController::class, 'destroy'])->name('farm.items.destroy');

// ---------- STOCK IN ----------
Route::get('/admin/farm/stock-in', [StockInController::class, 'index'])->name('farm.stock-in.index');
Route::get('/admin/farm/stock-in/create', [StockInController::class, 'create'])->name('farm.stock-in.create');
Route::post('/admin/farm/stock-in', [StockInController::class, 'store'])->name('farm.stock-in.store');
Route::get('/admin/farm/stock-in/{stockIn}', [StockInController::class, 'show'])->name('farm.stock-in.show');
Route::get('/admin/farm/stock-in/{stockIn}/receipt', [StockInController::class, 'receipt'])->name('farm.stock-in.receipt');
Route::get('/admin/farm/stock-in/{stockIn}/pdf', [StockInController::class, 'pdf'])->name('farm.stock-in.pdf');
Route::post('/admin/farm/stock-in/{stockIn}/photo', [StockInController::class, 'uploadPhoto'])->name('farm.stock-in.photo');
Route::delete('/admin/farm/stock-in/{stockIn}/photo', [StockInController::class, 'deletePhoto'])->name('farm.stock-in.photo.delete');
// Realisasi = hasil timbang ulang barang yang benar-benar diterima. Satu nota satu realisasi.
Route::post('/admin/farm/stock-in/{stockIn}/realization', [StockInController::class, 'storeRealization'])->name('farm.stock-in.realization');
Route::delete('/admin/farm/stock-in/{stockIn}/realization', [StockInController::class, 'destroyRealization'])->name('farm.stock-in.realization.delete');
Route::post('/admin/farm/stock-in/{stockIn}/payment', [StockInController::class, 'setPayment'])->name('farm.stock-in.payment');

// ---------- DEPOSIT SUPPLIER ----------
Route::get('/admin/farm/deposits', [DepositController::class, 'index'])->name('farm.deposits.index');
Route::get('/admin/farm/deposits/{supplier}', [DepositController::class, 'show'])->name('farm.deposits.show');
Route::post('/admin/farm/deposits/{supplier}/topup', [DepositController::class, 'topup'])->name('farm.deposits.topup');
Route::post('/admin/farm/deposits/{supplier}/adjust', [DepositController::class, 'adjust'])->name('farm.deposits.adjust');
Route::delete('/admin/farm/deposits/entry/{deposit}', [DepositController::class, 'reverse'])->name('farm.deposits.reverse');

// ---------- STOCK OUT ----------
Route::get('/admin/farm/stock-out', [StockOutController::class, 'index'])->name('farm.stock-out.index');
Route::get('/admin/farm/stock-out/create', [StockOutController::class, 'create'])->name('farm.stock-out.create');
Route::post('/admin/farm/stock-out/preview', [StockOutController::class, 'preview'])->name('farm.stock-out.preview');
Route::post('/admin/farm/stock-out', [StockOutController::class, 'store'])->name('farm.stock-out.store');
Route::get('/admin/farm/stock-out/{stockOut}', [StockOutController::class, 'show'])->name('farm.stock-out.show');
Route::get('/admin/farm/stock-out/{stockOut}/receipt', [StockOutController::class, 'receipt'])->name('farm.stock-out.receipt');

// ---------- PRODUKSI TELUR ----------
Route::get('/admin/farm/eggs', [EggProductionController::class, 'index'])->name('farm.eggs.index');
Route::post('/admin/farm/eggs', [EggProductionController::class, 'store'])->name('farm.eggs.store');
Route::get('/admin/farm/eggs/{eggProduction}/detail', [EggProductionController::class, 'detail'])->name('farm.eggs.detail');
Route::delete('/admin/farm/eggs/{eggProduction}', [EggProductionController::class, 'destroy'])->name('farm.eggs.destroy');

// ---------- PENYESUAIAN STOK ----------
Route::get('/admin/farm/adjustments', [AdjustmentController::class, 'index'])->name('farm.adjustments.index');
Route::post('/admin/farm/adjustments', [AdjustmentController::class, 'store'])->name('farm.adjustments.store');
Route::post('/admin/farm/adjustments/{adjustment}/approve', [AdjustmentController::class, 'approve'])->name('farm.adjustments.approve');
Route::get('/admin/farm/items/{item}/lots', [AdjustmentController::class, 'lots'])->name('farm.items.lots');

// ---------- LAPORAN ----------
Route::get('/admin/farm/reports', [ReportController::class, 'index'])->name('farm.reports.index');
Route::get('/admin/farm/reports/pdf', [ReportController::class, 'pdf'])->name('farm.reports.pdf');

// ---------- PIUTANG ----------
Route::get('/admin/farm/receivables', [ReceivableController::class, 'index'])->name('farm.receivables.index');
Route::get('/admin/farm/receivables/agent/{agent}', [ReceivableController::class, 'card'])->name('farm.receivables.card');
Route::post('/admin/farm/receivables/{stockOut}/pay', [ReceivableController::class, 'pay'])->name('farm.receivables.pay');

// ---------- GUDANG (tampilan stok) ----------
Route::get('/admin/farm/warehouse', [WarehouseController::class, 'index'])->name('farm.warehouse.index');
// Stok per supplier + rincian HPP — hanya untuk dilihat.
Route::get('/admin/farm/warehouse/stock', [WarehouseController::class, 'stock'])->name('farm.warehouse.stock');
Route::get('/admin/farm/warehouse/stock/{supplier}', [WarehouseController::class, 'stockDetail'])->name('farm.warehouse.stock.detail');
// Buka/tutup gudang dihapus — koreksi stok hanya lewat Penyesuaian Stok.
