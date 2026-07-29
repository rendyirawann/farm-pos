@extends('backend.layout.app')
@section('title', 'Setelan Paket')
@section('content')
    <div class="app-container container-xxl">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold text-gray-900 mb-1">Setelan Paket Langganan</h1>
                <span class="text-muted fs-7">Atur harga dasar, diskon %, label promo, & toggle per durasi. Perubahan langsung dipakai di landing & halaman billing.</span>
            </div>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('plan-settings.save') }}">
            @csrf
            <div class="row g-6">
                @foreach ($data as $key => $plan)
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header align-items-center">
                                <h2 class="card-title fw-bold text-gray-900">{{ $plan['name'] }}</h2>
                                <span class="badge badge-light-primary">{{ $key }}</span>
                            </div>
                            <div class="card-body">
                                <div class="mb-6">
                                    <label class="form-label fw-semibold required">Harga Dasar (per bulan)</label>
                                    <div class="input-group w-250px">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" min="0" step="1000" name="plans[{{ $key }}][base_price]"
                                            value="{{ (int) $plan['setting']->base_price }}" class="form-control">
                                    </div>
                                    <div class="form-text">Harga penuh (durasi 1 bulan). Harga durasi lain = harga dasar × (1 − diskon%).</div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-row-bordered align-middle gy-3">
                                        <thead>
                                            <tr class="fw-bold text-muted fs-8 text-uppercase">
                                                <th>Durasi</th><th style="width:120px">Diskon %</th><th>Label Promo</th><th class="text-center" style="width:90px">Promo Aktif</th><th class="text-end">Harga/bln</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($plan['promos'] as $pr)
                                                <tr>
                                                    <td class="fw-bold text-gray-800">{{ $pr->months }} bln</td>
                                                    <td>
                                                        <input type="number" min="0" max="100" step="0.01"
                                                            name="plans[{{ $key }}][promos][{{ $pr->months }}][discount_percent]"
                                                            value="{{ rtrim(rtrim(number_format((float) $pr->discount_percent, 2, '.', ''), '0'), '.') }}"
                                                            class="form-control form-control-sm" {{ $pr->months == 1 ? 'readonly' : '' }}>
                                                    </td>
                                                    <td>
                                                        <input type="text" maxlength="60"
                                                            name="plans[{{ $key }}][promos][{{ $pr->months }}][promo_label]"
                                                            value="{{ $pr->promo_label }}" placeholder="mis. Promo 6 Bulan"
                                                            class="form-control form-control-sm">
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check form-switch d-inline-block">
                                                            <input type="hidden" name="plans[{{ $key }}][promos][{{ $pr->months }}][is_active]" value="0">
                                                            <input class="form-check-input" type="checkbox" role="switch"
                                                                name="plans[{{ $key }}][promos][{{ $pr->months }}][is_active]" value="1"
                                                                {{ $pr->is_active ? 'checked' : '' }} {{ $pr->months == 1 ? 'disabled' : '' }}>
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold text-gray-800">Rp {{ number_format((int) $pr->price_per_month, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="form-text">Durasi 1 bulan = harga penuh (tanpa diskon). Toggle OFF = harga penuh & tanpa badge promo.</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                <button type="submit" class="btn btn-primary">Simpan Setelan Paket</button>
                <span class="text-muted fs-8 ms-3">Harga/bln akan dihitung ulang otomatis dari harga dasar × (1 − diskon%) saat disimpan.</span>
            </div>
        </form>
    </div>
@endsection
