@extends('backend.layout.app')
@section('title', 'Pengaturan Sistem')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <form action="{{ route('settings.update') }}" method="POST" id="form-settings">
                @csrf
                <div class="card shadow-sm">
                    <div class="card-header d-flex align-items-center">
                        <h3 class="card-title fw-bold m-0"><i class="ki-outline ki-setting-2 fs-2 me-2"></i> Konfigurasi Sistem</h3>
                        <ul class="nav nav-tabs nav-line-tabs ms-auto border-0" role="tablist">
                            <li class="nav-item"><a class="nav-link active fw-semibold" data-bs-toggle="tab" href="#tab-umum">Umum</a></li>
                            <li class="nav-item"><a class="nav-link fw-semibold" data-bs-toggle="tab" href="#tab-printer">Printer Struk</a></li>
                        </ul>
                    </div>

                    <div class="card-body tab-content">
                        {{-- ========== TAB UMUM ========== --}}
                        <div class="tab-pane fade show active" id="tab-umum">
                            <div class="row mb-6">
                                <label class="col-lg-3 col-form-label required fw-semibold fs-6">Nama Toko</label>
                                <div class="col-lg-9">
                                    <input type="text" name="store_name" class="form-control form-control-solid"
                                        value="{{ $setting->store_name }}" required>
                                </div>
                            </div>
                            <div class="row mb-6">
                                <label class="col-lg-3 col-form-label fw-semibold fs-6">Alamat Toko</label>
                                <div class="col-lg-9">
                                    <textarea name="address" class="form-control form-control-solid" rows="3">{{ $setting->address }}</textarea>
                                </div>
                            </div>
                            <div class="row mb-6">
                                <label class="col-lg-3 col-form-label fw-semibold fs-6">No. Telepon / WA</label>
                                <div class="col-lg-9">
                                    <input type="text" name="phone" class="form-control form-control-solid"
                                        value="{{ $setting->phone }}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <label class="col-lg-3 col-form-label required fw-semibold fs-6">Pajak Restoran (PB1)</label>
                                <div class="col-lg-9">
                                    <div class="input-group input-group-solid w-200px">
                                        <input type="number" name="tax_rate" class="form-control form-control-solid js-no-format"
                                            value="{{ $setting->tax_rate }}" min="0" max="100" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                    <div class="form-text">Masukkan angka 0 jika toko tidak membebankan pajak ke pelanggan.</div>
                                </div>
                            </div>
                        </div>

                        {{-- ========== TAB PRINTER ========== --}}
                        <div class="tab-pane fade" id="tab-printer">
                            <div class="alert alert-primary d-flex align-items-center">
                                <i class="ki-outline ki-information-5 fs-2x text-primary me-3"></i>
                                <span class="fs-7 text-gray-700">Pilih cara sistem menyambung ke printer thermal saat mencetak struk.
                                    Sesuaikan dengan perangkat Anda (PC/laptop atau tablet) & jenis koneksi printer (USB / LAN / Bluetooth).</span>
                            </div>

                            {{-- Ukuran kertas --}}
                            <div class="row mb-6 mt-4">
                                <label class="col-lg-3 col-form-label fw-semibold fs-6">Ukuran Kertas</label>
                                <div class="col-lg-9">
                                    <select name="paper_width" class="form-select form-select-solid w-250px">
                                        <option value="58" {{ (int) ($setting->paper_width ?? 58) === 58 ? 'selected' : '' }}>58 mm (32 kolom)</option>
                                        <option value="80" {{ (int) ($setting->paper_width ?? 58) === 80 ? 'selected' : '' }}>80 mm (48 kolom)</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Metode cetak --}}
                            <label class="fw-semibold fs-6 mb-3 d-block">Metode Cetak</label>
                            @php
                                $cur = $setting->printer_method ?? 'auto';
                                $methods = [
                                    ['auto', 'Otomatis (Rekomendasi)', 'Sistem memilih sendiri: di aplikasi tablet → printer bawaan aplikasi; selain itu → dialog browser.', 'ki-technology-4', null, null],
                                    ['browser', 'Dialog Browser / OS', 'Cetak lewat dialog print sistem. Printer thermal harus terpasang sebagai printer OS (driver). Cocok PC/laptop + USB/LAN.', 'ki-printer', null, null],
                                    ['qztray', 'QZ Tray (Desktop)', 'Cetak ESC/POS langsung tanpa dialog di PC/laptop (USB/LAN/Bluetooth). Perlu aplikasi QZ Tray berjalan di komputer.', 'ki-desktop', 'Download QZ Tray', 'https://qz.io/download/'],
                                    ['webbluetooth', 'Web Bluetooth (BLE)', 'Sambung printer thermal Bluetooth langsung dari browser Chrome/Edge. Cocok tablet/laptop. Perlu akses HTTPS.', 'ki-technology-2', 'Butuh Chrome / Edge', 'https://www.google.com/chrome/'],
                                    ['rawbt', 'RawBT (Android)', 'Cetak dari browser Android via aplikasi RawBT (Bluetooth / USB / WiFi). Cocok tablet dengan berbagai printer.', 'ki-tablet', 'Download RawBT', 'https://play.google.com/store/apps/details?id=ru.a402d.rawbtprinter'],
                                ];
                            @endphp
                            <div class="row g-4">
                                @foreach ($methods as [$val, $title, $desc, $icon, $dlText, $dlUrl])
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="printer_method" value="{{ $val }}"
                                            id="pm_{{ $val }}" {{ $cur === $val ? 'checked' : '' }}>
                                        <label for="pm_{{ $val }}"
                                            class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex align-items-start text-start p-4 h-100">
                                            <i class="ki-outline {{ $icon }} fs-2x text-primary me-3 mt-1"></i>
                                            <span>
                                                <span class="d-block fw-bold fs-5 text-gray-900">{{ $title }}</span>
                                                <span class="d-block text-muted fs-7">{{ $desc }}</span>
                                            </span>
                                        </label>
                                        @if ($dlUrl)
                                            <div class="mt-2 ps-1">
                                                <a href="{{ $dlUrl }}" target="_blank" rel="noopener"
                                                    class="btn btn-sm btn-light-primary">
                                                    <i class="ki-outline ki-cloud-download fs-5"></i> {{ $dlText }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="separator my-6"></div>
                            <div class="d-flex flex-wrap gap-3">
                                <button type="button" class="btn btn-light-primary" id="btn-connect-printer">
                                    <i class="ki-outline ki-plug fs-3"></i> Hubungkan / Pilih Printer
                                </button>
                                <button type="button" class="btn btn-light-success" id="btn-test-print">
                                    <i class="ki-outline ki-printer fs-3"></i> Test Cetak
                                </button>
                            </div>
                            <div class="form-text mt-3">
                                Untuk <b>Web Bluetooth</b>: klik "Hubungkan / Pilih Printer" & pilih printer (sekali per sesi).
                                Untuk <b>QZ Tray</b>: pastikan aplikasi QZ Tray berjalan, lalu pilih printer.
                                Untuk aplikasi tablet (APK), unduh di menu <a href="{{ route('download-app') }}">Aplikasi</a>.
                            </div>

                            {{-- Cetak senyap / kiosk untuk metode Dialog Browser --}}
                            <div class="separator my-6"></div>
                            <div class="d-flex bg-light-warning rounded border border-warning border-dashed p-5">
                                <i class="ki-outline ki-rocket fs-2x text-warning me-4 mt-1"></i>
                                <div>
                                    <h4 class="fw-bold text-gray-900 mb-2">Cetak otomatis tanpa dialog (mode Kiosk)</h4>
                                    <div class="fs-7 text-gray-700">
                                        Khusus metode <b>Dialog Browser / OS</b>: agar dialog print tidak perlu diklik
                                        (langsung tercetak ke printer default), jalankan browser dengan flag
                                        <code>--kiosk-printing</code> lewat <b>shortcut aplikasi</b>:
                                        <div class="bg-dark text-white rounded p-3 my-2" style="font-family:monospace; overflow-x:auto;">
                                            chrome.exe --kiosk-printing --app=http://127.0.0.1:8044/admin/kasir
                                        </div>
                                        Edge: <code>msedge.exe --kiosk-printing --app=&lt;URL&gt;</code>.
                                        Tambahkan <code>--kiosk</code> untuk layar penuh.
                                        <br>Lalu di Windows: <b>Settings → Printers</b> → jadikan printer thermal sebagai
                                        <b>default</b>, dan set ukuran kertas driver ke 58/80mm.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-primary" id="btn-save">Simpan Pengaturan</button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                @if (session('success'))
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: '{{ session('success') }}', timer: 3000 });
                @endif

                // Metode & ukuran kertas yang dipilih (belum disimpan) langsung dipakai tombol Test/Connect
                $('input[name="printer_method"]').on('change', function() {
                    if (window.STAKKO_PRINT) window.STAKKO_PRINT.method = this.value;
                });
                $('select[name="paper_width"]').on('change', function() {
                    if (window.STAKKO_PRINT) window.STAKKO_PRINT.paper_width = parseInt(this.value, 10);
                });

                $('#btn-test-print').on('click', function() {
                    if (window.StakkoPrint) window.StakkoPrint.test();
                    else Swal.fire('Info', 'Engine cetak belum termuat, muat ulang halaman.', 'info');
                });
                $('#btn-connect-printer').on('click', function() {
                    if (window.StakkoPrint) window.StakkoPrint.quickConnect();
                });

                $('#form-settings').on('submit', function() {
                    let btn = $('#btn-save');
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2 align-middle"></span> Memproses...');
                    Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                });
            });
        </script>
    @endpush
@endsection
