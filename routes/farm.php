<?php

use App\Http\Controllers\Backend\Farm\AdjustmentController;
use App\Http\Controllers\Backend\Farm\AgentController;
use App\Http\Controllers\Backend\Farm\EggProductionController;
use App\Http\Controllers\Backend\Farm\FarmDashboardController;
use App\Http\Controllers\Backend\Farm\ItemController;
use App\Http\Controllers\Backend\Farm\ReceivableController;
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
Route::delete('/admin/farm/eggs/{eggProduction}', [EggProductionController::class, 'destroy'])->name('farm.eggs.destroy');

// ---------- PENYESUAIAN STOK ----------
Route::get('/admin/farm/adjustments', [AdjustmentController::class, 'index'])->name('farm.adjustments.index');
Route::post('/admin/farm/adjustments', [AdjustmentController::class, 'store'])->name('farm.adjustments.store');
Route::post('/admin/farm/adjustments/{adjustment}/approve', [AdjustmentController::class, 'approve'])->name('farm.adjustments.approve');
Route::get('/admin/farm/items/{item}/lots', [AdjustmentController::class, 'lots'])->name('farm.items.lots');

// ---------- PIUTANG ----------
Route::get('/admin/farm/receivables', [ReceivableController::class, 'index'])->name('farm.receivables.index');
Route::get('/admin/farm/receivables/agent/{agent}', [ReceivableController::class, 'card'])->name('farm.receivables.card');
Route::post('/admin/farm/receivables/{stockOut}/pay', [ReceivableController::class, 'pay'])->name('farm.receivables.pay');

// ---------- BUKA / TUTUP GUDANG ----------
Route::get('/admin/farm/warehouse', [WarehouseController::class, 'index'])->name('farm.warehouse.index');
Route::post('/admin/farm/warehouse/open', [WarehouseController::class, 'open'])->name('farm.warehouse.open');
Route::post('/admin/farm/warehouse/{session}/close', [WarehouseController::class, 'close'])->name('farm.warehouse.close');
