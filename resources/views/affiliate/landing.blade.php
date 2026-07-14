@extends('affiliate.layout')
@section('title', 'Program Affiliate Mooda — Ajak Bisnis Go-Digital, Dapat Komisi')

@php
    $komisi = $commissionType === 'percent'
        ? number_format($commissionValue, 0, ',', '.') . '% dari langganan'
        : 'Rp ' . number_format($commissionValue, 0, ',', '.') . ' / referral';
@endphp

@section('content')
    {{-- HERO --}}
    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950"></div>
        <div class="absolute inset-0 opacity-30" style="background-image:radial-gradient(circle at 15% 20%, #6366f1 0, transparent 40%),radial-gradient(circle at 85% 10%, #10b981 0, transparent 38%);"></div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-20 sm:py-28 text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-sm font-semibold backdrop-blur mb-5">
                <span class="w-2 h-2 rounded-full bg-gradient-to-r from-indigo-400 to-emerald-400"></span> Program Affiliate Mooda
            </span>
            <h1 class="text-4xl sm:text-6xl font-extrabold leading-[1.05] tracking-tight mb-5 max-w-3xl mx-auto">
                Bagikan link, ajak bisnis go-digital, <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-emerald-400">dapat komisi.</span>
            </h1>
            <p class="text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto mb-8">
                Rekomendasikan Mooda ke pemilik warung, cafe, & resto. Setiap yang berlangganan lewat kodemu, kamu dapat komisi. Gratis, tanpa perlu jadi pengguna POS.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('affiliate.register') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-7 py-3.5 text-base font-semibold text-white shadow-xl shadow-indigo-900/40 hover:bg-indigo-700 transition">Gabung Sekarang</a>
                <a href="{{ route('affiliate.login') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-7 py-3.5 text-base font-semibold text-white backdrop-blur hover:bg-white/20 transition">Masuk</a>
            </div>
            <div class="mt-8 inline-flex items-center gap-2 text-sm text-slate-300">
                <span class="text-indigo-300 font-bold">Komisi:</span> {{ $komisi }}
            </div>
        </div>
    </section>

    {{-- CARA KERJA --}}
    <section class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
        <div class="text-center mb-12">
            <span class="text-sm font-bold uppercase tracking-wider text-indigo-600">Cara Kerja</span>
            <h2 class="text-3xl font-extrabold text-slate-900 mt-2">Tiga langkah, mulai hari ini</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @php($steps = [
                ['1', 'Daftar gratis', 'Buat akun afiliator dalam 1 menit — cukup nama, email, & nomor WA. Tak perlu jadi pengguna POS.', 'ki-user-tick'],
                ['2', 'Bagikan kodemu', 'Dapat link referral unik. Sebarkan ke pemilik usaha lewat WhatsApp, medsos, atau komunitas.', 'ki-share'],
                ['3', 'Dapat komisi', 'Setiap bisnis yang berlangganan Mooda lewat linkmu, kamu dapat komisi. Lacak semua di dashboard.', 'ki-wallet'],
            ])
            @foreach ($steps as [$n, $title, $desc, $icon])
                <div class="rounded-2xl border border-slate-200 p-8 hover:border-indigo-200 hover:shadow-xl hover:shadow-indigo-100 transition">
                    <div class="w-12 h-12 grid place-items-center rounded-xl bg-gradient-to-br from-indigo-600 to-blue-600 text-white font-black text-xl mb-5">{{ $n }}</div>
                    <h3 class="font-bold text-lg text-slate-900 mb-2">{{ $title }}</h3>
                    <p class="text-slate-500 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="max-w-6xl mx-auto px-4 sm:px-6 pb-20">
        <div class="rounded-3xl bg-gradient-to-br from-indigo-600 to-emerald-500 px-8 py-12 sm:px-14 sm:py-14 text-white text-center">
            <h2 class="text-3xl sm:text-4xl font-extrabold mb-3">Siap dapat penghasilan tambahan?</h2>
            <p class="text-indigo-50 text-lg mb-7 max-w-xl mx-auto">Gabung program afiliasi Mooda gratis. Semakin banyak yang kamu ajak, semakin besar komisimu.</p>
            <a href="{{ route('affiliate.register') }}" class="inline-flex bg-white text-indigo-700 font-bold rounded-xl px-8 py-3.5 hover:bg-indigo-50 transition">Daftar jadi Affiliate →</a>
        </div>
    </section>
@endsection
