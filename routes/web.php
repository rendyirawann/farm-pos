<?php

use Illuminate\Support\Facades\Route;

// Dashboard
use App\Http\Controllers\Backend\Dashboard\DashboardAdminController;
// Profile
use App\Http\Controllers\Backend\MyProfile\AccountController;
use App\Http\Controllers\Backend\MyProfile\ProfileController;
use App\Http\Controllers\Backend\MyProfile\SecurityController;
use App\Http\Controllers\Backend\MyProfile\ActivityController;
use App\Http\Controllers\Backend\MyProfile\LoginSessionController;
// User Management
use App\Http\Controllers\Backend\UserManagement\UserController;
use App\Http\Controllers\Backend\UserManagement\RoleController;
// Help / Log
use App\Http\Controllers\Backend\Help\LogActivityController;
// POS
use App\Http\Controllers\Backend\Kasir\KasirController;
use App\Http\Controllers\Backend\Kasir\ShiftController;
use App\Http\Controllers\Backend\Kitchen\KitchenController;
// Data Master
use App\Http\Controllers\Backend\Master\CategoriesController;
use App\Http\Controllers\Backend\Master\MenuController;
use App\Http\Controllers\Backend\Master\PromoController;
use App\Http\Controllers\Backend\Master\DiningTableController;
// Reports
use App\Http\Controllers\Backend\Report\ItemSalesReportController;
use App\Http\Controllers\Backend\Report\SalesReportController;
use App\Http\Controllers\Backend\Finance\ExpenseController;
// Settings / Billing / Tenant
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Backend\DownloadAppController;
use App\Http\Controllers\Backend\Billing\BillingController;
use App\Http\Controllers\Backend\Billing\DepositController;
use App\Http\Controllers\Backend\Superadmin\TenantController;
use App\Http\Controllers\Backend\Superadmin\DepositSettingController;
use App\Http\Controllers\Backend\Superadmin\DokuChannelController;
use App\Http\Controllers\Backend\Superadmin\PartnerLogoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ===== Subdomain: Blog (blog.mooda.id) & Affiliate (affiliate.mooda.id) =====
// Dilayani app yang sama via Octane. Didaftarkan SEBELUM route '/' landing agar
// request ke host subdomain diprioritaskan; mooda.id sendiri tetap ke landing.
Route::domain('blog.mooda.id')->group(base_path('routes/blog.php'));
Route::domain('affiliate.mooda.id')->group(base_path('routes/affiliate.php'));

// Modul BLOG — ADMIN (host utama, /admin/blog*). File route terpisah, khusus
// Superadmin (can:blog.manage). Bukan fitur tenant -> TANPA 'subscribed'.
Route::middleware(['auth', 'forbid-banned-user', 'can:blog.manage'])
    ->group(base_path('routes/blog_admin.php'));

// Modul AFFILIATE — ADMIN (host utama, /admin/affiliates*). Khusus Superadmin.
Route::middleware(['auth', 'forbid-banned-user', 'can:affiliate.manage'])
    ->group(base_path('routes/affiliate_admin.php'));

// Program Affiliate untuk OWNER tenant (gabung + dashboard di dalam POS mooda.id/admin).
Route::middleware(['auth', 'forbid-banned-user', 'can:affiliate.refer'])->group(function () {
    Route::get('/admin/affiliate-saya', [\App\Http\Controllers\Backend\Affiliate\MyAffiliateController::class, 'index'])->name('affiliate.my');
    Route::post('/admin/affiliate-saya/join', [\App\Http\Controllers\Backend\Affiliate\MyAffiliateController::class, 'join'])->name('affiliate.my.join');
});

// Halaman Depan: Landing Page SaaS
Route::get('/', function () {
    return view('landing', ['partnerLogos' => \App\Models\PartnerLogo::forLanding()]);
})->name('landing');

// SEO: sitemap.xml (dinamis — domain mengikuti APP_URL). Dirujuk di robots.txt.
Route::get('/sitemap.xml', function () {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
        . '  <url>' . "\n"
        . '    <loc>' . e(url('/')) . '</loc>' . "\n"
        . '    <lastmod>' . now()->toDateString() . '</lastmod>' . "\n"
        . '    <changefreq>weekly</changefreq>' . "\n"
        . '    <priority>1.0</priority>' . "\n"
        . '  </url>' . "\n"
        . '</urlset>';
    return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
})->name('sitemap');

