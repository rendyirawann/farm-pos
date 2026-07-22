{{-- Modal & helper Virtual Account DOKU (dipakai billing/index & billing/deposit saat driver=doku). --}}
@php $dokuChannelsJs = ($dokuChannels ?? collect())->map(fn ($c) => ['channel' => $c->channel, 'name' => $c->name])->values(); @endphp
@php $tripayChannelsJs = collect($tripayChannels ?? [])->map(fn ($c) => ['code' => $c['code'] ?? '', 'name' => $c['name'] ?? '', 'group' => $c['group'] ?? ''])->values(); @endphp

<div class="modal fade" id="dokuVaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Selesaikan Pembayaran</h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div>
            </div>
            <div class="modal-body">
                <div class="text-center mb-5">
                    <div class="fs-6 text-muted" id="doku-bank">Virtual Account</div>
                    <div class="fs-2hx fw-bolder text-gray-900 my-2" id="doku-va">-</div>
                    <button type="button" class="btn btn-sm btn-light-primary" id="doku-copy"><i class="ki-outline ki-copy fs-4"></i> Salin Nomor VA</button>
                </div>
                <div class="separator my-4"></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Nominal</span><span class="fw-bold fs-4" id="doku-amount">-</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Bayar sebelum</span><span class="fw-bold" id="doku-expired">-</span></div>
                <div class="alert alert-primary mt-4 fs-7 mb-0">Transfer <b>tepat</b> sejumlah di atas ke nomor Virtual Account melalui m-banking / ATM / internet banking. Pembayaran akan <b>otomatis terverifikasi</b> — halaman ini boleh ditutup.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="window.location.reload()">Cek Status</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.DOKU_CHANNELS = @json($dokuChannelsJs);

    // Pilih bank: 0 -> error, 1 -> langsung, >1 -> dialog pilih.
    window.dokuPickBank = function () {
        var chans = window.DOKU_CHANNELS || [];
        if (chans.length === 0) return Promise.reject('Belum ada metode pembayaran aktif. Hubungi admin.');
        if (chans.length === 1) return Promise.resolve(chans[0].channel);
        var opts = {};
        chans.forEach(function (c) { opts[c.channel] = c.name; });
        return Swal.fire({
            title: 'Pilih Bank', input: 'select', inputOptions: opts,
            inputPlaceholder: 'Pilih bank tujuan', showCancelButton: true,
            confirmButtonText: 'Lanjut', cancelButtonText: 'Batal',
            inputValidator: function (v) { return !v && 'Silakan pilih bank.'; },
        }).then(function (r) { return r.isConfirmed ? r.value : Promise.reject('__cancel__'); });
    };

    window.showDokuVa = function (data) {
        var va = (data.va_number || '').toString();
        document.getElementById('doku-bank').textContent = data.bank_name || 'Virtual Account';
        document.getElementById('doku-va').textContent = va || '-';
        document.getElementById('doku-amount').textContent = 'Rp ' + Number(data.amount || 0).toLocaleString('id-ID');
        var exp = data.expired_date ? new Date(data.expired_date) : null;
        document.getElementById('doku-expired').textContent = exp && !isNaN(exp) ? exp.toLocaleString('id-ID') : '-';
        var copyBtn = document.getElementById('doku-copy');
        copyBtn.onclick = function () {
            navigator.clipboard.writeText(va).then(function () {
                copyBtn.innerHTML = '<i class="ki-outline ki-check fs-4"></i> Tersalin';
                setTimeout(function () { copyBtn.innerHTML = '<i class="ki-outline ki-copy fs-4"></i> Salin Nomor VA'; }, 2000);
            });
        };
        new bootstrap.Modal(document.getElementById('dokuVaModal')).show();
    };

    // ===== Tripay: customer pilih channel (0 -> error, 1 -> langsung, >1 -> dialog pilih) =====
    window.TRIPAY_CHANNELS = @json($tripayChannelsJs);
    window.tripayPickChannel = function () {
        var chans = window.TRIPAY_CHANNELS || [];
        if (chans.length === 0) return Promise.reject('Belum ada metode pembayaran Tripay yang aktif. Hubungi admin.');
        if (chans.length === 1) return Promise.resolve(chans[0].code);
        var opts = {};
        chans.forEach(function (c) { opts[c.code] = c.name + (c.group ? ' (' + c.group + ')' : ''); });
        return Swal.fire({
            title: 'Pilih Metode Pembayaran', input: 'select', inputOptions: opts,
            inputPlaceholder: 'Pilih metode pembayaran', showCancelButton: true,
            confirmButtonText: 'Lanjut', cancelButtonText: 'Batal',
            inputValidator: function (v) { return !v && 'Silakan pilih metode pembayaran.'; },
        }).then(function (r) { return r.isConfirmed ? r.value : Promise.reject('__cancel__'); });
    };
