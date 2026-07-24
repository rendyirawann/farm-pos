@extends('backend.layout.app')
@section('title', 'Manajemen Shift Kasir')
@section('content')

    @php
        $isUmkm = optional($currentTenant)->isUmkm();
        $L = $isUmkm ? 'Kas' : 'Shift';
    @endphp

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            @if (session('success'))
                <div class="alert alert-success d-flex align-items-center p-5 mb-10">
                    <i class="ki-outline ki-shield-tick fs-2hx text-success me-4"></i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1 text-success">Berhasil</h4><span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
                    <i class="ki-outline ki-information-5 fs-2hx text-danger me-4"></i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1 text-danger">Gagal</h4><span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <div class="row g-5 g-xl-10">
                <div class="col-xl-5">
                    @if ($canOperate)
                        {{-- ================= OPERATOR (KASIR) ================= --}}
                        @if (!$currentShift)
                            <div class="card shadow-sm border-0">
                                <div class="card-body text-center p-10">
                                    <i class="ki-outline ki-time fs-5x text-primary mb-5"></i>
                                    <h2 class="fs-2x fw-bold text-gray-800 mb-2">{{ $L }} Belum Dibuka</h2>
                                    <p class="text-gray-500 fs-5 mb-8">Anda harus membuka {{ strtolower($L) }} dan memasukkan modal
                                        sebelum dapat menggunakan mesin kasir.</p>

                                    <form action="{{ route('shifts.open') }}" method="POST" id="formOpenShift">
                                        @csrf

                                        @if ($needTarget)
                                            <div class="bg-light-primary rounded p-5 mb-6 text-start">
                                                <div class="d-flex align-items-center mb-3">
                                                    <i class="ki-outline ki-sun fs-1 text-primary me-2"></i>
                                                    <span class="fw-bold text-primary fs-5">Setup Harian</span>
                                                </div>

                                                @if ($needTarget)
                                                    <div class="mb-4">
                                                        <label class="required fw-semibold fs-6 mb-1">Target Penjualan Hari Ini (Rp)</label>
                                                        <input type="number" name="target_penjualan"
                                                            class="form-control form-control-solid" placeholder="Contoh: 3000000"
                                                            min="0" required>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        <div class="text-start mb-6">
                                            <label class="required fw-semibold fs-5 mb-2">Uang Modal Laci (kembalian + pengeluaran) (Rp)</label>
                                            <input type="number" name="starting_cash"
                                                class="form-control form-control-lg form-control-solid text-center fs-3 fw-bold"
                                                placeholder="Contoh: 500000" min="0" required autofocus>
                                            <div class="form-text fs-8">Uang tunai yang ditaruh di laci: untuk kembalian sekaligus
                                                kas belanja/pengeluaran hari ini (jadi satu).</div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-lg w-100 fs-4 fw-bold">
                                            <i class="ki-outline ki-unlock fs-2 me-2"></i> Buka {{ $L }} Sekarang
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-light-primary pt-7 border-0">
                                    <h3 class="card-title align-items-start flex-column">
                                        <span class="card-label fw-bold text-primary fs-3"><i
                                                class="ki-outline ki-security-user fs-2 text-primary me-2"></i> {{ $L }} Sedang
                                            Berjalan</span>
                                        <span class="text-primary mt-1 fw-semibold fs-7">Dimulai:
                                            {{ \Carbon\Carbon::parse($currentShift->start_time)->translatedFormat('d M Y, H:i') }}</span>
                                    </h3>
                                </div>
                                <div class="card-body p-8">
                                    <div class="d-flex flex-stack mb-5">
                                        <span class="text-gray-600 fs-5">Uang Modal Laci <span class="text-muted fs-8">(kembalian + pengeluaran)</span></span>
                                        <span class="text-gray-800 fw-bold fs-4">Rp
                                            {{ number_format($currentShift->starting_cash, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex flex-stack mb-5">
                                        <span class="text-gray-600 fs-5">Pendapatan Tunai (Masuk)</span>
                                        <span class="text-success fw-bold fs-4">+ Rp
                                            {{ number_format($cashSales, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex flex-stack mb-5">
                                        <span class="text-gray-600 fs-5">Pendapatan QRIS <span class="text-muted fs-8">(info, tak masuk laci)</span></span>
                                        <span class="text-info fw-bold fs-4">Rp
                                            {{ number_format($qrisSales ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex flex-stack mb-5">
                                        <span class="text-gray-600 fs-5">Pengeluaran (Keluar)</span>
                                        <span class="text-danger fw-bold fs-4">- Rp
                                            {{ number_format($shiftExpenses ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="separator separator-dashed my-5"></div>
                                    <div class="d-flex flex-stack mb-8">
                                        <span class="text-gray-800 fw-bolder fs-4">Uang Fisik Seharusnya</span>
                                        <span class="text-primary fw-bolder fs-2qx">Rp
                                            {{ number_format($currentShift->starting_cash + $cashSales - ($shiftExpenses ?? 0), 0, ',', '.') }}</span>
                                    </div>

                                    <form action="{{ route('shifts.close', $currentShift->id) }}" method="POST"
                                        id="formCloseShift">
                                        @csrf
                                        <div class="bg-light-warning rounded p-6 mb-6">
                                            <label class="required fw-bold fs-5 text-gray-800 mb-2">Uang Fisik Aktual (Rp)</label>
                                            <p class="text-muted fs-7 mb-4">Hitung SEMUA uang tunai di laci sekarang (modal + hasil tunai −
                                                pengeluaran), lalu masukkan totalnya untuk menutup {{ strtolower($L) }}.</p>
                                            <input type="number" name="actual_cash"
                                                class="form-control form-control-lg text-center fs-2x fw-bold" placeholder="0"
                                                min="0" required>
                                        </div>
                                        <button type="button" onclick="confirmClose()"
                                            class="btn btn-danger btn-lg w-100 fs-4 fw-bold">
                                            <i class="ki-outline ki-lock-3 fs-2 me-2"></i> Tutup {{ $L }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    @endif

                    @if (! $ownOnly)
                        {{-- ================= PANTAU SHIFT TOKO (OWNER/ADMIN/SUPERADMIN) ================= --}}
                        <div class="card shadow-sm border-0 {{ $canOperate ? 'mt-6' : '' }}">
                            <div class="card-header bg-light-info pt-7 border-0">
                                <h3 class="card-title fw-bold text-info fs-3">
                                    <i class="ki-outline ki-eye fs-2 text-info me-2"></i> Pantau {{ $L }} Toko
                                </h3>
                            </div>
                            <div class="card-body p-8">
                                <p class="text-gray-600 fs-6 mb-6">Semua {{ strtolower($L) }} yang sedang berjalan di toko tampil di sini.
                                    Anda juga dapat <b>membuka kembali</b> {{ strtolower($L) }} yang tak sengaja ditutup lewat daftar
                                    riwayat di samping.</p>
                                <h4 class="fw-bold text-gray-800 fs-5 mb-4">{{ $L }} Sedang Berjalan</h4>
                                @forelse($openShiftsAll as $os)
                                    <div class="d-flex flex-stack border border-dashed rounded p-4 mb-3">
                                        <div>
                                            <span class="d-block fw-bold text-gray-800">{{ optional($os->user)->name ?? 'Kasir' }}</span>
                                            <span class="d-block text-muted fs-8">Sejak
                                                {{ \Carbon\Carbon::parse($os->start_time)->translatedFormat('d M Y, H:i') }}</span>
                                        </div>
                                        <div class="text-end align-self-center">
                                            <div class="badge badge-light-success mb-1">Modal Rp
                                                {{ number_format($os->starting_cash, 0, ',', '.') }}</div>
                                            @if ($canReopen)
                                                <div>
                                                    <a href="#" class="js-edit-modal fs-8 fw-bold text-primary"
                                                        data-id="{{ $os->id }}" data-modal="{{ (int) $os->starting_cash }}"
                                                        data-name="{{ e(optional($os->user)->name) }}"><i class="ki-outline ki-pencil fs-8"></i> Edit modal</a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted text-center py-6">Tidak ada {{ strtolower($L) }} {{ $canOperate ? 'lain ' : '' }}yang sedang berjalan.</div>
                                @endforelse

                                <div class="separator separator-dashed my-6"></div>
<a href="{{ route('kasir.index') }}" class="btn btn-primary btn-lg w-100 fs-4 fw-bold">
    <i class="ki-outline ki-handcart fs-2 me-2"></i> Lihat Layar Kasir
</a>

                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-xl-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header pt-7 border-0">
                            <h3 class="card-title fw-bold text-gray-800 fs-3">
                                Riwayat {{ $L }} {{ $ownOnly ? 'Anda' : 'Toko' }}
                            </h3>
                        </div>
                        <div class="card-body pt-3">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-4">
                                    <thead>
                                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                            <th>Waktu Buka - Tutup</th>
                                            @if (!$ownOnly)
                                                <th>Kasir</th>
                                            @endif
                                            <th class="text-end">Modal</th>
                                            <th class="text-end">Aktual</th>
                                            <th class="text-end">Selisih</th>
                                            @if ($canReopen)
                                                <th class="text-end">Aksi</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($history as $hist)
                                            <tr>
                                                <td>
                                                    <span class="d-block fw-bold text-gray-800">{{ \Carbon\Carbon::parse($hist->start_time)->format('d/m/Y H:i') }}</span>
                                                    <span class="d-block text-muted fs-8">s/d
                                                        {{ \Carbon\Carbon::parse($hist->end_time)->format('H:i') }}</span>
                                                </td>
                                                @if (!$ownOnly)
                                                    <td><span class="fw-semibold text-gray-700">{{ optional($hist->user)->name ?? '-' }}</span></td>
                                                @endif
                                                <td class="text-end">Rp
                                                    {{ number_format($hist->starting_cash, 0, ',', '.') }}</td>
                                                <td class="text-end fw-semibold">Rp
                                                    {{ number_format($hist->actual_cash, 0, ',', '.') }}</td>
                                                <td class="text-end">
                                                    @if ($hist->difference == 0)
                                                        <span class="badge badge-light-success">Pas (Rp 0)</span>
                                                    @elseif($hist->difference > 0)
                                                        <span class="badge badge-light-info">Lebih +Rp
                                                            {{ number_format($hist->difference, 0, ',', '.') }}</span>
                                                    @else
                                                        <span class="badge badge-light-danger">Kurang Rp
                                                            {{ number_format(abs($hist->difference), 0, ',', '.') }}</span>
                                                    @endif
                                                </td>
                                                @if ($canReopen)
                                                    <td class="text-end">
                                                        @if ($hist->end_time && \Carbon\Carbon::parse($hist->end_time)->isToday())
                                                            <form action="{{ route('shifts.reopen', $hist->id) }}" method="POST"
                                                                class="d-inline"
                                                                onsubmit="return confirm('Buka kembali shift {{ optional($hist->user)->name }} yang ditutup {{ \Carbon\Carbon::parse($hist->end_time)->format('H:i') }}? Kasir dapat melanjutkan transaksi di shift ini.');">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-light-warning fw-bold">
                                                                    <i class="ki-outline ki-arrow-circle-left fs-5"></i> Buka Kembali
                                                                </button>
                                                            </form>
                                                        @else
                                                            <span class="text-muted fs-8">—</span>
                                                        @endif
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-5">Belum ada riwayat
                                                    {{ strtolower($L) }}.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                @if (session('success'))
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: '{{ session('success') }}',
                        confirmButtonColor: '#4f46e5',
                        timer: 3000
                    });
                @endif

                @if (session('error'))
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops!',
                        text: '{{ session('error') }}',
                        confirmButtonColor: '#f1416c'
                    });
                @endif

                // Animasi Loading saat Buka Shift
                $('#formOpenShift').on('submit', function() {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Sedang mengatur setup harian dan membuka laci kasir.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                });
            });

            // Logika Tutup Shift
            function confirmClose() {
                Swal.fire({
                    title: "Yakin tutup {{ strtolower($L) }}?",
                    text: "Pastikan uang fisik yang dihitung sudah benar. Kalau tak sengaja tertutup, owner/admin bisa membukanya kembali (undo) di hari yang sama.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Tutup Shift!",
                    cancelButtonText: "Batal",
                    customClass: {
                        confirmButton: "btn btn-danger",
                        cancelButton: "btn btn-secondary"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Menutup Shift...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        // .submit() programatik TIDAK memicu event 'submit', jadi pembersih ribuan
                        // (_number_format) tak jalan. Bersihkan manual dulu: "400.000" -> "400000".
                        var _f = document.getElementById('formCloseShift');
                        var _ac = _f.querySelector('[name="actual_cash"]');
                        if (_ac) _ac.value = String((window.rawNum ? window.rawNum(_ac.value) : Number(String(_ac.value).replace(/[^\d]/g, ''))) || 0);
                        _f.submit();
                    }
                });
            }

            // Owner/admin: koreksi Uang Modal Laci shift yang sedang berjalan.
            document.addEventListener('click', function (e) {
                var a = e.target.closest('.js-edit-modal');
                if (!a) return;
                e.preventDefault();
                var id = a.getAttribute('data-id');
                var cur = a.getAttribute('data-modal') || '';
                var nm = a.getAttribute('data-name') || 'kasir';
                Swal.fire({
                    title: 'Edit Uang Modal Laci',
                    text: 'Perbaiki uang modal laci (kembalian + pengeluaran) shift ' + nm + '.',
                    input: 'number',
                    inputValue: (cur && cur !== '0') ? cur : '',
                    inputAttributes: { min: 0, step: 1000 },
                    inputPlaceholder: 'mis. 500000',
                    showCancelButton: true, confirmButtonText: 'Simpan', cancelButtonText: 'Batal',
                    inputValidator: function (v) {
                        var n = window.rawNum ? window.rawNum(v) : Number(String(v).replace(/[^\d]/g, ''));
                        return (v === '' || v === null || isNaN(n) || n < 0) ? 'Masukkan nominal yang valid' : undefined;
                    }
                }).then(function (r) {
                    if (!r.isConfirmed) return;
                    var n = window.rawNum ? window.rawNum(r.value) : Number(String(r.value).replace(/[^\d]/g, ''));
                    var f = document.createElement('form');
                    f.method = 'POST';
                    f.action = '{{ url('admin/shifts') }}/' + id + '/modal';
                    f.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">'
                        + '<input type="hidden" name="starting_cash" value="' + n + '">';
                    document.body.appendChild(f);
                    f.submit();
                });
            });
        </script>
    @endpush
@endsection
