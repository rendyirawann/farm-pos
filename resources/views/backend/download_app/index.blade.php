@extends('backend.layout.app')
@section('title', 'Aplikasi Tablet')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="row g-6 justify-content-center">
                {{-- KARTU APK --}}
                <div class="col-md-8 col-lg-7">
                    <div class="card card-flush shadow-sm h-100">
                        <div class="card-body text-center p-8 p-lg-10">
                            <img src="{{ asset('assets/media/logos/stakko-icon.png') }}" alt="Stakko POS"
                                class="h-90px rounded-4 shadow mb-5">
                            <h1 class="fw-bold text-gray-900 mb-2">Aplikasi Tablet Stakko POS</h1>
                            <div class="text-muted fs-5 mb-2">Versi {{ $version }} • Android</div>
                            <p class="text-gray-600 fs-6 mb-8 mx-auto" style="max-width:480px;">
                                Pasang Stakko POS sebagai aplikasi di tablet Anda untuk pengalaman layar penuh.
                                Aplikasi tetap terhubung ke server melalui internet — data selalu sinkron dengan versi web.
                            </p>

                            @if ($available)
                                <a href="{{ $apkUrl }}" class="btn btn-primary btn-lg fw-bold px-8"
                                    @if (Str::startsWith($apkUrl, url('/'))) download @endif>
                                    <i class="ki-outline ki-cloud-download fs-2 me-2"></i> Download APK
                                </a>
                                <div class="fs-8 text-muted mt-3">
                                    Ukuran kecil • Butuh Android 7.0+ • Aktifkan "Instal dari sumber ini" saat diminta.
                                </div>
                            @else
                                <button class="btn btn-light-primary btn-lg fw-bold px-8" disabled>
                                    <i class="ki-outline ki-timer fs-2 me-2"></i> Segera Hadir
                                </button>
                                <div class="fs-8 text-muted mt-3">
                                    File APK sedang disiapkan. Sementara itu, Anda bisa memasang versi PWA di bawah.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- CARA PASANG --}}
                <div class="col-md-8 col-lg-5">
                    <div class="card card-flush shadow-sm h-100">
                        <div class="card-header pt-6"><h3 class="card-title fw-bold">Cara Memasang</h3></div>
                        <div class="card-body pt-2">
                            <div class="mb-6">
                                <span class="badge badge-light-primary fw-bold mb-2">Opsi 1 — APK</span>
                                <ol class="text-gray-700 fs-7 ps-4 mb-0">
                                    <li class="mb-1">Buka halaman ini dari tablet, tap <b>Download APK</b>.</li>
                                    <li class="mb-1">Izinkan "Instal dari sumber ini" bila diminta.</li>
                                    <li class="mb-1">Buka aplikasi, login dengan akun Anda.</li>
                                </ol>
                            </div>
                            <div class="separator my-4"></div>
                            <div>
                                <span class="badge badge-light-success fw-bold mb-2">Opsi 2 — PWA (tanpa APK)</span>
                                <ol class="text-gray-700 fs-7 ps-4 mb-0">
                                    <li class="mb-1">Buka sistem ini di browser <b>Chrome</b> tablet.</li>
                                    <li class="mb-1">Menu ⋮ → <b>Tambahkan ke Layar Utama</b>.</li>
                                    <li class="mb-1">Ikon Stakko POS muncul & berjalan layar penuh.</li>
                                </ol>
                            </div>
                            <div class="alert alert-primary d-flex align-items-center mt-6 mb-0">
                                <i class="ki-outline ki-information-5 fs-2 text-primary me-3"></i>
                                <span class="fs-8 text-gray-700">Aplikasi membutuhkan koneksi internet & terhubung ke server Stakko.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection
