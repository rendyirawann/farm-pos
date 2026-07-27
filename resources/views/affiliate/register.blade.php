@extends('affiliate.layout')
@section('title', 'Daftar Affiliate — Mooda')

@section('content')
    <div class="max-w-md mx-auto px-4 sm:px-6 py-12 sm:py-16">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-slate-900">Daftar jadi Affiliate</h1>
            <p class="text-slate-500 mt-2">Gratis. Mulai dapat komisi hari ini.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xl shadow-slate-200/60">
            <form method="POST" action="{{ route('affiliate.register.post') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none">
                    @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none">
                    @error('email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">No. WhatsApp <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                    <input type="password" name="password" required
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none">
                    @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Ulangi Password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none">
                </div>
                <button type="submit" id="aff-register-btn" class="w-full rounded-xl bg-indigo-600 text-white font-semibold py-3 hover:bg-indigo-700 shadow-lg shadow-indigo-600/25 transition disabled:opacity-70">Daftar Gratis</button>
            </form>
        </div>
        <p class="text-center text-slate-500 text-sm mt-5">Sudah punya akun?
            <a href="{{ route('affiliate.login') }}" class="text-indigo-600 font-semibold hover:underline">Masuk di sini</a>
        </p>
    </div>

    <style>@keyframes affspin{to{transform:rotate(360deg)}}</style>
    <script>
        (function () {
            var form = document.querySelector('form[action="{{ route('affiliate.register.post') }}"]');
            var btn = document.getElementById('aff-register-btn');
            if (!form || !btn) return;
            form.addEventListener('submit', function (e) {
                if (typeof form.checkValidity === 'function' && !form.checkValidity()) return; // biarkan validasi HTML5
                if (btn.dataset.loading) { e.preventDefault(); return; }                       // anti klik ganda
                btn.dataset.loading = '1';
                btn.disabled = true;
                btn.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:affspin .6s linear infinite;vertical-align:-3px;margin-right:8px"></span>Memproses…';
            });
        })();
    </script>
@endsection
