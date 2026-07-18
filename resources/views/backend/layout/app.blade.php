<!DOCTYPE html>
<!--
Author: Rendy Irawan
Product Name: Mooda
Website: http://www.mooda.id
Contact: support@mooda.id
License: Proprietary - Mooda System
-->
<html lang="en">
<!--begin::Head-->

<head>
    <base href="{{ url('/') }}/" />
    <title>@yield('title')</title>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="description" content="Mooda - Dashboard Manajemen Restoran Berbasis Awan." />
    <meta name="keywords" content="dashboard pos, admin mooda, manajemen restoran, laporan penjualan" />
    <meta name="author" content="Rendy Irawan" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="id_ID" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="Mooda - Admin Dashboard" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:site_name" content="Mooda" />
    <link rel="canonical" href="{{ url()->current() }}" />
    <link rel="icon" type="image/png" href="{{ asset('assets/media/logos/mooda-mark-192.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('assets/media/logos/mooda-mark-192.png') }}" />
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/mooda-mark-192.png') }}" />
    <link rel="manifest" href="{{ asset('manifest.json') }}" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- display=swap: teks langsung tampil pakai font fallback, lalu Inter menyusul --}}
    {{-- (mencegah teks/logo "hilang/kedip" saat jaringan lambat). --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700&display=swap" media="print" onload="this.media='all'" />
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700&display=swap" /></noscript>
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/mooda-brand.css') }}" rel="stylesheet" type="text/css" />

    <!--end::Global Stylesheets Bundle-->

    {{-- Laravel Echo + Reverb client (real-time; menggantikan polling) --}}
    @auth
        <meta name="tenant-id" content="{{ auth()->user()->tenant_id }}">
    @endauth
    @vite(['resources/js/app.js'])

    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking)
        if (window.top != window.self) {
            window.top.location.replace(window.self.location.href);
        }
    </script>
    @stack('stylesheets')
</head>
<!--end::Head-->
<!--begin::Body-->

<body id="kt_app_body" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true"
    data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true"
    data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>
    <!--end::Theme mode setup on page load-->
    <!--begin::loader-->
    <div class="page-loader flex-column">
        <img alt="Mooda" class="theme-light-show max-h-50px"
            src="{{ asset('assets/media/logos/mooda-logo.png') }}" />
        <img alt="Mooda" class="theme-dark-show max-h-50px"
            src="{{ asset('assets/media/logos/mooda-logo-white.png') }}" />
        <div class="d-flex align-items-center mt-5">
            <span class="spinner-border text-primary" role="status"></span>
            <span class="text-muted fs-6 fw-semibold ms-5">Loading...</span>
        </div>
    </div>
    <!--end::Loader-->
    <!--begin::App-->
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <!--begin::Page-->
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            <!--begin::Header-->
            <div id="kt_app_header" class="app-header" data-kt-sticky="true" data-kt-sticky-activate-="true"
                data-kt-sticky-name="app-header-sticky" data-kt-sticky-offset="{default: '200px', lg: '300px'}">
                <!--begin::Header container-->
                <div class="app-container container-xxl d-flex align-items-stretch justify-content-between"
                    id="kt_app_header_container">
                    <!--begin::Header wrapper-->
                    <div class="app-header-wrapper d-flex flex-grow-1 align-items-stretch justify-content-between"
                        id="kt_app_header_wrapper">
                        <!--begin::Menu wrapper-->
                        @include('backend.layout.menu')
                        <!--end::Menu wrapper-->
                        <!--begin::Logo wrapper-->
                        <div class="d-flex align-items-center">
                            <!--begin::Logo wrapper-->
                            <div class="btn btn-icon btn-color-gray-600 btn-active-color-primary ms-n3 me-2 d-flex d-lg-none"
                                id="kt_app_sidebar_toggle">
                                <i class="ki-outline ki-abstract-14 fs-2"></i>
                            </div>
                            <!--end::Logo wrapper-->
                            <!--begin::Logo image-->
                            <a href="{{ route('dashboard') }}" class="d-flex d-lg-none">
                                <img alt="Mooda" src="{{ asset('assets/media/logos/mooda-logo.png') }}"
                                    class="h-25px theme-light-show" />
                                <img alt="Mooda" src="{{ asset('assets/media/logos/mooda-logo-white.png') }}"
                                    class="h-25px theme-dark-show" />
                            </a>
                            <!--end::Logo image-->
                        </div>
                        <!--end::Logo wrapper-->
                        {{-- Tombol Logout khusus mobile/tablet (di atas). --}}
                        <form method="POST" action="{{ route('logout') }}" class="d-flex d-lg-none align-items-center ms-auto me-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light-danger d-inline-flex align-items-center px-3" title="Logout">
                                <i class="ki-outline ki-exit-right fs-3 me-1"></i><span class="fw-bold">Logout</span>
                            </button>
                        </form>
                        <!--begin::Navbar-->
                        @include('backend.layout.navbar')
                        <!--end::Navbar-->
                    </div>
                    <!--end::Header wrapper-->
                </div>
                <!--end::Header container-->
            </div>
            <!--end::Header-->
            <!--begin::Wrapper-->
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <!--begin::Sidebar-->
                @include('backend.layout.sidebar')
                <!--end::Sidebar-->
                <!--begin::Main-->
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <!--begin::Content wrapper-->
                    <div class="d-flex flex-column flex-column-fluid">
                        @if (isset($currentTenant) && $currentTenant && !$currentTenant->hasActiveAccess())
                            <div class="app-container container-xxl mt-4">
                                <div class="alert alert-warning d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-0">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-outline ki-information-5 fs-2x text-warning me-3"></i>
                                        <div>
                                            <h5 class="mb-1">Langganan belum aktif</h5>
                                            <span class="text-gray-700">Fitur operasional terkunci. Aktifkan langganan untuk mulai memakai sistem.</span>
                                        </div>
                                    </div>
                                    @can('view_billing')
                                        <a href="{{ route('billing.index') }}" class="btn btn-warning btn-sm mt-3 mt-sm-0">Aktifkan Sekarang</a>
                                    @endcan
                                </div>
                            </div>
                        @endif

                        {{-- Banner plan deposit: saldo menipis / akan hangus --}}
                        @if (isset($currentTenant) && $currentTenant && $currentTenant->isDepositMode() && $currentTenant->hasActiveAccess())
                            @if (($depositPoints ?? 0) <= ($depositWarningThreshold ?? 10000))
                                <div class="app-container container-xxl mt-4">
                                    <div class="alert alert-danger d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-0">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-outline ki-wallet fs-2x text-danger me-3"></i>
                                            <div>
                                                <h5 class="mb-1">Saldo deposit menipis</h5>
                                                <span class="text-gray-700">Sisa saldo Rp{{ number_format($depositPoints ?? 0, 0, ',', '.') }}. Segera top up (potongan Rp{{ number_format($depositFee ?? 169, 0, ',', '.') }}/transaksi). Bila saldo habis, transaksi tidak bisa diselesaikan.</span>
                                            </div>
                                        </div>
                                        @can('view_billing')
                                            <a href="{{ route('deposit.index') }}" class="btn btn-danger btn-sm mt-3 mt-sm-0">Top Up Sekarang</a>
                                        @endcan
                                    </div>
                                </div>
                            @elseif (!empty($depositExpiringSoon) && !empty($depositExpiresAt))
                                <div class="app-container container-xxl mt-4">
                                    <div class="alert alert-warning d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-0">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-outline ki-time fs-2x text-warning me-3"></i>
                                            <div>
                                                <h5 class="mb-1">Saldo akan hangus</h5>
                                                <span class="text-gray-700">Sisa saldo Rp{{ number_format($depositPoints ?? 0, 0, ',', '.') }} akan hangus pada {{ \Carbon\Carbon::parse($depositExpiresAt)->translatedFormat('d F Y') }} bila tidak ada transaksi/top-up. Saldo hangus setelah {{ \App\Tenancy\DepositConfig::expiryDays() }} hari tidak dipakai.</span>
                                            </div>
                                        </div>
                                        @can('view_billing')
                                            <a href="{{ route('deposit.index') }}" class="btn btn-warning btn-sm mt-3 mt-sm-0">Lihat Deposit</a>
                                        @endcan
                                    </div>
                                </div>
                            @endif
                        @endif
                        {{-- Peringatan: shift/kas hari sebelumnya belum ditutup (tidak mengunci) --}}
                        @if ($shiftStale ?? false)
                            @php $shiftWord = optional($currentTenant)->isUmkm() ? 'Kas' : 'Shift'; @endphp
                            <div class="alert alert-warning d-flex align-items-center flex-wrap gap-3 mx-5 mt-4 mb-0" role="alert">
                                <i class="ki-outline ki-time fs-2x text-warning"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-bold fs-6 text-gray-900">{{ $shiftWord }} kemarin belum ditutup</div>
                                    <div class="fs-7 text-gray-700">Masih ada {{ strtolower($shiftWord) }} terbuka dari hari sebelumnya. Harap <b>tutup {{ strtolower($shiftWord) }}</b> dulu untuk memulai {{ strtolower($shiftWord) }} hari ini.</div>
                                </div>
                                <a href="{{ route('shifts.index') }}" class="btn btn-sm btn-warning fw-bold">Tutup {{ $shiftWord }}</a>
                            </div>
                            <script>
                                (function () {
                                    try { if (sessionStorage.getItem('mooda_shift_stale')) return; } catch (e) {}
                                    document.addEventListener('DOMContentLoaded', function () {
                                        try { sessionStorage.setItem('mooda_shift_stale', '1'); } catch (e) {}
                                        if (!window.Swal) return;
                                        Swal.fire({
                                            icon: 'warning',
                                            title: '{{ $shiftWord }} Belum Ditutup',
                                            html: 'Anda masih punya {{ strtolower($shiftWord) }} yang belum ditutup dari hari sebelumnya.<br>Harap <b>tutup {{ strtolower($shiftWord) }}</b> dulu untuk memulai {{ strtolower($shiftWord) }} hari ini.',
                                            confirmButtonText: 'Tutup {{ $shiftWord }} Sekarang',
                                            showCancelButton: true, cancelButtonText: 'Nanti',
                                        }).then(function (r) { if (r.isConfirmed) window.location.href = "{{ route('shifts.index') }}"; });
                                    });
                                })();
                            </script>
                        @endif
                        <!--begin::Content-->
                        @yield('content')
                        <!--end::Content-->
                    </div>
                    <!--end::Content wrapper-->
                    <!--begin::Footer-->
                    @include('backend.layout.footer')
                    <!--end::Footer-->
                </div>
                <!--end:::Main-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Page-->
    </div>
    <!--end::App-->

    <!--begin::Javascript-->
    <script>
        var hostUrl = "{{ asset('assets/') }}";
    </script>
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    {{-- CSRF untuk SEMUA AJAX via header X-CSRF-TOKEN (cara resmi Laravel).
         Penting: request DELETE/PUT dgn _token di body TIDAK dibaca Laravel -> 419;
         header ini membuat tombol Hapus/Edit (DELETE/PUT) lolos CSRF di semua halaman. --}}
    <script>
        if (window.jQuery) {
            jQuery.ajaxSetup({ headers: { 'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content') } });
        }
    </script>
    {{-- fullcalendar dihapus: tidak dipakai di halaman mana pun. --}}
    {{-- datatables.bundle.js (2.4MB) kini di-load PER-HALAMAN via @push('scripts')
         hanya di halaman tabel — Kasir/Dapur/Dashboard tak lagi memuatnya. --}}
    <script src="{{ asset('assets/js/widgets.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/custom/widgets.js') }}"></script>
    <script src="{{ asset('assets/js/custom/apps/chat/chat.js') }}"></script>
    <script src="{{ asset('assets/js/custom/utilities/modals/upgrade-plan.js') }}"></script>
    <script src="{{ asset('assets/js/custom/utilities/modals/create-campaign.js') }}"></script>
    <script src="{{ asset('assets/js/custom/utilities/modals/users-search.js') }}"></script>
    <!--end::Global Javascript Bundle-->

    <!-- Dexie.js for IndexedDB management (di-vendor lokal, tanpa CDN eksternal) -->
    <script src="{{ asset('assets/plugins/custom/dexie/dexie.min.js') }}"></script>

    <!-- Service Worker registration & Offline PWA Sync Engine -->
    <script>
        // 1. Initialize Dexie Database globally
        window.posDB = new Dexie('MoodaPOS');
        window.posDB.version(2).stores({
            menus: 'id, uuid, name, price, category_id',
            categories: 'id, name',
            promos: 'id, name',
            settings: 'id, tax_rate',
            offline_orders: 'uuid, invoice_no, status'
        });

        // 2. Global Sync State Flag
        window.isSyncing = false;

        // 3. Update Connection & Sync Status UI
        window.updateConnectionStatus = async function() {
            const statusBadge = document.getElementById('pwa-sync-status');
            const statusDot = document.getElementById('pwa-status-dot');
            const statusText = document.getElementById('pwa-status-text');

            if (!statusBadge || !statusDot || !statusText) return;

            const pendingCount = await window.posDB.offline_orders
                .where('status')
                .equals('pending_sync')
                .count();

            if (navigator.onLine) {
                if (pendingCount > 0) {
                    // Online but has unsynced orders
                    statusBadge.className = "badge badge-light-warning fw-bold px-4 py-3 d-flex align-items-center cursor-pointer";
                    statusDot.className = "bullet bullet-dot bg-warning h-8px w-8px me-2 spinner-border spinner-border-sm border-0";
                    statusText.innerText = `☁️ ${pendingCount} Syncing...`;
                    
                    // Trigger auto-sync
                    window.triggerManualSync();
                } else {
                    // Online & Clean
                    statusBadge.className = "badge badge-light-success fw-bold px-4 py-3 d-flex align-items-center cursor-pointer";
                    statusDot.className = "bullet bullet-dot bg-success h-8px w-8px me-2";
                    statusText.innerText = "Online";
                }
            } else {
                if (pendingCount > 0) {
                    // Offline with unsynced orders
                    statusBadge.className = "badge badge-light-danger fw-bold px-4 py-3 d-flex align-items-center cursor-pointer";
                    statusDot.className = "bullet bullet-dot bg-danger h-8px w-8px me-2";
                    statusText.innerText = `☁️ ${pendingCount} Pending Sync`;
                } else {
                    // Offline and Clean
                    statusBadge.className = "badge badge-light-danger fw-bold px-4 py-3 d-flex align-items-center cursor-pointer";
                    statusDot.className = "bullet bullet-dot bg-danger h-8px w-8px me-2";
                    statusText.innerText = "Offline Mode";
                }
            }
        };

        // 4. Synchronization Engine
        window.triggerManualSync = async function() {
            if (window.isSyncing || !navigator.onLine) return;

            const pendingOrders = await window.posDB.offline_orders
                .where('status')
                .equals('pending_sync')
                .toArray();

            if (pendingOrders.length === 0) return;

            window.isSyncing = true;
            console.log('[PWA Sync] Syncing ' + pendingOrders.length + ' offline orders to VPS...');

            $.ajax({
                url: "{{ route('kasir.sync-offline') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    orders: pendingOrders
                },
                success: async function(res) {
                    if (res.success) {
                        console.log('[PWA Sync] Successfully synced offline orders!');
                        
                        // Update status in local Dexie
                        for (let order of pendingOrders) {
                            await window.posDB.offline_orders.update(order.uuid, { status: 'synced' });
                        }
                        
                        // Show sweet alert if we are currently on the dashboard/kasir pages
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Sync Sukses!',
                                text: `${pendingOrders.length} transaksi offline berhasil di-upload ke server VPS!`,
                                icon: 'success',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    } else {
                        console.error('[PWA Sync] Sync failed:', res.error);
                    }
                    window.isSyncing = false;
                    window.updateConnectionStatus();
                },
                error: function(err) {
                    console.error('[PWA Sync] Sync network/server error:', err);
                    window.isSyncing = false;
                    window.updateConnectionStatus();
                }
            });
        };

        // 5. Network listeners & Initial trigger
        window.addEventListener('online', window.updateConnectionStatus);
        window.addEventListener('offline', window.updateConnectionStatus);

        document.addEventListener('DOMContentLoaded', () => {
            window.updateConnectionStatus();
            
            // Register Service Worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('{{ asset('sw.js') }}')
                    .then(reg => console.log('Service Worker registered successfully!', reg.scope))
                    .catch(err => console.error('Service Worker registration failed:', err));
            }
        });
    </script>

    <!-- Konfigurasi & engine cetak struk (multi-metode) -->
    @php
        $ps = $posSetting ?? null;
        $moodaPrintCfg = [
            'method' => optional($ps)->printer_method ?? 'auto',
            'paper_width' => (int) (optional($ps)->paper_width ?? 58),
            'store_name' => optional($ps)->store_name ?? 'Mooda',
            'tax_rate' => (int) (optional($ps)->tax_rate ?? 0),
            // Alamat & telepon di struk mengikuti toggle "tampilkan" (default tampil).
            'store_address' => (optional($ps)->receipt_show_address ?? true) ? (optional($ps)->address ?? '') : '',
            'store_phone' => (optional($ps)->receipt_show_phone ?? true) ? (optional($ps)->phone ?? '') : '',
            'receipt_header' => optional($ps)->receipt_header ?? '',
            'receipt_footer' => optional($ps)->receipt_footer ?? 'Terima kasih atas kunjungan Anda!',
            'qz_url' => asset('assets/plugins/custom/qz/qz-tray.js'),
        ];
    @endphp
    <script>
        window.MOODA_PRINT = @json($moodaPrintCfg);
    </script>
    <script src="{{ asset('assets/js/mooda-print.js') }}?v={{ @filemtime(public_path('assets/js/mooda-print.js')) }}"></script>
    <script>
        // APK: saat pertama masuk (sekali per sesi aplikasi), bantu pilih printer Bluetooth
        // otomatis supaya siap sebelum transaksi. Di browser biasa: tidak melakukan apa-apa.
        (function () {
            try { if (sessionStorage.getItem('mooda_printer_autosetup')) return; } catch (e) {}
            setTimeout(function () {
                try { sessionStorage.setItem('mooda_printer_autosetup', '1'); } catch (e) {}
                if (window.MoodaPrint && MoodaPrint.autoSetup) { try { MoodaPrint.autoSetup(); } catch (e) {} }
            }, 1200);
        })();
    </script>

    @include('partials._number_format')
    @stack('scripts')

    @can('view_expense')
        {{-- Modal catat pengeluaran cepat (dipicu dari sidebar) --}}
        <div class="modal fade" id="modal-quick-expense" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-550px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold text-danger">Catat Pengeluaran</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
                    </div>
                    <div class="modal-body mx-5 my-5">
                        <form id="form-quick-expense">
                            @csrf
                            <div class="mb-4">
                                <label class="required fs-6 fw-semibold mb-2">Tanggal</label>
                                <input type="date" class="form-control" name="date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" required>
                            </div>
                            <div class="mb-4">
                                <label class="required fs-6 fw-semibold mb-2">Kategori</label>
                                <input type="text" class="form-control" name="category" placeholder="Contoh: Bahan Baku, Listrik" list="qe-cat-list" required>
                                <datalist id="qe-cat-list">
                                    <option value="Bahan Baku"></option>
                                    <option value="Gaji Karyawan"></option>
                                    <option value="Listrik & Air"></option>
                                    <option value="Sewa Tempat"></option>
                                    <option value="Perlengkapan"></option>
                                    <option value="Lain-lain"></option>
                                </datalist>
                            </div>
                            <div class="mb-4">
                                <label class="required fs-6 fw-semibold mb-2">Nominal</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light-danger text-danger fw-bold">Rp</span>
                                    <input type="number" class="form-control fw-bold" name="amount" value="0" min="0" required>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="fs-6 fw-semibold mb-2">Keterangan (opsional)</label>
                                <textarea class="form-control" name="notes" rows="2"></textarea>
                            </div>
                            <div class="text-center pt-6">
                                <button type="submit" class="btn btn-danger w-100" id="btn-quick-expense-save">Simpan Pengeluaran</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script>
            (function () {
                var form = document.getElementById('form-quick-expense');
                if (!form) return;
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var btn = document.getElementById('btn-quick-expense-save');
                    btn.disabled = true; btn.textContent = 'Menyimpan...';
                    $.ajax({
                        url: "{{ route('expenses.store') }}", method: 'POST', data: $(form).serialize(),
                        success: function (res) {
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: (res && res.message) || 'Pengeluaran dicatat.', timer: 1800, showConfirmButton: false })
                                .then(function () { location.reload(); });
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && (xhr.responseJSON.message || 'Periksa isian form.')) || 'Gagal menyimpan.';
                            Swal.fire('Gagal', msg, 'error');
                            btn.disabled = false; btn.textContent = 'Simpan Pengeluaran';
                        }
                    });
                });
            })();
        </script>
    @endcan
    <!--end::Javascript-->
</body>
<!--end::Body-->

</html>
