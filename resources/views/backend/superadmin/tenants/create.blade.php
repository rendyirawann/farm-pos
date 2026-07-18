@extends('backend.layout.app')
@section('title', 'Buat Tenant Baru')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="fw-bold text-gray-800">Buat Tenant Baru <span class="badge badge-light-primary align-middle">Manual Superadmin</span></h2>
                <a href="{{ route('tenants.index') }}" class="btn btn-light-primary btn-sm">← Kembali</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ route('tenants.store') }}">
                @csrf
                <div class="row g-6">
                    {{-- PROFIL --}}
                    <div class="col-lg-6">
                        <div class="card card-flush h-100">
                            <div class="card-header pt-5"><h3 class="card-title fw-bold">Profil Tenant</h3></div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="required form-label">Nama Bisnis</label>
                                    <input name="name" value="{{ old('name') }}" class="form-control form-control-solid" placeholder="mis. Bakso Melati" required>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Jenis Usaha</label>
                                        <input name="business_type" value="{{ old('business_type') }}" class="form-control form-control-solid" placeholder="Resto / Cafe / Warung">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Kategori</label>
                                        <select name="category" class="form-select form-select-solid">
                                            <option value="">— Tidak ditentukan —</option>
                                            <option value="resto" @selected(old('category') === 'resto')>Resto</option>
                                            <option value="cafe" @selected(old('category') === 'cafe')>Cafe</option>
                                            <option value="umkm" @selected(old('category') === 'umkm')>UMKM</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Telepon</label>
                                        <input name="phone" value="{{ old('phone') }}" class="form-control form-control-solid">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Bisnis</label>
                                        <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-solid">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Alamat</label>
                                    <textarea name="address" rows="2" class="form-control form-control-solid">{{ old('address') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- LANGGANAN --}}
                    <div class="col-lg-6">
                        <div class="card card-flush h-100">
                            <div class="card-header pt-5"><h3 class="card-title fw-bold">Langganan</h3></div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label">Mode Akun</label>
                                    <select name="account_mode" class="form-select form-select-solid">
                                        <option value="monthly" @selected(old('account_mode', 'monthly') === 'monthly')>Langganan Bulanan (paket)</option>
                                        <option value="deposit" @selected(old('account_mode') === 'deposit')>Deposit / Starter (pay-as-you-go)</option>
                                    </select>
                                    <div class="form-text text-muted">Pilih <b>Deposit</b> untuk akun <b>Starter</b> berbasis saldo (Paket & durasi di bawah diabaikan; akun aktif setelah di-top-up via menu Setelan Deposit).</div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Paket</label>
                                    <select name="plan" class="form-select form-select-solid">
                                        <option value="">— Belum berlangganan —</option>
                                        @foreach ($plans as $key => $plan)
                                            <option value="{{ $key }}" @selected(old('plan') === $key)>{{ $plan['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col-6">
                                        <label class="form-label">Durasi (bulan)</label>
                                        <input type="number" name="billing_months" value="{{ old('billing_months') }}" min="0" max="120" class="form-control form-control-solid" placeholder="mis. 12">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Hari ekstra</label>
                                        <input type="number" name="extra_days" value="{{ old('extra_days') }}" min="0" max="365" class="form-control form-control-solid" placeholder="mis. 15">
                                    </div>
                                </div>
                                <div class="form-text mb-4">Expiry otomatis dihitung dari <b>hari ini</b> + durasi (mis. 12 bulan + 15 hari). Atau isi tanggal manual di bawah.</div>
                                <div class="mb-2">
                                    <label class="form-label">Aktif s/d <span class="text-muted fs-8">(opsional override)</span></label>
                                    <input type="date" name="subscription_ends_at" value="{{ old('subscription_ends_at') }}" class="form-control form-control-solid">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- AKUN OWNER --}}
                    <div class="col-lg-6">
                        <div class="card card-flush h-100">
                            <div class="card-header pt-5"><h3 class="card-title fw-bold">Akun Owner <span class="text-danger">*</span></h3></div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="required form-label">Nama Owner</label>
                                    <input name="owner_name" value="{{ old('owner_name') }}" class="form-control form-control-solid" required>
                                </div>
                                <div class="mb-4">
                                    <label class="required form-label">Email Owner (login)</label>
                                    <input type="email" name="owner_email" value="{{ old('owner_email') }}" class="form-control form-control-solid" required>
                                </div>
                                <div class="mb-2">
                                    <label class="required form-label">Password Owner</label>
                                    <input name="owner_password" class="form-control form-control-solid" placeholder="min 6 karakter" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- AKUN KASIR (opsional) --}}
                    <div class="col-lg-6">
                        <div class="card card-flush h-100">
                            <div class="card-header pt-5"><h3 class="card-title fw-bold">Akun Kasir <span class="text-muted fs-7">(opsional)</span></h3></div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label">Nama Kasir</label>
                                    <input name="kasir_name" value="{{ old('kasir_name') }}" class="form-control form-control-solid">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Email Kasir (login)</label>
                                    <input type="email" name="kasir_email" value="{{ old('kasir_email') }}" class="form-control form-control-solid">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Password Kasir</label>
                                    <input name="kasir_password" class="form-control form-control-solid" placeholder="min 6 (default kasir123)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-6">
                    <button type="submit" class="btn btn-primary fw-bold"><i class="ki-outline ki-plus fs-3"></i> Buat Tenant</button>
                </div>
            </form>

        </div>
    </div>

@endsection
