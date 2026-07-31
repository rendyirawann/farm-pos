@extends('backend.layout.app')
@section('title', 'Kasir Laundry — Nota Baru')
@section('content')

@php
    // Persen pajak dari Pengaturan (dipakai juga di server saat menyimpan).
    $taxRate = (float) (\App\Models\Setting::query()->value('tax_rate') ?? 0);
    // Kategori layanan untuk chip filter (dari master data Layanan).
    $svcCategories = $services->pluck('category')->filter()->unique()->values();
    $countActive = \App\Models\Laundry\LaundryOrder::whereIn('order_status', \App\Models\Laundry\LaundryOrder::ACTIVE_STATUSES)->count();
    $countReady  = \App\Models\Laundry\LaundryOrder::where('order_status', 'selesai')->count();
@endphp

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        {{-- Header + status ringkas (Sedang Diproses / Selesai / Offline) --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-5 gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('laundry.kasir.index') }}" class="btn btn-icon btn-light btn-sm"><i class="ki-outline ki-arrow-left fs-3"></i></a>
                <div>
                    <h1 class="fw-bold text-gray-900 mb-0 fs-2">Kasir Laundry</h1>
                    <span class="text-muted fs-7">Pembuatan nota laundry terpadu</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('laundry.kasir.index') }}" class="badge badge-light-primary fs-7 py-2 px-3">Sedang Diproses <span class="badge badge-circle badge-primary ms-1">{{ $countActive }}</span></a>
                <a href="{{ route('laundry.kasir.index') }}" class="badge badge-light-success fs-7 py-2 px-3">Selesai <span class="badge badge-circle badge-success ms-1">{{ $countReady }}</span></a>
                <span class="badge fs-7 py-2 px-3" id="net-badge"><span id="net-dot">●</span> <span id="net-text">Online</span></span>
            </div>
        </div>

        <div class="row g-6">
            {{-- ============ KIRI: katalog layanan ============ --}}
            <div class="col-lg-7">
                <div class="card card-flush mb-5">
                    <div class="card-body py-4">
                        <div class="position-relative">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute translate-middle-y top-50 ms-4 text-muted"></i>
                            <input type="text" id="svc_search" class="form-control form-control-solid ps-13" placeholder="Cari layanan laundry...">
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-4" id="cat_chips">
                            <button type="button" class="btn btn-sm btn-primary cat-chip active" data-cat="">Semua</button>
                            @foreach ($svcCategories as $cat)
                                <button type="button" class="btn btn-sm btn-light cat-chip" data-cat="{{ $cat }}">{{ $cat }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="row g-4" id="svc_grid">
                    @forelse ($services as $s)
                        <div class="col-md-6 svc-item" data-cat="{{ $s->category }}" data-name="{{ strtolower($s->name) }}">
                            <div class="card card-flush h-100 svc-card cursor-pointer border border-2 border-gray-200"
                                data-id="{{ $s->id }}" data-sname="{{ $s->name }}" data-price="{{ (int) $s->price_per_unit }}"
                                data-unit="{{ $s->unit }}" data-hours="{{ $s->estimated_duration_hours }}">
                                <div class="card-body p-5">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <span class="badge badge-light-primary fs-8">{{ $s->category ?: 'Layanan' }}</span>
                                        <span class="text-muted fs-8"><i class="ki-outline ki-time fs-6"></i>
                                            {{ $s->estimated_duration_hours >= 24 ? floor($s->estimated_duration_hours / 24) . ' Hari' : $s->estimated_duration_hours . ' Jam' }}
                                        </span>
                                    </div>
                                    <div class="fw-bold text-gray-900 fs-5 mb-2">{{ $s->name }}</div>
                                    <div class="d-flex justify-content-between align-items-end">
                                        <div>
                                            <span class="fw-bolder text-success fs-4">Rp {{ number_format($s->price_per_unit, 0, ',', '.') }}</span>
                                            <span class="fs-8 text-muted">/ {{ $s->unit }}</span>
                                        </div>
                                        <span class="btn btn-icon btn-sm btn-light-primary"><i class="ki-outline ki-plus fs-4"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="card card-flush"><div class="card-body text-center text-muted py-10">
                                Belum ada layanan aktif. Tambahkan dulu di <a href="{{ route('laundry.services.index') }}">Data Master → Layanan</a>.
                            </div></div>
                        </div>
                    @endforelse
                    <div class="col-12 d-none" id="svc_empty">
                        <div class="card card-flush"><div class="card-body text-center text-muted py-10">Layanan tidak ditemukan.</div></div>
                    </div>
                </div>

                {{-- Petunjuk global + estimasi --}}
                <div class="card card-flush mt-5">
                    <div class="card-header pt-5"><h3 class="card-title fw-bold fs-5">Diagnosis &amp; Petunjuk Produksi</h3></div>
                    <div class="card-body pt-2">
                        <label class="form-label fw-semibold fs-7">Petunjuk Khusus Cucian (Global)</label>
                        <textarea id="special_instructions" class="form-control form-control-solid" rows="2"
                            placeholder="Contoh: Pisahkan pakaian luntur, gantung gaun pesta, lipat rapi tanpa parfum..."></textarea>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold fs-7 required">Estimasi Selesai (Target)</label>
                                <input type="text" id="eta_display" class="form-control form-control-solid" readonly>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="fs-8 text-muted">Dihitung otomatis dari layanan dengan durasi terlama.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ KANAN: panel status + rincian transaksi ============ --}}
            <div class="col-lg-5">

                {{-- Panel pesanan: Sedang Diproses / Selesai / Offline --}}
                <div class="card card-flush shadow-sm mb-5">
                    <div class="card-header pt-4 pb-0 min-h-40px">
                        <ul class="nav nav-pills nav-pills-sm gap-2 w-100">
                            <li class="nav-item">
                                <a class="nav-link btn btn-sm btn-color-muted btn-active-light-primary fw-bold active"
                                    data-bs-toggle="tab" href="#ld-tab-processing">Sedang Diproses
                                    <span class="badge badge-primary ms-1">{{ $activeOrders->count() }}</span></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link btn btn-sm btn-color-muted btn-active-light-success fw-bold"
                                    data-bs-toggle="tab" href="#ld-tab-ready">Selesai
                                    <span class="badge badge-success ms-1">{{ $readyOrders->count() }}</span></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link btn btn-sm btn-color-muted btn-active-light-warning fw-bold"
                                    data-bs-toggle="tab" href="#ld-tab-offline">Offline
                                    <span class="badge badge-warning ms-1" id="ld-count-offline">0</span></a>
                            </li>
                            <li class="nav-item ms-auto d-flex align-items-center gap-1">
                                {{-- Tombol hubungkan printer (muncul bila metode butuh koneksi: Bluetooth/QZ/native) --}}
                                <button class="btn btn-sm btn-light-primary d-none" id="btn-printer" type="button">
                                    <i class="ki-outline ki-printer fs-4"></i> <span id="printer-label">Printer</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body pt-3 pb-4">
                        <div class="tab-content">
                            {{-- Sedang diproses --}}
                            <div class="tab-pane fade show active" id="ld-tab-processing" style="max-height:260px;overflow-y:auto">
                                @forelse ($activeOrders as $o)
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                        <div>
                                            <div class="fw-bold text-gray-900 fs-8">{{ $o->invoice_no }}</div>
                                            <div class="fs-8 text-muted">{{ $o->customer_name }} ·
                                                <span class="badge badge-light-primary fs-9">{{ \App\Models\Laundry\LaundryOrder::STAGE_LABELS[$o->order_status] ?? $o->order_status }}</span>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold fs-8">Rp {{ number_format($o->grand_total, 0, ',', '.') }}</div>
                                            <span class="badge badge-light-{{ $o->payment_status === 'paid' ? 'success' : 'warning' }} fs-9">
                                                {{ $o->payment_status === 'paid' ? 'Lunas' : 'Belum bayar' }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-muted fs-8 py-6">Belum ada cucian dalam proses.</div>
                                @endforelse
                            </div>
                            {{-- Selesai / siap diambil --}}
                            <div class="tab-pane fade" id="ld-tab-ready" style="max-height:260px;overflow-y:auto">
                                @forelse ($readyOrders as $o)
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                        <div>
                                            <div class="fw-bold text-gray-900 fs-8">{{ $o->invoice_no }}</div>
                                            <div class="fs-8 text-muted">{{ $o->customer_name }} · siap diambil</div>
                                        </div>
                                        <a href="{{ route('laundry.kasir.index') }}" class="btn btn-sm btn-light-success py-1 px-3 fs-8">Serahkan</a>
                                    </div>
                                @empty
                                    <div class="text-center text-muted fs-8 py-6">Belum ada cucian siap diambil.</div>
                                @endforelse
                            </div>
                            {{-- Offline (antrean lokal) --}}
                            <div class="tab-pane fade" id="ld-tab-offline" style="max-height:260px;overflow-y:auto">
                                <div class="text-center text-muted fs-8 py-6" id="ld-offline-empty">
                                    Tidak ada nota offline. Nota tersimpan langsung selama koneksi tersedia.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-flush" style="position:sticky;top:90px">
                    <div class="card-header pt-5"><h3 class="card-title fw-bold fs-4">Rincian Transaksi</h3></div>
                    <div class="card-body pt-2">

                        {{-- Pelanggan --}}
                        <label class="form-label fw-semibold fs-7 required d-flex align-items-center gap-2">
                            <i class="ki-outline ki-profile-user fs-4 text-primary"></i> Pilih / Input Pelanggan
                        </label>
                        <div class="d-flex gap-2 mb-3">
                            <select id="customer_select" class="form-select form-select-solid">
                                <option value="">— Pelanggan baru / walk-in —</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}" data-vip="{{ $c->member_status === 'vip' ? 1 : 0 }}"
                                        data-name="{{ $c->name }}" data-phone="{{ $c->phone }}" data-email="{{ $c->email }}" data-address="{{ $c->address }}">
                                        {{ $c->name }}{{ $c->member_status === 'vip' ? ' ★VIP' : '' }}{{ $c->phone ? ' · ' . $c->phone : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="border border-dashed rounded p-4 mb-4" id="cust_box">
                            <div class="row g-3">
                                <div class="col-7">
                                    <label class="form-label fs-8 fw-semibold required mb-1">Nama Pelanggan</label>
                                    <input type="text" id="customer_name" class="form-control form-control-solid form-control-sm" placeholder="Nama Pelanggan">
                                </div>
                                <div class="col-5">
                                    <label class="form-label fs-8 fw-semibold mb-1">Nomor WA / HP</label>
                                    <input type="text" id="customer_phone" class="form-control form-control-solid form-control-sm" placeholder="08123xxx">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fs-8 fw-semibold mb-1">Email Pelanggan <span class="text-muted">(notifikasi selesai)</span></label>
                                    <input type="email" id="customer_email" class="form-control form-control-solid form-control-sm" placeholder="pelanggan@gmail.com">
                                </div>
                                <div class="col-12">
                                    <label class="form-check form-check-sm form-check-custom">
                                        <input class="form-check-input" type="checkbox" id="save_customer" checked>
                                        <span class="form-check-label fs-8 fw-semibold">Simpan ke Data Master Pelanggan</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Metode pengiriman --}}
                        <label class="form-label fw-semibold fs-7 required">Metode Pengiriman</label>
                        <select id="order_type" class="form-select form-select-solid mb-3">
                            <option value="self_pickup">🧺 Ambil Sendiri (Self-Pickup)</option>
                            <option value="delivery">🚚 Antar-Jemput (Delivery)</option>
                        </select>
                        <div id="delivery_box" class="d-none mb-3">
                            <label class="form-label fs-8 fw-semibold mb-1">Alamat Lengkap Pelanggan</label>
                            <textarea id="delivery_address" class="form-control form-control-solid form-control-sm mb-2" rows="2"
                                placeholder="Masukkan alamat lengkap pengiriman/pengambilan laundry..."></textarea>
                            <label class="form-label fs-8 fw-semibold mb-1">Ongkir (Rp)</label>
                            <input type="number" id="delivery_fee" class="form-control form-control-solid form-control-sm" min="0" step="1000" value="0">
                        </div>

                        {{-- Keranjang --}}
                        <div class="separator my-4"></div>
                        <div class="d-flex justify-content-between text-muted fs-8 fw-bold text-uppercase mb-2">
                            <span>Layanan</span><span>Jumlah (Qty)</span><span>Subtotal</span>
                        </div>
                        <div id="cart"><div class="text-center text-muted py-6 fs-7" id="cart_empty">Keranjang laundry kosong.</div></div>

                        {{-- Ringkasan --}}
                        <div class="separator my-4"></div>
                        <div class="d-flex justify-content-between mb-2 fs-7"><span class="text-muted">Subtotal</span><span class="fw-bold" id="t_subtotal">Rp 0</span></div>
                        <div class="d-flex justify-content-between mb-2 fs-7" id="row_discount"><span class="text-muted">Diskon VIP (10%)</span><span class="fw-bold text-danger" id="t_discount">- Rp 0</span></div>
                        <div class="d-flex justify-content-between mb-2 fs-7"><span class="text-muted">Pajak ({{ rtrim(rtrim(number_format($taxRate, 2, '.', ''), '0'), '.') }}%)</span><span class="fw-bold" id="t_tax">Rp 0</span></div>
                        <div class="d-flex justify-content-between mb-2 fs-7" id="row_delivery"><span class="text-muted">Ongkir</span><span class="fw-bold" id="t_delivery">Rp 0</span></div>
                        <div class="d-flex justify-content-between align-items-center bg-light-success rounded p-4 my-3">
                            <span class="fw-bold text-gray-900">Grand Total</span>
                            <span class="fw-bolder fs-2 text-success" id="t_grand">Rp 0</span>
                        </div>

                        {{-- Pembayaran --}}
                        <label class="form-label fw-semibold fs-7">Metode Pembayaran</label>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><button type="button" class="btn btn-light-primary w-100 pay-opt active" data-pay="cash"><i class="ki-outline ki-dollar fs-4 me-1"></i>Tunai</button></div>
                            <div class="col-6"><button type="button" class="btn btn-light w-100 pay-opt" data-pay="nanti"><i class="ki-outline ki-time fs-4 me-1"></i>Bayar Nanti</button></div>
                        </div>
                        <div id="cash_box" class="mb-3">
                            <input type="number" id="cash_received" class="form-control form-control-solid" placeholder="Uang diterima (Rp)" min="0" step="1000">
                            <div class="fs-8 text-muted mt-1">Kembalian: <b id="t_change">Rp 0</b></div>
                        </div>

                        <button type="button" id="btn_save" class="btn btn-primary w-100" disabled>
                            <i class="ki-outline ki-check-circle fs-4 me-1"></i> Simpan Nota &amp; Cetak
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const STORE_URL = "{{ route('laundry.kasir.store') }}";
    const BOARD_URL = "{{ route('laundry.kasir.index') }}";
    const CSRF = "{{ csrf_token() }}";
    const TAX_RATE = {{ $taxRate }};
    const rp = n => 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    let cart = [], payMethod = 'cash';

    const isVip = () => { const o = document.querySelector('#customer_select option:checked'); return o && o.dataset.vip === '1'; };

    function renderCart() {
        const box = document.getElementById('cart');
        if (!cart.length) {
            box.innerHTML = '<div class="text-center text-muted py-6 fs-7">Keranjang laundry kosong.</div>';
        } else {
            box.innerHTML = cart.map((it, i) => `
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="me-2">
                            <div class="fw-bold text-gray-900 fs-7">${it.name}</div>
                            <div class="fs-8 text-success fw-semibold">${rp(it.price)} <span class="text-muted">/ ${it.unit}</span></div>
                        </div>
                        <div class="text-end">
                            <div class="input-group input-group-sm" style="width:130px">
                                <button class="btn btn-sm btn-light px-2" type="button" onclick="stepQty(${i},-1)">−</button>
                                <input type="number" class="form-control form-control-sm text-center js-no-format" value="${it.qty}"
                                    min="0.01" step="${it.unit === 'kg' ? '0.1' : '1'}" onchange="setQty(${i}, this.value)">
                                <button class="btn btn-sm btn-light px-2" type="button" onclick="stepQty(${i},1)">+</button>
                            </div>
                            <div class="fw-bolder text-gray-900 mt-1">${rp(it.qty * it.price)}</div>
                        </div>
                        <span class="text-hover-danger cursor-pointer ms-2" onclick="rmItem(${i})"><i class="ki-outline ki-cross fs-4"></i></span>
                    </div>
                    <input type="text" class="form-control form-control-sm form-control-solid mt-2" placeholder="Diagnosis noda/kerusakan item..."
                        value="${(it.item_condition || '').replace(/"/g, '&quot;')}" onchange="setCond(${i}, this.value)">
                    <input type="text" class="form-control form-control-sm form-control-solid mt-2" placeholder="Petunjuk khusus item ini..."
                        value="${(it.notes || '').replace(/"/g, '&quot;')}" onchange="setNotes(${i}, this.value)">
                </div>`).join('');
        }
        calc();
    }
    window.rmItem   = i => { cart.splice(i, 1); renderCart(); };
    window.setQty   = (i, v) => { cart[i].qty = Math.max(0.01, parseFloat(v) || 0.01); renderCart(); };
    window.stepQty  = (i, d) => { const st = cart[i].unit === 'kg' ? 0.5 : 1; cart[i].qty = Math.max(0.01, +(cart[i].qty + d * st).toFixed(2)); renderCart(); };
    window.setCond  = (i, v) => { cart[i].item_condition = v; };
    window.setNotes = (i, v) => { cart[i].notes = v; };

    function calc() {
        let subtotal = 0, maxHours = 0;
        cart.forEach(it => { subtotal += it.qty * it.price; maxHours = Math.max(maxHours, it.hours); });
        const discount = isVip() ? Math.round(subtotal * 0.10) : 0;
        const net = Math.max(0, subtotal - discount);
        const tax = TAX_RATE > 0 ? Math.round(net * TAX_RATE / 100) : 0;
        const delivery = document.getElementById('order_type').value === 'delivery'
            ? (parseFloat(document.getElementById('delivery_fee').value) || 0) : 0;
        const grand = net + tax + delivery;

        document.getElementById('t_subtotal').textContent = rp(subtotal);
        document.getElementById('t_discount').textContent = '- ' + rp(discount);
        document.getElementById('t_tax').textContent = rp(tax);
        document.getElementById('t_delivery').textContent = rp(delivery);
        document.getElementById('t_grand').textContent = rp(grand);
        document.getElementById('row_discount').classList.toggle('d-none', discount <= 0);
        document.getElementById('row_delivery').classList.toggle('d-none', delivery <= 0);

        // Estimasi selesai = sekarang + durasi terlama.
        const eta = new Date(Date.now() + (maxHours || 48) * 3600 * 1000);
        document.getElementById('eta_display').value = cart.length
            ? eta.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
            : '—';

        const cash = parseFloat(document.getElementById('cash_received').value) || 0;
        document.getElementById('t_change').textContent = rp(Math.max(0, cash - grand));
        document.getElementById('btn_save').disabled = cart.length === 0;
        window._grand = grand;
    }

    // Tambah layanan ke keranjang
    document.querySelectorAll('.svc-card').forEach(card => card.addEventListener('click', () => {
        const d = card.dataset;
        const ex = cart.find(x => x.service_id == d.id);
        if (ex) { ex.qty = +(ex.qty + (d.unit === 'kg' ? 0.5 : 1)).toFixed(2); }
        else {
            cart.push({ service_id: +d.id, name: d.sname, price: +d.price, unit: d.unit, hours: +d.hours, qty: 1, item_condition: '', notes: '' });
        }
        renderCart();
    }));

    // Cari + filter kategori
    let curCat = '';
    function filterSvc() {
        const q = (document.getElementById('svc_search').value || '').toLowerCase().trim();
        let shown = 0;
        document.querySelectorAll('.svc-item').forEach(el => {
            const okCat = !curCat || el.dataset.cat === curCat;
            const okQ = !q || (el.dataset.name || '').includes(q);
            const show = okCat && okQ;
            el.classList.toggle('d-none', !show);
            if (show) shown++;
        });
        document.getElementById('svc_empty').classList.toggle('d-none', shown > 0);
    }
    document.getElementById('svc_search').addEventListener('input', filterSvc);
    document.querySelectorAll('.cat-chip').forEach(b => b.addEventListener('click', function () {
        document.querySelectorAll('.cat-chip').forEach(x => { x.classList.remove('btn-primary', 'active'); x.classList.add('btn-light'); });
        this.classList.remove('btn-light'); this.classList.add('btn-primary', 'active');
        curCat = this.dataset.cat || ''; filterSvc();
    }));

    // Pelanggan tersimpan -> isi otomatis
    document.getElementById('customer_select').addEventListener('change', function () {
        const o = this.options[this.selectedIndex];
        const saved = !!this.value;
        document.getElementById('customer_name').value  = saved ? (o.dataset.name || '') : '';
        document.getElementById('customer_phone').value = saved ? (o.dataset.phone || '') : '';
        document.getElementById('customer_email').value = saved ? (o.dataset.email || '') : '';
        if (saved && o.dataset.address) document.getElementById('delivery_address').value = o.dataset.address;
        document.getElementById('save_customer').closest('.col-12').classList.toggle('d-none', saved);
        calc();
    });

    document.getElementById('order_type').addEventListener('change', function () {
        document.getElementById('delivery_box').classList.toggle('d-none', this.value !== 'delivery'); calc();
    });
    document.getElementById('delivery_fee').addEventListener('input', calc);
    document.getElementById('cash_received').addEventListener('input', calc);

    // Pilih metode bayar
    document.querySelectorAll('.pay-opt').forEach(b => b.addEventListener('click', function () {
        document.querySelectorAll('.pay-opt').forEach(x => { x.classList.remove('btn-light-primary', 'active'); x.classList.add('btn-light'); });
        this.classList.remove('btn-light'); this.classList.add('btn-light-primary', 'active');
        payMethod = this.dataset.pay;
        document.getElementById('cash_box').classList.toggle('d-none', payMethod !== 'cash');
        calc();
    }));

    // ===== Printer: tombol "Hubungkan" muncul bila metode butuh koneksi (Bluetooth/QZ/native) =====
    function initPrinterButton() {
        if (window.MoodaPrint && window.MoodaPrint.needsButton && window.MoodaPrint.needsButton()) {
            const btn = document.getElementById('btn-printer');
            btn.classList.remove('d-none');
            document.getElementById('printer-label').textContent = window.MoodaPrint.buttonLabel
                ? window.MoodaPrint.buttonLabel() : 'Hubungkan Printer';
            // Pulihkan izin printer BLE yang sudah pernah diberikan (tanpa dialog).
            if (window.MoodaPrint.restoreBle) { try { window.MoodaPrint.restoreBle(); } catch (e) {} }
        }
    }
    document.getElementById('btn-printer').addEventListener('click', function () {
        if (window.MoodaPrint && window.MoodaPrint.quickConnect) window.MoodaPrint.quickConnect();
    });
    // MoodaPrint dimuat di layout; beri jeda kecil agar autoSetup selesai.
    setTimeout(initPrinterButton, 400);

    // Cetak struk lewat engine terpusat (browser / QZ Tray / Web Bluetooth / RawBT).
    function doPrintReceipt(receipt, printUrl) {
        if (window.MoodaPrint && window.MoodaPrint.print) { window.MoodaPrint.print(receipt, printUrl); return; }
        if (printUrl) window.open(printUrl, '_blank');
    }

    // Indikator online/offline
    function netUI() {
        const on = navigator.onLine;
        document.getElementById('net-badge').className = 'badge fs-7 py-2 px-3 badge-light-' + (on ? 'success' : 'warning');
        document.getElementById('net-text').textContent = on ? 'Online' : 'Offline';
    }
    window.addEventListener('online', netUI); window.addEventListener('offline', netUI); netUI();

    // Simpan nota
    document.getElementById('btn_save').addEventListener('click', function () {
        const btn = this; btn.disabled = true;
        const original = btn.innerHTML; btn.innerHTML = 'Menyimpan...';
        const payload = {
            cart: cart.map(it => ({ service_id: it.service_id, qty: it.qty, notes: it.notes || null, item_condition: it.item_condition || null })),
            customer_id: document.getElementById('customer_select').value || null,
            customer_name: document.getElementById('customer_name').value || null,
            customer_phone: document.getElementById('customer_phone').value || null,
            customer_email: document.getElementById('customer_email').value || null,
            save_customer: document.getElementById('save_customer').checked ? 1 : 0,
            order_type: document.getElementById('order_type').value,
            delivery_fee: parseFloat(document.getElementById('delivery_fee').value) || 0,
            delivery_address: document.getElementById('delivery_address').value || null,
            special_instructions: document.getElementById('special_instructions').value || null,
            payment_method: payMethod,
            cash_received: parseFloat(document.getElementById('cash_received').value) || 0,
        };
        fetch(STORE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        })
            .then(r => r.json())
            .then(d => {
                if (d.status === 'success') {
                    // Cetak via printer terkonfigurasi (Bluetooth/QZ/RawBT) — fallback dialog browser.
                    doPrintReceipt(d.receipt || null, d.print_url);
                    setTimeout(() => { window.location = BOARD_URL; }, 800);
                }
                else { alert(d.message || 'Gagal menyimpan nota.'); btn.disabled = false; btn.innerHTML = original; }
            })
            .catch(() => { alert('Kesalahan jaringan.'); btn.disabled = false; btn.innerHTML = original; });
    });

    calc();
</script>
@endpush
@endsection
