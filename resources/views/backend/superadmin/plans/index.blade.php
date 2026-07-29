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
                        <div class="card h-100" data-plan-card>
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
                                            value="{{ (int) $plan['setting']->base_price }}" class="form-control pp-base">
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
                                                            class="form-control form-control-sm pp-disc" {{ $pr->months == 1 ? 'readonly' : '' }}>
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
                                                    <td class="text-end">
                                                        <div class="input-group input-group-sm" style="width:160px;margin-left:auto;">
                                                            <span class="input-group-text">Rp</span>
                                                            <input type="number" min="0" step="1000"
                                                                name="plans[{{ $key }}][promos][{{ $pr->months }}][price_per_month]"
                                                                value="{{ (int) $pr->price_per_month }}"
                                                                class="form-control form-control-sm pp-price text-end fw-bold" {{ $pr->months == 1 ? 'readonly' : '' }}>
                                                        </div>
                                                    </td>
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
                <span class="text-muted fs-8 ms-3">Preview harga/diskon berubah langsung; klik <b>Simpan</b> untuk menyimpan.</span>
            </div>
        </form>
    </div>

    <script>
        // Hitung dua arah (live, tanpa simpan): diskon% <-> harga/bln, berdasar harga dasar.
        document.querySelectorAll('[data-plan-card]').forEach(function (card) {
            var baseInput = card.querySelector('.pp-base');
            if (!baseInput) return;
            var base = function () { return Math.max(0, parseInt(baseInput.value || '0', 10)); };
            var priceFromDisc = function (discEl, priceEl) {
                var d = Math.min(100, Math.max(0, parseFloat(discEl.value || '0')));
                priceEl.value = Math.round(base() * (1 - d / 100));
            };
            var discFromPrice = function (priceEl, discEl) {
                var b = base(), p = Math.max(0, parseInt(priceEl.value || '0', 10));
                discEl.value = b > 0 ? Math.round((1 - p / b) * 10000) / 100 : 0; // 2 desimal
            };

            card.querySelectorAll('tbody tr').forEach(function (row) {
                var disc = row.querySelector('.pp-disc');
                var price = row.querySelector('.pp-price');
                var toggle = row.querySelector('input[type=checkbox]');
                if (!disc || !price) return;
                disc.addEventListener('input', function () { priceFromDisc(disc, price); });
                price.addEventListener('input', function () { discFromPrice(price, disc); });
                if (toggle) {
                    toggle.addEventListener('change', function () {
                        if (!toggle.checked) { price.value = base(); disc.value = 0; }  // OFF = harga penuh
                        else { priceFromDisc(disc, price); }
                    });
                }
            });

            // Ubah harga dasar -> semua harga durasi ikut menyesuaikan (dari diskon masing-masing).
            baseInput.addEventListener('input', function () {
                card.querySelectorAll('tbody tr').forEach(function (row) {
                    var disc = row.querySelector('.pp-disc');
                    var price = row.querySelector('.pp-price');
                    var toggle = row.querySelector('input[type=checkbox]');
                    if (!disc || !price) return;
                    if (toggle && !toggle.checked) { price.value = base(); }
                    else { priceFromDisc(disc, price); }
                });
            });
        });
    </script>
@endsection
