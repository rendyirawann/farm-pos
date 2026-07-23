@extends('backend.layout.app')
@section('title', 'Checkout Pembayaran')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        <div class="mb-4">
            <a href="{{ $summary['back'] }}" class="btn btn-sm btn-light"><i class="ki-outline ki-arrow-left fs-5"></i> Kembali</a>
        </div>
        <h1 class="fw-bold fs-2 mb-1">Checkout Pembayaran</h1>
        <div class="text-muted mb-6">Pilih metode pembayaran lalu selesaikan. Nomor VA / QRIS akan tampil langsung di halaman ini.</div>

        <div class="row g-6">
            {{-- ==== KIRI: METODE ==== --}}
            <div class="col-lg-7">
                <div class="card card-flush">
                    <div class="card-header pt-5"><h3 class="card-title fw-bold">Metode Pembayaran</h3></div>
                    <div class="card-body">
                        @if ($channels->isEmpty())
                            <div class="alert alert-warning mb-0">Belum ada metode pembayaran aktif. Hubungi admin / Superadmin (Payment → Channel Pembayaran Tripay).</div>
                        @else
                            @foreach ($channels->groupBy('group') as $grp => $items)
                                <div class="text-muted fw-bold fs-8 text-uppercase mt-3 mb-2" style="letter-spacing:.06em;">{{ $grp ?: 'Lainnya' }}</div>
                                @foreach ($items as $c)
                                    <label class="method-card d-flex align-items-center gap-3 border border-2 rounded p-4 mb-3" style="cursor:pointer;" data-code="{{ $c->code }}">
                                        <span class="badge badge-light-primary fw-bold d-inline-flex align-items-center justify-content-center" style="min-width:56px;height:34px;">{{ \Illuminate\Support\Str::of($c->name)->before(' ') }}</span>
                                        <span class="flex-grow-1"><span class="fw-bold text-gray-900">{{ $c->name }}</span></span>
                                        <input type="radio" name="method" value="{{ $c->code }}" class="form-check-input flex-shrink-0">
                                    </label>
                                @endforeach
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            {{-- ==== KANAN: RINGKASAN ==== --}}
            <div class="col-lg-5">
                <div class="card card-flush">
                    <div class="card-header pt-5"><h3 class="card-title fw-bold">Ringkasan Pesanan</h3></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-gray-200">
                            <span>{{ $summary['item'] }} <span class="badge badge-light-primary ms-1">{{ $summary['item_tag'] }}</span></span>
                            <span class="fw-bold">Rp {{ number_format($summary['amount'], 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom border-gray-200 text-muted fs-7"><span>Rincian</span><span class="text-end">{{ $summary['note'] }}</span></div>
                        <div class="d-flex justify-content-between py-2 text-muted fs-7"><span>Untuk</span><span>{{ $summary['purpose'] }}</span></div>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-gray-300">
                            <span class="fw-bold fs-4">Total Bayar</span>
                            <span class="fw-bolder fs-2 text-gray-900">Rp {{ number_format($summary['amount'], 0, ',', '.') }}</span>
                        </div>
                        <button id="btn-pay" class="btn btn-primary w-100 mt-4" disabled>Pilih metode dulu</button>
                        <div class="text-muted fs-8 text-center mt-3">Diproses aman via <b>Tripay</b> (VA / QRIS).</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('backend.billing._va_modal')

<script>
    (function () {
        var ENDPOINT = @json($summary['endpoint']);
        var PAYLOAD  = @json($summary['payload']);
        var AMOUNT_LABEL = 'Bayar Sekarang — Rp {{ number_format($summary['amount'], 0, ',', '.') }}';
        var selected = null;
        var btn = document.getElementById('btn-pay');

        document.querySelectorAll('.method-card').forEach(function (card) {
            card.addEventListener('click', function () {
                document.querySelectorAll('.method-card').forEach(function (c) { c.classList.remove('border-primary', 'bg-light-primary'); });
                card.classList.add('border-primary', 'bg-light-primary');
                var radio = card.querySelector('input[type=radio]'); if (radio) radio.checked = true;
                selected = card.getAttribute('data-code');
                btn.disabled = false;
                btn.textContent = AMOUNT_LABEL;
            });
        });

        btn.addEventListener('click', function () {
            if (!selected) return;
            btn.disabled = true; btn.textContent = 'Memproses…';
            var body = Object.assign({}, PAYLOAD, { method: selected });
            fetch(ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify(body),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'success' && data.driver === 'tripay') {
                    window.showTripayPayment(data);
                } else if (data.status === 'success' && data.driver === 'tripay' && data.checkout_url) {
                    window.location.href = data.checkout_url;
                } else {
                    (window.Swal ? Swal.fire('Gagal', data.message || 'Gagal memproses pembayaran.', 'error') : alert(data.message || 'Gagal'));
                }
                btn.disabled = false; btn.textContent = AMOUNT_LABEL;
            })
            .catch(function () {
                (window.Swal ? Swal.fire('Gagal', 'Terjadi kesalahan jaringan.', 'error') : alert('Kesalahan jaringan.'));
                btn.disabled = false; btn.textContent = AMOUNT_LABEL;
            });
        });
    })();
</script>
@endsection
