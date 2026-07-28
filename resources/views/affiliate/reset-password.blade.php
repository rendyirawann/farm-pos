@extends('affiliate.layout')
@section('title', 'Atur Ulang Kata Sandi — Mooda Affiliate')

@section('content')
    <div class="max-w-md mx-auto px-4 sm:px-6 py-12 sm:py-16">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-slate-900">Atur Ulang Kata Sandi</h1>
            <p class="text-slate-500 mt-2">Buat kata sandi baru untuk akun affiliate-mu.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xl shadow-slate-200/60">
            <form method="POST" action="{{ route('affiliate.password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email', $email) }}" required
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 bg-slate-50 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none">
                    @error('email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Kata Sandi Baru</label>
                    <div class="relative">
                        <input type="password" name="password" required data-pw
                            class="w-full rounded-xl border border-slate-200 px-4 py-2.5 pr-11 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none">
                        <button type="button" onclick="affTogglePw(this)" tabindex="-1"
                            class="absolute right-2 top-1/2 -translate-y-1/2 grid place-items-center w-8 h-8 rounded-lg text-slate-400 hover:text-indigo-600" aria-label="Lihat kata sandi">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <svg class="w-5 h-5 eye-off hidden" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.88 9.88"/></svg>
                        </button>
                    </div>
                    @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Ulangi Kata Sandi</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" required data-pw
                            class="w-full rounded-xl border border-slate-200 px-4 py-2.5 pr-11 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none">
                        <button type="button" onclick="affTogglePw(this)" tabindex="-1"
                            class="absolute right-2 top-1/2 -translate-y-1/2 grid place-items-center w-8 h-8 rounded-lg text-slate-400 hover:text-indigo-600" aria-label="Lihat kata sandi">
                            <svg class="w-5 h-5 eye-open" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <svg class="w-5 h-5 eye-off hidden" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.88 9.88"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-xl bg-indigo-600 text-white font-semibold py-3 hover:bg-indigo-700 shadow-lg shadow-indigo-600/25 transition">Simpan Kata Sandi Baru</button>
            </form>
        </div>
        <p class="text-center text-slate-500 text-sm mt-5">
            <a href="{{ route('affiliate.login') }}" class="text-indigo-600 font-semibold hover:underline">← Kembali ke Masuk</a>
        </p>
    </div>

    <script>
        function affTogglePw(btn) {
            var input = btn.parentElement.querySelector('input[data-pw]');
            var open = btn.querySelector('.eye-open'), off = btn.querySelector('.eye-off');
            if (input.type === 'password') { input.type = 'text'; open.classList.add('hidden'); off.classList.remove('hidden'); }
            else { input.type = 'password'; off.classList.add('hidden'); open.classList.remove('hidden'); }
        }
    </script>
@endsection
