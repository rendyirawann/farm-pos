@extends('backend.layout.app')
@section('title', 'Setelan Deposit')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <form action="{{ route('deposit-settings.update') }}" method="POST">
                @csrf

                {{-- ====== SETELAN UMUM ====== --}}
                <div class="card card-flush mb-8">
                    <div class="card-header pt-5">
                        <h3 class="card-title fw-bold text-gray-800 fs-2">Setelan Plan Deposit</h3>
                        <div class="card-toolbar">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-6">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Maksimum Saldo Poin (Rp)</label>
                                <input type="number" name="max_points" min="0" class="form-control"
                                    value="{{ old('max_points', $settings->max_points) }}" required>
                                <div class="form-text">Top-up yang melewati batas ini ditolak.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Potongan / Transaksi (Rp)</label>
                                <input type="number" name="fee_per_transaction" min="0" class="form-control"
                                    value="{{ old('fee_per_transaction', $settings->fee_per_transaction) }}" required>
                                <div class="form-text">Dipotong saat pesanan diselesaikan.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Masa Aktif Poin (hari)</label>
                                <input type="number" name="expiry_days" min="1" max="3650" class="form-control"
                                    value="{{ old('expiry_days', $settings->expiry_days) }}" required>
                                <div class="form-text">Poin hangus bila tak dipakai sekian hari.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Minimal Deposit (Rp)</label>
                                <input type="number" name="min_deposit" min="0" class="form-control"
                                    value="{{ old('min_deposit', $settings->min_deposit) }}" required>
                            </div>
                        </div>

                        <div class="alert alert-info d-flex align-items-center mt-6 mb-0">
                            <i class="ki-outline ki-information-5 fs-2x text-info me-4"></i>
                            <div class="text-gray-700 fs-7">
                                Catatan: bila bonus membuat poin sebuah tier melebihi <b>Maksimum Saldo Poin</b>, tier itu
                                akan otomatis <b>ter-nonaktif dari sisi tenant</b> (tidak muat) walau di sini masih aktif.
                                Contoh: maks 50.000 + tier bayar 50.000 → 62.500 poin ⇒ tak akan pernah muat. Naikkan maks
                                bila ingin tier besar tetap bisa dipakai.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ====== TIER TOP-UP ====== --}}
                <div class="card card-flush">
                    <div class="card-header pt-5">
                        <h3 class="card-title fw-bold text-gray-800 fs-3">Pilihan Nominal Top-up</h3>
                        <div class="card-toolbar">
                            <button type="button" class="btn btn-sm btn-light-primary" id="btn-add-tier">+ Tambah Tier</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle gs-0 gy-3" id="tier-table">
                                <thead>
                                    <tr class="fw-bold text-muted fs-7 text-uppercase">
                                        <th style="width:30%">Nominal Bayar (Rp)</th>
                                        <th style="width:30%">Poin Diterima (Rp)</th>
                                        <th style="width:20%">Bonus</th>
                                        <th style="width:10%">Aktif</th>
                                        <th style="width:10%"></th>
                                    </tr>
                                </thead>
                                <tbody id="tier-rows">
                                    @foreach ($tiers as $i => $tier)
                                        <tr class="tier-row">
                                            <td>
                                                <input type="number" name="tiers[{{ $i }}][amount]" min="1"
                                                    class="form-control form-control-sm tier-amount" value="{{ $tier->amount }}" required>
                                            </td>
                                            <td>
                                                <input type="number" name="tiers[{{ $i }}][points]" min="1"
                                                    class="form-control form-control-sm tier-points" value="{{ $tier->points }}" required>
                                            </td>
                                            <td class="tier-bonus text-success fw-semibold">Rp{{ number_format(max(0, $tier->points - $tier->amount), 0, ',', '.') }}</td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input type="hidden" name="tiers[{{ $i }}][is_active]" value="0">
                                                    <input class="form-check-input" type="checkbox" name="tiers[{{ $i }}][is_active]"
                                                        value="1" {{ $tier->is_active ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-icon btn-sm btn-light-danger btn-remove-tier">
                                                    <i class="ki-outline ki-trash fs-4"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        let tierIndex = {{ $tiers instanceof \Illuminate\Support\Collection ? $tiers->count() : count($tiers) }};

        function rupiah(n) { return 'Rp' + Number(n || 0).toLocaleString('id-ID'); }

        function bindRow(row) {
            const amount = row.querySelector('.tier-amount');
            const points = row.querySelector('.tier-points');
            const bonus = row.querySelector('.tier-bonus');
            function refresh() {
                bonus.textContent = rupiah(Math.max(0, (parseInt(points.value, 10) || 0) - (parseInt(amount.value, 10) || 0)));
            }
            amount.addEventListener('input', refresh);
            points.addEventListener('input', refresh);
            row.querySelector('.btn-remove-tier').addEventListener('click', function () {
                if (document.querySelectorAll('.tier-row').length <= 1) {
                    Swal.fire('Tidak bisa', 'Minimal harus ada 1 tier.', 'warning');
                    return;
                }
                row.remove();
            });
        }

        document.querySelectorAll('.tier-row').forEach(bindRow);

        document.getElementById('btn-add-tier').addEventListener('click', function () {
            const i = tierIndex++;
            const tr = document.createElement('tr');
            tr.className = 'tier-row';
            tr.innerHTML = `
                <td><input type="number" name="tiers[${i}][amount]" min="1" class="form-control form-control-sm tier-amount" value="5000" required></td>
                <td><input type="number" name="tiers[${i}][points]" min="1" class="form-control form-control-sm tier-points" value="5500" required></td>
                <td class="tier-bonus text-success fw-semibold">Rp500</td>
                <td>
                    <div class="form-check form-switch">
                        <input type="hidden" name="tiers[${i}][is_active]" value="0">
                        <input class="form-check-input" type="checkbox" name="tiers[${i}][is_active]" value="1" checked>
                    </div>
                </td>
                <td><button type="button" class="btn btn-icon btn-sm btn-light-danger btn-remove-tier"><i class="ki-outline ki-trash fs-4"></i></button></td>`;
            document.getElementById('tier-rows').appendChild(tr);
            bindRow(tr);
        });

        @if (session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: @json(session('success')), timer: 3000 });
        @endif
        @if ($errors->any())
            Swal.fire({ icon: 'error', title: 'Gagal menyimpan', html: `{!! implode('<br>', array_map('e', $errors->all())) !!}` });
        @endif
    </script>
@endpush
