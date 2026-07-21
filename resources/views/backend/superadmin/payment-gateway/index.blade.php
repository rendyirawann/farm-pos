@extends('backend.layout.app')
@section('title', 'Payment Gateway')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <div class="alert alert-primary d-flex flex-column">
            <div class="fw-bold fs-5 mb-1">Payment Gateway Aktif</div>
            <div class="fs-7">
                Pilih <b>SATU</b> gateway yang aktif untuk checkout <b>langganan</b> &amp; <b>top-up deposit</b> tenant.
                Hanya satu aktif; sisanya nonaktif otomatis. Kredensial tiap gateway diisi di <code>.env</code> server.
                Aktif sekarang: <b>{{ strtoupper($active) }}</b>.
            </div>
        </div>

        <form method="POST" action="{{ route('payment-gateway.update') }}">
            @csrf
            <div class="card card-flush mb-6">
                <div class="card-header pt-5"><h3 class="card-title fw-bold">Pilih Gateway</h3></div>
                <div class="card-body">
                    <div class="row g-4">
                        @foreach ($drivers as $key => $info)
                            <div class="col-md-4">
                                <label class="btn btn-outline btn-outline-dashed d-flex align-items-start p-4 h-100 w-100 {{ $active === $key ? 'active border-primary' : '' }}">
                                    <input type="radio" name="active_driver" value="{{ $key }}" class="form-check-input me-3 mt-1"
                                        {{ $active === $key ? 'checked' : '' }} {{ $info['configured'] ? '' : 'disabled' }}>
                                    <span class="d-flex flex-column text-start">
                                        <span class="fw-bold fs-5 text-gray-900">{{ $info['label'] }}</span>
                                        <span class="fs-8 mt-1 d-flex flex-wrap gap-1">
                                            @if ($active === $key)
                                                <span class="badge badge-light-success">Aktif</span>
                                            @endif
                                            @if ($info['configured'])
                                                <span class="badge badge-light-primary">Terkonfigurasi</span>
                                            @else
                                                <span class="badge badge-light-danger">Kredensial kosong</span>
                                            @endif
                                        </span>
                                        @if ($key === 'tripay')
                                            <span class="fs-8 text-muted mt-2">
                                                Mode: <b>{{ $tripayInfo['production'] ? 'PRODUCTION' : 'SANDBOX' }}</b>
                                                @if ($tripayInfo['configured'])
                                                    · {{ $tripayInfo['channels'] }} channel aktif
                                                @endif
                                            </span>
                                        @endif
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="separator my-6"></div>
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <button type="submit" class="btn btn-primary">Simpan Gateway Aktif</button>
                        <span class="text-muted fs-8">Gateway berkredensial kosong tidak bisa dipilih. Fallback <code>.env</code>: <b>{{ strtoupper($envFallback) }}</b>.</span>
                    </div>
                </div>
            </div>
        </form>

        <div class="alert alert-warning fs-8 mb-0">
            <b>Catatan aman:</b> selama Tripay masih <b>SANDBOX</b>, transaksi TIDAK nyata. Pindah ke Tripay hanya setelah akun production &amp; <code>TRIPAY_IS_PRODUCTION=true</code>.
            Ganti gateway langsung berlaku (tanpa restart) — cache driver otomatis di-refresh.
        </div>

    </div>
</div>
@endsection
