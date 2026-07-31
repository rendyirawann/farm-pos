@extends('backend.layout.app')
@section('title', 'Kasir Laundry — Nota Baru')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">
        <div class="row g-6">

            {{-- KIRI: daftar layanan --}}
            <div class="col-lg-7">
                <div class="card card-flush h-100">
                    <div class="card-header pt-5 d-flex justify-content-between align-items-center">
                        <h3 class="card-title fw-bold">Pilih Layanan</h3>
                        <a href="{{ route('laundry.kasir.index') }}" class="btn btn-sm btn-light">← Board</a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @forelse ($services as $s)
                                <div class="col-6 col-md-4">
                                    <div class="border border-dashed rounded p-3 h-100 cursor-pointer svc-card"
                                        data-id="{{ $s->id }}" data-name="{{ $s->name }}" data-price="{{ (int) $s->price_per_unit }}"
                                        data-unit="{{ $s->unit }}" data-hours="{{ $s->estimated_duration_hours }}">
                                        <div class="fw-bold text-gray-900">{{ $s->name }}</div>
                                        <div class="fs-8 text-muted">{{ $s->category }}</div>
                                        <div class="fw-bolder text-primary mt-2">Rp {{ number_format($s->price_per_unit, 0, ',', '.') }}<span class="fs-8 text-muted">/{{ $s->unit }}</span></div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted py-8">Belum ada layanan aktif. Tambahkan di <a href="{{ route('laundry.services.index') }}">Layanan</a>.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- KANAN: keranjang + pembayaran --}}
            <div class="col-lg-5">
                <div class="card card-flush">
                    <div class="card-header pt-5"><h3 class="card-title fw-bold">Nota</h3></div>
                    <div class="card-body">
                        {{-- Pelanggan --}}
                        <label class="form-label fw-semibold">Pelanggan</label>
                        <select id="customer_select" class="form-select form-select-solid mb-2">
                            <option value="">— Walk-in / manual —</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" data-vip="{{ $c->member_status === 'vip' ? 1 : 0 }}" data-phone="{{ $c->phone }}">
                                    {{ $c->name }} {{ $c->member_status === 'vip' ? '★VIP' : '' }} {{ $c->phone ? '· ' . $c->phone : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div id="manual_customer" class="row g-2 mb-3">
                            <div class="col-7"><input type="text" id="customer_name" class="form-control form-control-solid form-control-sm" placeholder="Nama (opsional)"></div>
                            <div class="col-5"><input type="text" id="customer_phone" class="form-control form-control-solid form-control-sm" placeholder="No. HP"></div>
                        </div>

                        {{-- Keranjang --}}
                        <div class="separator my-3"></div>
                        <div id="cart" class="mb-2"><div class="text-muted text-center py-4" id="cart_empty">Klik layanan untuk menambah.</div></div>

                        {{-- Antar-jemput --}}
                        <div class="separator my-3"></div>
                        <label class="form-label fw-semibold">Tipe</label>
                        <select id="order_type" class="form-select form-select-solid form-select-sm mb-2">
                            <option value="self_pickup">Antar sendiri (self-pickup)</option>
                            <option value="delivery">Antar-jemput (delivery)</option>
                        </select>
                        <div id="delivery_box" class="d-none mb-2">
                            <input type="number" id="delivery_fee" class="form-control form-control-solid form-control-sm mb-2" placeholder="Ongkir (Rp)" min="0" step="1000" value="0">
                            <input type="text" id="delivery_address" class="form-control form-control-solid form-control-sm" placeholder="Alamat antar">
                        </div>
                        <textarea id="special_instructions" class="form-control form-control-solid form-control-sm mb-3" rows="1" placeholder="Instruksi khusus (opsional)"></textarea>

                        {{-- Total --}}
                        <div class="bg-light rounded p-3 mb-3 fs-7">
                            <div class="d-flex justify-content-between"><span class="text-muted">Subtotal</span><span id="t_subtotal">Rp 0</span></div>
                            <div class="d-flex justify-content-between"><span class="text-muted">Diskon VIP</span><span id="t_discount">Rp 0</span></div>
                            <div class="d-flex justify-content-between"><span class="text-muted">Ongkir</span><span id="t_delivery">Rp 0</span></div>
                            <div class="d-flex justify-content-between fw-bolder fs-4 text-gray-900 mt-1"><span>Total</span><span id="t_grand">Rp 0</span></div>
                            <div class="fs-8 text-muted mt-1" id="t_eta"></div>
                        </div>

                        {{-- Pembayaran --}}
                        <label class="form-label fw-semibold">Pembayaran</label>
                        <select id="payment_method" class="form-select form-select-solid form-select-sm mb-2">
                            <option value="cash">Tunai (lunas)</option>
                            <option value="nanti">Bayar nanti (saat ambil)</option>
                        </select>
                        <div id="cash_box" class="mb-3">
                            <input type="number" id="cash_received" class="form-control form-control-solid" placeholder="Uang diterima (Rp)" min="0" step="1000">
                            <div class="fs-8 text-muted mt-1">Kembalian: <b id="t_change">Rp 0</b></div>
                        </div>

                        <button type="button" id="btn_save" class="btn btn-primary w-100" disabled>Simpan Nota</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const STORE_URL = "{{ route('laundry.kasir.store') }}";
    const CSRF = "{{ csrf_token() }}";
    const rp = n => 'Rp ' + Number(n || 0).toLocaleString('id-ID');
    let cart = [];

    function isVip() {
        const opt = document.querySelector('#customer_select option:checked');
        return opt && opt.dataset.vip === '1';
    }

    function renderCart() {
        const box = document.getElementById('cart');
        if (cart.length === 0) { box.innerHTML = '<div class="text-muted text-center py-4">Klik layanan untuk menambah.</div>'; }
        else {
            box.innerHTML = cart.map((it, i) => `
                <div class="d-flex flex-column border-bottom py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-gray-900">${it.name} <span class="fs-8 text-muted">/${it.unit}</span></span>
                        <span class="text-hover-danger cursor-pointer" onclick="rmItem(${i})">✕</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <input type="number" class="form-control form-control-sm w-100px" value="${it.qty}" min="0.01" step="${it.unit==='kg'?'0.1':'1'}" onchange="setQty(${i}, this.value)">
                        <span class="text-muted fs-8">× ${rp(it.price)}</span>
                        <span class="ms-auto fw-bold">${rp(it.qty * it.price)}</span>
                    </div>
                    <input type="text" class="form-control form-control-sm mt-1" placeholder="Catatan / diagnosis noda" value="${it.item_condition||''}" onchange="setCond(${i}, this.value)">
                </div>`).join('');
        }
        calc();
    }
    window.rmItem = i => { cart.splice(i, 1); renderCart(); };
    window.setQty = (i, v) => { cart[i].qty = Math.max(0.01, parseFloat(v) || 0.01); renderCart(); };
    window.setCond = (i, v) => { cart[i].item_condition = v; };

    function calc() {
        let subtotal = 0, maxHours = 0;
        cart.forEach(it => { subtotal += it.qty * it.price; maxHours = Math.max(maxHours, it.hours); });
        const discount = isVip() ? Math.round(subtotal * 0.10) : 0;
        const net = Math.max(0, subtotal - discount);
        const delivery = document.getElementById('order_type').value === 'delivery' ? (parseFloat(document.getElementById('delivery_fee').value) || 0) : 0;
        const grand = net + delivery;
        document.getElementById('t_subtotal').textContent = rp(subtotal);
        document.getElementById('t_discount').textContent = '- ' + rp(discount);
        document.getElementById('t_delivery').textContent = rp(delivery);
        document.getElementById('t_grand').textContent = rp(grand);
        document.getElementById('t_eta').textContent = cart.length ? ('Estimasi selesai ~' + (maxHours||48) + ' jam') : '';
        const cash = parseFloat(document.getElementById('cash_received').value) || 0;
        document.getElementById('t_change').textContent = rp(Math.max(0, cash - grand));
        document.getElementById('btn_save').disabled = cart.length === 0;
        window._grand = grand;
    }

    document.querySelectorAll('.svc-card').forEach(card => card.addEventListener('click', () => {
        const d = card.dataset;
        const ex = cart.find(x => x.service_id == d.id);
        if (ex) { ex.qty += 1; } else {
            cart.push({ service_id: +d.id, name: d.name, price: +d.price, unit: d.unit, hours: +d.hours, qty: d.unit === 'kg' ? 1 : 1, item_condition: '' });
        }
        renderCart();
    }));

    document.getElementById('customer_select').addEventListener('change', function () {
        document.getElementById('manual_customer').style.display = this.value ? 'none' : '';
        calc();
    });
    document.getElementById('order_type').addEventListener('change', function () {
        document.getElementById('delivery_box').classList.toggle('d-none', this.value !== 'delivery'); calc();
    });
    document.getElementById('delivery_fee').addEventListener('input', calc);
    document.getElementById('cash_received').addEventListener('input', calc);
    document.getElementById('payment_method').addEventListener('change', function () {
        document.getElementById('cash_box').style.display = this.value === 'cash' ? '' : 'none'; calc();
    });

    document.getElementById('btn_save').addEventListener('click', function () {
        const btn = this; btn.disabled = true; btn.textContent = 'Menyimpan...';
        const payload = {
            cart: cart.map(it => ({ service_id: it.service_id, qty: it.qty, item_condition: it.item_condition || null })),
            customer_id: document.getElementById('customer_select').value || null,
            customer_name: document.getElementById('customer_name').value || null,
            customer_phone: document.getElementById('customer_phone').value || null,
            order_type: document.getElementById('order_type').value,
            delivery_fee: parseFloat(document.getElementById('delivery_fee').value) || 0,
            delivery_address: document.getElementById('delivery_address').value || null,
            special_instructions: document.getElementById('special_instructions').value || null,
            payment_method: document.getElementById('payment_method').value,
            cash_received: parseFloat(document.getElementById('cash_received').value) || 0,
        };
        fetch(STORE_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: JSON.stringify(payload) })
            .then(r => r.json())
            .then(d => {
                if (d.status === 'success') {
                    window.open(d.print_url, '_blank');
                    window.location = "{{ route('laundry.kasir.index') }}";
                } else { alert(d.message || 'Gagal menyimpan.'); btn.disabled = false; btn.textContent = 'Simpan Nota'; }
            })
            .catch(() => { alert('Kesalahan jaringan.'); btn.disabled = false; btn.textContent = 'Simpan Nota'; });
    });
</script>
@endpush
@endsection
