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
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                    <input type="password" name="password" required
                        class="w-full rounded-xl border border-slate-200 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Ingat saya
                </label>
                <button type="submit" class="w-full rounded-xl bg-indigo-600 text-white font-semibold py-3 hover:bg-indigo-700 shadow-lg shadow-indigo-600/25 transition">Masuk</button>
            </form>
        </div>
        <p class="text-center text-slate-500 text-sm mt-5">Belum punya akun?
            <a href="{{ route('affiliate.register') }}" class="text-indigo-600 font-semibold hover:underline">Daftar gratis</a>
        </p>
    </div>
@endsection
