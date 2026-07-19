<?php

namespace App\Http\Controllers\Backend\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\DailySalesTarget;
use App\Models\Tenant;
use App\Models\Subscription;
use App\Models\DepositTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardAdminController extends Controller
{
    public function index(Request $request)
    {
        // Superadmin default: dashboard ANALITIK platform (bukan kasir).
        // Bisa dialihkan ke mode kasir/POS lewat tombol (session sa_mode).
        if (auth()->user()->hasRole('Superadmin') && session('sa_mode', 'analytics') === 'analytics') {
            return $this->analytics();
        }

        // Bulan yang dipilih (format Y-m); default = bulan berjalan.
        $selectedMonth = (string) $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $monthStart = Carbon::now()->startOfMonth();
        }
        $selectedMonth = $monthStart->format('Y-m');
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Pilihan bulan untuk filter (12 bulan terakhir).
        $monthOptions = [];
        for ($c = Carbon::now()->startOfMonth(), $i = 0; $i < 12; $i++, $c->subMonth()) {
            $monthOptions[] = ['value' => $c->format('Y-m'), 'label' => $c->translatedFormat('F Y')];
        }

        // 1. Menu Tidak Tersedia / Habis (Real-time)
        $unavailableMenus = Menu::with('category')
            ->where('is_available', false)
            ->get();

        // 2. Top Selling Menus (Bulan Ini) - Real-time
        $topProducts = OrderDetail::with(['menu.category'])
            ->whereHas('order', function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where('payment_status', 'paid')
                    ->whereNull('voided_at'); // pesanan salah tak dihitung
            })
            ->select('menu_id', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
            ->groupBy('menu_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // 3. Data Grafik Penjualan vs Target (Bulan Ini) - Real-time
        // Ambil total penjualan per hari
        $actualSales = Order::whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('payment_status', 'paid')
            ->whereNull('voided_at') // pesanan salah tak dihitung ke grafik penjualan
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(grand_total) as total'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'date');

        // Ambil target per hari
        $targets = DailySalesTarget::whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->pluck('amount', 'date');

        $dates = [];
        $salesSeries = [];
        $targetSeries = [];

        // Sampai hari ini bila bulan berjalan; sampai akhir bulan bila bulan lampau.
        $chartEnd = $monthEnd->lt(Carbon::now()) ? $monthEnd->copy() : Carbon::now();
        for ($date = $monthStart->copy(); $date->lte($chartEnd); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $dates[] = $date->format('d M'); 
            $salesSeries[] = (int) $actualSales->get($dateString, 0);
            $targetSeries[] = (int) $targets->get($dateString, 0);
        }

        $chartData = [
            'categories' => $dates,
            'sales'      => $salesSeries,
            'targets'    => $targetSeries,
        ];

        // 4. Quick Summary Widget - Real-time
        $revenue = Order::whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('payment_status', 'paid')
            ->whereNull('voided_at') // pesanan salah tak dihitung ke omzet
            ->sum('grand_total');

        $ordersCount = Order::whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('payment_status', 'paid')
            ->whereNull('voided_at') // pesanan salah tak dihitung
            ->count();

        $itemsSold = OrderDetail::whereHas('order', function ($q) use ($monthStart, $monthEnd) {
            $q->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('payment_status', 'paid')
                ->whereNull('voided_at'); // pesanan salah tak dihitung
        })->sum('qty');

        $summary = [
            'revenue'      => $revenue,
            'orders_count' => $ordersCount,
            'items_sold'   => $itemsSold,
        ];

        // Misi onboarding setup awal (deteksi otomatis selesai/belum).
        $setting = \App\Models\Setting::first();
        $onbSettings = $setting
            && trim((string) $setting->store_name) !== ''
            && trim((string) $setting->address) !== ''
            && ! empty($setting->printer_method)
            && (trim((string) $setting->receipt_header) !== '' || trim((string) $setting->receipt_footer) !== '');
        $onbMaster = \App\Models\Category::count() > 0 && Menu::count() > 0;
        // Setup Karyawan: selesai bila tenant sudah punya akun ber-role owner, admin, DAN kasir.
        // Nama role lowercase (spatie); TenantScope global otomatis membatasi ke tenant aktif.
        $onbEmployees = \App\Models\User::role('owner')->exists()
            && \App\Models\User::role('admin')->exists()
            && \App\Models\User::role('kasir')->exists();
        // Langkah "Setup Karyawan" (no.3) HANYA tampil untuk OWNER (buat akun karyawan = tugas owner).
        // Admin & kasir cukup langkah 1 & 2.
        $isOwner = auth()->user()->hasRole('owner');
        $onboarding = [
            'settings'  => (bool) $onbSettings,
            'master'    => (bool) $onbMaster,
            'employees' => (bool) $onbEmployees,
            'is_owner'  => (bool) $isOwner,
            'done'      => $isOwner
                ? (bool) ($onbSettings && $onbMaster && $onbEmployees)
                : (bool) ($onbSettings && $onbMaster),
        ];

        return view('backend.dashboard.index', compact('unavailableMenus', 'topProducts', 'chartData', 'summary', 'selectedMonth', 'monthOptions', 'onboarding'));
    }

    /** Alihkan tampilan Superadmin: 'analytics' (platform) <-> 'pos' (kasir). */
    public function switchMode(string $mode)
    {
        abort_unless(auth()->user()->hasRole('Superadmin'), 403);
        $mode = $mode === 'pos' ? 'pos' : 'analytics';
        session(['sa_mode' => $mode]);

        // Saat masuk mode POS: pastikan ADA tenant terpilih (default tenant pertama) agar
        // data ter-scope ke satu toko & tidak agregat/yatim (tenant_id NULL).
        if ($mode === 'pos' && ! session('sa_pos_tenant_id')) {
            $firstTenant = Tenant::orderBy('id')->value('id');
            if ($firstTenant) {
                session(['sa_pos_tenant_id' => $firstTenant]);
            }
        }

        return redirect()->route('dashboard');
    }

    /** Superadmin memilih toko/tenant untuk dioperasikan di mode POS/kasir. */
    public function setPosTenant($id)
    {
        abort_unless(auth()->user()->hasRole('Superadmin'), 403);
        $tenant = Tenant::find($id);
        abort_if(! $tenant, 404, 'Toko tidak ditemukan.');

        session(['sa_pos_tenant_id' => $tenant->id, 'sa_mode' => 'pos']);

        return redirect()->route('dashboard')->with('success', 'Mode kasir kini untuk toko: ' . $tenant->name);
    }

    /** Dashboard analitik platform untuk Superadmin (lintas tenant). */
    private function analytics()
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();
        $now        = Carbon::now();

        // Tenant mode bulanan yang masih aktif (langganan/trial belum kedaluwarsa).
        $monthlyActive = Tenant::where('billing_mode', '!=', 'deposit')
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->where(function ($x) use ($now) {
                    $x->where('subscription_status', 'active')->where('subscription_ends_at', '>', $now);
                })->orWhere(function ($x) use ($now) {
                    $x->where('subscription_status', 'trial')->where('trial_ends_at', '>', $now);
                });
            })->count();

        $stats = [
            'total_tenants'       => Tenant::count(),
            'active_tenants'      => Tenant::where('is_active', true)->count(),
            'deposit_tenants'     => Tenant::where('billing_mode', 'deposit')->count(),
            'monthly_tenants'     => Tenant::where('billing_mode', '!=', 'deposit')->count(),
            'monthly_active'      => $monthlyActive,
            'new_this_month'      => Tenant::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            'platform_revenue'    => (float) Order::whereBetween('created_at', [$monthStart, $monthEnd])->where('payment_status', 'paid')->whereNull('voided_at')->sum('grand_total'),
            'platform_tx'         => (int) Order::whereBetween('created_at', [$monthStart, $monthEnd])->where('payment_status', 'paid')->whereNull('voided_at')->count(),
            'sub_revenue'         => (float) Subscription::where('status', 'paid')->whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount'),
            'deposit_outstanding' => (float) Tenant::sum('deposit_points'),
        ];

        // Grafik omzet platform harian (bulan ini)
        $dailyOmzet = Order::whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('payment_status', 'paid')
            ->whereNull('voided_at') // pesanan salah tak dihitung ke omzet platform
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(grand_total) as total'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'date');

        $dates = [];
        $omzetSeries = [];
        for ($d = $monthStart->copy(); $d->lte($now); $d->addDay()) {
            $ds = $d->format('Y-m-d');
            $dates[] = $d->format('d M');
            $omzetSeries[] = (int) $dailyOmzet->get($ds, 0);
        }
        $chart = ['categories' => $dates, 'omzet' => $omzetSeries];

        // Top tenant berdasarkan omzet bulan ini
        $topRows = Order::whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('payment_status', 'paid')
            ->whereNull('voided_at') // pesanan salah tak dihitung ke ranking omzet tenant
            ->whereNotNull('tenant_id')
            ->select('tenant_id', DB::raw('SUM(grand_total) as omzet'), DB::raw('COUNT(*) as tx'))
            ->groupBy('tenant_id')
            ->orderByDesc('omzet')
            ->limit(5)
            ->get();
        $names = Tenant::whereIn('id', $topRows->pluck('tenant_id'))->pluck('name', 'id');
        $topTenants = $topRows->map(fn ($r) => [
            'name'  => $names[$r->tenant_id] ?? ('Tenant #' . $r->tenant_id),
            'omzet' => (float) $r->omzet,
            'tx'    => (int) $r->tx,
        ]);

        $latestTenants = Tenant::orderByDesc('id')->limit(8)->get();

        $recentTopups = DepositTransaction::where('type', 'topup')
            ->with('tenant:id,name')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('backend.dashboard.analytics', compact('stats', 'chart', 'topTenants', 'latestTenants', 'recentTopups'));
    }
}