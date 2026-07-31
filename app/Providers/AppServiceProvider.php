<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\DailySalesTarget;
use App\Models\Order; // Pastikan menggunakan Order (bukan Sale)
use App\Models\Setting;
use Carbon\Carbon;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use App\Tenancy\TenantManager;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Konteks tenant aktif: scoped (bukan singleton) agar aman di runtime persisten
        // seperti Octane — state tenant di-reset tiap request, tidak bocor antar-request.
        $this->app->scoped(TenantManager::class);
    }

    public function boot(): void
    {
        // Real-time: broadcast perubahan order (menggantikan polling Kasir & Dapur)
        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
        \App\Models\OrderDetail::observe(\App\Observers\OrderDetailObserver::class);

        // Aktivasi email (link) -> tenant jadi Starter (mode deposit) + bonus saldo Rp2.000.
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Verified::class,
            \App\Listeners\GrantStarterOnVerified::class
        );

        // Paksa HTTPS di Production/VPS agar tidak terjadi Mixed Content
        if (config('app.env') === 'production') {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        // Implicitly grant "Superadmin" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Superadmin') ? true : null;
        });

        // Blog publik (blog.mooda.id): daftar kategori untuk navbar & footer (1 query/halaman).
        View::composer('blog.layout', function ($view) {
            $view->with('navCategories', \App\Models\Blog\Category::query()
                ->whereHas('posts', fn ($q) => $q->published())
                ->withCount(['posts as published_count' => fn ($q) => $q->published()])
                ->orderByDesc('published_count')->orderBy('name')->get());
        });

        // Bagikan tenant aktif + status langganan ke semua view backend (untuk banner billing & gating menu)
        View::composer('backend.*', function ($view) {
            $tenant = null;
            if (auth()->check() && auth()->user()->tenant_id) {
                $tenant = app(TenantManager::class)->tenant();
            }
            $view->with('currentTenant', $tenant);

            // Info plan deposit untuk sidebar & banner.
            $view->with([
                'depositMode'         => $tenant ? $tenant->isDepositMode() : false,
                'depositPoints'       => $tenant ? (float) $tenant->deposit_points : 0,
                'depositExpiresAt'    => $tenant ? $tenant->deposit_expires_at : null,
                'depositExpiringSoon' => $tenant ? $tenant->depositExpiringSoon(5) : false,
                'depositFee'          => \App\Tenancy\DepositConfig::feePerTransaction(),
                'depositWarningThreshold' => \App\Tenancy\DepositConfig::warningThreshold(),
            ]);
        });

        // Mode tampilan Superadmin: 'analytics' (platform) atau 'pos' (kasir).
        // Untuk non-Superadmin selalu 'pos' (tampilan operasional biasa).
        View::composer('backend.*', function ($view) {
            $isSA = auth()->check() && auth()->user()->hasRole('Superadmin');

            $saTenants = null;
            $saPosTenantId = null;
            $saPosTenantName = null;
            if ($isSA) {
                $saTenants = \App\Models\Tenant::orderBy('name')->get(['id', 'name']);
                $saPosTenantId = session('sa_pos_tenant_id');
                $saPosTenantName = optional($saTenants->firstWhere('id', $saPosTenantId))->name;
            }

            $view->with([
                'isSuperadminView' => $isSA,
                'saMode'           => $isSA ? session('sa_mode', 'analytics') : 'pos',
                'saTenants'        => $saTenants,
                'saPosTenantId'    => $saPosTenantId,
                'saPosTenantName'  => $saPosTenantName,
            ]);
        });

        // Inject data ringkas ke sidebar backend (HANYA jika user login):
        // Target Penjualan Harian vs Omzet hari ini. (Budget & pengeluaran sudah dihapus.)
        // Bagikan setelan toko (untuk konfigurasi printer di layout).
        View::composer('backend.*', function ($view) {
            $view->with('posSetting', auth()->check() ? Setting::first() : null);
        });

        View::composer('backend.*', function ($view) {
            if (auth()->check()) {
                $today = date('Y-m-d');

                // Angka sidebar MENGIKUTI shift yang sedang TERBUKA (per user): tidak reset saat
                // ganti hari selama shift belum ditutup. Bila tak ada shift terbuka -> kalender
                // hari ini (perilaku lama, aman untuk tenant yang tak memakai shift).
                //
                // OPERATOR (kasir): ikut shift MILIKNYA. PENINJAU (owner/admin): ikut shift KASIR
                // yang sedang berjalan di tenant-nya (agar target & pendapatan sidebar sesuai inputan
                // kasir, live). Bila tak ada shift terbuka -> kalender hari ini.
                $isOperator = auth()->user()->can('shift.operate');
                $openShift = $isOperator
                    ? \App\Models\Shift::where('user_id', auth()->id())
                        ->where('status', 'open')->latest('start_time')->first()
                    : \App\Models\Shift::where('status', 'open')->latest('start_time')->first();

                $shiftStale = false; // shift terbuka dari hari sebelumnya (lupa ditutup)

                if ($openShift) {
                    $start     = $openShift->start_time;
                    $scopeDate = Carbon::parse($start)->toDateString();
                    // Banner "shift kemarin belum ditutup" HANYA untuk operator (yang bisa menutup);
                    // peninjau cukup melihat angkanya tanpa banner aksi.
                    $shiftStale = $isOperator && ! Carbon::parse($start)->isToday();

                    $income      = (float) Order::where('payment_status', 'paid')
                        ->where('created_at', '>=', $start)
                        ->whereNull('voided_at') // pesanan salah tak dihitung ke omzet
                        ->sum('grand_total');
                    $salesTarget = (float) (DailySalesTarget::where('date', $scopeDate)->value('amount') ?? 0);
                    // Pengeluaran: basis kolom `date` pada tanggal operasional shift (bukan created_at),
                    // agar sama dengan Laporan Penjualan & halaman Pengeluaran (total per tanggal).
                    $dailySpent  = (float) \App\Models\Expense::whereDate('date', $scopeDate)->sum('amount');
                } else {
                    $income      = (float) Order::whereDate('created_at', $today)
                        ->where('payment_status', 'paid')
                        ->whereNull('voided_at') // pesanan salah tak dihitung ke omzet
                        ->sum('grand_total');
                    $salesTarget = (float) (DailySalesTarget::where('date', $today)->value('amount') ?? 0);
                    $dailySpent  = (float) \App\Models\Expense::whereDate('date', $today)->sum('amount');
                }

                // ===== VERTICAL LAUNDRY =====
                // Omzet diambil dari nota LAUNDRY (bukan Order F&B, yang selalu 0 di laundry),
                // dan capaian target dihitung dari PROFIT = omzet - pengeluaran hari itu.
                $tenantNow    = auth()->user()->tenant;
                $isLaundryNow = $tenantNow && method_exists($tenantNow, 'isLaundry') && $tenantNow->isLaundry();

                if ($isLaundryNow) {
                    $ldQuery = \App\Models\Laundry\LaundryOrder::where('payment_status', 'paid');
                    $income  = (float) ($openShift
                        ? $ldQuery->where('created_at', '>=', $openShift->start_time)->sum('grand_total')
                        : $ldQuery->whereDate('created_at', $today)->sum('grand_total'));
                }

                // Nilai yang dibandingkan dgn target: laundry = profit, F&B = omzet.
                $achieved = $isLaundryNow ? max(0, $income - $dailySpent) : $income;

                // Kalkulasi Persentase vs Target
                $salesPercentage = 0;
                $salesBarWidth = 0;
                $salesProgressColor = 'bg-warning';
                if ($salesTarget > 0) {
                    $salesPercentage = round(($achieved / $salesTarget) * 100);
                    $salesBarWidth = $salesPercentage > 100 ? 100 : $salesPercentage;
                    if ($salesPercentage >= 100) {
                        $salesProgressColor = 'bg-success';
                    } elseif ($salesPercentage >= 50) {
                        $salesProgressColor = 'bg-primary';
                    }
                }

                $view->with(compact(
                    'salesTarget',
                    'income',
                    'achieved',
                    'isLaundryNow',
                    'salesPercentage',
                    'salesBarWidth',
                    'salesProgressColor',
                    'dailySpent',
                    'shiftStale',
                    'openShift'
                ));
            }
        });
    }
}
