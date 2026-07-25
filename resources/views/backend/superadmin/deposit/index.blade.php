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
                                <label class="form-label fw-semibold">Maksimum Saldo (Rp)</label>
                                <input type="number" name="max_points" min="0" class="form-control"
                                    value="{{ old('max_points', $settings->max_points) }}" placeholder="Kosongkan = tanpa batas">
                                <div class="form-text">Kosong / 0 = <b>tanpa batas</b> (unlimited).</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Potongan / Transaksi (Rp)</label>
                                <input type="number" name="fee_per_transaction" min="0" class="form-control"
                                    value="{{ old('fee_per_transaction', $settings->fee_per_transaction) }}" required>
                                <div class="form-text">Dipotong saat pesanan diselesaikan.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Masa Aktif Saldo (hari)</label>
                                <input type="number" name="expiry_days" min="1" max="3650" class="form-control"
                                    value="{{ old('expiry_days', $settings->expiry_days) }}" required>
                                <div class="form-text">Saldo hangus bila tak dipakai sekian hari berturut-turut.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Top-up Awal / Aktivasi (Rp)</label>
                                <input type="number" name="initial_topup" min="1" class="form-control"
                                    value="{{ old('initial_topup', $settings->initial_topup) }}" required>
                                <div class="form-text">Wajib untuk akun deposit baru.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Min. Top-up (Rp)</label>
                                <input type="number" name="min_deposit" min="0" class="form-control"
                                    value="{{ old('min_deposit', $settings->min_deposit) }}" required>
                                <div class="form-text">Paket terkecil.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Batas Peringatan Saldo (Rp)</label>
                                <input type="number" name="warning_threshold" min="0" class="form-control"
                                    value="{{ old('warning_threshold', $settings->warning_threshold) }}" required>
                                <div class="form-text">Saldo ≤ nilai ini → peringatan merah "segera top up".</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">WhatsApp Admin (top-up manual)</label>
                                <input type="text" name="manual_wa" class="form-control"
                                    value="{{ old('manual_wa', $settings->manual_wa) }}" placeholder="mis. 6281265558044">
                                <div class="form-text">Format 62xxx. Untuk tombol chat di halaman tenant.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Info Rekening Bank (top-up manual)</label>
                                <input type="text" name="manual_bank" class="form-control"
                                    value="{{ old('manual_bank', $settings->manual_bank) }}" placeholder="mis. BCA 1234567890 a.n. Mooda">
                            </div>
                        </div>

                        <div class="alert alert-info d-flex align-items-center mt-6 mb-0">
                            <i class="ki-outline ki-information-5 fs-2x text-info me-4"></i>
                            <div class="text-gray-700 fs-7">
                                Catatan: bila ada <b>batas maksimum</b> dan bonus membuat saldo sebuah paket melebihi batas, paket itu
                                otomatis <b>ter-nonaktif dari sisi tenant</b> (tidak muat) walau di sini masih aktif.
                                Naikkan/kosongkan batas bila ingin paket besar tetap bisa dipakai.
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
                                        <th style="width:30%">Saldo Diterima (Rp)</th>
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

            {{-- ====== TOP-UP MANUAL KE TENANT ====== --}}
            <div class="card card-flush mt-8">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-3">Top-up Manual ke Tenant</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-center mb-5">
                        <i class="ki-outline ki-information-5 fs-2x text-info me-4"></i>
                        <div class="text-gray-700 fs-7">Gunakan setelah tenant transfer ke bank & konfirmasi via WhatsApp. Saldo langsung masuk ke tenant dan tercatat di Riwayat Saldo mereka + activity log. Batas maksimum tidak berlaku untuk top-up manual.</div>
                    </div>
                    <form action="{{ route('deposit-settings.manual-topup') }}" method="POST">
                        @csrf
                        <div class="row g-5 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tenant (mode deposit)</label>
                                <select name="tenant_id" class="form-select" required>
                                    <option value="">— pilih tenant —</option>
                                    @foreach ($tenants as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }} — {{ $t->isDepositMode() ? 'saldo Rp' . number_format($t->deposit_points, 0, ',', '.') : '⚠ belum deposit (akan dijadikan Starter/Deposit)' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Paket Top-up</label>
                                <select name="amount" class="form-select" required>
                                    <option value="">— pilih paket —</option>
                                    <option value="5000">Rp5.000 → 5.000 saldo (khusus Superadmin)</option>
                                    @foreach ($activeTiers as $tier)
                                        <option value="{{ $tier->amount }}">Rp{{ number_format($tier->amount, 0, ',', '.') }} → {{ number_format($tier->points, 0, ',', '.') }} saldo</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Catatan (mis. ref transfer)</label>
                                <input type="text" name="note" class="form-control" maxlength="255" placeholder="BCA ref 12345">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-success w-100">Kredit</button>
                            </div>
                        </div>
                    </form>
                    @if ($tenants->isEmpty())
                        <div class="text-muted fs-7 mt-3">Belum ada tenant yang memakai plan deposit.</div>
                    @endif
                </div>
            </div>

            {{-- ====== RIWAYAT TOP-UP MANUAL ====== --}}
            <div class="card card-flush mt-8">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-3">Riwayat Top-up Manual Terbaru</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-0 gy-3">
                            <thead>
                                <tr class="fw-bold text-muted fs-7 text-uppercase">
                                    <th>Waktu</th>
                                    <th>Tenant</th>
                                    <th class="text-end">Saldo</th>
                                    <th class="text-end">Nominal</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentManual as $row)
                                    <tr>
                                        <td class="text-gray-700">{{ $row->created_at->translatedFormat('d M Y H:i') }}</td>
                                        <td class="fw-semibold">{{ $row->tenant->name ?? '—' }}</td>
                                        <td class="text-end text-success fw-bold">+Rp{{ number_format($row->points, 0, ',', '.') }}</td>
                                        <td class="text-end text-gray-700">{{ $row->cash_amount ? 'Rp' . number_format($row->cash_amount, 0, ',', '.') : '—' }}</td>
                                        <td class="text-gray-600 fs-7">{{ $row->description }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-6">Belum ada top-up manual.</td></tr>
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
                <td><input type="number" name="tiers[${i}][amount]" min="1" class="form-control form-control-sm tier-amount" value="25000" required></td>
                <td><input type="number" name="tiers[${i}][points]" min="1" class="form-control form-control-sm tier-points" value="30000" required></td>
                <td class="tier-bonus text-success fw-semibold">Rp5.000</td>
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

        @if (session('error'))
            Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')) });
        @endif
        @if (session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: @json(session('success')), timer: 3000 });
        @endif
        @if ($errors->any())
            Swal.fire({ icon: 'error', title: 'Gagal menyimpan', html: `{!! implode('<br>', array_map('e', $errors->all())) !!}` });
        @endif
    </script>
@endpush
