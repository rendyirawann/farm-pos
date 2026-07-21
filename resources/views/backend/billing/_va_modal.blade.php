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
