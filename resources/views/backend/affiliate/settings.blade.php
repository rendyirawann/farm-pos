@extends('backend.layout.app')
@section('title', 'Setelan Program Affiliate')
@section('content')
    <div class="app-container container-xxl">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold text-gray-900 mb-1">Setelan Program Affiliate</h1>
                <span class="text-muted fs-7">Atur komisi affiliate & cashback untuk user yang daftar via referral.</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('affiliates.index') }}" class="btn btn-sm btn-light">← Affiliate</a>
                <a href="{{ route('affiliates.withdrawals') }}" class="btn btn-sm btn-light-primary">Pencairan</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('affiliates.settings.save') }}">
                    @csrf
                    <div class="row g-6">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Tipe Komisi Affiliate</label>
                            <select name="commission_type" class="form-select" id="ctype">
                                <option value="flat" @selected($setting->commission_type === 'flat')>Flat (Rupiah)</option>
                                <option value="percent" @selected($setting->commission_type === 'percent')>Persen (% dari nilai langganan)</option>
                            </select>
                            <div class="form-text">Komisi yang didapat affiliate saat referralnya berlangganan plan Basic/Enterprise.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Nilai Komisi</label>
                            <div class="input-group">
                                <span class="input-group-text" id="cprefix">{{ $setting->commission_type === 'percent' ? '%' : 'Rp' }}</span>
                                <input type="number" step="0.01" min="0" name="commission_value" class="form-control" value="{{ (float) $setting->commission_value }}">
                            </div>
                            <div class="form-text">Contoh: 50000 (flat) atau 10 (persen). <b>Rp0 = tanpa komisi.</b></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold required">Cashback User (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" name="cashback_percent" class="form-control" value="{{ (float) $setting->cashback_percent }}">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Potongan harga untuk user yang daftar via referral saat bayar plan Basic/Enterprise di Tripay. <b>0 = tanpa cashback.</b></div>
                        </div>
                    </div>
                    <div class="mt-8">
                        <button type="submit" class="btn btn-primary">Simpan Setelan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Ubah prefix Rp/% mengikuti tipe komisi.
        document.getElementById('ctype')?.addEventListener('change', function () {
            document.getElementById('cprefix').textContent = this.value === 'percent' ? '%' : 'Rp';
        });
    </script>
@endsection
