@extends('backend.layout.app')
@section('title', 'Akun Demo')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- KREDENSIAL HASIL GENERATE --}}
        @if (session('demo_created'))
            @php($d = session('demo_created'))
            <div class="card card-flush mb-6 border border-2 border-success">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-success">
                        <i class="ki-outline ki-check-circle fs-2 text-success me-2"></i>
                        Akun Demo Dibuat — {{ $d['tenant'] }}
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning fs-7">Salin kredensial ini sekarang — password <b>tidak ditampilkan lagi</b> setelah halaman di-refresh.</div>
                    <div class="row g-4">
                        @foreach ([['Owner', $d['owner_email'], $d['owner_password']], ['Kasir', $d['kasir_email'], $d['kasir_password']]] as [$role, $email, $pass])
                            <div class="col-md-6">
                                <div class="border border-gray-300 rounded p-4 h-100">
                                    <div class="fw-bold fs-5 text-gray-900 mb-3">{{ $role }}</div>
                                    <div class="mb-2">
                                        <div class="fs-8 text-muted">Email</div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-gray-800 js-copyval">{{ $email }}</span>
                                            <button type="button" class="btn btn-sm btn-icon btn-light-primary js-copy" data-copy="{{ $email }}"><i class="ki-outline ki-copy fs-4"></i></button>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fs-8 text-muted">Password</div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-gray-800 js-copyval">{{ $pass }}</span>
                                            <button type="button" class="btn btn-sm btn-icon btn-light-primary js-copy" data-copy="{{ $pass }}"><i class="ki-outline ki-copy fs-4"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex flex-wrap gap-4 mt-4 fs-7 text-muted">
                        <span>Saldo deposit: <b class="text-gray-900">Rp {{ number_format($d['deposit'], 0, ',', '.') }}</b></span>
                        <span>Login: <a href="{{ $d['login_url'] }}" target="_blank">{{ $d['login_url'] }}</a></span>
                    </div>
                    <button type="button" class="btn btn-sm btn-light-primary mt-4 js-copy"
                        data-copy="Login: {{ $d['login_url'] }}&#10;Owner: {{ $d['owner_email'] }} / {{ $d['owner_password'] }}&#10;Kasir: {{ $d['kasir_email'] }} / {{ $d['kasir_password'] }}">
                        <i class="ki-outline ki-copy fs-4 me-1"></i> Salin Semua
                    </button>
                </div>
            </div>
        @endif

        <div class="row g-6">
            {{-- GENERATE AKUN DEMO --}}
            <div class="col-lg-6">
                <div class="card card-flush h-100">
                    <div class="card-header pt-5"><h3 class="card-title fw-bold">Buat Akun Demo</h3></div>
                    <div class="card-body d-flex flex-column">
                        <p class="text-gray-700">
                            Satu klik membuat <b>tenant demo</b> baru + user <b>Owner</b> &amp; <b>Kasir</b> (email &amp; password acak)
                            dengan saldo deposit <b>Rp {{ number_format($demoDeposit, 0, ',', '.') }}</b>. Kredensial langsung tampil untuk diberikan.
                        </p>
                        <form method="POST" action="{{ route('demo-accounts.generate') }}" class="mt-auto">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ki-outline ki-plus-square fs-2 me-1"></i> Generate Akun Demo
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- DEPOSIT Rp5.000 KE AKUN --}}
            <div class="col-lg-6">
                <div class="card card-flush h-100">
                    <div class="card-header pt-5"><h3 class="card-title fw-bold">Deposit Rp {{ number_format($demoDeposit, 0, ',', '.') }} ke Akun</h3></div>
                    <div class="card-body d-flex flex-column">
                        <p class="text-gray-700">Kreditkan <b>Rp {{ number_format($demoDeposit, 0, ',', '.') }}</b> ke saldo deposit tenant yang dipilih. Tenant yang belum mode deposit akan otomatis dijadikan mode deposit (Starter).</p>
                        <form method="POST" action="{{ route('demo-accounts.deposit') }}" class="mt-auto">
                            @csrf
                            <label class="form-label required">Pilih Akun (Tenant)</label>
                            <select name="tenant_id" class="form-select form-select-solid mb-3" data-control="select2" data-placeholder="Cari tenant..." required>
                                <option></option>
                                @foreach ($tenants as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }} — saldo Rp {{ number_format($t->deposit_points, 0, ',', '.') }}{{ $t->isDepositMode() ? '' : ' (belum deposit)' }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="ki-outline ki-wallet fs-2 me-1"></i> Deposit Rp {{ number_format($demoDeposit, 0, ',', '.') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- DAFTAR AKUN DEMO --}}
        <div class="card card-flush mt-6">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Akun Demo Terakhir</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle gs-0 gy-3">
                        <thead><tr class="fw-bold text-muted">
                            <th>Tenant</th><th>Email Owner</th><th>Saldo</th><th>Dibuat</th>
                        </tr></thead>
                        <tbody>
                            @forelse ($demoTenants as $t)
                                <tr>
                                    <td class="fw-bold text-gray-800">{{ $t->name }}</td>
                                    <td>{{ $t->owner->email ?? '—' }}</td>
                                    <td>Rp {{ number_format($t->deposit_points, 0, ',', '.') }}</td>
                                    <td>{{ $t->created_at->translatedFormat('d M Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-5">Belum ada akun demo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.js-copy').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = (btn.dataset.copy || '').replace(/&#10;/g, '\n');
            navigator.clipboard.writeText(text).then(function () {
                var original = btn.innerHTML;
                btn.innerHTML = '<i class="ki-outline ki-check fs-4"></i>';
                setTimeout(function () { btn.innerHTML = original; }, 1500);
            });
        });
    });
</script>
@endpush
@endsection
