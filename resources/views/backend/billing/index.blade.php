@extends('backend.layout.app')
@section('title', 'Langganan')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- STATUS LANGGANAN --}}
            <div class="card card-flush mb-8">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-2">Status Langganan</h3>
                </div>
                <div class="card-body">
                    @php
                        $active = $tenant->hasActiveAccess();
                        $statusMap = [
                            'active'   => ['Aktif', 'success'],
                            'trial'    => ['Trial', 'info'],
                            'expired'  => ['Kedaluwarsa', 'danger'],
                            'inactive' => ['Belum Aktif', 'warning'],
                        ];
                        [$statusLabel, $statusColor] = $statusMap[$tenant->subscription_status] ?? ['-', 'secondary'];
                    @endphp

                    <div class="row g-5">
                        <div class="col-md-3">
                            <div class="fs-7 text-muted">Bisnis</div>
                            <div class="fs-4 fw-bold text-gray-800">{{ $tenant->name }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="fs-7 text-muted">Paket</div>
                            <div class="fs-4 fw-bold text-gray-800">{{ $tenant->plan ? ($plans[$tenant->plan]['name'] ?? ucfirst($tenant->plan)) : '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="fs-7 text-muted">Status</div>
                            <span class="badge badge-light-{{ $statusColor }} fs-6 fw-bold">{{ $statusLabel }}</span>
                        </div>
                        <div class="col-md-3">
                            <div class="fs-7 text-muted">Masa aktif s/d</div>
                            <div class="fs-4 fw-bold text-gray-800">
                                {{ $tenant->subscription_ends_at ? $tenant->subscription_ends_at->translatedFormat('d M Y') : '—' }}
                            </div>
                        </div>
                    </div>

                    @unless ($active)
                        <div class="alert alert-warning d-flex align-items-center mt-6 mb-0">
                            <i class="ki-outline ki-information-5 fs-2x text-warning me-4"></i>
                            <div>
                                <h4 class="mb-1 text-gray-900">Sistem belum aktif</h4>
                                <span class="text-gray-700">Pilih paket di bawah & selesaikan pembayaran untuk membuka semua fitur.</span>
                            </div>
                        </div>
                    @endunless
                </div>
            </div>

            {{-- PILIHAN PAKET (2 kartu, presisi di tengah) --}}
            <div class="row g-6 mb-8 justify-content-center">
                @foreach ($plans as $key => $plan)
                    @php
                        $isContact = $plan['contact'] ?? false;
                        $isCurrent = $tenant->plan === $key && $active;
                        $waLink = $isContact
                            ? 'https://wa.me/' . ($plan['wa'] ?? '') . '?text=' . rawurlencode('Halo, saya ingin berlangganan paket ' . $plan['name'] . ' Mooda untuk bisnis "' . $tenant->name . '". Mohon info fitur & harganya.')
                            : null;
                        $periods = $isContact ? [] : \App\Tenancy\Plan::periods($key);
                        $basePpm = $periods[0]['price_per_month'] ?? ($plan['price'] ?? 0);
                        $minPpm  = collect($periods)->min('price_per_month') ?? ($plan['price'] ?? 0);
                        $firstTotal = isset($periods[0]) ? $periods[0]['price_per_month'] * $periods[0]['months'] : ($plan['price'] ?? 0);
                    @endphp
                    <div class="col-md-6 col-lg-5">
                        <div class="card card-flush h-100 border border-2 {{ $isCurrent ? 'border-success' : ($isContact ? 'border-primary' : 'border-gray-200') }}">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h2 class="fw-bolder text-gray-900">{{ $plan['name'] }}</h2>
                                        <div class="text-muted fs-7">{{ $plan['tagline'] }}</div>
                                    </div>
                                    @if ($isCurrent)
                                        <span class="badge badge-success">Paket Anda</span>
                                    @elseif ($isContact)
                                        <span class="badge badge-light-primary">Fleksibel</span>
                                    @endif
                                </div>

                                <div class="my-5">
                                    @if ($isContact)
                                        <span class="fs-3x fw-bolder text-gray-900">Custom</span>
                                        <span class="fs-6 text-muted">/sesuai fitur</span>
                                    @else
                                        @if (count($periods) > 1)
                                            <span class="fs-7 text-muted d-block">mulai</span>
                                        @endif
                                        <span class="fs-3x fw-bolder text-gray-900">Rp {{ number_format($minPpm, 0, ',', '.') }}</span>
                                        <span class="fs-6 text-muted">/bulan</span>
                                    @endif
                                </div>

                                <ul class="list-unstyled mb-6">
                                    @foreach ($plan['features'] as $feature)
                                        <li class="d-flex align-items-center mb-3">
                                            <i class="ki-outline ki-check-circle fs-2 text-success me-3"></i>
                                            <span class="text-gray-700">{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>

                                @if ($isContact)
                                    @if ($isCurrent)
                                        <div class="mt-auto">
                                            <div class="btn btn-light-success w-100 mb-2 disabled">
                                                <i class="ki-outline ki-check-circle fs-3 me-1"></i> Paket Aktif Anda
                                            </div>
                                            <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn btn-light-primary w-100">
                                                <i class="ki-outline ki-whatsapp fs-3 me-1"></i> Perpanjang / Konsultasi
                                            </a>
                                        </div>
                                    @else
                                        <a href="{{ $waLink }}" target="_blank" rel="noopener" class="btn btn-primary mt-auto">
                                            <i class="ki-outline ki-whatsapp fs-3 me-1"></i> Konsultasi via WhatsApp
                                        </a>
                                    @endif
                                @else
                                    {{-- Pilihan durasi langganan (bisa di-scroll) --}}
                                    <div class="mb-4 mt-auto">
                                        <label class="fw-semibold fs-7 text-muted mb-2 d-block">Pilih durasi langganan</label>
                                        <div class="pe-1" style="max-height: 232px; overflow-y: auto;">
                                            @foreach ($periods as $i => $per)
                                                @php
                                                    $ppm = (int) $per['price_per_month'];
                                                    $pm = (int) $per['months'];
                                                    $ptotal = $ppm * $pm;
                                                    $disc = $basePpm > 0 ? (int) round((1 - $ppm / $basePpm) * 100) : 0;
                                                @endphp
                                                <label class="d-flex align-items-center justify-content-between border border-gray-300 rounded p-3 mb-2 cursor-pointer">
                                                    <span class="d-flex align-items-start">
                                                        <input class="form-check-input mt-1 me-3 plan-period" type="radio"
                                                            name="period-{{ $key }}" value="{{ $pm }}" data-total="{{ $ptotal }}"
                                                            {{ $i === 0 ? 'checked' : '' }}>
                                                        <span>
                                                            <span class="fw-bold text-gray-900">{{ $per['label'] ?? ($pm . ' Bulan') }}</span>
                                                            @if ($disc > 0)
                                                                <span class="badge badge-light-success ms-2">Hemat {{ $disc }}%</span>
                                                            @endif
                                                            <span class="d-block fs-8 text-muted">{{ $pm == 1 ? 'Tanpa komitmen' : 'Bayar ' . $pm . ' bulan di muka' }}</span>
                                                        </span>
                                                    </span>
                                                    <span class="text-end text-nowrap ps-2">
                                                        <span class="fw-bolder text-gray-900">Rp {{ number_format($ppm, 0, ',', '.') }}</span><span class="fs-8 text-muted">/bln</span>
                                                        <span class="d-block fs-8 text-muted">Total Rp {{ number_format($ptotal, 0, ',', '.') }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    @php $prefix = ($isCurrent ? 'Perpanjang ' : 'Berlangganan ') . $plan['name']; @endphp
                                    <button type="button"
                                        class="btn {{ $isCurrent ? 'btn-success' : 'btn-light-primary' }} btn-subscribe"
                                        data-plan="{{ $key }}" data-group="period-{{ $key }}" data-prefix="{{ $prefix }}">
                                        @if ($isCurrent)<i class="ki-outline ki-arrows-circle fs-3 me-1"></i>@endif
                                        <span class="btn-subscribe-label">{{ $prefix }} — Rp {{ number_format($firstTotal, 0, ',', '.') }}</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- RIWAYAT PEMBAYARAN --}}
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800">Riwayat Pembayaran</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-0 gy-3">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th>Tanggal</th>
                                    <th>Paket</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th>Berlaku s/d</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($history as $sub)
                                    @php
                                        $sc = ['paid' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'expired' => 'secondary', 'cancelled' => 'secondary'][$sub->status] ?? 'secondary';
                                    @endphp
                                    <tr>
                                        <td>{{ $sub->created_at->translatedFormat('d M Y H:i') }}</td>
                                        <td>{{ $plans[$sub->plan]['name'] ?? ucfirst($sub->plan) }}</td>
                                        <td>Rp {{ number_format($sub->amount, 0, ',', '.') }}</td>
                                        <td><span class="badge badge-light-{{ $sc }} text-uppercase">{{ $sub->status }}</span></td>
                                        <td>{{ $sub->ends_at ? $sub->ends_at->translatedFormat('d M Y') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-5">Belum ada transaksi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://app.{{ $isProduction ? '' : 'sandbox.' }}midtrans.com/snap/snap.js"
        data-client-key="{{ $clientKey }}"></script>
    <script>
        const rp = n => 'Rp ' + Number(n || 0).toLocaleString('id-ID');
        document.querySelectorAll('.btn-subscribe').forEach(function (btn) {
            const group = btn.dataset.group;
            const prefix = btn.dataset.prefix || 'Berlangganan';
            const labelEl = btn.querySelector('.btn-subscribe-label');
            const selectedRadio = () => group ? document.querySelector('input[name="' + group + '"]:checked') : null;

            function refreshLabel() {
                const r = selectedRadio();
                if (r && labelEl) labelEl.textContent = prefix + ' — ' + rp(r.dataset.total);
            }
            if (group) {
                document.querySelectorAll('input[name="' + group + '"]').forEach(function (r) {
                    r.addEventListener('change', refreshLabel);
                });
                refreshLabel();
            }

            btn.addEventListener('click', function () {
                const plan = btn.dataset.plan;
                const r = selectedRadio();
                const months = r ? parseInt(r.value, 10) : 1;
                const original = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = 'Memproses...';

                fetch("{{ route('billing.checkout') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ plan: plan, months: months }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success' && data.snap_token) {
                        if (typeof snap === 'undefined') {
                            alert('Gagal memuat Midtrans. Periksa koneksi / client key.');
                            return;
                        }
                        snap.pay(data.snap_token, {
                            onSuccess: function () { window.location.reload(); },
                            onPending: function () { window.location.reload(); },
                            onError: function () { alert('Pembayaran gagal. Silakan coba lagi.'); },
                            onClose: function () { /* dibatalkan user */ },
                        });
                    } else {
                        alert(data.message || 'Gagal memproses pembayaran.');
                    }
                })
                .catch(() => alert('Terjadi kesalahan jaringan.'))
                .finally(() => { btn.disabled = false; btn.innerHTML = original; });
            });
        });
    </script>
@endpush
