@extends('backend.layout.app')
@section('title', 'Kasir POS')

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
                    <button id="btn-printer" type="button" class="btn btn-sm btn-light-primary d-none">
                        <i class="ki-outline ki-printer fs-4 me-1"></i><span id="printer-label">Printer</span>
                    </button>
                    <button id="btn-sync" type="button" class="btn btn-sm btn-light-success">
                        <i class="ki-outline ki-arrows-circle fs-4 me-1"></i>Sync
                        <span id="sync-count" class="badge badge-danger ms-1 d-none">0</span>
                    </button>
                    <span id="net-status" class="badge badge-light-success">
                        <span class="bullet bullet-dot bg-success me-1"></span>Online
                    </span>
                </div>
            </div>

            <div class="row g-4">
                {{-- =============== KIRI: NAMA + MENU =============== --}}
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
                        </div>
                    </div>
                </div>

                {{-- =============== KANAN: PESANAN BERJALAN + KERANJANG =============== --}}
                <div class="col-12 col-md-5 col-xl-4">
                    <div class="pos-right-col">
                        {{-- Pesanan berjalan --}}
                        <div class="card card-flush shadow-sm mb-4">
                            <div class="card-header pt-4 pb-0 min-h-40px">
                                <ul class="nav nav-pills nav-pills-sm gap-2 w-100">
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
                                    <li class="nav-item ms-auto">
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
                                        <div id="list-completed"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Keranjang + Checkout --}}
                        <div class="card card-flush shadow-sm">
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
@endsection

@php
    $menusData = $menus->map(fn($m) => [
        'id' => $m->id,
        'name' => $m->name,
        'price' => (float) $m->price,
        'category_id' => $m->category_id,
        'image' => $m->image ? asset('storage/menus/' . $m->image) : null,
        'addons' => $m->activeAddons->map(fn($a) => ['id' => $a->id, 'name' => $a->name, 'price' => (float) $a->price])->values(),
    ])->values();
