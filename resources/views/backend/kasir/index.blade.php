@extends('backend.layout.app')
@section('title', 'Kasir POS')

@php
    // Split bill & Merge table = kasir lanjutan, KHUSUS paket Customize.
    // Superadmin tetap bisa melihat saat mode kasir (sama seperti modul HPP/Inventory).
    $canSplitMerge = auth()->user()?->isSuperadmin()
        || \App\Tenancy\Plan::tenantAllows(app(\App\Tenancy\TenantManager::class)->tenant(), 'split_merge');
@endphp

@push('stylesheets')
    <style>
        .pos-menu-card { cursor: pointer; transition: transform .1s ease, box-shadow .1s ease; }
        .pos-menu-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
        .pos-menu-img { height: 90px; width: 100%; object-fit: cover; border-radius: .5rem .5rem 0 0; background:#f5f8fa; }
        .pos-right-col { position: sticky; top: 90px; }
        .pos-orders-scroll { max-height: 32vh; overflow-y: auto; }
        .pos-cart-scroll { max-height: 34vh; overflow-y: auto; }
        .cat-pill.active { background: var(--bs-primary); color:#fff; }
        @media (max-width: 767.98px) {
            .pos-right-col { position: static; }
            .pos-orders-scroll { max-height: 40vh; }
            .pos-cart-scroll { max-height: 50vh; }
        }
    </style>
@endpush

@section('content')
    <div id="kt_app_content" class="app-content flex-column-fluid mt-4">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- Toolbar Kasir --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
                <h1 class="fs-3 fw-bold text-gray-900 m-0">
                    <i class="ki-outline ki-handcart fs-2 me-2 text-primary"></i>Kasir
                </h1>
                <div class="d-flex align-items-center gap-2">
                    @if ($isOperator)
                    <div class="form-check form-switch form-check-custom form-check-solid me-1" title="Tampilkan / sembunyikan pilihan meja">
                        <input class="form-check-input h-20px w-30px" type="checkbox" id="toggle-tables" {{ $showTables ? 'checked' : '' }}>
                        <label class="form-check-label fs-8 text-gray-600 ms-2" for="toggle-tables">Meja</label>
                    </div>
                    @endif
                    <button id="btn-printer" type="button" class="btn btn-sm btn-light-primary d-none">
                        <i class="ki-outline ki-printer fs-4 me-1"></i><span id="printer-label">Printer</span>
                    </button>
                    <button id="btn-sync" type="button" class="btn btn-sm btn-light-success">
                        <i class="ki-outline ki-arrows-circle fs-4 me-1"></i>Sync
                        <span id="sync-count" class="badge badge-danger ms-1 d-none">0</span>
                    </button>
                    <button id="btn-reload-page" type="button" class="btn btn-sm btn-icon btn-light" title="Muat ulang halaman" onclick="window.location.reload()">
                        <i class="ki-outline ki-arrows-loop fs-4"></i>
                    </button>
                    <span id="net-status" class="badge badge-light-success">
                        <span class="bullet bullet-dot bg-success me-1"></span>Online
                    </span>
                </div>
            </div>

            {{-- Banner mode LIHAT untuk peninjau (owner/admin/superadmin non-operator). --}}
            @unless ($isOperator)
                <div class="alert alert-primary d-flex align-items-start mb-4">
                    <i class="ki-outline ki-eye fs-2x me-3 text-primary"></i>
                    <div>
                        <div class="fw-bold fs-6 text-gray-900">Mode Lihat — Akun Owner/Admin</div>
                        <div class="fs-7 text-gray-700">Anda masuk sebagai <b>peninjau</b>: hanya bisa <b>melihat pesanan berjalan</b> serta menandai salah / menghapus. Panel input menu, keranjang & pembayaran disembunyikan.
                        Untuk membuka <b>tampilan kasir lengkap</b> (input pesanan &amp; bayar), silakan <b>login memakai akun ber-role Kasir</b>.</div>
                    </div>
                </div>
            @endunless

            <div class="row g-4">
                {{-- =============== KIRI: NAMA + MENU (hanya OPERATOR/kasir) =============== --}}
                @if ($isOperator)
                <div class="col-12 col-md-7 col-xl-8">
                    <div class="card card-flush shadow-sm mb-4">
                        <div class="card-body py-4">
                            <label class="fw-bold fs-6 mb-2">Nama Pelanggan</label>
                            <div class="d-flex gap-2">
                                <input type="text" id="customer-name" class="form-control form-control-solid"
                                    placeholder="Pelanggan (default)" autocomplete="off">
                                <button type="button" class="btn btn-light-primary fw-bold text-nowrap" id="btn-default-name"
                                    title="Isi cepat tanpa nama">
                                    <i class="ki-outline ki-flash fs-4"></i> Cepat
                                </button>
                            </div>

                            {{-- Pilih Meja (statis 1..25, opsional). Kotak kecil biar hemat ruang di HP. --}}
                            <div class="mt-3 {{ $showTables ? '' : 'd-none' }}" id="table-wrap">
                                <label class="fw-bold fs-7 mb-2 d-block text-gray-600">Meja <span class="fw-normal text-muted">(opsional)</span></label>
                                <div class="d-flex flex-wrap gap-1" id="table-picker">
                                    <button type="button" class="btn btn-sm btn-primary text-white table-pick px-3" data-table="">Tanpa</button>
                                    @if (!empty($useDynamicTables))
                                        @foreach ($diningTables as $t)
                                            <button type="button" class="btn btn-sm btn-light table-pick px-3" data-table="{{ $t->name }}">{{ $t->name }}</button>
                                        @endforeach
                                        @if ($diningTables->isEmpty())
                                            <span class="text-muted fs-8 align-self-center ms-2">Belum ada meja — tambah di menu Manajemen Meja.</span>
                                        @endif
                                    @else
                                        @for ($i = 1; $i <= 25; $i++)
                                            <button type="button" class="btn btn-sm btn-light table-pick" data-table="{{ $i }}" style="width:34px;height:34px;padding:0;line-height:1">{{ $i }}</button>
                                        @endfor
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-flush shadow-sm">
                        <div class="card-header pt-4 gap-3 flex-wrap">
                            <div class="d-flex align-items-center position-relative flex-grow-1">
                                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                                <input type="text" id="menu-search" class="form-control form-control-solid ps-12"
                                    placeholder="Cari menu...">
                            </div>
                        </div>
                        <div class="card-body pt-3">
                            <div class="d-flex flex-nowrap overflow-auto gap-2 pb-3 mb-3" id="category-pills">
                                <button type="button" class="btn btn-sm btn-active-primary cat-pill active" data-cat="all">Semua</button>
                                @foreach ($categories as $cat)
                                    <button type="button" class="btn btn-sm btn-light cat-pill text-nowrap" data-cat="{{ $cat->id }}">{{ $cat->name }}</button>
                                @endforeach
                            </div>

                            <div class="row row-cols-2 row-cols-xl-3 g-3" id="menu-grid">
                                {{-- diisi via JS --}}
                            </div>
                            <div id="menu-empty" class="text-center text-muted py-10 d-none">
                                <i class="ki-outline ki-magnifier fs-3x mb-3"></i>
                                <div>Menu tidak ditemukan.</div>
                            </div>
                            {{-- Pagination menu (client-side; tetap offline-ready) --}}
                            <div id="menu-pagination" class="d-none justify-content-center align-items-center gap-3 mt-4"></div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- =============== KANAN: PESANAN BERJALAN + KERANJANG =============== --}}
                {{-- Non-operator (owner/admin): full-width, hanya lihat + Tandai Salah/Hapus. --}}
                <div class="col-12 {{ $isOperator ? 'col-md-5 col-xl-4' : '' }}">
                    <div class="pos-right-col">
                        {{-- Pesanan berjalan --}}
                        <div class="card card-flush shadow-sm mb-4">
                            <div class="card-header pt-4 pb-0 min-h-40px">
                                <ul class="nav nav-pills nav-pills-sm gap-2 w-100 flex-wrap">
                                    <li class="nav-item">
                                        <a class="nav-link btn btn-sm btn-color-muted btn-active-light-primary fw-bold active"
                                            data-bs-toggle="tab" href="#tab-processing">Sedang Diproses
                                            <span class="badge badge-primary ms-1" id="count-processing">0</span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link btn btn-sm btn-color-muted btn-active-light-success fw-bold"
                                            data-bs-toggle="tab" href="#tab-completed">Selesai
                                            <span class="badge badge-success ms-1" id="count-completed">0</span></a>
                                    </li>
                                    {{-- Muncul hanya bila shift aktif dibuka sebelum hari ini (melewati tengah malam). --}}
                                    <li class="nav-item d-none" id="tabli-prev-completed">
                                        <a class="nav-link btn btn-sm btn-color-muted btn-active-light-success fw-bold"
                                            data-bs-toggle="tab" href="#tab-prev-completed">Selesai Sebelumnya
                                            <span class="badge badge-secondary ms-1" id="count-prev-completed">0</span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link btn btn-sm btn-color-muted btn-active-light-warning fw-bold"
                                            data-bs-toggle="tab" href="#tab-offline">Offline
                                            <span class="badge badge-warning ms-1" id="count-offline">0</span></a>
                                    </li>
                                    <li class="nav-item ms-sm-auto d-flex align-items-center flex-wrap gap-1">
                                        {{-- MERGE TABLE: gabungkan beberapa nota belum lunas (paket Customize) --}}
                                        @if ($canSplitMerge)
                                            <button class="btn btn-sm btn-light-warning fw-bold" id="btn-merge-open"
                                                title="Gabung beberapa nota belum lunas jadi satu nota">
                                                <i class="ki-outline ki-arrow-mix fs-4"></i>
                                                <span class="d-none d-sm-inline">Gabung Nota</span></button>
                                        @endif
                                        @can('sales.target')
                                            <button class="btn btn-sm btn-icon btn-light-primary" id="btn-set-target"
                                                title="Set / ubah target penjualan hari ini">
                                                <i class="ki-outline ki-dollar fs-4"></i></button>
                                        @endcan
                                        @can('sales.clear')
                                            <button class="btn btn-sm btn-icon btn-light-danger" id="btn-reset-today"
                                                title="Reset penjualan hari ini (hapus semua pesanan hari ini)">
                                                <i class="ki-outline ki-trash fs-4"></i></button>
                                        @endcan
                                        <button class="btn btn-sm btn-icon btn-light" id="btn-refresh-orders" title="Muat ulang">
                                            <i class="ki-outline ki-arrows-circle fs-4"></i></button>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body py-3">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active pos-orders-scroll" id="tab-processing">
                                        <div id="list-processing"></div>
                                    </div>
                                    <div class="tab-pane fade pos-orders-scroll" id="tab-completed">
                                        {{-- Kartu penanda pesanan SALAH (tidak dihitung ke omzet). --}}
                                        <div id="completed-voided-card" class="d-none alert bg-light-danger border border-danger border-dashed d-flex align-items-center p-3 mb-3">
                                            <i class="ki-outline ki-information-5 fs-2x text-danger me-3"></i>
                                            <div class="fs-8 text-gray-800">
                                                <b><span id="completed-voided-count">0</span> pesanan ditandai SALAH</b> —
                                                tidak dihitung ke penjualan/omzet hari ini.
                                            </div>
                                        </div>
                                        <div id="list-completed"></div>
                                    </div>
                                    <div class="tab-pane fade pos-orders-scroll" id="tab-prev-completed">
                                        <div class="alert bg-light-info border border-info border-dashed fs-8 text-gray-800 p-3 mb-3" id="prev-completed-note">
                                            Pesanan <b>selesai dari sesi/shift sebelumnya</b> (dibuka sejak hari kemarin & belum ditutup). Tetap tampil agar tidak hilang saat pergantian hari.
                                        </div>
                                        <div id="list-prev-completed"></div>
                                    </div>
                                    <div class="tab-pane fade pos-orders-scroll" id="tab-offline">
                                        <div id="list-offline"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Keranjang + Checkout (hanya OPERATOR/kasir): offcanvas di layar kecil, inline di md+ --}}
                        @if ($isOperator)
                        <div class="offcanvas-md offcanvas-end" tabindex="-1" id="cart-offcanvas" style="--bs-offcanvas-width: min(430px, 92vw);">
                            <div class="offcanvas-header border-bottom d-flex justify-content-between align-items-center py-3 d-md-none">
                                <h4 class="fw-bold mb-0 text-gray-800"><i class="ki-outline ki-basket fs-2 me-2 text-primary"></i>Keranjang</h4>
                                {{-- data-bs-dismiss tidak bekerja pada offcanvas RESPONSIF (.offcanvas-md):
                                     Bootstrap mencari .closest('.offcanvas') yang tak ada -> tutup via JS eksplisit. --}}
                                <button type="button" class="btn btn-light-danger btn-active-danger fw-bold d-inline-flex align-items-center gap-2 px-4" aria-label="Tutup keranjang" style="min-height:46px;"
                                    onclick="try{bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('cart-offcanvas')).hide()}catch(e){}">
                                    <i class="ki-outline ki-cross-circle fs-2"></i> Tutup
                                </button>
                            </div>
                            <div class="offcanvas-body">
                                <div class="card card-flush shadow-sm w-100">
                            <div class="card-header pt-4">
                                <h3 class="card-title fw-bold"><i class="ki-outline ki-basket fs-2 me-2"></i>Keranjang</h3>
                                <div class="card-toolbar">
                                    <button class="btn btn-sm btn-light-danger" id="btn-clear-cart"><i class="ki-outline ki-trash fs-5"></i></button>
                                </div>
                            </div>
                            <div class="card-body py-3">
                                <div class="pos-cart-scroll mb-3" id="cart-items">
                                    <div class="text-center text-muted py-8" id="cart-empty">
                                        <i class="ki-outline ki-basket fs-3x mb-2"></i>
                                        <div>Keranjang kosong</div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="fw-semibold fs-7 text-muted mb-1">Promo (opsional)</label>
                                    <select id="promo-select" class="form-select form-select-sm form-select-solid">
                                        <option value="">Tanpa promo</option>
                                        @foreach ($promos as $promo)
                                            <option value="{{ $promo->id }}" data-type="{{ $promo->discount_type }}"
                                                data-value="{{ $promo->discount_value }}">
                                                {{ $promo->name }}
                                                ({{ $promo->discount_type === 'percentage' ? $promo->discount_value . '%' : 'Rp ' . number_format($promo->discount_value, 0, ',', '.') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="separator my-3"></div>

                                <div class="d-flex justify-content-between mb-1"><span class="text-muted">Subtotal</span><span id="sum-subtotal">Rp 0</span></div>
                                <div class="d-flex justify-content-between mb-1"><span class="text-muted">Diskon</span><span id="sum-discount" class="text-danger">- Rp 0</span></div>
                                <div class="d-flex justify-content-between mb-1"><span class="text-muted">Pajak (<span id="tax-rate">{{ (int) ($setting->tax_rate ?? 0) }}</span>%)</span><span id="sum-tax">Rp 0</span></div>
                                <div class="d-flex justify-content-between fw-bold fs-4 mb-3"><span>Total</span><span id="sum-total" class="text-success">Rp 0</span></div>

                                {{-- Banner MODE TAMBAH (gabung item ke pesanan BELUM LUNAS) --}}
                                <div id="append-banner" class="alert alert-warning d-flex align-items-center justify-content-between py-2 px-3 mb-3 d-none">
                                    <span class="fw-semibold fs-8">➕ Menambah menu ke <b id="append-label">Pesanan</b></span>
                                    <button type="button" class="btn btn-sm btn-light-danger py-1 px-2" id="append-cancel">Batal</button>
                                </div>
                                <div class="d-grid mb-3 d-none" id="append-submit-wrap">
                                    <button class="btn btn-warning fw-bold" id="btn-append-submit">
                                        <i class="ki-outline ki-plus-square fs-3 me-1"></i> Tambah ke Pesanan
                                    </button>
                                </div>

                                <div id="checkout-normal">
                                {{-- Metode Pembayaran --}}
                                <label class="fw-semibold fs-7 text-muted mb-2 d-block">Metode Pembayaran</label>
                                <div class="btn-group w-100 mb-3" role="group" id="pay-method-group">
                                    <input type="radio" class="btn-check" name="pay_method" id="pm-cash" value="cash" autocomplete="off">
                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary" for="pm-cash"><i class="ki-outline ki-dollar fs-4 me-1"></i>Tunai</label>
                                    <input type="radio" class="btn-check" name="pay_method" id="pm-qris" value="qris" autocomplete="off">
                                    <label class="btn btn-outline btn-outline-dashed btn-active-light-primary" for="pm-qris"><i class="ki-outline ki-scan-barcode fs-4 me-1"></i>QRIS</label>
                                </div>

                                <div id="cash-box" class="mb-3 d-none">
                                    <label class="fw-semibold fs-7 text-muted mb-1">Uang Diterima</label>
                                    <input type="number" id="cash-received" class="form-control form-control-solid" placeholder="0" min="0">
                                    <div class="d-flex justify-content-between mt-2 fw-bold">
                                        <span class="text-muted">Kembalian</span><span id="cash-change" class="text-primary">Rp 0</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mt-2" id="quick-cash"></div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary fw-bold" id="btn-pay-now">
                                        <i class="ki-outline ki-check-circle fs-3 me-1"></i> Bayar Sekarang (Lunas)
                                    </button>
                                    <button class="btn btn-light-warning fw-bold" id="btn-pay-later">
                                        <i class="ki-outline ki-timer fs-3 me-1"></i> Bayar Nanti (Kirim ke Dapur)
                                    </button>
                                </div>
                                </div>{{-- /#checkout-normal --}}
                            </div>
                                </div>{{-- /card --}}
                            </div>{{-- /offcanvas-body --}}
                        </div>{{-- /offcanvas keranjang --}}

                        {{-- Notif floating MODE TAMBAH (layar kecil): pengingat "silakan tambah menu" + Batal.
                             Sembunyi saat keranjang dibuka agar tak menutupi tampilan keranjang. --}}
                        <div id="append-float-notif" class="d-none position-fixed start-50 translate-middle-x shadow-lg rounded-3 bg-warning text-gray-900 px-3 py-2 d-flex align-items-center gap-2"
                             style="bottom: 90px; z-index: 1035; max-width: 94vw;">
                            <span class="fw-semibold fs-8">➕ Silakan tambahkan menu ke <b id="append-float-label">Pesanan</b></span>
                            <button type="button" class="btn btn-sm btn-dark py-1 px-2 flex-shrink-0" id="append-float-cancel">Batal</button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FAB Keranjang (hanya OPERATOR/kasir, layar kecil): buka keranjang mengambang --}}
    @if ($isOperator)
    <button type="button" class="btn btn-primary d-md-none shadow-lg" id="cart-fab"
        data-bs-toggle="offcanvas" data-bs-target="#cart-offcanvas"
        style="position:fixed; left:50%; transform:translateX(-50%); bottom:16px; z-index:1030; border-radius:999px; padding:.7rem 1.15rem;">
        <i class="ki-outline ki-basket fs-3 me-1"></i>
        <span class="fw-bold" id="cart-fab-count">0</span> item
        <span class="mx-1 opacity-50">·</span>
        <span class="fw-bold" id="cart-fab-total">Rp 0</span>
    </button>
    @endif
    <script>
        // Sembunyikan FAB keranjang saat sidebar drawer (mobile) terbuka agar tidak menutupi
        // tombol Setelan di footer sidebar.
        (function () {
            var sb = document.getElementById('kt_app_sidebar');
            var fab = document.getElementById('cart-fab');
            if (!sb || !fab) return;
            var sync = function () { fab.style.display = sb.classList.contains('drawer-on') ? 'none' : ''; };
            new MutationObserver(sync).observe(sb, { attributes: true, attributeFilter: ['class'] });
            sync();
        })();
    </script>

    {{-- Notif khusus KASIR: shift/kas hari sebelumnya belum ditutup (tidak mengunci) --}}
    @if ($shiftStale ?? false)
        @php $shiftWord = optional($currentTenant)->isUmkm() ? 'Kas' : 'Shift'; @endphp
        <script>
            (function () {
                try { if (sessionStorage.getItem('mooda_shift_stale_kasir')) return; } catch (e) {}
                document.addEventListener('DOMContentLoaded', function () {
                    try { sessionStorage.setItem('mooda_shift_stale_kasir', '1'); } catch (e) {}
                    if (!window.Swal) return;
                    Swal.fire({
                        icon: 'warning',
                        title: '{{ $shiftWord }} Belum Ditutup',
                        html: '{{ $shiftWord }} dari hari sebelumnya belum ditutup. Sebaiknya <b>tutup {{ strtolower($shiftWord) }}</b> dulu sebelum melayani transaksi hari ini.',
                        confirmButtonText: 'Tutup {{ $shiftWord }}',
                        showCancelButton: true, cancelButtonText: 'Lanjut Jualan',
                    }).then(function (r) { if (r.isConfirmed) window.location.href = "{{ route('shifts.index') }}"; });
                });
            })();
        </script>
    @endif

    {{-- ===== Modal: Add-On saat menambah menu ===== --}}
    <div class="modal fade" id="modal-addon" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-4">
                    <h3 class="fw-bold mb-0" id="addon-menu-name">Menu</h3>
                    <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="addon-menu-id">
                    <div id="addon-list" class="mb-4"></div>
                    <div class="row g-3">
                        <div class="col-5">
                            <label class="fw-semibold fs-7 text-muted mb-1">Qty</label>
                            <input type="number" id="addon-qty" class="form-control form-control-solid" value="1" min="1">
                        </div>
                        <div class="col-7">
                            <label class="fw-semibold fs-7 text-muted mb-1">Catatan</label>
                            <input type="text" id="addon-note" class="form-control form-control-solid" placeholder="mis. tidak pedas">
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-3">
                    <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary fw-bold" id="btn-addon-confirm">Tambah ke Keranjang</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Modal: Pembayaran saat menyelesaikan order belum lunas ===== --}}
    <div class="modal fade" id="modal-pay" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-4">
                    <h3 class="fw-bold mb-0">Pembayaran</h3>
                    <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="pay-order-id">
                    <div class="text-center mb-4">
                        <div class="text-muted">Total yang harus dibayar</div>
                        <div class="fs-2hx fw-bold text-success" id="pay-total">Rp 0</div>
                    </div>
                    <div class="btn-group w-100 mb-3" role="group">
                        <input type="radio" class="btn-check" name="pay_method_modal" id="pmm-cash" value="cash" autocomplete="off" checked>
                        <label class="btn btn-outline btn-outline-dashed btn-active-light-primary" for="pmm-cash">Tunai</label>
                        <input type="radio" class="btn-check" name="pay_method_modal" id="pmm-qris" value="qris" autocomplete="off">
                        <label class="btn btn-outline btn-outline-dashed btn-active-light-primary" for="pmm-qris">QRIS</label>
                    </div>
                    <div id="modal-cash-box">
                        <label class="fw-semibold fs-7 text-muted mb-1">Uang Diterima</label>
                        <input type="number" id="modal-cash-received" class="form-control form-control-solid" placeholder="0" min="0">
                        <div class="d-flex justify-content-between mt-2 fw-bold"><span class="text-muted">Kembalian</span><span id="modal-cash-change" class="text-primary">Rp 0</span></div>
                    </div>
                </div>
                <div class="modal-footer py-3">
                    <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary fw-bold" id="btn-pay-confirm">Bayar & Selesaikan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Modal: Detail order (View) ===== --}}
    <div class="modal fade" id="modal-detail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-4">
                    <h3 class="fw-bold mb-0">Detail Pesanan</h3>
                    <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body" id="detail-body">
                    <div class="text-center py-6"><span class="spinner-border text-primary"></span></div>
                </div>
            </div>
        </div>
    </div>
    {{-- ================= MODAL SPLIT BILL (paket Customize) ================= --}}
    @if ($canSplitMerge)
    <div class="modal fade" id="modal-split" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header py-4">
                    <div>
                        <h3 class="fw-bold mb-0">Split Bill — Pecah Nota <span class="text-muted fs-6 fw-normal" id="split-order-label"></span></h3>
                        <span class="text-muted fs-8">Tentukan mau jadi berapa struk, lalu bagi porsi tiap item ke struk yang dituju.</span>
                    </div>
                    <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-wrap align-items-end gap-4 mb-4">
                        <div>
                            <label class="form-label fw-semibold fs-7 mb-1">Pecah jadi berapa struk?</label>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-icon btn-light-warning" id="split-minus"><i class="ki-outline ki-minus fs-3"></i></button>
                                <input type="number" id="split-count" class="form-control form-control-solid text-center fw-bold"
                                    style="max-width:90px" min="2" max="6" value="2" readonly>
                                <button type="button" class="btn btn-sm btn-icon btn-light-warning" id="split-plus"><i class="ki-outline ki-plus fs-3"></i></button>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <button type="button" class="btn btn-sm btn-light-primary fw-bold" id="split-even">
                                <i class="ki-outline ki-arrows-circle fs-5 me-1"></i> Bagi rata otomatis
                            </button>
                            <button type="button" class="btn btn-sm btn-light fw-bold" id="split-reset">Kosongkan</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gy-2 mb-0" id="split-grid">
                            <thead><tr class="fw-bold text-muted bg-light fs-8" id="split-head"></tr></thead>
                            <tbody id="split-body"></tbody>
                            <tfoot class="fw-bold" id="split-foot"></tfoot>
                        </table>
                    </div>

                    <div class="alert alert-primary d-flex align-items-start py-3 fs-8 mt-4 mb-0">
                        <i class="ki-outline ki-information-5 fs-2 me-2"></i>
                        <div>
                            <b>Seluruh porsi harus terbagi</b> — kolom "sisa" tiap item wajib 0, dan tiap struk minimal berisi 1 porsi.
                            Struk #1 memakai nota asal (nomor antrian tetap); struk lain jadi nota baru di meja yang sama, status belum lunas.
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-3">
                    <span class="me-auto fs-8 fw-bold" id="split-status"></span>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning fw-bold" id="btn-split-confirm">Pecah Nota</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= MODAL MERGE TABLE ================= --}}
    <div class="modal fade" id="modal-merge" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-600px">
            <div class="modal-content">
                <div class="modal-header py-4">
                    <div>
                        <h3 class="fw-bold mb-0">Gabung Meja / Nota</h3>
                        <span class="text-muted fs-8">Item dari nota terpilih dipindah ke nota tujuan.</span>
                    </div>
                    <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-semibold fs-7 required">Nota Tujuan (menampung)</label>
                        <select id="merge-target" class="form-select form-select-solid"></select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold fs-7 required">Nota yang Digabungkan</label>
                        <div id="merge-sources" class="border rounded p-3" style="max-height:240px;overflow-y:auto"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold fs-7">Nomor Meja Setelah Gabung <span class="text-muted">(opsional)</span></label>
                        <input type="text" id="merge-table" class="form-control form-control-solid" placeholder="mis. 1+2">
                    </div>
                    <div class="alert alert-warning d-flex align-items-center py-3 fs-8 mb-0">
                        <i class="ki-outline ki-information-5 fs-2 me-2"></i>
                        Nota sumber akan dihapus setelah digabung. Hanya nota <b>belum lunas</b> yang bisa digabung.
                    </div>
                </div>
                <div class="modal-footer py-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning fw-bold" id="btn-merge-confirm">Gabungkan</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@php
    $menusData = $menus->map(fn($m) => [
        'id' => $m->id,
        'name' => $m->name,
        'price' => (float) $m->price,
        'category_id' => $m->category_id,
        'image' => $m->image ? asset('storage/menus/' . $m->image) : null,
        'addons' => $m->activeAddons->map(fn($a) => ['id' => $a->id, 'name' => $a->name, 'price' => (float) $a->price])->values(),
        'bestseller' => in_array($m->id, $bestsellerIds ?? [], true),
    ])->values();
@endphp
@push('scripts')
    <script>
        // ================= DATA DARI SERVER =================
        const MENUS = @json($menusData);
        const TAX_RATE = {{ (float) ($setting->tax_rate ?? 0) }};
        const ROUTES = {
            store:      "{{ route('kasir.store') }}",
            orders:     "{{ route('kasir.orders') }}",
            base:       "{{ url('admin/kasir/order') }}",   // + /{id}, /{id}/pay, /{id}/complete, DELETE /{id}
            print:      "{{ url('admin/kasir/print') }}",   // + /{id}
            resetToday: "{{ route('kasir.sales.reset-today') }}",
            setTarget:  "{{ route('kasir.sales.target') }}",
            merge:      "{{ route('kasir.merge') }}",
        };
        // Paket Customize saja: split bill & merge table. Server dijaga middleware plan:split_merge.
        const CAN_SPLIT_MERGE = @json($canSplitMerge);
        const CSRF = "{{ csrf_token() }}";
        // Hak akses owner: tombol HAPUS pesanan (hanya tab "Sedang Diproses"). Server jaga via can:order.delete.
        const CAN_DELETE = @json(auth()->user()->can('order.delete'));
        // Hak akses owner + kasir: tombol TANDAI SALAH (hanya tab "Selesai"). Server jaga via can:order.void.
        const CAN_VOID = @json(auth()->user()->can('order.void'));

        // ================= STATE =================
        let cart = [];
        let currentCat = 'all';

        const rupiah = n => 'Rp ' + Number(n || 0).toLocaleString('id-ID');
        const esc = s => $('<div>').text(s == null ? '' : s).html();
        const escAttr = s => String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const openPrint = url => window.open(url, '_blank');

        // ===== Cetak struk: bridge printer thermal (APK) atau window.print (browser) =====
        const STORE_NAME = @json($setting->store_name ?? 'Mooda');
        const hasPrinter = () => !!(window.AndroidPrinter && typeof window.AndroidPrinter.printReceipt === 'function');

        function doPrintReceipt(receipt, printUrl) {
            // Engine cetak terpusat (browser/qztray/webbluetooth/rawbt/native)
            if (window.MoodaPrint) { window.MoodaPrint.print(receipt, printUrl); return; }
            if (printUrl) openPrint(printUrl);
        }

        // ================= RENDER MENU (pagination client-side; tetap offline-ready) =================
        let menuPage = 1;
        const MENU_PAGE_SIZE = 8;

        function filteredMenus() {
            const q = ($('#menu-search').val() || '').toLowerCase();
            return MENUS.filter(m => {
                if (currentCat !== 'all' && String(m.category_id) !== String(currentCat)) return false;
                if (q && !m.name.toLowerCase().includes(q)) return false;
                return true;
            });
        }

        function renderMenus() {
            const list = filteredMenus();
            const totalPages = Math.max(1, Math.ceil(list.length / MENU_PAGE_SIZE));
            if (menuPage > totalPages) menuPage = totalPages;
            if (menuPage < 1) menuPage = 1;
            const start = (menuPage - 1) * MENU_PAGE_SIZE;
            const pageItems = list.slice(start, start + MENU_PAGE_SIZE);

            const grid = $('#menu-grid').empty();
            pageItems.forEach(m => {
                const img = m.image
                    ? `<img src="${m.image}" class="pos-menu-img">`
                    : `<div class="pos-menu-img d-flex align-items-center justify-content-center"><i class="ki-outline ki-coffee fs-2x text-muted"></i></div>`;
                const badge = m.addons.length ? `<span class="badge badge-light-info fs-9 mt-1">+${m.addons.length} add-on</span>` : '';
                grid.append(`
                    <div class="col">
                        <div class="card card-bordered pos-menu-card h-100 position-relative" data-menu="${m.id}">
                            ${m.bestseller ? '<span class="badge badge-danger position-absolute px-2 py-1" style="top:6px;left:6px;z-index:3;font-size:.62rem">🔥 Terlaris</span>' : ''}
                            ${img}
                            <div class="p-2">
                                <div class="fw-bold text-gray-800 fs-7 text-truncate">${esc(m.name)}</div>
                                <div class="text-success fw-bold fs-7">${rupiah(m.price)}</div>
                                ${badge}
                            </div>
                        </div>
                    </div>`);
            });
            $('#menu-empty').toggleClass('d-none', list.length > 0);
            renderMenuPagination(list.length, totalPages);
        }

        function renderMenuPagination(total, totalPages) {
            const box = $('#menu-pagination');
            if (!box.length) return;
            if (total <= MENU_PAGE_SIZE) { box.removeClass('d-flex').addClass('d-none').empty(); return; }
            box.removeClass('d-none').addClass('d-flex').html(`
                <button type="button" class="btn btn-sm btn-icon btn-light" id="menu-prev" ${menuPage <= 1 ? 'disabled' : ''}>‹</button>
                <span class="fs-8 text-muted fw-semibold">Hal ${menuPage}/${totalPages} · ${total} menu</span>
                <button type="button" class="btn btn-sm btn-icon btn-light" id="menu-next" ${menuPage >= totalPages ? 'disabled' : ''}>›</button>
            `);
        }

        // ================= CART =================
        // Total baris = harga menu × qty menu + Σ(harga add-on × qty add-on). Add-on LEPAS dari qty menu.
        function cartLineTotal(item) {
            const base = (item.menu_unit != null ? item.menu_unit : item.unit) * item.qty;
            const addonSum = (item.addons || []).reduce((s, a) => s + a.price * (a.qty || 1), 0);
            return base + addonSum;
        }

        function renderCart() {
            const box = $('#cart-items');
            box.find('.cart-row').remove();
            if (cart.length === 0) {
                $('#cart-empty').removeClass('d-none');
            } else {
                $('#cart-empty').addClass('d-none');
                cart.forEach((it, idx) => {
                    const addonRows = (it.addons || []).map(a => `
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <span class="fs-8 text-primary">+ ${esc(a.name)}${a.price ? ` <span class="text-muted">(${rupiah(a.price)})</span>` : ''}</span>
                            <span class="d-flex align-items-center">
                                <button class="btn btn-icon btn-xs btn-light-danger addon-qty-dec" data-aid="${a.id}"><i class="ki-outline ki-minus fs-8"></i></button>
                                <span class="mx-1 fw-bold fs-8">${a.qty}</span>
                                <button class="btn btn-icon btn-xs btn-light-primary addon-qty-inc" data-aid="${a.id}"><i class="ki-outline ki-plus fs-8"></i></button>
                            </span>
                        </div>`).join('');
                    box.append(`
                        <div class="cart-row border-bottom py-2" data-idx="${idx}">
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="me-2 flex-grow-1">
                                    <div class="fw-bold text-gray-800 fs-7">${esc(it.name)}</div>
                                    <div class="text-muted fs-8">${rupiah(it.menu_unit)}</div>
                                    ${addonRows}
                                </div>
                                <div class="text-end">
                                    <div class="d-flex align-items-center justify-content-end mb-1">
                                        <button class="btn btn-icon btn-xs btn-light-danger qty-dec"><i class="ki-outline ki-minus fs-6"></i></button>
                                        <span class="mx-2 fw-bold">${it.qty}</span>
                                        <button class="btn btn-icon btn-xs btn-light-primary qty-inc"><i class="ki-outline ki-plus fs-6"></i></button>
                                    </div>
                                    <div class="fw-bold text-gray-800 fs-7">${rupiah(cartLineTotal(it))}</div>
                                    <button class="btn btn-xs btn-link text-danger cart-remove p-0">hapus</button>
                                </div>
                            </div>
                            <input type="text" class="form-control form-control-sm form-control-solid mt-2 cart-note"
                                data-idx="${idx}" value="${escAttr(it.note)}" placeholder="Catatan item (mis. tanpa gula, less ice)">
                        </div>`);
                });
            }
            recalcTotals();
        }

        function recalcTotals() {
            const subtotal = cart.reduce((s, it) => s + cartLineTotal(it), 0);
            let discount = 0;
            const opt = $('#promo-select').find(':selected');
            if (opt.val()) {
                discount = opt.data('type') === 'percentage'
                    ? Math.round(subtotal * (Number(opt.data('value')) / 100))
                    : Number(opt.data('value'));
            }
            let net = subtotal - discount; if (net < 0) net = 0;
            const tax = Math.round(net * (TAX_RATE / 100));
            const total = net + tax;

            $('#sum-subtotal').text(rupiah(subtotal));
            $('#sum-discount').text('- ' + rupiah(discount));
            $('#sum-tax').text(rupiah(tax));
            $('#sum-total').text(rupiah(total));
            window.__grandTotal = total;
            // Ringkasan di FAB keranjang (layar kecil)
            $('#cart-fab-count').text(cart.reduce((s, it) => s + it.qty, 0));
            $('#cart-fab-total').text(rupiah(total));
            updateChange();
            renderQuickCash(total);
        }

        function updateChange() {
            const total = window.__grandTotal || 0;
            const received = window.rawNum($('#cash-received').val());
            const change = received - total;
            $('#cash-change').text(rupiah(change > 0 ? change : 0));
        }

        function renderQuickCash(total) {
            const box = $('#quick-cash').empty();
            if (total <= 0) return;
            const opts = new Set([total]);
            const round = (v, step) => Math.ceil(v / step) * step;
            [1000, 5000, 10000, 50000].forEach(s => opts.add(round(total, s)));
            [...opts].sort((a, b) => a - b).slice(0, 5).forEach(v => {
                box.append(`<button type="button" class="btn btn-sm btn-light-primary quick-cash-btn" data-val="${v}">${rupiah(v)}</button>`);
            });
        }

        // addonSel: [{id, qty}] (dari popup). Simpan add-on ber-qty + harga menu terpisah.
        function addToCart(menu, qty, addonSel, note) {
            const addons = (addonSel || []).map(sel => {
                const a = menu.addons.find(x => x.id === sel.id);
                return a ? { id: a.id, name: a.name, price: a.price, qty: Math.max(1, sel.qty || 1) } : null;
            }).filter(Boolean);
            cart.push({
                menu_id: menu.id, name: menu.name, menu_unit: menu.price,
                addons, qty: qty, note: note || ''
            });
            renderCart();
        }

        // ===== Animasi: kartu menu "terbang" mengecil ke keranjang saat ditambahkan =====
        function cartTargetEl() {
            var fab = document.getElementById('cart-fab');
            // FAB pakai position:fixed (offsetParent selalu null) -> cek display, bukan offsetParent.
            if (fab && getComputedStyle(fab).display !== 'none') return fab;   // mobile: FAB keranjang bawah
            var ci = document.getElementById('cart-items');
            if (ci && ci.offsetParent !== null) return ci;                     // desktop: keranjang inline
            return fab || ci;
        }
        // Overlay khusus (fixed, full-viewport, transform:none) -> imun dari ancestor ber-transform
        // yang bisa merusak position:fixed. Semua elemen animasi ditaruh di sini.
        function flyLayer() {
            var l = document.getElementById('fly-layer');
            if (!l) {
                l = document.createElement('div');
                l.id = 'fly-layer';
                l.style.cssText = 'position:fixed;left:0;top:0;width:100vw;height:100vh;pointer-events:none;z-index:99999;overflow:hidden;transform:none;';
                document.body.appendChild(l);
            }
            return l;
        }
        function flyToCart(sourceEl) {
            try {
                var target = cartTargetEl();
                if (!sourceEl || !target || !target.getBoundingClientRect) return;
                var s = sourceEl.getBoundingClientRect();
                var t = target.getBoundingClientRect();
                if (!s.width || !t.width) return;

                // Elemen terbang KOMPAK (bukan kartu penuh yang tinggi): kotak kecil berisi gambar menu.
                var SZ = 64;
                var srcImg = sourceEl.querySelector('img');
                var fly = document.createElement('div');
                fly.style.cssText = 'position:absolute;margin:0;pointer-events:none;transform-origin:center center;'
                    + 'width:' + SZ + 'px;height:' + SZ + 'px;border-radius:16px;overflow:hidden;background:#fff;'
                    + 'border:2px solid #4f46e5;box-shadow:0 14px 34px rgba(79,70,229,.5);'
                    + 'display:flex;align-items:center;justify-content:center;';
                if (srcImg && srcImg.getAttribute('src')) {
                    var im = document.createElement('img');
                    im.src = srcImg.getAttribute('src');
                    im.style.cssText = 'width:100%;height:100%;object-fit:cover;';
                    fly.appendChild(im);
                } else {
                    fly.innerHTML = '<i class="ki-outline ki-handcart" style="font-size:30px;color:#4f46e5"></i>';
                }
                flyLayer().appendChild(fly);

                // Mulai dari PUSAT kartu menu -> ke PUSAT keranjang (FAB di mobile / panel di desktop).
                var sx = (s.left + s.width / 2) - SZ / 2, sy = (s.top + s.height / 2) - SZ / 2;
                var tx = (t.left + t.width / 2) - SZ / 2, ty = (t.top + t.height / 2) - SZ / 2;
                var dur = 620, startT = 0;

                // Animasi MANUAL requestAnimationFrame -> mulus di semua device, tak bisa "lompat".
                function step(ts) {
                    if (!startT) startT = ts;
                    var p = Math.min(1, (ts - startT) / dur);
                    var e = 1 - Math.pow(1 - p, 3);            // easeOutCubic (smooth)
                    var arc = -70 * Math.sin(Math.PI * p);      // lintasan melengkung ke atas
                    var sc = 1 - 0.68 * e;                      // 1 -> ~0.32
                    fly.style.left = (sx + (tx - sx) * e) + 'px';
                    fly.style.top = (sy + (ty - sy) * e + arc) + 'px';
                    fly.style.transform = 'scale(' + sc + ')';
                    fly.style.opacity = String(1 - 0.65 * e);
                    if (p < 1) requestAnimationFrame(step);
                    else { try { fly.remove(); } catch (err) {} }
                }
                requestAnimationFrame(step);
                setTimeout(function () { try { fly.remove(); } catch (err) {} }, 1400);

                // Pulse keranjang TANPA menggeser: pertahankan transform asli (mis. translateX(-50%)
                // untuk memusatkan FAB) lalu tambahkan scale -> tidak melompat/bergeser.
                if (target.animate) {
                    var baseTf = getComputedStyle(target).transform;
                    baseTf = (baseTf && baseTf !== 'none') ? baseTf + ' ' : '';
                    target.animate([
                        { transform: baseTf + 'scale(1)' },
                        { transform: baseTf + 'scale(1.18)' },
                        { transform: baseTf + 'scale(1)' }
                    ], { duration: 360 });
                }
            } catch (e) {}
        }

        // Toggle tampil/sembunyi display meja (real-time + simpan preferensi via AJAX, tanpa reload).
        $('#toggle-tables').on('change', function () {
            var show = this.checked;
            $('#table-wrap').toggleClass('d-none', !show);
            $.ajax({
                url: "{{ route('kasir.toggle-tables') }}",
                method: 'POST',
                data: { show: show ? 1 : 0, _token: '{{ csrf_token() }}' },
            });
        });

        // ================= ADD MENU (with add-on modal) =================
        $('#menu-grid').on('click', '.pos-menu-card', function() {
            const menu = MENUS.find(m => String(m.id) === String($(this).data('menu')));
            if (!menu) return;
            if (menu.addons.length === 0) { addToCart(menu, 1, [], ''); flyToCart(this); return; }
            window.__addonSrc = this;
            $('#addon-menu-id').val(menu.id);
            $('#addon-menu-name').text(menu.name);
            $('#addon-qty').val(1);
            $('#addon-note').val('');
            const list = $('#addon-list').empty();
            list.append('<label class="fw-semibold fs-7 text-muted mb-2 d-block">Pilih Add-On</label>');
            menu.addons.forEach(a => {
                list.append(`
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-check form-check-custom form-check-solid d-flex align-items-center mb-0">
                            <input class="form-check-input me-2 addon-check" type="checkbox" value="${a.id}">
                            <span>${esc(a.name)} <span class="text-success fw-bold ms-1">+ ${rupiah(a.price)}</span></span>
                        </label>
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-icon btn-xs btn-light-danger addon-modal-dec" data-id="${a.id}"><i class="ki-outline ki-minus fs-7"></i></button>
                            <input type="number" min="1" value="1" class="form-control form-control-sm form-control-solid mx-1 text-center addon-qty-input" data-id="${a.id}" style="width:56px" disabled>
                            <button type="button" class="btn btn-icon btn-xs btn-light-primary addon-modal-inc" data-id="${a.id}"><i class="ki-outline ki-plus fs-7"></i></button>
                        </div>
                    </div>`);
            });
            $('#modal-addon').modal('show');
        });

        // Centang add-on -> aktifkan kotak qty-nya (default 1).
        $('#addon-list').on('change', '.addon-check', function() {
            const q = $(this).closest('div').find('.addon-qty-input[data-id="' + this.value + '"]');
            q.prop('disabled', !this.checked);
            if (this.checked && (!q.val() || Number(q.val()) < 1)) q.val(1);
        });
        // Stepper qty add-on di dalam popup (otomatis mencentang add-on tsb).
        $('#addon-list').on('click', '.addon-modal-inc, .addon-modal-dec', function() {
            const id = $(this).data('id');
            const q = $('.addon-qty-input[data-id="' + id + '"]');
            const chk = $('.addon-check[value="' + id + '"]');
            if (!chk.prop('checked')) { chk.prop('checked', true); q.prop('disabled', false); }
            let v = Math.max(1, Number(q.val() || 1));
            v = $(this).hasClass('addon-modal-inc') ? v + 1 : Math.max(1, v - 1);
            q.val(v);
        });

        $('#btn-addon-confirm').on('click', function() {
            const menu = MENUS.find(m => String(m.id) === String($('#addon-menu-id').val()));
            if (!menu) return;
            const addonSel = $('.addon-check:checked').map((i, el) => ({
                id: Number(el.value),
                qty: Math.max(1, Number($('.addon-qty-input[data-id="' + el.value + '"]').val() || 1))
            })).get();
            const qty = Math.max(1, Number($('#addon-qty').val() || 1));
            addToCart(menu, qty, addonSel, $('#addon-note').val());
            flyToCart(window.__addonSrc);
            $('#modal-addon').modal('hide');
        });

        // ================= CART EVENTS =================
        $('#cart-items').on('click', '.qty-inc', function() { cart[$(this).closest('.cart-row').data('idx')].qty++; renderCart(); });
        $('#cart-items').on('click', '.qty-dec', function() {
            const i = $(this).closest('.cart-row').data('idx');
            if (cart[i].qty > 1) cart[i].qty--; else cart.splice(i, 1);
            renderCart();
        });
        // Stepper qty PER ADD-ON di baris keranjang (lepas dari qty menu).
        $('#cart-items').on('click', '.addon-qty-inc', function() {
            const i = $(this).closest('.cart-row').data('idx');
            const a = (cart[i].addons || []).find(x => x.id == $(this).data('aid'));
            if (a) { a.qty++; renderCart(); }
        });
        $('#cart-items').on('click', '.addon-qty-dec', function() {
            const i = $(this).closest('.cart-row').data('idx');
            const aid = $(this).data('aid');
            const a = (cart[i].addons || []).find(x => x.id == aid);
            if (!a) return;
            if (a.qty > 1) a.qty--; else cart[i].addons = cart[i].addons.filter(x => x.id != aid);
            renderCart();
        });
        $('#cart-items').on('click', '.cart-remove', function() { cart.splice($(this).closest('.cart-row').data('idx'), 1); renderCart(); });
        $('#btn-clear-cart').on('click', function() { if (cart.length) { cart = []; renderCart(); } });
        $('#promo-select').on('change', recalcTotals);
        // Catatan per-item: update state tanpa re-render (agar fokus input tidak hilang).
        $('#cart-items').on('input', '.cart-note', function() {
            const i = $(this).closest('.cart-row').data('idx');
            if (cart[i]) cart[i].note = this.value;
        });

        // ===== Pagination menu =====
        $('#menu-pagination').on('click', '#menu-prev', function() { if (menuPage > 1) { menuPage--; renderMenus(); } });
        $('#menu-pagination').on('click', '#menu-next', function() { menuPage++; renderMenus(); });

        // ================= PAYMENT METHOD (inline) =================
        $('input[name="pay_method"]').on('change', function() {
            $('#cash-box').toggleClass('d-none', this.value !== 'cash');
        });
        $('#cash-received').on('input', updateChange);
        $('#quick-cash').on('click', '.quick-cash-btn', function() {
            const el = document.getElementById('cash-received');
            el.value = String($(this).data('val'));
            if (window.formatMoneyInput) window.formatMoneyInput(el);
            updateChange();
        });
        $('#btn-default-name').on('click', function() { $('#customer-name').val('Pelanggan'); });

        // Pilih meja (statis 1..25, opsional)
        let selectedTable = '';
        $('#table-picker').on('click', '.table-pick', function () {
            selectedTable = $(this).data('table') ? String($(this).data('table')) : '';
            $('#table-picker .table-pick').removeClass('btn-primary text-white').addClass('btn-light');
            $(this).removeClass('btn-light').addClass('btn-primary text-white');
        });

        // ================= CHECKOUT =================
        // Kunci transaksi klien (idempotency). Dibuat SEKALI per klik "bayar/simpan",
        // lalu STABIL selama percobaan-ulang (retry) & saat disimpan/di-sinkron offline,
        // sehingga jaringan lambat tidak menyebabkan pesanan tercatat dobel.
        function genTxnId() {
            return 'txn-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
        }
        function buildPayload(withPayment) {
            const payload = {
                _token: CSRF,
                client_txn_id: genTxnId(),
                customer_name: $('#customer-name').val().trim(),
                table_no: selectedTable || null,
                promo_id: $('#promo-select').val() || null,
                cart: cart.map(it => ({ menu_id: it.menu_id, qty: it.qty, addons: it.addons.map(a => ({ id: a.id, qty: a.qty })), note: it.note })),
            };
            if (withPayment) {
                const method = $('input[name="pay_method"]:checked').val();
                payload.payment_method = method;
                if (method === 'cash') payload.cash_received = window.rawNum($('#cash-received').val());
            }
            return payload;
        }

        function submitOrder(withPayment) {
            if (cart.length === 0) { Swal.fire('Keranjang kosong', 'Tambahkan menu dulu.', 'info'); return; }
            if (withPayment) {
                const method = $('input[name="pay_method"]:checked').val();
                if (!method) { Swal.fire('Pilih metode', 'Silakan pilih Tunai atau QRIS.', 'warning'); return; }
                if (method === 'cash' && window.rawNum($('#cash-received').val()) < (window.__grandTotal || 0)) {
                    Swal.fire('Uang kurang', 'Uang tunai kurang dari total.', 'warning'); return;
                }
            }
            // payload dibuat SEKALI di sini; retry memakai payload (client_txn_id) yang sama.
            const payload = buildPayload(withPayment);
            sendOrder(payload, withPayment);
        }

        // Kirim order ke server. Aman diulang: client_txn_id yang sama -> server mengembalikan
        // pesanan yang sudah ada (tidak dobel). Timeout supaya jaringan menggantung cepat gagal
        // dan pengguna bisa "Coba Lagi".
        function sendOrder(payload, withPayment) {
            const $btn = withPayment ? $('#btn-pay-now') : $('#btn-pay-later');
            const orig = $btn.data('orig-html') || $btn.html();
            $btn.data('orig-html', orig);
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({ url: ROUTES.store, method: 'POST', data: payload, timeout: 25000 })
                .done(res => {
                    if (res.success) {
                        cart = []; renderCart(); resetCheckout();
                        // Tutup keranjang mengambang (mobile) setelah order tersimpan.
                        try { const _oc = bootstrap.Offcanvas.getInstance(document.getElementById('cart-offcanvas')); if (_oc) _oc.hide(); } catch (e) {}
                        loadOrders();
                        afterOrder(res);
                    } else { Swal.fire('Gagal', res.error || 'Terjadi kesalahan', 'error'); }
                })
                .fail(xhr => handleSubmitFailure(xhr, payload, withPayment))
                .always(() => $btn.prop('disabled', false).html($btn.data('orig-html')));
        }

        // Penanganan gagal kirim: bedakan benar-benar OFFLINE vs jaringan lambat/timeout vs error server.
        function handleSubmitFailure(xhr, payload, withPayment) {
            // 1) Benar-benar offline -> simpan ke penyimpanan lokal (fitur PWA). Aman: idempoten saat sinkron.
            if (!navigator.onLine) { handleOfflineOrder({ status: 0 }, payload); return; }

            // 2) Online tapi request gagal/timeout (jaringan bermasalah) -> mungkin sudah tersimpan di server.
            //    Tawarkan "Coba Lagi" (memakai client_txn_id yang sama -> data tetap SATU).
            const networkish = (xhr.status === 0 || xhr.statusText === 'timeout' || xhr.statusText === 'abort');
            if (networkish) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Koneksi Bermasalah',
                    html: 'Pesanan gagal terkirim karena jaringan. Silakan <b>coba lagi</b> — sistem mencegah pencatatan ganda, jadi <b>data tetap satu</b> walau tadi sempat terkirim.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showCancelButton: true,
                    confirmButtonText: '<i class="ki-outline ki-arrows-circle"></i> Coba Lagi',
                    cancelButtonText: 'Simpan Offline',
                    confirmButtonColor: '#4f46e5',
                }).then(r => {
                    if (r.isConfirmed) sendOrder(payload, withPayment);
                    else handleOfflineOrder({ status: 0 }, payload);
                });
                return;
            }

            // 3) Error lain (mis. 500 dgn pesan) -> tampilkan pesan server.
            const msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Gagal menyimpan pesanan.';
            Swal.fire('Gagal', msg, 'error');
        }

        function afterOrder(res) {
            const r = res.receipt || {};
            Swal.fire({
                icon: 'success',
                title: (r.payment_status === 'paid' ? 'Lunas!' : 'Terkirim ke Dapur'),
                html: `<div class="fs-5">Nomor Antrian</div><div class="fs-3x fw-bold text-primary">${r.queue_number ?? '-'}</div>
                       <div class="text-muted">${esc(r.customer_name || '')} • ${rupiah(r.grand_total)}</div>`,
                showCancelButton: true,
                confirmButtonText: '<i class="ki-outline ki-printer"></i> Cetak Struk',
                cancelButtonText: 'Tutup',
            }).then(result => { if (result.isConfirmed) doPrintReceipt(res.receipt, res.print_url); });
        }

        function resetCheckout() {
            $('input[name="pay_method"]').prop('checked', false);
            $('#cash-box').addClass('d-none');
            $('#cash-received').val('');
            $('#promo-select').val('');
            $('#customer-name').val('');
            selectedTable = '';
            $('#table-picker .table-pick').removeClass('btn-primary text-white').addClass('btn-light');
            $('#table-picker .table-pick[data-table=""]').removeClass('btn-light').addClass('btn-primary text-white');
            recalcTotals();
        }

        // Bangun struk siap-cetak dari keranjang saat ini (dipakai mode offline, tanpa jaringan).
        function buildReceiptFromCart(payload, invoiceNo) {
            const subtotal = cart.reduce((s, it) => s + cartLineTotal(it), 0);
            let discount = 0;
            const opt = $('#promo-select').find(':selected');
            if (opt.val()) {
                discount = opt.data('type') === 'percentage'
                    ? Math.round(subtotal * (Number(opt.data('value')) / 100))
                    : Number(opt.data('value'));
            }
            let net = subtotal - discount; if (net < 0) net = 0;
            const tax = Math.round(net * (TAX_RATE / 100));
            const total = net + tax;
            const paid = !!payload.payment_method;
            const cash = (paid && payload.payment_method === 'cash') ? (payload.cash_received || 0) : null;
            return {
                store_name: STORE_NAME,
                invoice_no: invoiceNo,
                queue_number: 'OFF',
                customer_name: payload.customer_name || 'Pelanggan',
                table_no: payload.table_no || null,
                datetime: new Date().toLocaleString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
                items: cart.map(it => ({
                    name: it.name, qty: it.qty, price: it.menu_unit || 0, subtotal: cartLineTotal(it),
                    addons: (it.addons || []).map(a => ({ name: a.name, qty: a.qty, price: a.price })), notes: it.note || null,
                })),
                subtotal: subtotal, discount_amount: discount, tax: tax, grand_total: total,
                payment_method: payload.payment_method || null,
                payment_status: paid ? 'paid' : 'unpaid',
                cash_received: cash,
                change_amount: (cash !== null) ? Math.max(0, cash - total) : null,
            };
        }

        // Offline fallback: simpan ke Dexie + tampilkan di tab Offline (bisa langsung dicetak).
        function handleOfflineOrder(xhr, payload) {
            if (navigator.onLine && xhr.status !== 0) {
                const msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Gagal menyimpan pesanan.';
                Swal.fire('Gagal', msg, 'error');
                return;
            }
            if (!window.posDB) { Swal.fire('Offline', 'Tidak ada koneksi & penyimpanan offline tidak tersedia.', 'error'); return; }
            const ts = Date.now();
            const uuid = 'off-' + ts + '-' + Math.floor(Math.random() * 100000);
            const receipt = buildReceiptFromCart(payload, 'OFF-' + ts);
            const offline = Object.assign({}, payload, {
                uuid, invoice_no: 'OFF-' + ts, status: 'pending_sync',
                receipt: receipt, grand_total: receipt.grand_total, items_count: (receipt.items || []).length,
            });
            delete offline._token;
            window.posDB.offline_orders.put(offline).then(() => {
                cart = []; renderCart(); resetCheckout();
                if (window.updateConnectionStatus) window.updateConnectionStatus();
                loadOfflineOrders();
                try { const t = document.querySelector('a[href="#tab-offline"]'); if (t) new bootstrap.Tab(t).show(); } catch (e) {}
                try { const _oc = bootstrap.Offcanvas.getInstance(document.getElementById('cart-offcanvas')); if (_oc) _oc.hide(); } catch (e) {}
                Swal.fire({ icon: 'info', title: 'Tersimpan Offline', text: 'Pesanan masuk ke tab Offline & bisa dicetak sekarang. Otomatis tersinkron saat online.', timer: 3000, showConfirmButton: false });
            });
        }

        // ===== Daftar pesanan OFFLINE (dari Dexie, dirender lokal) =====
        window.__offlineRows = {};
        function offlineCard(o) {
            const r = o.receipt || {};
            const paid = r.payment_status === 'paid';
            const payBadge = paid ? '<span class="badge badge-light-success">Lunas</span>' : '<span class="badge badge-light-warning">Belum Lunas</span>';
            const n = (r.items || []).length;
            return `
                <div class="d-flex flex-column border border-warning border-dashed rounded p-3 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="fw-bold fs-5 text-gray-800">${esc(r.customer_name || 'Pelanggan')}</span>
                            <div class="fs-8 text-muted"><span class="badge badge-light-warning">OFFLINE</span> ${n} item • ${r.datetime || ''}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-gray-800">${rupiah(r.grand_total || 0)}</div>
                            ${payBadge}
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn btn-sm btn-light flex-fill btn-view-offline" data-uuid="${o.uuid}"><i class="ki-outline ki-eye fs-5"></i> Lihat</button>
                        <button class="btn btn-sm btn-light-primary flex-fill btn-print-offline" data-uuid="${o.uuid}"><i class="ki-outline ki-printer fs-5"></i> Cetak Struk</button>
                    </div>
                </div>`;
        }
        function loadOfflineOrders() {
            if (!window.posDB) return;
            window.posDB.offline_orders.where('status').equals('pending_sync').toArray().then(rows => {
                rows.reverse(); // terbaru di atas
                window.__offlineRows = {};
                rows.forEach(r => { window.__offlineRows[r.uuid] = r; });
                $('#count-offline').text(rows.length);
                $('#list-offline').html(rows.length ? rows.map(offlineCard).join('') :
                    '<div class="text-center text-muted py-6">Tidak ada pesanan offline.</div>');
            });
        }

        $('#btn-pay-now').on('click', () => submitOrder(true));
        $('#btn-pay-later').on('click', () => submitOrder(false));

        // ========== MODE TAMBAH: gabung item ke pesanan BELUM LUNAS ==========
        let appendOrder = null; // {id, label} saat mode tambah aktif

        let appendFloatTimer = null;

        function enterAppendMode(id, label) {
            appendOrder = { id: id, label: label };
            cart = []; renderCart();
            $('#append-label').text(label);
            $('#append-banner').removeClass('d-none');
            $('#append-submit-wrap').removeClass('d-none');
            $('#checkout-normal').addClass('d-none'); // sembunyikan metode bayar & tombol Bayar
            $('#append-float-label').text(label);
            // Notif floating pengingat "sedang menambah menu ke pesanan" — tampil di SEMUA layar.
            // HP: JANGAN buka keranjang otomatis; notif jadi pengingat utama (menetap).
            // PC/tablet: notif tampil lalu AUTO-TUTUP 10 detik (mode tambah tetap aktif via banner).
            showAppendFloat();
        }

        function showAppendFloat() {
            if (appendFloatTimer) { clearTimeout(appendFloatTimer); appendFloatTimer = null; }
            $('#append-float-notif').removeClass('d-none');
            if (!window.matchMedia('(max-width: 767.98px)').matches) {
                appendFloatTimer = setTimeout(function () {
                    $('#append-float-notif').addClass('d-none');
                    appendFloatTimer = null;
                }, 10000); // PC/tablet: tutup otomatis setelah 10 detik
            }
        }

        function exitAppendMode() {
            appendOrder = null;
            if (appendFloatTimer) { clearTimeout(appendFloatTimer); appendFloatTimer = null; }
            $('#append-banner').addClass('d-none');
            $('#append-submit-wrap').addClass('d-none');
            $('#append-float-notif').addClass('d-none');
            $('#checkout-normal').removeClass('d-none');
        }

        function submitAppendItems() {
            if (!appendOrder) return;
            if (cart.length === 0) { Swal.fire('Keranjang kosong', 'Tambahkan menu yang mau digabung.', 'info'); return; }
            const $btn = $('#btn-append-submit');
            const orig = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
            $.ajax({
                url: ROUTES.base + '/' + appendOrder.id + '/add-items',
                method: 'POST',
                data: {
                    _token: CSRF,
                    cart: cart.map(it => ({ menu_id: it.menu_id, qty: it.qty, addons: it.addons.map(a => ({ id: a.id, qty: a.qty })), note: it.note })),
                },
                timeout: 25000
            }).done(res => {
                if (res.success) {
                    exitAppendMode();
                    cart = []; renderCart(); resetCheckout();
                    try { const _oc = bootstrap.Offcanvas.getInstance(document.getElementById('cart-offcanvas')); if (_oc) _oc.hide(); } catch (e) {}
                    loadOrders();
                    Swal.fire({ icon: 'success', title: 'Menu ditambahkan', text: 'Item digabung ke pesanan & dikirim ke dapur.', timer: 1800, showConfirmButton: false });
                } else { Swal.fire('Gagal', res.error || 'Terjadi kesalahan', 'error'); }
            }).fail(xhr => {
                const msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Gagal menambah item (jaringan/server).';
                Swal.fire('Gagal', msg, 'error');
            }).always(() => $btn.prop('disabled', false).html(orig));
        }

        $('#btn-append-submit').on('click', submitAppendItems);
        $('#append-cancel').on('click', function () { exitAppendMode(); cart = []; renderCart(); });
        $('#append-float-cancel').on('click', function () { exitAppendMode(); cart = []; renderCart(); });

        // Notif floating mode-tambah: sembunyikan saat keranjang (offcanvas) dibuka agar tak
        // menutupi; tampilkan lagi saat keranjang ditutup bila masih mode tambah (layar kecil).
        (function () {
            var oc = document.getElementById('cart-offcanvas');
            if (!oc) return;
            oc.addEventListener('show.bs.offcanvas', function () { $('#append-float-notif').addClass('d-none'); });
            oc.addEventListener('hidden.bs.offcanvas', function () {
                if (appendOrder && window.matchMedia('(max-width: 767.98px)').matches) {
                    $('#append-float-notif').removeClass('d-none');
                }
            });
        })();

        // ================= PESANAN BERJALAN =================
        // tab: 'processing' | 'completed' — menentukan tombol yang muncul.
        function orderCard(o, tab) {
            const paid = o.payment_status === 'paid';
            const payBadge = paid
                ? '<span class="badge badge-light-success">Lunas</span>'
                : '<span class="badge badge-light-danger">Belum Lunas</span>';
            const done = o.order_status === 'completed';
            const voided = !!o.voided;
            const selesaiBtn = done ? '' :
                `<button class="btn btn-sm btn-light-success flex-fill fw-bold btn-complete" data-id="${o.id}" data-paid="${paid ? 1 : 0}" data-total="${o.grand_total}">
                    <i class="ki-outline ki-check fs-5"></i> Selesai</button>`;
            // HAPUS: hanya di tab "Sedang Diproses" & khusus owner.
            const delBtn = (tab === 'processing' && CAN_DELETE)
                ? `<button class="btn btn-sm btn-light-danger fw-bold btn-del-order" data-id="${o.id}" data-q="${o.queue_number ?? ''}" title="Hapus pesanan ini"><i class="ki-outline ki-trash fs-5"></i> Hapus</button>`
                : '';
            // TANDAI SALAH: hanya di tab "Selesai" (owner + kasir). Toggle.
            const voidBtn = (tab === 'completed' && CAN_VOID)
                ? (voided
                    ? `<button class="btn btn-sm btn-light-warning flex-fill fw-bold btn-void-order" data-id="${o.id}" data-q="${o.queue_number ?? ''}" data-voided="1"><i class="ki-outline ki-arrows-circle fs-5"></i> Batalkan Tanda</button>`
                    : `<button class="btn btn-sm btn-light-danger flex-fill fw-bold btn-void-order" data-id="${o.id}" data-q="${o.queue_number ?? ''}" data-voided="0"><i class="ki-outline ki-cross-circle fs-5"></i> Tandai Salah</button>`)
                : '';
            // SPLIT BILL: hanya tab "Sedang Diproses", belum lunas, & item lebih dari 1.
            const splitBtn = (CAN_SPLIT_MERGE && tab === 'processing' && !paid && (o.items_count || 0) > 1)
                ? `<button class="btn btn-sm btn-light-warning fw-bold btn-split-order" data-id="${o.id}" data-q="${o.queue_number ?? ''}" title="Split bill — pecah nota ini jadi beberapa struk"><i class="ki-outline ki-arrow-two-diagonals fs-5"></i> Pecah Nota</button>`
                : '';
            // UNMERGE: hanya untuk nota hasil gabungan yang belum lunas.
            const mergedLabels = o.merged_labels || [];
            const unmergeBtn = (CAN_SPLIT_MERGE && tab === 'processing' && !paid && mergedLabels.length)
                ? `<button class="btn btn-sm btn-light-info fw-bold btn-unmerge-order" data-id="${o.id}" data-q="${o.queue_number ?? ''}" data-labels="${esc(mergedLabels.join(','))}" title="Pisahkan kembali nota gabungan ini ke nota asalnya"><i class="ki-outline ki-arrows-loop fs-5"></i> Pisah Nota</button>`
                : '';
            const mergedBadge = mergedLabels.length
                ? `<span class="badge badge-light-warning ms-1 fs-9" title="Nota ini gabungan dari No. ${esc(mergedLabels.join(', No. '))}">+ No. ${esc(mergedLabels.join(', '))}</span>`
                : '';
            const salahBadge = voided
                ? '<span class="badge badge-danger ms-1">SALAH</span>'
                : '';
            const totalHtml = voided
                ? `<div class="fw-bold text-muted text-decoration-line-through">${rupiah(o.grand_total)}</div>`
                : `<div class="fw-bold text-gray-800">${rupiah(o.grand_total)}</div>`;
            return `
                <div class="d-flex flex-column border rounded p-3 mb-2 ${voided ? 'border-danger bg-light-danger' : ''}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="fw-bold fs-5 text-gray-800">No. ${o.queue_number ?? '-'}</span>${mergedBadge}${salahBadge}
                            <div class="fs-8 text-muted">${o.table_no ? '<span class="badge badge-light-primary">Meja ' + esc(o.table_no) + '</span> ' : ''}${esc(o.customer_name || '')} • ${o.items_count} item • ${o.created_at ?? ''}</div>
                        </div>
                        <div class="text-end">
                            ${totalHtml}
                            ${payBadge}
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <button class="btn btn-sm btn-light flex-fill btn-view" data-id="${o.id}" title="Lihat rincian pesanan"><i class="ki-outline ki-eye fs-5"></i> Lihat</button>
                        ${selesaiBtn}
                        ${splitBtn}
                        ${unmergeBtn}
                        ${voidBtn}
                        ${delBtn}
                    </div>
                </div>`;
        }

        function loadOrders() {
            $.get(ROUTES.orders).done(res => {
                $('#count-processing').text(res.processing.length);
                $('#count-completed').text(res.completed.length);
                $('#list-processing').html(res.processing.length ? res.processing.map(o => orderCard(o, 'processing')).join('') :
                    '<div class="text-center text-muted py-6">Belum ada pesanan berjalan.</div>');
                $('#list-completed').html(res.completed.length ? res.completed.map(o => orderCard(o, 'completed')).join('') :
                    '<div class="text-center text-muted py-6">Belum ada pesanan selesai hari ini.</div>');
                // Kartu penanda: berapa pesanan SALAH di antara yang selesai (tak dihitung omzet).
                const vc = res.voided_count || 0;
                if (vc > 0) {
                    $('#completed-voided-card').removeClass('d-none').find('#completed-voided-count').text(vc);
                } else {
                    $('#completed-voided-card').addClass('d-none');
                }

                // Tab "Selesai Sebelumnya": hanya tampil bila shift aktif melewati tengah malam.
                const prev = res.previous_completed || [];
                if (prev.length) {
                    $('#count-prev-completed').text(prev.length);
                    $('#list-prev-completed').html(prev.map(o => orderCard(o, 'completed')).join(''));
                    $('#tabli-prev-completed').removeClass('d-none');
                } else {
                    $('#tabli-prev-completed').addClass('d-none');
                    $('#list-prev-completed').html('');
                }
            });
        }
        $('#btn-refresh-orders').on('click', loadOrders);

        // ===== Perbarui widget "Penjualan Hari Ini" di sidebar tanpa reload =====
        function applyWidget(w) {
            if (!w) return;
            $('#sb-income').text(rupiah(w.income));
            $('#sb-target').text(rupiah(w.target));
            $('#sb-percent').text('Tercapai ' + (w.percentage || 0) + '%');
            const $bar = $('#sb-progress');
            if ($bar.length) {
                $bar.css('width', (w.bar_width || 0) + '%').attr('aria-valuenow', w.bar_width || 0);
                $bar.removeClass('bg-warning bg-primary bg-success').addClass(w.bar_color || 'bg-warning');
            }
        }

        // ===== Hapus pesanan (owner) — berjalan/selesai, lunas/belum lunas =====
        $('body').on('click', '.btn-del-order', function() {
            const id = $(this).data('id');
            const q = $(this).data('q');
            Swal.fire({
                title: 'Hapus pesanan?',
                html: `Pesanan <b>No. ${q || '-'}</b> beserta itemnya akan dihapus permanen.<br>Tindakan ini tidak bisa dibatalkan.`,
                icon: 'warning', showCancelButton: true,
                confirmButtonText: '<i class="ki-outline ki-trash"></i> Ya, Hapus',
                cancelButtonText: 'Batal', confirmButtonColor: '#d33',
            }).then(r => {
                if (!r.isConfirmed) return;
                $.ajax({ url: ROUTES.base + '/' + id, method: 'POST', data: { _token: CSRF, _method: 'DELETE' } })
                    .done(res => {
                        if (res.success) {
                            applyWidget(res.widget);
                            loadOrders();
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Pesanan dihapus', showConfirmButton: false, timer: 1800 });
                        } else { Swal.fire('Gagal', res.error || 'Gagal menghapus pesanan.', 'error'); }
                    })
                    .fail(xhr => Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.error) || 'Gagal menghapus pesanan.', 'error'));
            });
        });

        // ===== Tandai / batalkan tanda SALAH (owner + kasir) — hanya tab "Selesai" =====
        // Pesanan salah tidak dihitung ke omzet/kas, tetapi tetap tampil di laporan.
        $('body').on('click', '.btn-void-order', function() {
            const id = $(this).data('id');
            const q = $(this).data('q');
            const isVoided = String($(this).data('voided')) === '1';
            Swal.fire({
                title: isVoided ? 'Batalkan tanda salah?' : 'Tandai pesanan salah?',
                html: isVoided
                    ? `Pesanan <b>No. ${q || '-'}</b> akan <b>dihitung kembali</b> ke penjualan/omzet.`
                    : `Pesanan <b>No. ${q || '-'}</b> akan <b>tidak dihitung</b> ke penjualan hari ini, omzet, & kas laci.<br>Tetap tersimpan di laporan dengan penanda <b>SALAH</b>.`,
                icon: 'warning', showCancelButton: true,
                confirmButtonText: isVoided ? 'Ya, Batalkan Tanda' : '<i class="ki-outline ki-cross-circle"></i> Ya, Tandai Salah',
                cancelButtonText: 'Batal', confirmButtonColor: isVoided ? '#f1c40f' : '#d33',
            }).then(r => {
                if (!r.isConfirmed) return;
                $.ajax({ url: ROUTES.base + '/' + id + '/void', method: 'POST', data: { _token: CSRF } })
                    .done(res => {
                        if (res.success) {
                            applyWidget(res.widget);
                            loadOrders();
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.voided ? 'Ditandai salah' : 'Tanda salah dibatalkan', showConfirmButton: false, timer: 1800 });
                        } else { Swal.fire('Gagal', res.error || 'Gagal memproses.', 'error'); }
                    })
                    .fail(xhr => Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.error) || 'Gagal memproses pesanan.', 'error'));
            });
        });

        // ===== Set / ubah target penjualan hari ini (owner) =====
        $('#btn-set-target').on('click', function() {
            const current = window.rawNum ? window.rawNum($('#sb-target').text()) : 0;
            Swal.fire({
                title: @json((($currentTenant ?? null) && $currentTenant->isLaundry()) ? 'Target Profit Hari Ini' : 'Target Penjualan Hari Ini'),
                text: @json((($currentTenant ?? null) && $currentTenant->isLaundry()) ? 'Masukkan target profit (Rupiah) untuk hari ini.' : 'Masukkan target penjualan (Rupiah) untuk hari ini.'),
                input: 'number',
                inputValue: current || '',
                inputAttributes: { min: 0, step: 1000 },
                inputPlaceholder: 'mis. 1000000',
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                inputValidator: v => { const n = window.rawNum ? window.rawNum(v) : Number(String(v).replace(/[^\d]/g, '')); return (v === '' || v === null || isNaN(n) || n < 0) ? 'Masukkan nominal yang valid' : undefined; },
            }).then(r => {
                if (!r.isConfirmed) return;
                // rawNum: buang pemisah ribuan ("50.000" -> 50000). Jangan Number() ("50.000" -> 50).
                const amount = window.rawNum ? window.rawNum(r.value) : Number(String(r.value).replace(/[^\d]/g, ''));
                $.ajax({ url: ROUTES.setTarget, method: 'POST', data: { _token: CSRF, amount: amount } })
                    .done(res => {
                        if (res.success) {
                            applyWidget(res.widget);
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Target diperbarui', showConfirmButton: false, timer: 1800 });
                        } else { Swal.fire('Gagal', res.error || 'Gagal menyimpan target.', 'error'); }
                    })
                    .fail(xhr => Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.error) || 'Gagal menyimpan target.', 'error'));
            });
        });

        // ===== Reset penjualan hari ini (owner) — bersihkan data testing =====
        $('#btn-reset-today').on('click', function() {
            Swal.fire({
                title: 'Reset penjualan hari ini?',
                html: 'Semua <b>pesanan hari ini</b> akan dihapus permanen dan <b>target penjualan hari ini</b> direset ke 0.<br>Cocok untuk membersihkan data testing.',
                icon: 'warning', showCancelButton: true,
                confirmButtonText: 'Ya, Reset', cancelButtonText: 'Batal', confirmButtonColor: '#d33',
            }).then(r => {
                if (!r.isConfirmed) return;
                $.ajax({ url: ROUTES.resetToday, method: 'POST', data: { _token: CSRF } })
                    .done(res => {
                        if (res.success) {
                            applyWidget(res.widget);
                            loadOrders();
                            Swal.fire({ icon: 'success', title: 'Direset', text: (res.deleted || 0) + ' pesanan hari ini dihapus.' });
                        } else { Swal.fire('Gagal', res.error || 'Gagal mereset penjualan.', 'error'); }
                    })
                    .fail(xhr => Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.error) || 'Gagal mereset penjualan.', 'error'));
            });
        });


        /**
         * Item hasil MERGE dikelompokkan per nota asal (order_details.merged_from).
         * Kelompok pertama = item milik nota ini, lalu tiap nota yang digabung diberi
         * judul + subtotalnya sendiri. Total keseluruhan tetap total nota gabungan.
         */
        function renderDetailRows(items, queueNumber) {
            const groups = new Map();
            (items || []).forEach(it => {
                const key = it.merged_from || '';
                if (!groups.has(key)) groups.set(key, []);
                groups.get(key).push(it);
            });

            const line = it => {
                const ad = (it.addons && it.addons.length) ? `<div class="fs-8 text-primary">+ ${it.addons.map(a => esc(a.name) + (a.qty > 1 ? ' ×' + a.qty : '')).join(', ')}</div>` : '';
                const nt = it.notes ? `<div class="fs-8 text-muted fst-italic">“${esc(it.notes)}”</div>` : '';
                return `<div class="d-flex justify-content-between border-bottom py-2">
                    <div><span class="fw-bold">${it.qty}x</span> ${esc(it.name)}${ad}${nt}</div>
                    <div class="fw-bold">${rupiah(it.subtotal)}</div></div>`;
            };

            // Tanpa item gabungan -> tampilkan datar seperti biasa.
            if (groups.size <= 1 && groups.has('')) return (items || []).map(line).join('');

            let html = '';
            const keys = [...groups.keys()].sort((a, b) => (a === '' ? -1 : b === '' ? 1 : String(a).localeCompare(String(b), 'id', { numeric: true })));
            keys.forEach(k => {
                const list = groups.get(k);
                const sub  = list.reduce((a, b) => a + Number(b.subtotal || 0), 0);
                const qty  = list.reduce((a, b) => a + Number(b.qty || 0), 0);
                const judul = k === ''
                    ? `No. ${queueNumber ?? '-'} <span class="badge badge-light-primary fs-9 ms-1">nota ini</span>`
                    : `No. ${esc(k)} <span class="badge badge-light-warning fs-9 ms-1">digabung</span>`;
                html += `<div class="d-flex align-items-center justify-content-between bg-light rounded px-3 py-2 mt-3 mb-1">
                        <span class="fw-bold fs-7 text-gray-800">${judul}</span>
                        <span class="fs-8 text-muted">${qty} item · <b class="text-gray-800">${rupiah(sub)}</b></span>
                    </div>`;
                html += list.map(line).join('');
            });
            return html;
        }

        // View detail
        $('body').on('click', '.btn-view', function() {
            const id = $(this).data('id');
            $('#detail-body').html('<div class="text-center py-6"><span class="spinner-border text-primary"></span></div>');
            $('#modal-detail').modal('show');
            $.get(ROUTES.base + '/' + id).done(res => {
                const o = res.order;
                window.__lastDetail = {
                    store_name: STORE_NAME, invoice_no: o.invoice_no, queue_number: o.queue_number,
                    customer_name: o.customer_name, table_no: o.table_no, datetime: o.created_at, items: res.items,
                    subtotal: o.subtotal, discount_amount: o.discount_amount, tax: o.tax, grand_total: o.grand_total,
                    payment_method: o.payment_method, payment_status: o.payment_status,
                    cash_received: o.cash_received, change_amount: o.change_amount
                };
                const rows = renderDetailRows(res.items, o.queue_number);
                $('#detail-body').html(`
                    <div class="mb-3">
                        <div class="fs-2 fw-bold text-primary">No. ${o.queue_number ?? '-'}</div>
                        <div class="text-muted">${o.table_no ? 'Meja ' + esc(o.table_no) + ' · ' : ''}${esc(o.customer_name || '')} • #${o.invoice_no}</div>
                        <div>${o.payment_status === 'paid' ? '<span class="badge badge-success">Lunas</span>' : '<span class="badge badge-danger">Belum Lunas</span>'}
                             <span class="badge badge-light-info text-uppercase">${o.payment_method || '-'}</span></div>
                    </div>
                    ${rows}
                    <div class="d-flex justify-content-between mt-3"><span class="text-muted">Subtotal</span><span>${rupiah(o.subtotal)}</span></div>
                    ${Number(o.discount_amount) > 0 ? `<div class="d-flex justify-content-between"><span class="text-muted">Diskon</span><span class="text-danger">- ${rupiah(o.discount_amount)}</span></div>` : ''}
                    <div class="d-flex justify-content-between"><span class="text-muted">Pajak</span><span>${rupiah(o.tax)}</span></div>
                    <div class="d-flex justify-content-between fw-bold fs-4"><span>Total</span><span class="text-success">${rupiah(o.grand_total)}</span></div>
                    <div class="text-end mt-4 d-flex gap-2 justify-content-end">
                        ${o.payment_status !== 'paid' ? `<button type="button" class="btn btn-sm btn-warning btn-append-order" data-id="${o.id}" data-label="No. ${o.queue_number ?? o.invoice_no}"><i class="ki-outline ki-plus-square"></i> Tambah Menu</button>` : ''}
                        <button type="button" class="btn btn-sm btn-light-primary" onclick="doPrintReceipt(window.__lastDetail, '${ROUTES.print}/${o.id}')"><i class="ki-outline ki-printer"></i> Cetak Struk</button>
                    </div>`);
            }).fail(function () {
                $('#detail-body').html('<div class="text-center text-danger py-6">Gagal memuat detail — mungkin sedang offline. Untuk pesanan yang dibuat offline, buka tab <b>Offline</b> lalu tekan tombol <b>Cetak Struk</b>.</div>');
            });
        });

        // Mulai MODE TAMBAH dari modal detail pesanan BELUM LUNAS: tutup modal, buka keranjang mode tambah.
        $('body').on('click', '.btn-append-order', function () {
            const id = $(this).data('id');
            const label = 'Pesanan ' + $(this).data('label');
            const modalEl = document.getElementById('modal-detail');
            const inst = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
            if (inst) {
                // Masuk mode tambah SETELAH modal benar-benar tertutup (backdrop dibersihkan
                // Bootstrap) -> mencegah layar menggelap / harus klik 2x.
                modalEl.addEventListener('hidden.bs.modal', function handler() {
                    modalEl.removeEventListener('hidden.bs.modal', handler);
                    enterAppendMode(id, label);
                }, { once: true });
                inst.hide();
            } else {
                enterAppendMode(id, label);
            }
        });

        // Cetak struk pesanan OFFLINE — langsung dari data lokal, tanpa jaringan.
        $('body').on('click', '.btn-print-offline', function () {
            const rec = (window.__offlineRows || {})[$(this).data('uuid')];
            if (rec && rec.receipt) doPrintReceipt(rec.receipt, null);
            else Swal.fire('Info', 'Data struk offline tidak ditemukan.', 'info');
        });

        // Lihat detail pesanan OFFLINE — dibangun dari data lokal (tidak fetch, tidak loading).
        $('body').on('click', '.btn-view-offline', function () {
            const rec = (window.__offlineRows || {})[$(this).data('uuid')];
            if (!rec || !rec.receipt) return;
            const r = rec.receipt; window.__lastDetail = r;
            const rows = (r.items || []).map(it => {
                const ad = (it.addons && it.addons.length) ? `<div class="fs-8 text-primary">+ ${it.addons.map(a => esc(a.name) + (a.qty > 1 ? ' ×' + a.qty : '')).join(', ')}</div>` : '';
                const nt = it.notes ? `<div class="fs-8 text-muted fst-italic">“${esc(it.notes)}”</div>` : '';
                return `<div class="d-flex justify-content-between border-bottom py-2"><div><span class="fw-bold">${it.qty}x</span> ${esc(it.name)}${ad}${nt}</div><div class="fw-bold">${rupiah(it.subtotal)}</div></div>`;
            }).join('');
            $('#detail-body').html(`
                <div class="alert alert-warning py-2 fs-8 mb-3">Pesanan <b>OFFLINE</b> — belum tersinkron ke server. Bisa dicetak sekarang; otomatis terkirim saat online.</div>
                <div class="mb-3">
                    <div class="fs-2 fw-bold text-warning">OFFLINE</div>
                    <div class="text-muted">${esc(r.customer_name || '')} • ${r.datetime || ''}</div>
                    <div>${r.payment_status === 'paid' ? '<span class="badge badge-success">Lunas</span>' : '<span class="badge badge-danger">Belum Lunas</span>'} <span class="badge badge-light-info text-uppercase">${r.payment_method || '-'}</span></div>
                </div>
                ${rows}
                <div class="d-flex justify-content-between mt-3"><span class="text-muted">Subtotal</span><span>${rupiah(r.subtotal)}</span></div>
                ${Number(r.discount_amount) > 0 ? `<div class="d-flex justify-content-between"><span class="text-muted">Diskon</span><span class="text-danger">- ${rupiah(r.discount_amount)}</span></div>` : ''}
                <div class="d-flex justify-content-between"><span class="text-muted">Pajak</span><span>${rupiah(r.tax)}</span></div>
                <div class="d-flex justify-content-between fw-bold fs-4"><span>Total</span><span class="text-success">${rupiah(r.grand_total)}</span></div>
                <div class="text-end mt-4"><button type="button" class="btn btn-sm btn-light-primary" onclick="doPrintReceipt(window.__lastDetail, null)"><i class="ki-outline ki-printer"></i> Cetak Struk</button></div>`);
            $('#modal-detail').modal('show');
        });

        // Selesai
        $('body').on('click', '.btn-complete', function() {
            const id = $(this).data('id');
            const paid = String($(this).data('paid')) === '1';
            const total = Number($(this).data('total'));
            if (paid) {
                Swal.fire({ title: 'Selesaikan pesanan?', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Selesai' })
                    .then(r => { if (r.isConfirmed) doComplete(id, null); });
            } else {
                $('#pay-order-id').val(id);
                $('#pay-total').text(rupiah(total));
                window.__payTotal = total;
                $('#pmm-cash').prop('checked', true);
                $('#modal-cash-box').removeClass('d-none');
                $('#modal-cash-received').val('');
                $('#modal-cash-change').text(rupiah(0));
                $('#modal-pay').modal('show');
            }
        });

        $('input[name="pay_method_modal"]').on('change', function() {
            $('#modal-cash-box').toggleClass('d-none', this.value !== 'cash');
        });
        $('#modal-cash-received').on('input', function() {
            const change = window.rawNum(this.value) - (window.__payTotal || 0);
            $('#modal-cash-change').text(rupiah(change > 0 ? change : 0));
        });

        $('#btn-pay-confirm').on('click', function() {
            const id = $('#pay-order-id').val();
            const method = $('input[name="pay_method_modal"]:checked').val();
            const data = { payment_method: method };
            if (method === 'cash') {
                const received = window.rawNum($('#modal-cash-received').val());
                if (received < (window.__payTotal || 0)) { Swal.fire('Uang kurang', 'Uang tunai kurang dari total.', 'warning'); return; }
                data.cash_received = received;
            }
            doComplete(id, data);
        });

        function doComplete(id, paymentData) {
            const data = Object.assign({ _token: CSRF }, paymentData || {});
            $.ajax({ url: ROUTES.base + '/' + id + '/complete', method: 'POST', data })
                .done(res => {
                    if (res.success) {
                        $('#modal-pay').modal('hide');
                        loadOrders();
                        Swal.fire({
                            icon: 'success', title: 'Selesai!',
                            showCancelButton: true, confirmButtonText: '<i class="ki-outline ki-printer"></i> Cetak Struk', cancelButtonText: 'Tutup'
                        }).then(r => { if (r.isConfirmed) doPrintReceipt(res.receipt, res.print_url); });
                    } else { Swal.fire('Gagal', res.error || 'Terjadi kesalahan', 'error'); }
                })
                .fail(xhr => {
                    const msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Gagal menyelesaikan pesanan.';
                    Swal.fire('Gagal', msg, 'error');
                });
        }

        // ================= INIT =================
        $('#category-pills').on('click', '.cat-pill', function() {
            $('.cat-pill').removeClass('active btn-primary').addClass('btn-light');
            $(this).addClass('active btn-primary').removeClass('btn-light');
            currentCat = $(this).data('cat');
            menuPage = 1;
            renderMenus();
        });
        $('#menu-search').on('keyup', function() { menuPage = 1; renderMenus(); });

        function cacheForOffline() {
            if (!window.posDB) return;
            try {
                window.posDB.menus.bulkPut(MENUS.map(m => ({ id: m.id, name: m.name, price: m.price, category_id: m.category_id })));
                window.posDB.settings.put({ id: 1, tax_rate: TAX_RATE });
            } catch (e) { /* abaikan */ }
        }

        // ===== Tombol Printer (muncul jika metode butuh koneksi: native/BT/QZ) =====
        function initPrinterButton() {
            if (window.MoodaPrint && window.MoodaPrint.needsButton()) {
                $('#btn-printer').removeClass('d-none');
                $('#printer-label').text(window.MoodaPrint.buttonLabel());
                // Pulihkan koneksi printer BT yg sudah pernah diizinkan, tanpa dialog pemilihan.
                if (window.MoodaPrint.restoreBle) window.MoodaPrint.restoreBle();
            }
        }
        $('#btn-printer').on('click', function() {
            if (window.MoodaPrint) window.MoodaPrint.quickConnect();
        });

        // ===== Sync + status jaringan =====
        async function updateSyncBadge() {
            let pending = 0;
            try { if (window.posDB) pending = await window.posDB.offline_orders.where('status').equals('pending_sync').count(); } catch (e) {}
            const b = $('#sync-count');
            if (pending > 0) b.removeClass('d-none').text(pending); else b.addClass('d-none');
            const on = navigator.onLine;
            $('#net-status')
                .html('<span class="bullet bullet-dot ' + (on ? 'bg-success' : 'bg-danger') + ' me-1"></span>' + (on ? 'Online' : 'Offline'))
                .removeClass('badge-light-success badge-light-danger')
                .addClass(on ? 'badge-light-success' : 'badge-light-danger');
        }
        $('#btn-sync').on('click', async function() {
            if (!navigator.onLine) { Swal.fire('Masih Offline', 'Koneksi belum ada. Data akan otomatis tersinkron saat online.', 'info'); return; }
            if (window.triggerManualSync) { await window.triggerManualSync(); }
            await updateSyncBadge();
            loadOrders();
            loadOfflineOrders();
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Sinkronisasi dijalankan', showConfirmButton: false, timer: 2000 });
        });
        window.addEventListener('online', updateSyncBadge);
        window.addEventListener('offline', updateSyncBadge);

        $(document).ready(function() {
            renderMenus();
            renderCart();
            loadOrders();
            loadOfflineOrders();
            cacheForOffline();
            initPrinterButton();
            updateSyncBadge();

            // Real-time via Reverb (menggantikan polling 20 detik). Poll 60s
            // dipertahankan sebagai fallback bila koneksi WebSocket terputus.
            (function () {
                const tenantId = document.querySelector('meta[name="tenant-id"]')?.content;
                if (window.Echo && tenantId) {
                    let deb;
                    window.Echo.private('orders.' + tenantId)
                        .listen('.order.changed', function () {
                            clearTimeout(deb);
                            deb = setTimeout(loadOrders, 400);
                        });
                    setInterval(loadOrders, 60000);
                } else {
                    setInterval(loadOrders, 20000); // fallback: Echo tidak tersedia
                }
            })();

            setInterval(updateSyncBadge, 5000);
        });
    
        // ==================== SPLIT BILL (N struk) ====================
        let splitOrderId = null;
        let splitItems   = [];   // [{detail_id, name, qty, price, notes}]
        let splitCount   = 2;

        function splitRender() {
            // Header: kolom Item + satu kolom per struk + kolom sisa
            let head = '<th class="ps-3">Item</th>';
            for (let g = 0; g < splitCount; g++) {
                head += `<th class="text-center" style="min-width:130px">
                    <div class="text-gray-800">Struk #${g + 1}${g === 0 ? ' <span class="badge badge-light-warning fs-9">nota asal</span>' : ''}</div>
                    <input type="text" class="form-control form-control-sm form-control-solid mt-1 split-label"
                           data-g="${g}" placeholder="nama (opsional)">
                </th>`;
            }
            head += '<th class="text-center pe-3">Sisa</th>';
            $('#split-head').html(head);

            let body = '';
            splitItems.forEach((it, i) => {
                let cells = '';
                for (let g = 0; g < splitCount; g++) {
                    cells += `<td class="text-center">
                        <input type="number" class="form-control form-control-sm text-center split-cell js-no-format"
                               data-i="${i}" data-g="${g}" min="0" max="${it.qty}" value="0" style="max-width:80px;margin:auto">
                    </td>`;
                }
                body += `<tr data-i="${i}">
                    <td class="ps-3">
                        <div class="fw-bold text-gray-800 fs-7">${esc(it.name)}</div>
                        <div class="fs-8 text-muted">${it.qty} porsi x ${rupiah(it.price)}${it.notes ? ' • ' + esc(it.notes) : ''}</div>
                    </td>${cells}
                    <td class="text-center pe-3"><span class="badge badge-light-danger split-left" data-i="${i}">${it.qty}</span></td>
                </tr>`;
            });
            $('#split-body').html(body || `<tr><td colspan="${splitCount + 2}" class="text-center text-muted py-8">Pesanan tidak punya item.</td></tr>`);

            let foot = '<tr><td class="ps-3 text-muted fs-8">Total per struk</td>';
            for (let g = 0; g < splitCount; g++) foot += `<td class="text-center fs-7 split-total" data-g="${g}">Rp 0</td>`;
            foot += '<td></td></tr>';
            $('#split-foot').html(foot);

            splitRecalc();
        }

        function splitRecalc() {
            let ok = splitItems.length > 0;
            const totals = new Array(splitCount).fill(0);

            splitItems.forEach((it, i) => {
                let used = 0;
                $(`.split-cell[data-i="${i}"]`).each(function () {
                    const g = +$(this).data('g');
                    let v = parseInt($(this).val(), 10) || 0;
                    if (v < 0) { v = 0; $(this).val(0); }
                    used += v;
                    totals[g] += v * it.price;
                });
                const left = it.qty - used;
                const badge = $(`.split-left[data-i="${i}"]`);
                badge.text(left)
                    .removeClass('badge-light-danger badge-light-success badge-light-warning')
                    .addClass(left === 0 ? 'badge-light-success' : (left < 0 ? 'badge-light-warning' : 'badge-light-danger'));
                if (left !== 0) ok = false;
            });

            totals.forEach((v, g) => $(`.split-total[data-g="${g}"]`).text(rupiah(v)));
            const emptyStruk = totals.some(v => v <= 0);
            if (emptyStruk) ok = false;

            $('#split-status')
                .removeClass('text-success text-danger')
                .addClass(ok ? 'text-success' : 'text-danger')
                .text(ok ? '✓ Pembagian sudah pas' : (emptyStruk ? 'Ada struk yang masih kosong' : 'Masih ada porsi yang belum dibagi'));
            $('#btn-split-confirm').prop('disabled', !ok);
            return ok;
        }

        /**
         * Bagi rata: porsi disebar bergilir antar struk memakai kursor GLOBAL yang
         * berlanjut dari satu item ke item berikutnya.
         * Kursor per-item (cara lama) salah: item ber-qty 1 selalu jatuh ke struk #1,
         * sehingga nota berisi 8 item @1 porsi menumpuk semua di struk #1.
         */
        function splitEven() {
            const acc = splitItems.map(() => new Array(splitCount).fill(0));
            let cur = 0;
            splitItems.forEach((it, i) => {
                for (let n = 0; n < it.qty; n++) {
                    acc[i][cur % splitCount]++;
                    cur++;
                }
            });
            splitItems.forEach((it, i) => {
                for (let g = 0; g < splitCount; g++) {
                    $(`.split-cell[data-i="${i}"][data-g="${g}"]`).val(acc[i][g]);
                }
            });
            splitRecalc();
        }

        $(document).on('click', '.btn-split-order', function () {
            splitOrderId = $(this).data('id');
            splitCount = 2;
            splitItems = [];
            $('#split-count').val(2);
            $('#split-order-label').text('');
            $('#split-head, #split-foot').empty();
            $('#split-body').html('<tr><td class="text-center py-8"><span class="spinner-border text-primary"></span></td></tr>');
            $('#btn-split-confirm').prop('disabled', true);
            new bootstrap.Modal(document.getElementById('modal-split')).show();

            $.get(`${ROUTES.base}/${splitOrderId}`).done(function (o) {
                splitItems = (o.items || []).map(it => ({
                    detail_id: it.detail_id ?? it.id,
                    name: it.name, qty: +it.qty, price: +it.price, notes: it.notes,
                }));
                const totalQty = splitItems.reduce((a, b) => a + b.qty, 0);
                $('#split-order-label').text(`• No. ${(o.order && o.order.queue_number) ?? '-'} • ${totalQty} porsi`);
                // Jumlah struk tidak boleh melebihi jumlah porsi yang tersedia.
                $('#split-count').attr('max', Math.max(2, Math.min(6, totalQty)));
                splitRender();
            }).fail(() => $('#split-body').html('<tr><td class="text-center text-danger py-8">Gagal memuat item pesanan.</td></tr>'));
        });

        $('#split-plus').on('click', function () {
            const max = +$('#split-count').attr('max') || 6;
            if (splitCount < max) { splitCount++; $('#split-count').val(splitCount); splitRender(); }
        });
        $('#split-minus').on('click', function () {
            if (splitCount > 2) { splitCount--; $('#split-count').val(splitCount); splitRender(); }
        });
        $('#split-even').on('click', splitEven);
        $('#split-reset').on('click', function () { $('.split-cell').val(0); splitRecalc(); });
        $(document).on('input', '.split-cell', splitRecalc);

        $('#btn-split-confirm').on('click', function () {
            if (!splitRecalc()) return;

            const groups = [];
            for (let g = 0; g < splitCount; g++) {
                const items = [];
                splitItems.forEach((it, i) => {
                    const qty = parseInt($(`.split-cell[data-i="${i}"][data-g="${g}"]`).val(), 10) || 0;
                    if (qty > 0) items.push({ detail_id: it.detail_id, qty: qty });
                });
                groups.push({ label: $(`.split-label[data-g="${g}"]`).val() || null, items: items });
            }

            const btn = $(this); btn.prop('disabled', true).text('Memproses...');
            $.ajax({
                url: `${ROUTES.base}/${splitOrderId}/split`,
                method: 'POST',
                data: { _token: CSRF, groups: groups },
            }).done(function (res) {
                bootstrap.Modal.getInstance(document.getElementById('modal-split')).hide();
                Swal.fire({ icon: 'success', title: res.message || 'Nota dipecah', timer: 1800, showConfirmButton: false });
                loadOrders();
            }).fail(function (x) {
                Swal.fire('Gagal', (x.responseJSON && x.responseJSON.error) || 'Tidak bisa memecah nota.', 'error');
            }).always(() => btn.text('Pecah Nota').prop('disabled', false));
        });

        // ==================== UNMERGE (pisahkan nota gabungan) ====================
        $(document).on('click', '.btn-unmerge-order', function () {
            const id = $(this).data('id');
            const q = $(this).data('q');
            const labels = String($(this).data('labels') || '').split(',').filter(Boolean);

            Swal.fire({
                title: 'Pisahkan nota gabungan?',
                html: `Nota <b>No. ${q}</b> akan dipisah kembali menjadi <b>${labels.length + 1} nota</b>:`
                    + `<div class="text-start mt-3 fs-7">`
                    + `<div class="mb-1">• <b>No. ${q}</b> — item aslinya tetap di sini</div>`
                    + labels.map(l => `<div class="mb-1">• <b>No. ${l}</b> — item yang dulu digabung, kembali jadi nota sendiri</div>`).join('')
                    + `</div>`
                    + `<div class="text-start mt-3"><label class="form-label fw-semibold fs-8 mb-1">Nomor meja untuk No. ${q} setelah dipisah (opsional)</label>`
                    + `<input id="unmerge-table" class="form-control form-control-sm form-control-solid" placeholder="mis. 6"></div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Pisahkan',
                cancelButtonText: 'Batal',
                customClass: { confirmButton: 'btn btn-info', cancelButton: 'btn btn-light' },
                buttonsStyling: false,
                preConfirm: () => document.getElementById('unmerge-table').value || null,
            }).then(res => {
                if (!res.isConfirmed) return;
                $.ajax({
                    url: `${ROUTES.base}/${id}/unmerge`,
                    method: 'POST',
                    data: { _token: CSRF, table_no: res.value },
                }).done(function (r) {
                    Swal.fire({ icon: 'success', title: r.message || 'Nota dipisah', timer: 1900, showConfirmButton: false });
                    loadOrders();
                }).fail(function (x) {
                    Swal.fire('Gagal', (x.responseJSON && x.responseJSON.error) || 'Tidak bisa memisah nota.', 'error');
                });
            });
        });

        // ==================== MERGE TABLE ====================
        $('#btn-merge-open').on('click', function () {
            $.get(ROUTES.orders).done(function (res) {
                // Hanya nota BELUM LUNAS yang boleh digabung.
                const unpaid = (res.processing || []).filter(o => o.payment_status !== 'paid');
                if (unpaid.length < 2) {
                    Swal.fire('Belum bisa digabung', 'Perlu minimal 2 pesanan belum lunas untuk digabung.', 'info');
                    return;
                }
                const label = o => `No. ${o.queue_number ?? '-'}${o.table_no ? ' • Meja ' + esc(o.table_no) : ''} • ${esc(o.customer_name || '')} • ${rupiah(o.grand_total)}`;
                $('#merge-target').html(unpaid.map(o => `<option value="${o.id}">${label(o)}</option>`).join(''));
                $('#merge-table').val('');
                renderMergeSources(unpaid);
                new bootstrap.Modal(document.getElementById('modal-merge')).show();
            });
        });

        function renderMergeSources(list) {
            const target = $('#merge-target').val();
            $('#merge-sources').html(list.filter(o => String(o.id) !== String(target)).map(o => `
                <label class="form-check form-check-custom form-check-sm d-flex align-items-center mb-2">
                    <input class="form-check-input merge-src" type="checkbox" value="${o.id}">
                    <span class="form-check-label fs-8 fw-semibold ms-2">No. ${o.queue_number ?? '-'}${o.table_no ? ' • Meja ' + esc(o.table_no) : ''} • ${esc(o.customer_name || '')} • ${rupiah(o.grand_total)}</span>
                </label>`).join('') || '<div class="text-muted fs-8">Tidak ada nota lain.</div>');
        }
        $(document).on('change', '#merge-target', function () {
            $.get(ROUTES.orders).done(res => renderMergeSources((res.processing || []).filter(o => o.payment_status !== 'paid')));
        });

        $('#btn-merge-confirm').on('click', function () {
            const sources = $('#merge-sources .merge-src:checked').map(function () { return $(this).val(); }).get();
            if (!sources.length) { Swal.fire('Pilih nota', 'Centang nota yang mau digabungkan.', 'info'); return; }

            const btn = $(this); btn.prop('disabled', true).text('Menggabungkan...');
            $.ajax({
                url: ROUTES.merge,
                method: 'POST',
                data: { _token: CSRF, target_id: $('#merge-target').val(), source_ids: sources, table_no: $('#merge-table').val() || null },
            }).done(function () {
                bootstrap.Modal.getInstance(document.getElementById('modal-merge')).hide();
                Swal.fire({ icon: 'success', title: 'Nota digabung', timer: 1600, showConfirmButton: false });
                loadOrders();
            }).fail(function (x) {
                Swal.fire('Gagal', (x.responseJSON && x.responseJSON.error) || 'Tidak bisa menggabungkan nota.', 'error');
            }).always(() => btn.prop('disabled', false).text('Gabungkan'));
        });

    </script>
@endpush
