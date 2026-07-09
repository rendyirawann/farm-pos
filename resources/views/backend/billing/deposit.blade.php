@extends('backend.layout.app')
@section('title', 'Plan Deposit')
@section('content')

    @php
        $isDeposit = $tenant->isDepositMode();
        $balance   = (float) $tenant->deposit_points;
        $pctOfMax  = ($maxPoints && $maxPoints > 0) ? min(100, round($balance / $maxPoints * 100)) : 0;
    @endphp

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- ============ STATUS DEPOSIT ============ --}}
            <div class="card card-flush mb-8">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-2">Plan Deposit / Poin</h3>
                    <div class="card-toolbar">
                        <span class="badge badge-light-{{ $isDeposit ? 'success' : 'secondary' }} fs-6 fw-bold">
                            Mode: {{ $isDeposit ? 'Deposit (Poin)' : 'Bulanan' }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <div class="fs-7 text-muted">Sisa Poin</div>
                            <div class="fs-2x fw-bold text-gray-900">Rp {{ number_format($balance, 0, ',', '.') }}</div>
                            <div class="w-100 bg-light-primary rounded mt-2" style="height: 8px">
                                <div class="bg-primary rounded" style="height: 8px; width: {{ $pctOfMax }}%"></div>
                            </div>
                            <div class="fs-8 text-muted mt-1">Batas maksimum: {{ $maxPoints ? 'Rp ' . number_format($maxPoints, 0, ',', '.') : 'Tanpa batas' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="fs-7 text-muted">Potongan per transaksi</div>
                            <div class="fs-2x fw-bold text-gray-900">Rp {{ number_format($fee, 0, ',', '.') }}</div>
                            <div class="fs-8 text-muted mt-1">Dipotong saat pesanan diselesaikan.</div>
                        </div>
                        <div class="col-md-4">
                            <div class="fs-7 text-muted">Poin hangus pada</div>
                            <div class="fs-2x fw-bold text-gray-900">
                                {{ $tenant->deposit_expires_at ? $tenant->deposit_expires_at->translatedFormat('d M Y') : '—' }}
                            </div>
                            <div class="fs-8 text-muted mt-1">Bila tak ada aktivitas {{ $expiryDays }} hari.</div>
                        </div>
                    </div>

                    @if ($monthlyActive)
                        <div class="alert alert-info d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mt-6 mb-0">
                            <div class="d-flex align-items-center">
                                <i class="ki-outline ki-information-5 fs-2x text-info me-4"></i>
                                <div>
                                    <h5 class="mb-1 text-gray-900">Anda sedang memakai plan bulanan</h5>
                                    <span class="text-gray-700">Poin deposit <b>dibekukan</b> (Rp{{ number_format($balance, 0, ',', '.') }} tetap tersimpan) dan tidak dipotong selama langganan bulanan aktif. Bila beralih ke deposit sekarang, <b>langganan bulanan akan hangus</b>.</span>
                                </div>
                            </div>
                            <form action="{{ route('deposit.switch') }}" method="POST" class="mt-3 mt-sm-0 form-switch-deposit">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-light-danger">Beralih ke Deposit</button>
                            </form>
                        </div>
                    @elseif (!$isDeposit)
                        <div class="alert alert-primary d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mt-6 mb-0">
                            <div class="d-flex align-items-center">
                                <i class="ki-outline ki-wallet fs-2x text-primary me-4"></i>
                                <div>
                                    <h5 class="mb-1 text-gray-900">Aktifkan plan deposit</h5>
                                    <span class="text-gray-700">Bayar sesuai pemakaian: top-up poin, tiap transaksi dipotong Rp{{ number_format($fee, 0, ',', '.') }}. Cocok untuk usaha yang belum mau langganan bulanan.</span>
                                </div>
                            </div>
                            <form action="{{ route('deposit.switch') }}" method="POST" class="mt-3 mt-sm-0 form-switch-deposit">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">Gunakan Plan Deposit</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ============ PILIHAN TOP-UP ============ --}}
            <div class="card card-flush mb-8">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-3">Top Up Poin</h3>
                </div>
                <div class="card-body">
                    @unless ($purchaseEnabled)
                        <div class="alert alert-warning d-flex align-items-center mb-6">
                            <i class="ki-outline ki-information-5 fs-2x text-warning me-4"></i>
                            <div><span class="text-gray-700">{{ $maintenanceText }} Sementara ini, gunakan <b>top-up manual</b> (lihat kartu di bawah).</span></div>
                        </div>
                    @endunless

                    @if ($needsInitial)
                        {{-- Aktivasi: wajib top-up awal --}}
                        <div class="alert alert-primary d-flex align-items-center mb-6">
                            <i class="ki-outline ki-rocket fs-2x text-primary me-4"></i>
                            <div>
                                <h5 class="mb-1 text-gray-900">Aktivasi plan deposit</h5>
                                <span class="text-gray-700">Untuk mulai memakai plan deposit, wajib melakukan <b>top-up awal Rp{{ number_format($initialTopup, 0, ',', '.') }}</b> (dapat <b>{{ number_format($initialPoints, 0, ',', '.') }} poin</b>). Setelah itu Anda bebas top-up nominal berapa pun.</span>
                            </div>
                        </div>
                        <div class="row g-5">
                            <div class="col-md-5">
                                <div class="border border-success border-dashed bg-light-success rounded p-5 h-100 d-flex flex-column">
                                    <span class="badge badge-success mb-2 align-self-start">Top-up Aktivasi</span>
                                    <div class="fs-7 text-muted">Bayar</div>
                                    <div class="fs-2x fw-bold text-gray-900">Rp {{ number_format($initialTopup, 0, ',', '.') }}</div>
                                    <div class="fs-4 fw-bold text-success mt-1">= {{ number_format($initialPoints, 0, ',', '.') }} poin</div>
                                    <div class="mt-auto pt-4">
                                        @if ($purchaseEnabled)
                                            <button class="btn btn-success w-100 btn-topup" data-amount="{{ $initialTopup }}">Top Up & Aktifkan</button>
                                        @else
                                            <button class="btn btn-light w-100" disabled>Segera Hadir (pakai top-up manual)</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- Sudah aktif: paket preset (bonus) + nominal bebas (1:1) --}}
                        @unless ($tiers['any_fits'])
                            <div class="alert alert-danger d-flex align-items-center mb-6">
                                <i class="ki-outline ki-cross-circle fs-2x text-danger me-4"></i>
                                <div>
                                    <h5 class="mb-1 text-gray-900">Belum bisa top-up</h5>
                                    <span class="text-gray-700">Saldo poin Anda (Rp{{ number_format($balance, 0, ',', '.') }}) sudah mendekati batas maksimum Rp{{ number_format($maxPoints, 0, ',', '.') }}. Tidak ada paket yang muat. Pakai poin dulu, lalu top-up lagi.</span>
                                </div>
                            </div>
                        @endunless

                        <div class="fs-7 fw-bold text-muted text-uppercase mb-3">Paket bonus</div>
                        <div class="row g-5 mb-8">
                            @foreach ($tiers['options'] as $opt)
                                @php $recommended = $tiers['recommended'] === $opt['amount']; @endphp
                                <div class="col-6 col-md-3">
                                    <div class="border border-dashed rounded p-4 h-100 d-flex flex-column {{ $recommended ? 'border-success bg-light-success' : ($opt['fits'] ? 'border-gray-300' : 'border-gray-300 opacity-50') }}">
                                        @if ($recommended)
                                            <span class="badge badge-success mb-2 align-self-start">Disarankan</span>
                                        @endif
                                        <div class="fs-7 text-muted">Bayar</div>
                                        <div class="fs-2 fw-bold text-gray-900">Rp {{ number_format($opt['amount'], 0, ',', '.') }}</div>
                                        <div class="fs-6 fw-bold text-success mt-1">= {{ number_format($opt['points'], 0, ',', '.') }} poin</div>
                                        <div class="fs-8 text-muted">Bonus Rp{{ number_format($opt['bonus'], 0, ',', '.') }}</div>
                                        <div class="mt-auto pt-3">
                                            @if (!$opt['fits'])
                                                <button class="btn btn-sm btn-light w-100" disabled title="Saldo akhir Rp{{ number_format($opt['resulting_balance'], 0, ',', '.') }} melebihi batas maks">Melebihi batas</button>
                                            @elseif (!$purchaseEnabled)
                                                <button class="btn btn-sm btn-light w-100" disabled>Segera Hadir</button>
                                            @else
                                                <button class="btn btn-sm btn-primary w-100 btn-topup" data-amount="{{ $opt['amount'] }}">Top Up</button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="fs-7 fw-bold text-muted text-uppercase mb-3">Nominal bebas (1:1, tanpa bonus)</div>
                        <div class="row g-3 align-items-end">
                            <div class="col-sm-5">
                                <label class="form-label fs-7 text-muted">Masukkan nominal (min Rp{{ number_format($minTopup, 0, ',', '.') }})</label>
                                <input type="number" id="custom-amount" class="form-control" min="{{ $minTopup }}" step="1000" placeholder="mis. 17000" {{ $purchaseEnabled ? '' : 'disabled' }}>
                                <div class="fs-8 text-muted mt-1" id="custom-points-hint">Poin didapat = nominal (1 poin = Rp1).</div>
                            </div>
                            <div class="col-sm-3">
                                @if ($purchaseEnabled)
                                    <button class="btn btn-primary w-100" id="btn-topup-custom">Top Up</button>
                                @else
                                    <button class="btn btn-light w-100" disabled>Segera Hadir</button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ============ TOP-UP MANUAL (jika pembayaran bermasalah) ============ --}}
            <div class="card card-flush mb-8">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-3">Top Up Manual (Transfer Bank)</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-center mb-5">
                        <i class="ki-outline ki-information-5 fs-2x text-info me-4"></i>
                        <div><span class="text-gray-700">Bila pembayaran otomatis (Midtrans) bermasalah atau sedang dinonaktifkan, lakukan top-up manual berikut. Poin akan dikreditkan oleh admin dan tercatat di <b>Riwayat Poin</b>.</span></div>
                    </div>
                    <ol class="text-gray-700 fs-6 lh-lg mb-5">
                        <li>Transfer nominal top-up ke rekening: <b>{{ $manualBank ?: 'hubungi admin untuk info rekening' }}</b>.</li>
                        <li>Chat admin via WhatsApp dengan menyertakan <b>bukti transfer</b> + <b>nama tenant</b> ({{ $tenant->name }}).</li>
                        <li>Admin akan menambahkan poin ke akun Anda. Poin muncul di Riwayat Poin di bawah.</li>
                    </ol>
                    @if ($manualWa)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $manualWa) }}?text={{ rawurlencode('Halo admin, saya ingin top-up manual deposit untuk tenant ' . $tenant->name . '. Berikut bukti transfernya:') }}"
                           target="_blank" class="btn btn-success">
                            <i class="ki-outline ki-whatsapp fs-3 me-2"></i>Chat Admin via WhatsApp
                        </a>
                    @endif
                </div>
            </div>

            {{-- ============ ATURAN / KETENTUAN ============ --}}
            <div class="card card-flush mb-8">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-3">Ketentuan Plan Deposit</h3>
                </div>
                <div class="card-body">
                    <ul class="text-gray-700 fs-6 lh-lg mb-0">
                        <li><b>Aktivasi:</b> akun baru wajib top-up awal <b>Rp{{ number_format($initialTopup, 0, ',', '.') }}</b> (dapat {{ number_format($initialPoints, 0, ',', '.') }} poin). Setelah aktif, top-up lanjutan bebas nominal (min Rp{{ number_format($minTopup, 0, ',', '.') }}).</li>
                        <li>Poin bernilai Rupiah (1 poin = Rp1). <b>Paket preset</b> dapat bonus; <b>nominal bebas</b> dihitung 1:1 (tanpa bonus).</li>
                        <li>Saldo poin {!! $maxPoints ? 'maksimum <b>Rp' . number_format($maxPoints, 0, ',', '.') . '</b> — top-up yang melebihi batas ditolak (sistem menyarankan nominal yang muat)' : '<b>tanpa batas</b>' !!}.</li>
                        <li>Tiap transaksi (pesanan diselesaikan) dipotong <b>Rp{{ number_format($fee, 0, ',', '.') }}</b>. Bila poin tidak cukup, transaksi tidak bisa diselesaikan sampai Anda top-up lagi.</li>
                        <li>Untuk mencegah kecurangan: bila masih ada pesanan menggantung/belum dibayar, Anda <b>tidak bisa menutup atau membuka shift</b> sampai pesanan diselesaikan.</li>
                        <li><b>Poin hangus</b> bila tidak ada aktivitas (top-up/pemakaian) selama <b>{{ $expiryDays }} hari</b> — berlaku juga saat Anda sedang memakai plan bulanan.</li>
                        <li>Plan deposit & langganan bulanan <b>tidak bisa dipakai bersamaan</b>: berlangganan bulanan membekukan poin (tetap tersimpan); beralih ke deposit menghanguskan langganan bulanan. Saat langganan bulanan berakhir, poin otomatis aktif kembali (bila belum hangus).</li>
                        <li>Jika pembayaran otomatis bermasalah, gunakan <b>top-up manual</b> (transfer bank + chat WhatsApp admin) — poin dikreditkan admin & tercatat di Riwayat Poin.</li>
                    </ul>
                </div>
            </div>

            {{-- ============ RIWAYAT POIN ============ --}}
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-3">Riwayat Poin</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-0 gy-3">
                            <thead>
                                <tr class="fw-bold text-muted fs-7 text-uppercase">
                                    <th>Waktu</th>
                                    <th>Jenis</th>
                                    <th class="text-end">Poin</th>
                                    <th class="text-end">Saldo</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $typeMap = [
                                        'topup'      => ['Top-up', 'success'],
                                        'usage'      => ['Transaksi', 'primary'],
                                        'expiry'     => ['Hangus', 'danger'],
                                        'adjustment' => ['Koreksi', 'warning'],
                                        'refund'     => ['Refund', 'info'],
                                    ];
                                @endphp
                                @forelse ($history as $row)
                                    @php [$tLabel, $tColor] = $typeMap[$row->type] ?? [$row->type, 'secondary']; @endphp
                                    <tr>
                                        <td class="text-gray-700">{{ $row->created_at->translatedFormat('d M Y H:i') }}</td>
                                        <td><span class="badge badge-light-{{ $tColor }}">{{ $tLabel }}</span></td>
                                        <td class="text-end fw-bold {{ $row->points < 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $row->points < 0 ? '-' : '+' }}Rp{{ number_format(abs($row->points), 0, ',', '.') }}
                                        </td>
                                        <td class="text-end text-gray-700">Rp{{ number_format($row->balance_after, 0, ',', '.') }}</td>
                                        <td class="text-gray-600 fs-7">{{ $row->description }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-6">Belum ada riwayat poin.</td></tr>
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
        // Konfirmasi peralihan plan (hangus-menghangus)
        document.querySelectorAll('.form-switch-deposit').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Beralih ke plan deposit?',
                    html: 'Jika Anda sedang berlangganan bulanan, sisa masa aktifnya akan <b>hangus</b>. Poin deposit akan aktif dan bisa dipakai.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, beralih',
                    cancelButtonText: 'Batal',
                }).then(function (res) { if (res.isConfirmed) form.submit(); });
            });
        });

        // Top-up via Midtrans Snap (dipakai paket preset, aktivasi, & nominal bebas)
        function topupRequest(amount, btn) {
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Memproses...';

            fetch("{{ route('deposit.checkout') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ amount: amount }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success' && data.snap_token) {
                    if (typeof snap === 'undefined') {
                        Swal.fire('Gagal', 'Gagal memuat Midtrans. Periksa koneksi / client key.', 'error');
                        return;
                    }
                    snap.pay(data.snap_token, {
                        onSuccess: function () { window.location.reload(); },
                        onPending: function () { window.location.reload(); },
                        onError: function () { Swal.fire('Gagal', 'Pembayaran gagal. Silakan coba lagi.', 'error'); },
                        onClose: function () { /* dibatalkan user */ },
                    });
                } else {
                    Swal.fire('Gagal', data.message || 'Gagal memproses top-up.', 'error');
                }
            })
            .catch(() => Swal.fire('Gagal', 'Terjadi kesalahan jaringan.', 'error'))
            .finally(() => { btn.disabled = false; btn.innerHTML = original; });
        }

        document.querySelectorAll('.btn-topup').forEach(function (btn) {
            btn.addEventListener('click', function () {
                topupRequest(parseInt(btn.dataset.amount, 10), btn);
            });
        });

        // Nominal bebas (1:1)
        var customBtn = document.getElementById('btn-topup-custom');
        var customInput = document.getElementById('custom-amount');
        if (customBtn && customInput) {
            customBtn.addEventListener('click', function () {
                var amount = parseInt(customInput.value, 10);
                var min = parseInt(customInput.min, 10) || 0;
                if (!amount || amount < min) {
                    Swal.fire('Nominal tidak valid', 'Masukkan nominal minimal Rp' + min.toLocaleString('id-ID') + '.', 'warning');
                    return;
                }
                topupRequest(amount, customBtn);
            });
            customInput.addEventListener('input', function () {
                var v = parseInt(customInput.value, 10) || 0;
                var hint = document.getElementById('custom-points-hint');
                if (hint) hint.textContent = v > 0
                    ? ('Dapat ' + v.toLocaleString('id-ID') + ' poin (1:1).')
                    : 'Poin didapat = nominal (1 poin = Rp1).';
            });
        }

        // Flash messages
        @if (session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: @json(session('success')), timer: 4000 });
        @endif
        @if (session('info'))
            Swal.fire({ icon: 'info', title: 'Info', text: @json(session('info')), timer: 4000 });
        @endif
        @if (session('warning'))
            Swal.fire({ icon: 'warning', title: 'Perhatian', text: @json(session('warning')) });
        @endif
        @if (session('error'))
            Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')) });
        @endif
    </script>
@endpush
