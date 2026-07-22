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

                    {{-- Status --}}
                    @if (session('status') == 'verification-link-sent')
                        <div class="alert alert-success d-flex align-items-center p-4 mb-8 rounded-3 w-100" role="alert">
                            <i class="ki-outline ki-shield-tick fs-2hx text-success me-4"></i>
                            <div class="d-flex flex-column">
                                <h5 class="mb-1 fw-bold">Link Aktivasi Terkirim!</h5>
                                <span class="fs-6">Link aktivasi baru sudah dikirim ke email Anda.</span>
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
                        {{-- Kirim ulang --}}
                        <form method="POST" action="{{ route('verification.send') }}" class="flex-fill">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ki-outline ki-sms fs-4 me-2"></i>Kirim Ulang Link
                            </button>
                        </form>

                        {{-- Logout --}}
                        <form method="POST" action="{{ route('logout') }}" class="flex-fill">
                            @csrf
                            <button type="submit" class="btn btn-light w-100">
                                <i class="ki-outline ki-exit-right fs-4 me-2"></i>Keluar
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