@endphp
@push('scripts')
    <script>
        // ================= DATA DARI SERVER =================
        const MENUS = @json($menusData);
        const TAX_RATE = {{ (float) ($setting->tax_rate ?? 0) }};
        const ROUTES = {
            store:    "{{ route('kasir.store') }}",
            orders:   "{{ route('kasir.orders') }}",
            base:     "{{ url('admin/kasir/order') }}",   // + /{id}, /{id}/pay, /{id}/complete
            print:    "{{ url('admin/kasir/print') }}",   // + /{id}
        };
        const CSRF = "{{ csrf_token() }}";

        // ================= STATE =================
        let cart = [];
        let currentCat = 'all';

        const rupiah = n => 'Rp ' + Number(n || 0).toLocaleString('id-ID');
        const esc = s => $('<div>').text(s == null ? '' : s).html();
        const openPrint = url => window.open(url, '_blank');

        // ===== Cetak struk: bridge printer thermal (APK) atau window.print (browser) =====
        const STORE_NAME = @json($setting->store_name ?? 'Stakko POS');
        const hasPrinter = () => !!(window.AndroidPrinter && typeof window.AndroidPrinter.printReceipt === 'function');

        function escposText(r) {
            const W = 32;
            const money = n => 'Rp' + Number(n || 0).toLocaleString('id-ID');
            const center = s => { s = String(s); if (s.length >= W) return s.slice(0, W); return ' '.repeat(Math.floor((W - s.length) / 2)) + s; };
            const row = (l, rr) => { l = String(l); rr = String(rr); const sp = W - l.length - rr.length; return sp > 0 ? l + ' '.repeat(sp) + rr : (l + ' ' + rr).slice(0, W); };
            const sep = '-'.repeat(W);
            const o = [];
            o.push(center((r.store_name || STORE_NAME).toUpperCase()));
            o.push(sep);
            o.push(center('NO. ANTRIAN ' + (r.queue_number ?? '-')));
            o.push(sep);
            o.push(row('No', r.invoice_no || ''));
            o.push(row('Tgl', r.datetime || ''));
            if (r.customer_name) o.push(row('Plg', r.customer_name));
            o.push(sep);
            (r.items || []).forEach(it => {
                o.push(String(it.name));
                if (it.addons && it.addons.length) it.addons.forEach(a => o.push('  + ' + (a.name || '')));
                o.push(row('  ' + it.qty + ' x ' + money(it.price), money(it.subtotal)));
                if (it.notes) o.push('  * ' + it.notes);
            });
            o.push(sep);
            o.push(row('Subtotal', money(r.subtotal)));
            if (Number(r.discount_amount) > 0) o.push(row('Diskon', '-' + money(r.discount_amount)));
            o.push(row('Pajak', money(r.tax)));
            o.push(row('TOTAL', money(r.grand_total)));
            o.push(row('Metode', (r.payment_method || '-').toUpperCase()));
            if (r.payment_method === 'cash' && r.cash_received != null) {
                o.push(row('Tunai', money(r.cash_received)));
                o.push(row('Kembali', money(r.change_amount)));
            }
            o.push(sep);
            o.push(center(r.payment_status === 'paid' ? '*** LUNAS ***' : '** BELUM LUNAS **'));
            o.push(center('Terima kasih!'));
            return o.join('\n');
        }

        function doPrintReceipt(receipt, printUrl) {
            // Engine cetak terpusat (browser/qztray/webbluetooth/rawbt/native)
            if (window.StakkoPrint) { window.StakkoPrint.print(receipt, printUrl); return; }
            if (printUrl) openPrint(printUrl);
        }

        // ================= RENDER MENU =================
        function renderMenus() {
            const q = ($('#menu-search').val() || '').toLowerCase();
            const grid = $('#menu-grid').empty();
            let shown = 0;
            MENUS.forEach(m => {
                if (currentCat !== 'all' && String(m.category_id) !== String(currentCat)) return;
                if (q && !m.name.toLowerCase().includes(q)) return;
                shown++;
                const img = m.image
                    ? `<img src="${m.image}" class="pos-menu-img">`
                    : `<div class="pos-menu-img d-flex align-items-center justify-content-center"><i class="ki-outline ki-coffee fs-2x text-muted"></i></div>`;
                const badge = m.addons.length ? `<span class="badge badge-light-info fs-9 mt-1">+${m.addons.length} add-on</span>` : '';
                grid.append(`
                    <div class="col">
                        <div class="card card-bordered pos-menu-card h-100" data-menu="${m.id}">
                            ${img}
                            <div class="p-2">
                                <div class="fw-bold text-gray-800 fs-7 text-truncate">${esc(m.name)}</div>
                                <div class="text-success fw-bold fs-7">${rupiah(m.price)}</div>
                                ${badge}
                            </div>
                        </div>
                    </div>`);
            });
            $('#menu-empty').toggleClass('d-none', shown > 0);
        }

        // ================= CART =================
        function cartLineTotal(item) { return item.unit * item.qty; }

        function renderCart() {
            const box = $('#cart-items');
            box.find('.cart-row').remove();
            if (cart.length === 0) {
                $('#cart-empty').removeClass('d-none');
            } else {
                $('#cart-empty').addClass('d-none');
                cart.forEach((it, idx) => {
                    const addonTxt = it.addons.length ? `<div class="fs-8 text-primary">+ ${it.addons.map(a => esc(a.name)).join(', ')}</div>` : '';
                    const noteTxt = it.note ? `<div class="fs-8 text-muted fst-italic">“${esc(it.note)}”</div>` : '';
                    box.append(`
                        <div class="cart-row d-flex align-items-start justify-content-between border-bottom py-2" data-idx="${idx}">
                            <div class="me-2">
                                <div class="fw-bold text-gray-800 fs-7">${esc(it.name)}</div>
                                ${addonTxt}${noteTxt}
                                <div class="text-muted fs-8">${rupiah(it.unit)}</div>
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

        function addToCart(menu, qty, addonIds, note) {
            const addons = menu.addons.filter(a => addonIds.includes(a.id));
            const unit = menu.price + addons.reduce((s, a) => s + a.price, 0);
            cart.push({
                menu_id: menu.id, name: menu.name, base_price: menu.price,
                addons, addon_ids: addons.map(a => a.id), qty: qty, note: note || '', unit
            });
            renderCart();
        }

        // ================= ADD MENU (with add-on modal) =================
        $('#menu-grid').on('click', '.pos-menu-card', function() {
            const menu = MENUS.find(m => String(m.id) === String($(this).data('menu')));
            if (!menu) return;
            if (menu.addons.length === 0) { addToCart(menu, 1, [], ''); return; }
            $('#addon-menu-id').val(menu.id);
            $('#addon-menu-name').text(menu.name);
            $('#addon-qty').val(1);
            $('#addon-note').val('');
            const list = $('#addon-list').empty();
            list.append('<label class="fw-semibold fs-7 text-muted mb-2 d-block">Pilih Add-On</label>');
            menu.addons.forEach(a => {
                list.append(`
                    <label class="form-check form-check-custom form-check-solid mb-2 d-flex justify-content-between">
                        <span><input class="form-check-input me-2 addon-check" type="checkbox" value="${a.id}"> ${esc(a.name)}</span>
                        <span class="text-success fw-bold">+ ${rupiah(a.price)}</span>
                    </label>`);
            });
            $('#modal-addon').modal('show');
        });

        $('#btn-addon-confirm').on('click', function() {
            const menu = MENUS.find(m => String(m.id) === String($('#addon-menu-id').val()));
            if (!menu) return;
            const ids = $('.addon-check:checked').map((i, el) => Number(el.value)).get();
            const qty = Math.max(1, Number($('#addon-qty').val() || 1));
            addToCart(menu, qty, ids, $('#addon-note').val());
            $('#modal-addon').modal('hide');
        });

        // ================= CART EVENTS =================
        $('#cart-items').on('click', '.qty-inc', function() { cart[$(this).closest('.cart-row').data('idx')].qty++; renderCart(); });
        $('#cart-items').on('click', '.qty-dec', function() {
            const i = $(this).closest('.cart-row').data('idx');
            if (cart[i].qty > 1) cart[i].qty--; else cart.splice(i, 1);
            renderCart();
        });
        $('#cart-items').on('click', '.cart-remove', function() { cart.splice($(this).closest('.cart-row').data('idx'), 1); renderCart(); });
        $('#btn-clear-cart').on('click', function() { if (cart.length) { cart = []; renderCart(); } });
        $('#promo-select').on('change', recalcTotals);

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

        // ================= CHECKOUT =================
        function buildPayload(withPayment) {
            const payload = {
                _token: CSRF,
                customer_name: $('#customer-name').val().trim(),
                promo_id: $('#promo-select').val() || null,
                cart: cart.map(it => ({ menu_id: it.menu_id, qty: it.qty, addon_ids: it.addon_ids, note: it.note })),
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
            const payload = buildPayload(withPayment);
            const $btn = withPayment ? $('#btn-pay-now') : $('#btn-pay-later');
            const orig = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({ url: ROUTES.store, method: 'POST', data: payload })
                .done(res => {
                    if (res.success) {
                        cart = []; renderCart(); resetCheckout();
                        loadOrders();
                        afterOrder(res);
                    } else { Swal.fire('Gagal', res.error || 'Terjadi kesalahan', 'error'); }
                })
                .fail(xhr => handleOfflineOrder(xhr, payload))
                .always(() => $btn.prop('disabled', false).html(orig));
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
            recalcTotals();
        }

        // Offline fallback: simpan ke Dexie utk disync engine di layout
        function handleOfflineOrder(xhr, payload) {
            if (navigator.onLine && xhr.status !== 0) {
                const msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Gagal menyimpan pesanan.';
                Swal.fire('Gagal', msg, 'error');
                return;
            }
            if (!window.posDB) { Swal.fire('Offline', 'Tidak ada koneksi & penyimpanan offline tidak tersedia.', 'error'); return; }
            const uuid = 'off-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
            const offline = Object.assign({}, payload, { uuid, invoice_no: 'OFF-' + Date.now(), status: 'pending_sync' });
            delete offline._token;
            window.posDB.offline_orders.put(offline).then(() => {
                cart = []; renderCart(); resetCheckout();
                if (window.updateConnectionStatus) window.updateConnectionStatus();
                Swal.fire('Tersimpan Offline', 'Pesanan disimpan lokal & akan otomatis tersinkron saat online.', 'info');
            });
        }

        $('#btn-pay-now').on('click', () => submitOrder(true));
        $('#btn-pay-later').on('click', () => submitOrder(false));

        // ================= PESANAN BERJALAN =================
        function orderCard(o) {
            const paid = o.payment_status === 'paid';
            const payBadge = paid
                ? '<span class="badge badge-light-success">Lunas</span>'
                : '<span class="badge badge-light-danger">Belum Lunas</span>';
            const done = o.order_status === 'completed';
            const selesaiBtn = done ? '' :
                `<button class="btn btn-sm btn-light-success flex-fill fw-bold btn-complete" data-id="${o.id}" data-paid="${paid ? 1 : 0}" data-total="${o.grand_total}">
                    <i class="ki-outline ki-check fs-5"></i> Selesai</button>`;
            return `
                <div class="d-flex flex-column border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="fw-bold fs-5 text-gray-800">No. ${o.queue_number ?? '-'}</span>
                            <div class="fs-8 text-muted">${esc(o.customer_name || '')} • ${o.items_count} item • ${o.created_at ?? ''}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-gray-800">${rupiah(o.grand_total)}</div>
                            ${payBadge}
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn btn-sm btn-light flex-fill btn-view" data-id="${o.id}"><i class="ki-outline ki-eye fs-5"></i> View</button>
                        ${selesaiBtn}
                    </div>
                </div>`;
        }

        function loadOrders() {
            $.get(ROUTES.orders).done(res => {
                $('#count-processing').text(res.processing.length);
                $('#count-completed').text(res.completed.length);
                $('#list-processing').html(res.processing.length ? res.processing.map(orderCard).join('') :
                    '<div class="text-center text-muted py-6">Belum ada pesanan berjalan.</div>');
                $('#list-completed').html(res.completed.length ? res.completed.map(orderCard).join('') :
                    '<div class="text-center text-muted py-6">Belum ada pesanan selesai hari ini.</div>');
            });
        }
        $('#btn-refresh-orders').on('click', loadOrders);

        // View detail
        $('body').on('click', '.btn-view', function() {
            const id = $(this).data('id');
            $('#detail-body').html('<div class="text-center py-6"><span class="spinner-border text-primary"></span></div>');
            $('#modal-detail').modal('show');
            $.get(ROUTES.base + '/' + id).done(res => {
                const o = res.order;
                window.__lastDetail = {
                    store_name: STORE_NAME, invoice_no: o.invoice_no, queue_number: o.queue_number,
                    customer_name: o.customer_name, datetime: o.created_at, items: res.items,
                    subtotal: o.subtotal, discount_amount: o.discount_amount, tax: o.tax, grand_total: o.grand_total,
                    payment_method: o.payment_method, payment_status: o.payment_status,
                    cash_received: o.cash_received, change_amount: o.change_amount
                };
                const rows = res.items.map(it => {
                    const ad = (it.addons && it.addons.length) ? `<div class="fs-8 text-primary">+ ${it.addons.map(a => esc(a.name)).join(', ')}</div>` : '';
                    const nt = it.notes ? `<div class="fs-8 text-muted fst-italic">“${esc(it.notes)}”</div>` : '';
                    return `<div class="d-flex justify-content-between border-bottom py-2">
                        <div><span class="fw-bold">${it.qty}x</span> ${esc(it.name)}${ad}${nt}</div>
                        <div class="fw-bold">${rupiah(it.subtotal)}</div></div>`;
                }).join('');
                $('#detail-body').html(`
                    <div class="mb-3">
                        <div class="fs-2 fw-bold text-primary">No. ${o.queue_number ?? '-'}</div>
                        <div class="text-muted">${esc(o.customer_name || '')} • #${o.invoice_no}</div>
                        <div>${o.payment_status === 'paid' ? '<span class="badge badge-success">Lunas</span>' : '<span class="badge badge-danger">Belum Lunas</span>'}
                             <span class="badge badge-light-info text-uppercase">${o.payment_method || '-'}</span></div>
                    </div>
                    ${rows}
                    <div class="d-flex justify-content-between mt-3"><span class="text-muted">Subtotal</span><span>${rupiah(o.subtotal)}</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Diskon</span><span class="text-danger">- ${rupiah(o.discount_amount)}</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Pajak</span><span>${rupiah(o.tax)}</span></div>
                    <div class="d-flex justify-content-between fw-bold fs-4"><span>Total</span><span class="text-success">${rupiah(o.grand_total)}</span></div>
                    <div class="text-end mt-4"><button type="button" class="btn btn-sm btn-light-primary" onclick="doPrintReceipt(window.__lastDetail, '${ROUTES.print}/${o.id}')"><i class="ki-outline ki-printer"></i> Cetak Struk</button></div>`);
            });
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
            renderMenus();
        });
        $('#menu-search').on('keyup', renderMenus);

        function cacheForOffline() {
            if (!window.posDB) return;
            try {
                window.posDB.menus.bulkPut(MENUS.map(m => ({ id: m.id, name: m.name, price: m.price, category_id: m.category_id })));
                window.posDB.settings.put({ id: 1, tax_rate: TAX_RATE });
            } catch (e) { /* abaikan */ }
        }

        // ===== Tombol Printer (muncul jika metode butuh koneksi: native/BT/QZ) =====
        function initPrinterButton() {
            if (window.StakkoPrint && window.StakkoPrint.needsButton()) {
                $('#btn-printer').removeClass('d-none');
                $('#printer-label').text(window.StakkoPrint.buttonLabel());
            }
        }
        $('#btn-printer').on('click', function() {
            if (window.StakkoPrint) window.StakkoPrint.quickConnect();
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
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Sinkronisasi dijalankan', showConfirmButton: false, timer: 2000 });
        });
        window.addEventListener('online', updateSyncBadge);
        window.addEventListener('offline', updateSyncBadge);

        $(document).ready(function() {
            renderMenus();
            renderCart();
            loadOrders();
            cacheForOffline();
            initPrinterButton();
            updateSyncBadge();
            setInterval(loadOrders, 20000);
            setInterval(updateSyncBadge, 5000);
        });
    </script>
@endpush
