@extends('auth.app')
@section('title', 'Daftar')

@section('content')
    <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-start p-8 p-lg-12">

        <div class="bg-body d-flex flex-column flex-center rounded-4 w-md-600px p-8 p-lg-10 shadow-lg">

            <div class="d-flex flex-center flex-column align-items-stretch h-lg-100 w-100 w-md-450px">

                <div class="d-flex flex-center flex-column flex-column-fluid mb-2">
                    <img alt="Logo" class="h-40px h-lg-90px" src="{{ asset('assets/media/logos/mooda-logo.png') }}" />
                </div>

                <div class="d-flex flex-center flex-column flex-column-fluid py-6">

                    <form class="form w-100" method="POST" action="{{ route('register') }}">
                        @csrf

                        @php
                            // Form pendaftaran menyesuaikan VERTICAL host (mooda.id = F&B, laundry.mooda.id = Laundry).
                            $vReg   = \App\Verticals\VerticalRegistry::current();
                            $isLdry = $vReg === 'laundry';
                            $regTitle = $isLdry ? 'Daftar Akun Laundry' : 'Daftar Akun Bisnis';
                            $bizLabel = $isLdry ? 'Nama Usaha Laundry' : 'Nama Bisnis / Restoran';
                            $bizPlace = $isLdry ? 'cth: Laundry Bersih Wangi' : 'cth: Warung Sederhana';
                            $bizTypes = $isLdry
                                ? ['Laundry Kiloan', 'Laundry Satuan', 'Dry Clean', 'Laundry Express', 'Lainnya']
                                : ['Restoran', 'Cafe', 'Warung', 'Bakery', 'Bar', 'Catering', 'Lainnya'];
                        @endphp

                        <div class="text-center mb-6">
                            <h1 class="text-gray-900 fw-bolder mb-2">{{ $regTitle }}</h1>
                            <div class="text-gray-500 fw-semibold fs-6">Buat akun & data usaha Anda. Aktifkan langganan untuk mulai memakai sistem.</div>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger d-flex align-items-center p-4 mb-6">
                                <i class="ki-outline ki-information-5 fs-2 text-danger me-3"></i>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold">Periksa kembali isian Anda:</span>
                                    <ul class="mb-0 mt-1 ps-4 fs-7">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Nama Bisnis --}}
                        <div class="fv-row mb-4">
                            <label class="form-label fw-semibold required">{{ $bizLabel }}</label>
                            <input type="text" name="business_name" value="{{ old('business_name') }}"
                                class="form-control bg-transparent" placeholder="{{ $bizPlace }}" />
                            @error('business_name')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Kategori Usaha: khusus F&B (menentukan sistem kas: Resto/Cafe = Shift, UMKM = Kas Harian).
                             Laundry tak memakai kategori ini -> dikirim sebagai 'resto' (Shift kasir) secara diam-diam. --}}
                        @if ($isLdry)
                            <input type="hidden" name="category" value="resto">
                        @else
                            <div class="fv-row mb-4">
                                <label class="form-label fw-semibold required">Kategori Usaha</label>
                                <div class="d-flex gap-2">
                                    @php $curCat = old('category', 'resto'); @endphp
                                    @foreach (['resto' => 'Resto', 'cafe' => 'Cafe', 'umkm' => 'UMKM'] as $val => $label)
                                        <input type="radio" class="btn-check" name="category" value="{{ $val }}" id="cat-{{ $val }}" @checked($curCat === $val)>
                                        <label class="btn btn-outline btn-outline-dashed btn-active-light-primary flex-fill py-3 fw-bold" for="cat-{{ $val }}">{{ $label }}</label>
                                    @endforeach
                                </div>
                                <div class="form-text">Resto &amp; Cafe pakai Shift kasir; UMKM pakai Kas Harian (lebih simpel).</div>
                                @error('category')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                            </div>
                        @endif

                        {{-- Jenis Bisnis: khusus F&B. Untuk LAUNDRY tidak ditanyakan — jenis layanan
                             (kiloan/satuan/express/dll) dikelola sendiri di Data Master > Layanan
                             dan bisa ditambah sebanyak apa pun oleh tenant. --}}
                        @if ($isLdry)
                            <input type="hidden" name="business_type" value="Laundry">
                        @else
                            <div class="fv-row mb-4">
                                <label class="form-label fw-semibold">Jenis Bisnis</label>
                                <select name="business_type" class="form-select bg-transparent">
                                    @foreach ($bizTypes as $type)
                                        <option value="{{ $type }}" @selected(old('business_type') === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('business_type')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                            </div>
                        @endif

                        {{-- Nama Pemilik --}}
                        <div class="fv-row mb-4">
                            <label class="form-label fw-semibold required">Nama Pemilik</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                class="form-control bg-transparent" placeholder="Nama lengkap Anda" />
                            @error('name')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- No WhatsApp --}}
                        <div class="fv-row mb-4">
                            <label class="form-label fw-semibold">No. WhatsApp / Telepon</label>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                class="form-control bg-transparent" placeholder="08xxxxxxxxxx" />
                            @error('phone')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Email --}}
                        <div class="fv-row mb-4">
                            <label class="form-label fw-semibold required">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                autocapitalize="off" autocorrect="off" autocomplete="email" spellcheck="false"
                                oninput="this.value=this.value.toLowerCase()"
                                class="form-control bg-transparent" placeholder="email@bisnis.com" />
                            @error('email')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Password --}}
                        <div class="fv-row mb-4" data-kt-password-meter="true">
                            <label class="form-label fw-semibold required">Password</label>
                            <div class="position-relative">
                                <input type="password" name="password" autocomplete="new-password"
                                    class="form-control bg-transparent" id="regPassword" placeholder="Minimal 8 karakter" />
                                <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2" id="regTogglePassword">
                                    <i class="ki-outline ki-eye-slash fs-2" id="regToggleIcon"></i>
                                </span>
                            </div>
                            @error('password')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Konfirmasi Password --}}
                        <div class="fv-row mb-6">
                            <label class="form-label fw-semibold required">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" autocomplete="new-password"
                                class="form-control bg-transparent" placeholder="Ulangi password" />
                        </div>

                        {{-- Kode Referral (opsional) — terisi otomatis dari link referral, atau isi manual --}}
                        <div class="fv-row mb-6">
                            <label class="form-label fw-semibold">Kode Referral <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="text" name="ref" value="{{ old('ref', $ref ?? '') }}"
                                class="form-control bg-transparent text-uppercase" placeholder="cth: RENDYENKW" style="text-transform:uppercase" />
                            <div class="form-text">Punya kode dari teman/affiliate? Isi di sini. Otomatis terisi bila Anda datang lewat link referral.</div>
                        </div>

                        <div class="d-grid mb-6">
                            <button type="submit" class="btn btn-primary" id="regSubmitBtn">
                                <span class="reg-idle">Daftar &amp; Lanjut Bayar</span>
                                <span class="reg-loading d-none"><span class="spinner-border spinner-border-sm align-middle me-2"></span>Memproses…</span>
                            </button>
                        </div>

                        <div class="text-gray-500 text-center fw-semibold fs-6 mb-4">
                            Sudah punya akun?
                            <a href="{{ route('login') }}" class="link-primary fw-bold">Masuk di sini</a>
                        </div>

                        {{-- KUSTOMISASI farm.mooda.id: tanpa landing page, jadi "Kembali ke Beranda"
                             hanya akan berputar ke halaman ini sendiri -> dihapus. --}}
                    </form>

                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const regToggle = document.querySelector('#regTogglePassword');
            const regPass = document.querySelector('#regPassword');
            const regIcon = document.querySelector('#regToggleIcon');
            if (regToggle && regPass) {
                regToggle.addEventListener('click', function () {
                    const type = regPass.getAttribute('type') === 'password' ? 'text' : 'password';
                    regPass.setAttribute('type', type);
                    regIcon.classList.toggle('ki-eye-slash', type === 'password');
                    regIcon.classList.toggle('ki-eye', type === 'text');
                });
            }

            // Loader tombol daftar (anti klik 2x). Hanya jalan bila validasi HTML5 lolos.
            var regBtn = document.getElementById('regSubmitBtn');
            if (regBtn && regBtn.closest('form')) {
                regBtn.closest('form').addEventListener('submit', function () {
                    regBtn.disabled = true;
                    regBtn.querySelector('.reg-idle').classList.add('d-none');
                    regBtn.querySelector('.reg-loading').classList.remove('d-none');
                });
            }
        </script>
    @endpush
@endsection