</script>

{{-- ===== Modal pembayaran Tripay IN-APP (VA / QRIS tampil di Mooda, tanpa redirect) ===== --}}
<div class="modal fade" id="tripayPayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Selesaikan Pembayaran</h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4"><span class="badge badge-light-primary fs-7 fw-bold" id="tp-method">Pembayaran</span></div>
                <div id="tp-va-wrap">
                    <div class="text-muted fs-7 mb-1">Nomor Virtual Account</div>
                    <div class="d-flex align-items-center justify-content-between rounded p-4 mb-2" style="background:#0f172a;">
                        <span class="fw-bolder fs-2 text-white" id="tp-va" style="font-family:monospace;letter-spacing:1px;">-</span>
                        <button type="button" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;" id="tp-copy"><i class="ki-outline ki-copy fs-5 text-white"></i> Salin</button>
                    </div>
                </div>
                <div id="tp-qr-wrap" class="text-center">
                    <div class="text-muted fs-7 mb-2">Scan QRIS dengan aplikasi e-wallet / m-banking</div>
                    <img id="tp-qr" src="" alt="QRIS" style="width:230px;height:230px;border:1px solid #e5e7eb;border-radius:12px;object-fit:contain;background:#fff;padding:6px;">
                </div>
                <div class="separator my-4"></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Nominal</span><span class="fw-bold fs-3 text-gray-900" id="tp-amount">-</span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">Bayar sebelum</span><span class="fw-bold" id="tp-expired">-</span></div>
                <div class="alert alert-primary mt-4 fs-7 mb-0">Bayar <b>tepat</b> sejumlah di atas. Pembayaran <b>otomatis terverifikasi</b> — halaman boleh ditutup, saldo bertambah otomatis.</div>
            </div>
            <div class="modal-footer">
                <a href="#" target="_blank" rel="noopener" class="btn btn-light" id="tp-checkout">Buka Halaman Tripay</a>
                <button type="button" class="btn btn-primary" onclick="window.location.reload()">Cek Status</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.showTripayPayment = function (data) {
        document.getElementById('tp-method').textContent = data.payment_name || 'Pembayaran';
        var isQr = !!data.qr_url;
        document.getElementById('tp-va-wrap').style.display = isQr ? 'none' : 'block';
        document.getElementById('tp-qr-wrap').style.display = isQr ? 'block' : 'none';
        if (isQr) {
            document.getElementById('tp-qr').src = data.qr_url;
        } else {
            var va = (data.pay_code || '').toString();
            document.getElementById('tp-va').textContent = va || '-';
            var cp = document.getElementById('tp-copy');
            cp.onclick = function () {
                if (navigator.clipboard) navigator.clipboard.writeText(va);
                cp.innerHTML = '<i class="ki-outline ki-check fs-5 text-white"></i> Tersalin';
                setTimeout(function () { cp.innerHTML = '<i class="ki-outline ki-copy fs-5 text-white"></i> Salin'; }, 1800);
            };
        }
        document.getElementById('tp-amount').textContent = 'Rp ' + Number(data.amount || 0).toLocaleString('id-ID');
        var exp = data.expired_time ? new Date(data.expired_time * 1000) : null;
        document.getElementById('tp-expired').textContent = (exp && !isNaN(exp)) ? exp.toLocaleString('id-ID') : '-';
        var co = document.getElementById('tp-checkout');
        if (data.checkout_url) { co.href = data.checkout_url; co.style.display = ''; } else { co.style.display = 'none'; }
        new bootstrap.Modal(document.getElementById('tripayPayModal')).show();
    };
</script>
