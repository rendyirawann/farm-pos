@extends('backend.layout.app')
@section('title', 'Program Affiliate')
@section('content')

    <div class="app-container container-fluid">
        <div class="d-flex flex-wrap flex-stack mb-6">
            <h1 class="fw-bold my-1 fs-2">Program Affiliate
                <span class="fs-6 text-gray-500 fw-semibold ms-1">Ajak bisnis lain pakai Mooda, dapat komisi</span>
            </h1>
        </div>

        @if (session('success'))
            <div class="alert alert-success d-flex align-items-center"><i class="ki-outline ki-check-circle fs-2 text-success me-3"></i>{{ session('success') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning d-flex align-items-center"><i class="ki-outline ki-information fs-2 text-warning me-3"></i>{{ session('warning') }}</div>
        @endif

        @if (! $affiliate)
            {{-- ===== BELUM GABUNG ===== --}}
            <div class="card">
                <div class="card-body p-8 p-lg-12">
                    <div class="row align-items-center g-8">
                        <div class="col-lg-7">
                            <span class="badge badge-light-primary fw-bold mb-3">Gratis</span>
                            <h2 class="fs-1 fw-bolder text-gray-900 mb-3">Dapat komisi dengan merekomendasikan Mooda</h2>
                            <p class="fs-5 text-gray-600 mb-5">Bagikan kode referral Anda ke pemilik usaha lain. Setiap bisnis yang berlangganan Mooda lewat kode Anda, Anda dapat <b>{{ $komisi }}</b>.</p>
                            <ul class="mb-8 fs-6 text-gray-700">
                                <li class="mb-2">Kode & link referral otomatis</li>
                                <li class="mb-2">Pantau siapa saja yang memakai kode Anda</li>
                                <li class="mb-2">Komisi tercatat & dibayarkan oleh tim Mooda</li>
                            </ul>
                            <form method="POST" action="{{ route('affiliate.my.join') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-lg fw-bold"><i class="ki-outline ki-rocket fs-2"></i> Gabung Program Affiliate</button>
                            </form>
                        </div>
                        <div class="col-lg-5 text-center">
                            <div class="d-inline-grid" style="place-items:center;width:180px;height:180px;border-radius:2rem;background:linear-gradient(135deg,#4f46e5,#2563eb);color:#fff;font-size:5rem;">🤝</div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- ===== SUDAH GABUNG: DASHBOARD ===== --}}
            <div class="row g-5 mb-5">
                <div class="col-lg-5">
                    <div class="card h-100" style="background:linear-gradient(135deg,#4f46e5,#2563eb);">
                        <div class="card-body text-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="fs-7 opacity-75">Kode referral Anda</span>
                                @if ($affiliate->status === 'active')
                                    <span class="badge badge-light-success">Aktif</span>
                                @elseif ($affiliate->status === 'pending')
                                    <span class="badge badge-light-warning">Menunggu</span>
                                @else
                                    <span class="badge badge-light-danger">Ditangguhkan</span>
                                @endif
                            </div>
                            <div class="fs-2x fw-bolder mb-4">{{ $affiliate->code }}</div>
                            <span class="fs-8 opacity-75 d-block mb-1">Link referral (bagikan ini):</span>
                            <div class="d-flex gap-2">
                                <input id="ref-link" readonly value="{{ $affiliate->referralUrl() }}" class="form-control form-control-sm bg-white bg-opacity-25 text-white border-0">
                                <button id="copy-link" class="btn btn-sm btn-light fw-bold text-nowrap">Salin</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="row g-5 h-100">
                        @php($cards = [
                            ['Total referral', $stats['total'] . ' tenant', 'primary', 'ki-share'],
                            ['Berlangganan', $stats['subscribed'] . ' tenant', 'info', 'ki-check-circle'],
                            ['Komisi cair', 'Rp ' . number_format($stats['earned'], 0, ',', '.'), 'success', 'ki-wallet'],
                            ['Komisi pending', 'Rp ' . number_format($stats['pending'], 0, ',', '.'), 'warning', 'ki-time'],
                        ])
                        @foreach ($cards as [$label, $val, $c, $icon])
                            <div class="col-6">
                                <div class="card card-flush border border-{{ $c }} border-dashed h-100">
                                    <div class="card-body d-flex align-items-center gap-3 py-4">
                                        <i class="ki-outline {{ $icon }} fs-2x text-{{ $c }}"></i>
                                        <div><div class="fs-4 fw-bold text-gray-900">{{ $val }}</div><div class="fs-8 text-muted">{{ $label }}</div></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title fw-bold">Tenant yang memakai kode Anda</h3></div>
                <div class="card-body pt-2">
                    @if ($referrals->count())
                        <table class="table table-row-dashed align-middle fs-6">
                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Tenant</th><th>Tanggal</th><th>Status</th><th class="text-end">Komisi</th></tr></thead>
                            <tbody>
                                @foreach ($referrals as $r)
                                    <tr>
                                        <td class="fw-bold text-gray-800">{{ optional($r->tenant)->name ?? $r->tenant_name ?? '(tenant)' }}</td>
                                        <td>{{ optional($r->created_at)->locale('id')->translatedFormat('d M Y') }}</td>
                                        <td>@if ($r->status === 'subscribed')<span class="badge badge-light-info">Berlangganan</span>@else<span class="badge badge-light">Daftar</span>@endif</td>
                                        <td class="text-end fw-bold {{ $r->commission_status === 'paid' ? 'text-success' : 'text-muted' }}">
                                            Rp {{ number_format($r->commission_amount, 0, ',', '.') }}
                                            <span class="badge badge-light-{{ $r->commission_status === 'paid' ? 'success' : 'warning' }} ms-1">{{ $r->commission_status === 'paid' ? 'lunas' : 'pending' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center text-muted py-10">
                            <i class="ki-outline ki-share fs-3x mb-3"></i>
                            <div class="fw-bold">Belum ada yang memakai kode Anda.</div>
                            <div class="fs-7">Bagikan link referral di atas untuk mulai dapat komisi.</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

@endsection

@push('scripts')
    <script>
        document.getElementById('copy-link')?.addEventListener('click', function () {
            var i = document.getElementById('ref-link'); i.select(); i.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(i.value).then(() => { this.textContent = 'Tersalin ✓'; setTimeout(() => this.textContent = 'Salin', 1800); });
        });
    </script>
@endpush