// Group Middleware untuk User yang sudah Login (+ blokir user yang di-ban)
Route::middleware(['auth', 'forbid-banned-user'])->group(function () {

    // --- DASHBOARD (accessible by ALL authenticated roles) ---
    Route::get('/admin/dashboard', [DashboardAdminController::class, 'index'])->name('dashboard');
    // Toggle mode tampilan Superadmin: analytics (platform) <-> pos (kasir)
    Route::get('/admin/view-mode/{mode}', [DashboardAdminController::class, 'switchMode'])->name('view-mode.switch');
    // Superadmin memilih toko yang dioperasikan di mode POS
    Route::get('/admin/pos-tenant/{id}', [DashboardAdminController::class, 'setPosTenant'])->name('pos-tenant.set');

    // --- MY ACCOUNT / PROFILE (accessible by ALL authenticated users) ---
    Route::get('/admin/my-account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/admin/my-account/{id}/avatar', [AccountController::class, 'editAvatar'])->name('avatar-edit');
    Route::post('/admin/my-account/{id}/update-avatar', [AccountController::class, 'updateAvatar'])->name('avatar-update');

    Route::resource('/admin/my-profile', ProfileController::class);
    Route::resource('/admin/my-security', SecurityController::class);
    Route::post('/admin/my-security', [SecurityController::class, 'store'])->name('change.password');

    Route::get('/admin/my-activity', [ActivityController::class, 'index'])->name('my-activity.index');
    Route::get('/admin/mget-my-activity', [ActivityController::class, 'getActivity'])->name('get-my-activity');

    Route::get('/admin/mmy-login-session', [LoginSessionController::class, 'index'])->name('my-login-session.index');
    Route::get('/admin/mget-my-login-session', [LoginSessionController::class, 'getLoginSession'])->name('get-my-login-session');

    // --- SETTINGS (accessible by ALL authenticated users) ---
    Route::get('/admin/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/admin/settings/update', [SettingController::class, 'update'])->name('settings.update');

    // --- APLIKASI TABLET (APK) — hanya tenant berlangganan aktif ---
    Route::middleware('subscribed')->group(function () {
        Route::get('/admin/download-app', [DownloadAppController::class, 'index'])->name('download-app');
        Route::get('/admin/download-app/apk', [DownloadAppController::class, 'apk'])->name('download-app.apk');
    });

    // ====================================================
    // BILLING / LANGGANAN — Owner & admin tenant (TANPA 'subscribed' agar bisa bayar saat belum aktif)
    // ====================================================
    Route::middleware('can:view_billing')->group(function () {
        Route::get('/admin/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('/admin/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');

        // Plan Deposit / Poin
        Route::get('/admin/deposit', [DepositController::class, 'index'])->name('deposit.index');
        Route::post('/admin/deposit/checkout', [DepositController::class, 'checkout'])->name('deposit.checkout');
        Route::post('/admin/deposit/switch', [DepositController::class, 'switchToDeposit'])->name('deposit.switch');
    });

    // ====================================================
    // MANAJEMEN TENANT — Superadmin (lintas tenant)
    // ====================================================
    Route::middleware('can:view_tenants')->group(function () {
        Route::get('/admin/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('/admin/tenants/data', [TenantController::class, 'getData'])->name('tenants.data');
        // create HARUS sebelum {id} agar "create" tak dianggap id.
        Route::get('/admin/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
        Route::post('/admin/tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::get('/admin/tenants/{id}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
        Route::post('/admin/tenants/{id}/update', [TenantController::class, 'update'])->name('tenants.update');
        Route::post('/admin/tenants/{id}/users', [TenantController::class, 'storeUser'])->name('tenants.users.store');
        Route::get('/admin/tenants/{id}', [TenantController::class, 'show'])->name('tenants.show');
        Route::post('/admin/tenants/{id}/toggle-active', [TenantController::class, 'toggleActive'])->name('tenants.toggle-active');
        Route::post('/admin/tenants/{id}/reset-data', [TenantController::class, 'resetData'])->name('tenants.reset-data');
        Route::post('/admin/tenants/{id}/subscription', [TenantController::class, 'updateSubscription'])->name('tenants.subscription.update');
        Route::delete('/admin/tenants/{id}', [TenantController::class, 'destroy'])->name('tenants.destroy');

        // Setelan Plan Deposit (platform-wide, Superadmin)
        Route::get('/admin/deposit-settings', [DepositSettingController::class, 'index'])->name('deposit-settings.index');
        Route::post('/admin/deposit-settings', [DepositSettingController::class, 'update'])->name('deposit-settings.update');
        Route::post('/admin/deposit-settings/manual-topup', [DepositSettingController::class, 'manualTopup'])->name('deposit-settings.manual-topup');

        // Channel Virtual Account DOKU (SNAP) — platform-wide, Superadmin
        Route::get('/admin/doku-channels', [DokuChannelController::class, 'index'])->name('doku-channels.index');
        Route::post('/admin/doku-channels', [DokuChannelController::class, 'store'])->name('doku-channels.store');
        Route::put('/admin/doku-channels/{channel}', [DokuChannelController::class, 'update'])->name('doku-channels.update');
        Route::post('/admin/doku-channels/{channel}/toggle', [DokuChannelController::class, 'toggle'])->name('doku-channels.toggle');
        Route::delete('/admin/doku-channels/{channel}', [DokuChannelController::class, 'destroy'])->name('doku-channels.destroy');

        // Logo Partner (marquee landing) — platform-wide, Superadmin
        Route::get('/admin/partner-logos', [PartnerLogoController::class, 'index'])->name('partner-logos.index');
        Route::post('/admin/partner-logos', [PartnerLogoController::class, 'store'])->name('partner-logos.store');
        Route::put('/admin/partner-logos/{partnerLogo}', [PartnerLogoController::class, 'update'])->name('partner-logos.update');
        Route::post('/admin/partner-logos/{partnerLogo}/toggle', [PartnerLogoController::class, 'toggle'])->name('partner-logos.toggle');
        Route::delete('/admin/partner-logos/{partnerLogo}', [PartnerLogoController::class, 'destroy'])->name('partner-logos.destroy');
        Route::post('/admin/partner-logos-limit', [PartnerLogoController::class, 'updateLimit'])->name('partner-logos.limit');
    });

    // ====================================================
    // KASIR (POS satu-halaman, tanpa meja) : view_kasir — Superadmin, admin, kasir
    // ====================================================
    Route::middleware(['can:view_kasir', 'subscribed'])->group(function () {
        // Shift — halaman bisa dilihat semua (view_kasir); AKSI dibatasi permission:
        //  - buka/tutup  : kasir (shift.operate)
        //  - buka kembali: owner/admin (shift.reopen)
        Route::get('/admin/shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::post('/admin/shifts/open', [ShiftController::class, 'openShift'])->middleware('can:shift.operate')->name('shifts.open');
        Route::post('/admin/shifts/close/{id}', [ShiftController::class, 'closeShift'])->middleware('can:shift.operate')->name('shifts.close');
        Route::post('/admin/shifts/reopen/{id}', [ShiftController::class, 'reopenShift'])->middleware('can:shift.reopen')->name('shifts.reopen');
        Route::post('/admin/shifts/{id}/modal', [ShiftController::class, 'updateModal'])->middleware('can:shift.reopen')->name('shifts.update-modal');

        // Kasir single-page
        Route::get('/admin/kasir', [KasirController::class, 'index'])->name('kasir.index');
        Route::post('/admin/kasir/toggle-tables', [KasirController::class, 'toggleTables'])->name('kasir.toggle-tables');
        Route::get('/admin/kasir/orders', [KasirController::class, 'listOrders'])->name('kasir.orders');
        Route::get('/admin/kasir/order/{id}', [KasirController::class, 'showOrder'])->name('kasir.order.show');
        Route::post('/admin/kasir/order/store', [KasirController::class, 'storeOrder'])->name('kasir.store');
        Route::post('/admin/kasir/order/sync-offline', [KasirController::class, 'syncOfflineOrders'])->name('kasir.sync-offline');
        Route::post('/admin/kasir/order/{id}/pay', [KasirController::class, 'payOrder'])->name('kasir.pay');
        Route::post('/admin/kasir/order/{id}/complete', [KasirController::class, 'completeOrder'])->name('kasir.complete');
        Route::get('/admin/kasir/print/{id}', [KasirController::class, 'printReceipt'])->name('kasir.print');

        // Aksi sensitif khusus OWNER (Superadmin lolos via Gate::before) —
        // hapus pesanan & reset penjualan hari ini.
        Route::delete('/admin/kasir/order/{id}', [KasirController::class, 'destroyOrder'])
            ->middleware('can:order.delete')->name('kasir.order.destroy');
        // Tandai / batalkan tanda "SALAH" pada pesanan SELESAI (OWNER + KASIR).
        // Toggle: pesanan salah tidak dihitung ke omzet & kas, tetap tampil di laporan.
        Route::post('/admin/kasir/order/{id}/void', [KasirController::class, 'voidOrder'])
            ->middleware('can:order.void')->name('kasir.order.void');
        Route::post('/admin/kasir/sales/reset-today', [KasirController::class, 'resetToday'])
            ->middleware('can:sales.clear')->name('kasir.sales.reset-today');
        Route::post('/admin/kasir/sales/target', [KasirController::class, 'setTarget'])
            ->middleware('can:sales.target')->name('kasir.sales.target');
    });

    // ====================================================
    // KITCHEN: view_kitchen — Superadmin, admin, kasir, kitchen
    // ====================================================
    Route::middleware(['can:view_kitchen', 'subscribed'])->group(function () {
        Route::get('/admin/kitchen', [KitchenController::class, 'index'])->name('kitchen.index');
        Route::post('/admin/kitchen/update-status', [KitchenController::class, 'updateItemStatus'])->name('kitchen.update-status');
        Route::post('/admin/kitchen/update-order-status', [KitchenController::class, 'updateOrderStatus'])->name('kitchen.update-order-status');
    });

    // ====================================================
    // DATA MASTER: view_data_master — Superadmin, admin
    // ====================================================
    Route::middleware(['can:view_data_master', 'subscribed'])->group(function () {
        Route::resource('/admin/categories', CategoriesController::class);
        Route::get('/admin/get-datacategories', [CategoriesController::class, 'getDataCategories'])->name('get-datacategories');

        // Import menu via CSV (template + upload) — didefinisikan SEBELUM resource
        // agar '/menus/template' & '/menus/import' tidak bentrok dgn '/menus/{menu}'.
        Route::get('/admin/menus/template', [MenuController::class, 'downloadTemplate'])->name('menus.template');
        Route::post('/admin/menus/import', [MenuController::class, 'importCsv'])->name('menus.import');

        Route::post('/admin/menus/mass-delete', [MenuController::class, 'massDestroy'])->name('menus.mass-delete');
        Route::resource('/admin/menus', MenuController::class);
        Route::get('/admin/get-datamenus', [MenuController::class, 'getDataMenus'])->name('get-datamenus');
        // Add-ons per menu (untuk form kelola & untuk kasir)
        Route::get('/admin/menus/{id}/addons', [MenuController::class, 'getAddons'])->name('menus.addons');

        // Promos — fitur paket Business (plan:promo)
        Route::middleware('plan:promo')->group(function () {
            Route::get('/admin/promos/data', [PromoController::class, 'getData'])->name('promos.data');
            Route::post('/admin/promos/toggle/{id}', [PromoController::class, 'toggleStatus'])->name('promos.toggle');
            Route::resource('/admin/promos', PromoController::class)
                ->except(['create', 'show'])
                ->names('promos');
        });

        // Manajemen Meja — fitur paket Enterprise ke atas (plan:tables)
        Route::middleware('plan:tables')->group(function () {
            Route::get('/admin/tables', [DiningTableController::class, 'index'])->name('tables.index');
            Route::get('/admin/get-datatables', [DiningTableController::class, 'getData'])->name('tables.data');
            Route::post('/admin/tables', [DiningTableController::class, 'store'])->name('tables.store');
            Route::put('/admin/tables/{id}', [DiningTableController::class, 'update'])->name('tables.update');
            Route::delete('/admin/tables/{id}', [DiningTableController::class, 'destroy'])->name('tables.destroy');
        });
    });

    // ====================================================
    // REPORTS: view_report — Superadmin, admin, kasir
    // ====================================================
    Route::middleware(['can:view_report', 'subscribed'])->group(function () {
        Route::get('/admin/reports/sales', [SalesReportController::class, 'index'])->name('reports.sales.index');
        Route::get('/admin/reports/sales/data', [SalesReportController::class, 'getData'])->name('reports.sales.data');
        Route::get('/admin/reports/sales/print', [SalesReportController::class, 'print'])->name('reports.sales.print');

        // Laporan per-item — fitur paket Business (plan:report_items)
        Route::middleware('plan:report_items')->group(function () {
            Route::get('/admin/reports/items', [ItemSalesReportController::class, 'index'])->name('reports.items.index');
            Route::get('/admin/reports/items/data', [ItemSalesReportController::class, 'getData'])->name('reports.items.data');
            Route::get('/admin/reports/items/print', [ItemSalesReportController::class, 'print'])->name('reports.items.print');
        });
    });

    // ====================================================
    // PENGELUARAN (Expenses): view_expense — Superadmin, owner, admin, kasir
    // ====================================================
    Route::middleware(['can:view_expense', 'subscribed'])->group(function () {
        Route::get('/admin/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/admin/get-dataexpenses', [ExpenseController::class, 'getDataExpenses'])->name('expenses.data');
        Route::post('/admin/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::put('/admin/expenses/{id}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/admin/expenses/{id}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    });

    // ====================================================
    // RESOURCES (User & Role Mgmt): view_resources — Superadmin only
    // ====================================================
    Route::middleware(['can:view_resources', 'subscribed'])->group(function () {
        // --- User management (owner boleh kelola staf-nya) ---
        Route::resource('/admin/users', UserController::class);
        Route::get('/admin/get-datauser', [UserController::class, 'getDataUsers'])->name('get-users');
        Route::post('/admin/users/mass-delete', [UserController::class, 'massDelete'])->name('users.mass-delete');
        Route::get('/admin/get-user-show-log/{id}', [UserController::class, 'getLoginSession'])->name('get-user-show-log');
        Route::get('/admin/get-user-show-log-activity/{id}', [UserController::class, 'getActivity'])->name('get-user-show-log-activity');
        Route::post('/admin/users/{id}/ban', [UserController::class, 'ban'])->name('users.ban');
        Route::post('/admin/users/{id}/unban', [UserController::class, 'unban'])->name('users.unban');
        Route::get('/admin/select/role', [RoleController::class, 'select'])->name('role.select');

        // --- Role / Hak Akses management: KHUSUS Superadmin (role bersifat global lintas-tenant) ---
        Route::middleware('role:Superadmin')->group(function () {
            Route::resource('/admin/roles', RoleController::class);
            Route::get('/admin/get-datarole', [RoleController::class, 'getDataRoles'])->name('get-datarole');
            Route::post('/admin/roles/mass-delete', [RoleController::class, 'massDelete'])->name('roles.mass-delete');
            Route::post('/admin/roles/generate-permissions', [RoleController::class, 'generatePermissions'])->name('roles.generate');
        });
    });

    // ====================================================
    // HELP (Log Activity): view_help — Superadmin, admin
    // ====================================================
    Route::middleware(['can:view_help', 'subscribed'])->group(function () {
        Route::resource('/admin/log-activity', LogActivityController::class);
        Route::get('/admin/get-datalogactivity', [LogActivityController::class, 'getDataLogActivity'])->name('get-datalogactivity');
    });
});

// Webhook langganan SaaS (billing) — tetap memakai Midtrans untuk pembayaran langganan tenant.
Route::post('/api/subscription-webhook', [BillingController::class, 'webhook']);

// Webhook DOKU (diteruskan oleh doku-gateway; gateway sudah verifikasi Bearer JWT).
// Menangani langganan (DSP-SUB-) & top-up deposit (DSP-DEP-).
Route::post('/api/doku-webhook', [BillingController::class, 'dokuWebhook']);

// Load Routes Authentication (Login, Register, Reset Password)
require __DIR__ . '/auth.php';
