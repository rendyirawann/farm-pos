@extends('auth.app')
@section('title', 'Aktivasi Akun - Mooda')
@section('content')
    <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-start p-12">

        <div class="bg-body d-flex flex-column flex-center rounded-4 w-md-600px p-10 shadow-lg">

            <div class="d-flex flex-center flex-column align-items-stretch h-lg-100 w-md-400px">

                {{-- Logo --}}
                <div class="d-flex flex-center flex-column flex-column-fluid mb-2">
                    <img alt="Logo" class="theme-light-show h-40px h-lg-120px"
                        src="{{ asset('assets/media/logos/mooda-logo.png') }}" />
                    <img alt="Logo" class="theme-dark-show h-40px h-lg-120px"
                        src="{{ asset('assets/media/logos/mooda-logo.png') }}" />
                </div>

                <div class="d-flex flex-center flex-column flex-column-fluid pb-15 pb-lg-20 my-6">

                    {{-- Title --}}
                    <div class="text-center mb-8">
                        <h1 class="text-gray-900 fw-bolder mb-3 fs-2">Aktivasi Akun Anda</h1>
                        <div class="text-gray-500 fw-semibold fs-6">
                            Kami sudah mengirim <b>link aktivasi</b> ke email Anda.<br>
                            Klik link tersebut untuk mengaktifkan akun &amp; dapat<br>
                            saldo <b>Starter Rp2.000</b> gratis.
                        </div>
                    </div>

                    {{-- Link kedaluwarsa (diklik setelah lewat 2 menit) --}}
                    @if (session('link_expired'))
                        <div class="alert alert-danger d-flex align-items-center p-4 mb-8 rounded-3 w-100" role="alert">
                            <i class="ki-outline ki-time fs-2hx text-danger me-4"></i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1 fw-bold">Link Aktivasi Kedaluwarsa</h5>
                                <span class="fs-6">Link aktivasi sudah lewat 2 menit. Silakan <b>kirim ulang link aktivasi</b> di bawah, lalu klik link terbaru di email Anda.</span>
                            </div>
                        </div>
                    @endif

                    {{-- Status --}}
                    @if (session('status') == 'verification-link-sent')
                        <div class="alert alert-success d-flex align-items-center p-4 mb-8 rounded-3 w-100" role="alert">
                            <i class="ki-outline ki-shield-tick fs-2hx text-success me-4"></i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1 fw-bold">Link Aktivasi Terkirim!</h5>
                                <span class="fs-6">Link aktivasi baru sudah dikirim ke email Anda. Berlaku 2 menit.</span>
                            </div>
                        </div>
                    @elseif (session('status') == 'cooldown')
                        <div class="alert alert-warning d-flex align-items-center p-4 mb-8 rounded-3 w-100" role="alert">
                            <i class="ki-outline ki-time fs-2hx text-warning me-4"></i>
                            <div class="d-flex flex-column">
                                <span class="fs-6">Mohon tunggu hitungan mundur di tombol sebelum mengirim ulang link aktivasi.</span>
                            </div>
                        </div>
                    @elseif (session('status'))
                        <div class="alert alert-primary d-flex align-items-center p-4 mb-8 rounded-3 w-100" role="alert">
                            <i class="ki-outline ki-sms fs-2hx text-primary me-4"></i>
                            <div class="d-flex flex-column">
                                <span class="fs-6">{{ session('status') }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="alert alert-light-primary d-flex align-items-center p-4 mb-8 rounded-3 w-100">
                        <i class="ki-outline ki-information-5 fs-2hx text-primary me-4"></i>
                        <div class="fs-7 text-gray-700">
                            Cek folder <b>Spam / Promosi</b> bila email belum masuk. Belum menerima? Kirim ulang di bawah.
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-3 w-100">
                        {{-- Kirim ulang (cooldown 2 menit + loader anti klik-2x) --}}
                        <form method="POST" action="{{ route('verification.send') }}" class="flex-fill" id="resendForm">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100" id="resendBtn" @if (($cooldown ?? 0) > 0) disabled @endif>
                                <span class="resend-idle"><i class="ki-outline ki-sms fs-4 me-2"></i>Kirim Ulang Link</span>
                                <span class="resend-loading d-none"><span class="spinner-border spinner-border-sm align-middle me-2"></span>Mengirim…</span>
                                <span class="resend-count d-none"></span>
                            </button>
                        </form>

                        {{-- Logout (loader) --}}
                        <form method="POST" action="{{ route('logout') }}" class="flex-fill" id="logoutForm">
                            @csrf
                            <button type="submit" class="btn btn-light w-100" id="logoutBtn">
                                <span class="logout-idle"><i class="ki-outline ki-exit-right fs-4 me-2"></i>Keluar</span>
                                <span class="logout-loading d-none"><span class="spinner-border spinner-border-sm align-middle me-2"></span>Keluar…</span>
                            </button>
                        </form>
                    </div>

                    <script>
                        (function () {
                            var COOLDOWN = {{ (int) ($cooldown ?? 0) }};
                            var btn = document.getElementById('resendBtn');
                            var idle = btn.querySelector('.resend-idle');
                            var loading = btn.querySelector('.resend-loading');
                            var count = btn.querySelector('.resend-count');

                            function fmt(s) { var m = Math.floor(s / 60), sec = s % 60; return m + ':' + (sec < 10 ? '0' : '') + sec; }
                            function tick() {
                                if (COOLDOWN <= 0) {
                                    btn.disabled = false;
                                    count.classList.add('d-none'); idle.classList.remove('d-none');
                                    return;
                                }
                                btn.disabled = true;
                                idle.classList.add('d-none'); count.classList.remove('d-none');
                                count.innerHTML = '<i class="ki-outline ki-time fs-4 me-2"></i>Kirim ulang dalam ' + fmt(COOLDOWN);
                                COOLDOWN--;
                                setTimeout(tick, 1000);
                            }
                            tick();

                            // Loader saat submit (cegah klik ganda)
                            document.getElementById('resendForm').addEventListener('submit', function () {
                                btn.disabled = true;
                                idle.classList.add('d-none'); count.classList.add('d-none'); loading.classList.remove('d-none');
                            });
                            document.getElementById('logoutForm').addEventListener('submit', function () {
                                var lb = document.getElementById('logoutBtn');
                                lb.disabled = true;
                                lb.querySelector('.logout-idle').classList.add('d-none');
                                lb.querySelector('.logout-loading').classList.remove('d-none');
                            });
                        })();
                    </script>

                </div>
            </div>
        </div>
    </div>
@endsection
