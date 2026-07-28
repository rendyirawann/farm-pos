@extends('affiliate.layout')
@section('title', 'Masuk Affiliate — Mooda')

@section('content')
    <div class="max-w-md mx-auto px-4 sm:px-6 py-12 sm:py-16">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-slate-900">Masuk Affiliate</h1>
            <p class="text-slate-500 mt-2">Kelola referral & komisimu.</p>
        </div>

        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 mb-5">{{ session('status') }}</div>
        @endif

        <div class="rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xl shadow-slate-200/60">
            <form method="POST" action="{{ route('affiliate.login.post') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none">
                    @error('email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Password</label>
                        <a href="{{ route('affiliate.password.request') }}" class="text-xs font-semibold text-indigo-600 hover:underline">Lupa kata sandi?</a>
                    </div>
                    <div class="relative">
                        <input type="password" name="password" required data-pw
                            class="w-full rounded-xl border border-slate-200 px-4 py-2.5 pr-11 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none">
                        <button type="button" onclick="affTogglePw(this)" tabindex="-1"
                            class="absolute right-2 top-1/2 -translate-y-1/2 grid place-items-center w-8 h-8 rounded-lg text-slate-400 hover:text-indigo-600" aria-label="Lihat kata sandi">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <svg class="w-5 h-5 eye-off hidden" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.88 9.88"/></svg>
                        </button>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Ingat saya
                </label>
                <button type="submit" id="aff-login-btn" class="w-full rounded-xl bg-indigo-600 text-white font-semibold py-3 hover:bg-indigo-700 shadow-lg shadow-indigo-600/25 transition disabled:opacity-70">Masuk</button>
            </form>
        </div>
        <p class="text-center text-slate-500 text-sm mt-5">Belum punya akun?
            <a href="{{ route('affiliate.register') }}" class="text-indigo-600 font-semibold hover:underline">Daftar gratis</a>
        </p>
    </div>

    <style>@keyframes affspin{to{transform:rotate(360deg)}}</style>
    <script>
        function affTogglePw(btn) {
            var input = btn.parentElement.querySelector('input[data-pw]');
            var open = btn.querySelector('.eye-open'), off = btn.querySelector('.eye-off');
            if (input.type === 'password') { input.type = 'text'; open.classList.add('hidden'); off.classList.remove('hidden'); }
            else { input.type = 'password'; off.classList.add('hidden'); open.classList.remove('hidden'); }
        }
        (function () {
            var form = document.querySelector('form[action="{{ route('affiliate.login.post') }}"]');
            var btn = document.getElementById('aff-login-btn');
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
