@extends('backend.layout.app')
@section('title', 'Dashboard Analitik')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- Hero --}}
            <div class="card border-0 shadow-sm mb-6 mb-xl-8"
                style="background: linear-gradient(120deg, #4f46e5 0%, #6366f1 55%, #818cf8 100%);">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between py-6">
                    <div class="text-white">
                        <h2 class="text-white fw-bold mb-1">Halo, {{ auth()->user()->name }} 👋</h2>
                        <div class="text-white opacity-75 fs-6">
                            Mooda • Analitik Platform • {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3 mt-sm-0">
                        <a href="{{ route('tenants.index') }}" class="btn btn-active-light text-white border border-white border-opacity-25 fw-bold">
                            <i class="ki-outline ki-abstract-26 fs-3 me-1"></i> Manajemen Tenant
                        </a>
                        <a href="{{ route('view-mode.switch', 'pos') }}" class="btn btn-light fw-bold">
                            <i class="ki-outline ki-handcart fs-3 me-1"></i> Dashboard Kasir
                        </a>
                    </div>
                </div>
            </div>

            {{-- KPI baris 1: tenant --}}
            <div class="row g-5 g-xl-8 mb-2">
                <div class="col-6 col-md-3">
                    <div class="card bg-light-primary border-0 shadow-sm h-100">
                        <div class="card-body p-6">
                            <div class="fs-6 fw-semibold text-primary mb-2">Total Tenant</div>
                            <div class="fs-2hx fw-bold text-gray-800">{{ number_format($stats['total_tenants'], 0, ',', '.') }}</div>
                            <div class="fs-8 text-muted mt-1">{{ number_format($stats['active_tenants'], 0, ',', '.') }} aktif</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-success border-0 shadow-sm h-100">
                        <div class="card-body p-6">
                            <div class="fs-6 fw-semibold text-success mb-2">Langganan Bulanan Aktif</div>
                            <div class="fs-2hx fw-bold text-gray-800">{{ number_format($stats['monthly_active'], 0, ',', '.') }}</div>
                            <div class="fs-8 text-muted mt-1">dari {{ number_format($stats['monthly_tenants'], 0, ',', '.') }} tenant bulanan</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-warning border-0 shadow-sm h-100">
                        <div class="card-body p-6">
                            <div class="fs-6 fw-semibold text-warning mb-2">Tenant Plan Deposit</div>
                            <div class="fs-2hx fw-bold text-gray-800">{{ number_format($stats['deposit_tenants'], 0, ',', '.') }}</div>
                            <div class="fs-8 text-muted mt-1">akun pay-as-you-go</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-info border-0 shadow-sm h-100">
                        <div class="card-body p-6">
                            <div class="fs-6 fw-semibold text-info mb-2">Tenant Baru (Bulan Ini)</div>
                            <div class="fs-2hx fw-bold text-gray-800">{{ number_format($stats['new_this_month'], 0, ',', '.') }}</div>
                            <div class="fs-8 text-muted mt-1">pendaftaran baru</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KPI baris 2: keuangan platform --}}
            <div class="row g-5 g-xl-8 mb-xl-8 mt-1">
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-6">
                            <div class="fs-6 fw-semibold text-gray-500 mb-2">Omzet Semua Tenant (Bulan Ini)</div>
                            <div class="fs-2x fw-bold text-gray-800">Rp {{ number_format($stats['platform_revenue'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-6">
                            <div class="fs-6 fw-semibold text-gray-500 mb-2">Transaksi Platform (Bulan Ini)</div>
                            <div class="fs-2x fw-bold text-gray-800">{{ number_format($stats['platform_tx'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-6">
                            <div class="fs-6 fw-semibold text-gray-500 mb-2">Pendapatan Langganan (Bulan Ini)</div>
                            <div class="fs-2x fw-bold text-success">Rp {{ number_format($stats['sub_revenue'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-6">
                            <div class="fs-6 fw-semibold text-gray-500 mb-2">Poin Deposit Beredar</div>
                            <div class="fs-2x fw-bold text-warning">Rp {{ number_format($stats['deposit_outstanding'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Chart + Top tenant --}}
            <div class="row g-5 g-xl-8 mb-xl-8">
                <div class="col-xl-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header pt-5 border-0">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold fs-3 mb-1">Omzet Platform Harian</span>
                                <span class="text-muted fw-semibold fs-7">Total omzet seluruh tenant (bulan ini)</span>
                            </h3>
                        </div>
                        <div class="card-body pt-2">
                            <div id="platform_omzet_chart" style="height: 340px"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header pt-5 border-0">
                            <h3 class="card-title fw-bold fs-3">Tenant Teratas</h3>
                            <span class="text-muted fw-semibold fs-7">Omzet terbesar bulan ini</span>
                        </div>
                        <div class="card-body pt-2">
                            @forelse ($topTenants as $i => $t)
                                <div class="d-flex align-items-center mb-5">
                                    <span class="badge badge-circle badge-light-primary me-3">{{ $i + 1 }}</span>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-gray-800">{{ $t['name'] }}</div>
                                        <div class="fs-8 text-muted">{{ number_format($t['tx'], 0, ',', '.') }} transaksi</div>
                                    </div>
                                    <span class="fw-bold text-success">Rp {{ number_format($t['omzet'], 0, ',', '.') }}</span>
                                </div>
                            @empty
                                <div class="text-muted text-center py-8">Belum ada transaksi bulan ini.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tenant terbaru + Top-up deposit terbaru --}}
            <div class="row g-5 g-xl-8">
                <div class="col-xl-7">
                    <div class="card shadow-sm h-100">
                        <div class="card-header pt-5 border-0">
                            <h3 class="card-title fw-bold fs-3">Tenant Terbaru</h3>
                            <div class="card-toolbar">
                                <a href="{{ route('tenants.index') }}" class="btn btn-sm btn-light-primary">Lihat Semua</a>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle gs-0 gy-3">
                                    <thead>
                                        <tr class="fw-bold text-muted fs-7 text-uppercase">
                                            <th>Tenant</th>
                                            <th>Plan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($latestTenants as $t)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-gray-800">{{ $t->name }}</div>
                                                    <div class="fs-8 text-muted">{{ $t->business_type ?? '—' }}</div>
                                                </td>
                                                <td>
                                                    @if ($t->billing_mode === 'deposit')
                                                        <span class="badge badge-light-warning">Deposit</span>
                                                    @else
                                                        <span class="badge badge-light-primary">Bulanan</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (!$t->is_active)
                                                        <span class="badge badge-light-dark">Nonaktif</span>
                                                    @elseif ($t->billing_mode === 'deposit')
                                                        <span class="badge badge-light-success">Aktif · Rp{{ number_format($t->deposit_points, 0, ',', '.') }} poin</span>
                                                    @elseif ($t->monthlyActive())
                                                        <span class="badge badge-light-success">Aktif</span>
                                                    @else
                                                        <span class="badge badge-light-danger">Kedaluwarsa</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-muted py-6">Belum ada tenant.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5">
                    <div class="card shadow-sm h-100">
                        <div class="card-header pt-5 border-0">
                            <h3 class="card-title fw-bold fs-3">Top-up Deposit Terbaru</h3>
                            <div class="card-toolbar">
                                <a href="{{ route('deposit-settings.index') }}" class="btn btn-sm btn-light-primary">Setelan</a>
                            </div>
                        </div>
                        <div class="card-body pt-2">
                            @forelse ($recentTopups as $row)
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div>
                                        <div class="fw-bold text-gray-800">{{ $row->tenant->name ?? '—' }}</div>
                                        <div class="fs-8 text-muted">{{ $row->created_at->translatedFormat('d M Y H:i') }}</div>
                                    </div>
                                    <span class="fw-bold text-success">+Rp {{ number_format($row->points, 0, ',', '.') }}</span>
                                </div>
                            @empty
                                <div class="text-muted text-center py-8">Belum ada top-up deposit.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            var el = document.getElementById('platform_omzet_chart');
            if (!el || typeof ApexCharts === 'undefined') return;
            var chart = new ApexCharts(el, {
                chart: { type: 'area', height: 340, toolbar: { show: false }, fontFamily: 'inherit' },
                series: [{ name: 'Omzet Platform', data: @json($chart['omzet']) }],
                xaxis: { categories: @json($chart['categories']), labels: { rotate: -45, style: { fontSize: '11px' } } },
                colors: ['#4f46e5'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
                grid: { borderColor: '#eff2f5', strokeDashArray: 4 },
                yaxis: { labels: { formatter: function (v) { return 'Rp' + Number(v).toLocaleString('id-ID'); } } },
                tooltip: { y: { formatter: function (v) { return 'Rp' + Number(v).toLocaleString('id-ID'); } } },
            });
            chart.render();
        })();
    </script>
@endpush
