@extends('affiliate.layout')
@section('title', 'Program Affiliate Mooda — Ajak Bisnis Go-Digital, Dapat Komisi')

@php
    $flat = $commissionType !== 'percent';
    $komisiText = $flat
        ? 'Rp ' . number_format($commissionValue, 0, ',', '.')
        : number_format($commissionValue, 0, ',', '.') . '%';
    $per = $flat ? '/ referral' : 'dari langganan';
    $sim = fn ($n) => $flat ? 'Rp ' . number_format($commissionValue * $n, 0, ',', '.') : $n . ' × ' . $komisiText;
@endphp

@section('content')
    {{-- ================= HERO ================= --}}
    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950"></div>
        <div class="absolute inset-0 opacity-30" style="background-image:radial-gradient(circle at 12% 15%, #6366f1 0, transparent 42%),radial-gradient(circle at 88% 8%, #10b981 0, transparent 40%);"></div>
        <div class="relative max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-20 sm:py-28">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-sm font-semibold backdrop-blur mb-5">
                        <span class="w-2 h-2 rounded-full bg-gradient-to-r from-indigo-400 to-emerald-400"></span> Program Affiliate Mooda — Gratis
                    </span>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.05] tracking-tight mb-5">
                        Rekomendasikan Mooda, <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-emerald-400">dapat komisi.</span>
                    </h1>
                    <p class="text-lg sm:text-xl text-slate-300 max-w-xl mb-8">
                        Bagikan link referralmu ke pemilik warung, cafe, & resto. Setiap bisnis yang berlangganan Mooda lewat kodemu — kamu dapat komisi. Tanpa modal, tanpa perlu jadi pengguna POS.
                    </p>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('affiliate.register') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-8 py-4 text-base font-bold text-white shadow-xl shadow-indigo-900/40 hover:bg-indigo-700 transition">Gabung Sekarang — Gratis</a>
                        <a href="{{ route('affiliate.login') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-8 py-4 text-base font-semibold text-white backdrop-blur hover:bg-white/20 transition">Masuk</a>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-8 text-sm text-slate-400">
                        <span class="flex items-center gap-2">✅ Daftar 1 menit</span>
                        <span class="flex items-center gap-2">✅ Tanpa biaya</span>
                        <span class="flex items-center gap-2">✅ Lacak komisi real-time</span>
                    </div>
                </div>
                {{-- Kartu komisi mengambang --}}
                <div class="relative lg:justify-self-end w-full max-w-md">
                    <div class="rounded-3xl bg-white/10 border border-white/20 backdrop-blur p-6 shadow-2xl">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm text-slate-300">Komisi kamu</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">● Live</span>
                        </div>
                        <div class="text-4xl font-black mb-1">{{ $komisiText }} <span class="text-lg font-semibold text-slate-300">{{ $per }}</span></div>
                        <div class="text-sm text-slate-400 mb-5">untuk setiap bisnis yang berlangganan</div>
                        <div class="space-y-3">
                            @foreach ([['🏪', 'Warung Bu Sri', 'baru berlangganan'], ['☕', 'Kopi Senja', 'baru berlangganan'], ['🍜', 'Mie Ayam Pak Budi', 'baru berlangganan']] as [$e, $n, $s])
                                <div class="flex items-center gap-3 rounded-xl bg-white/5 px-4 py-3">
                                    <span class="text-2xl">{{ $e }}</span>
                                    <div class="flex-1"><div class="font-semibold text-sm">{{ $n }}</div><div class="text-xs text-slate-400">{{ $s }}</div></div>
                                    <span class="text-emerald-300 font-bold text-sm">+{{ $komisiText }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= BENEFITS ================= --}}
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-16 sm:py-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ([
                ['💸', 'Komisi menarik', 'Dapat komisi nyata setiap referral berhasil berlangganan.'],
                ['🎁', '100% gratis', 'Tak ada biaya pendaftaran. Tak perlu jadi pengguna POS.'],
                ['📊', 'Transparan', 'Pantau siapa yang pakai kodemu & status komisi di dashboard.'],
                ['🚀', 'Produk laris', 'Mooda dibutuhkan jutaan UMKM — mudah direkomendasikan.'],
            ] as [$icon, $title, $desc])
                <div class="rounded-2xl border border-slate-200 p-7 hover:border-indigo-200 hover:shadow-xl hover:shadow-indigo-100 transition">
                    <div class="text-3xl mb-4">{{ $icon }}</div>
                    <h3 class="font-bold text-lg text-slate-900 mb-1.5">{{ $title }}</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ================= CARA KERJA ================= --}}
    <section class="bg-slate-50 border-y border-slate-100">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-16 sm:py-20">
            <div class="text-center mb-12">
                <span class="text-sm font-bold uppercase tracking-wider text-indigo-600">Cara Kerja</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2">Tiga langkah, mulai hari ini</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ([
                    ['1', 'Daftar gratis', 'Buat akun afiliator dalam 1 menit — cukup nama, email, & nomor WA.'],
                    ['2', 'Bagikan link', 'Dapat link referral unik. Sebarkan lewat WhatsApp, medsos, atau komunitas.'],
                    ['3', 'Dapat komisi', 'Setiap bisnis berlangganan lewat linkmu, komisi tercatat di dashboard.'],
                ] as [$n, $title, $desc])
                    <div class="relative rounded-2xl bg-white border border-slate-200 p-8">
                        <div class="w-12 h-12 grid place-items-center rounded-xl bg-gradient-to-br from-indigo-600 to-blue-600 text-white font-black text-xl mb-5">{{ $n }}</div>
                        <h3 class="font-bold text-lg text-slate-900 mb-2">{{ $title }}</h3>
                        <p class="text-slate-500 leading-relaxed">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= SIMULASI KOMISI ================= --}}
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-16 sm:py-20">
        <div class="text-center mb-12">
            <span class="text-sm font-bold uppercase tracking-wider text-indigo-600">Simulasi</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mt-2">Makin banyak diajak, makin besar komisimu</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-4xl mx-auto">
            @foreach ([['5 bisnis', 5], ['10 bisnis', 10], ['25 bisnis', 25]] as [$label, $n])
                <div class="rounded-2xl border-2 {{ $n === 10 ? 'border-indigo-500 shadow-xl shadow-indigo-100' : 'border-slate-200' }} p-7 text-center relative">
                    @if ($n === 10)<span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-xs font-bold px-3 py-1 rounded-full">Populer</span>@endif
                    <div class="text-slate-500 font-semibold mb-2">{{ $label }} berlangganan</div>
                    <div class="text-3xl font-black text-indigo-600">{{ $sim($n) }}</div>
                    <div class="text-xs text-slate-400 mt-1">total komisi</div>
                </div>
            @endforeach
        </div>
        <p class="text-center text-slate-400 text-sm mt-6">*Ilustrasi. Komisi {{ $komisiText }} {{ $per }} (dapat berubah sesuai ketentuan).</p>
    </section>

    {{-- ================= FAQ ================= --}}
    <section class="bg-slate-50 border-t border-slate-100">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-16 sm:py-20">
            <h2 class="text-3xl font-extrabold text-slate-900 text-center mb-10">Pertanyaan umum</h2>
            <div class="space-y-3">
                @foreach ([
                    ['Apakah harus punya usaha / pakai POS Mooda?', 'Tidak. Siapa saja bisa jadi afiliator — cukup daftar, bagikan link. Kalau kamu sudah pakai Mooda POS, kamu bisa gabung langsung dari dashboard POS-mu.'],
                    ['Bagaimana komisi dibayarkan?', 'Komisi tercatat otomatis saat bisnis yang kamu ajak berlangganan. Pencairan diproses oleh tim Mooda sesuai info rekening yang kamu berikan.'],
                    ['Berapa lama link referral berlaku?', 'Saat calon pelanggan klik linkmu, kodemu tersimpan hingga 30 hari — jadi tetap terhitung meski mereka daftar beberapa hari kemudian.'],
                    ['Apakah ada batas jumlah referral?', 'Tidak ada batas. Semakin banyak bisnis yang kamu ajak, semakin besar komisimu.'],
                ] as [$q, $a])
                    <details class="group rounded-2xl bg-white border border-slate-200 p-5 open:shadow-md">
                        <summary class="flex items-center justify-between cursor-pointer font-bold text-slate-900 list-none">
                            {{ $q }}
                            <span class="text-indigo-600 group-open:rotate-45 transition text-2xl leading-none">+</span>
                        </summary>
                        <p class="text-slate-500 mt-3 leading-relaxed">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= CTA ================= --}}
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-16 sm:py-20">
        <div class="rounded-3xl bg-gradient-to-br from-indigo-600 to-emerald-500 px-8 py-14 sm:px-16 text-white text-center">
            <h2 class="text-3xl sm:text-4xl font-extrabold mb-3">Mulai dapat penghasilan tambahan hari ini</h2>
            <p class="text-indigo-50 text-lg mb-8 max-w-xl mx-auto">Gabung program afiliasi Mooda gratis. Daftar sekarang, bagikan linkmu, & dapat komisi.</p>
            <a href="{{ route('affiliate.register') }}" class="inline-flex bg-white text-indigo-700 font-bold rounded-xl px-10 py-4 text-lg hover:bg-indigo-50 transition">Daftar jadi Affiliate →</a>
            <p class="text-indigo-100 text-sm mt-4">Sudah pakai Mooda POS? <a href="https://mooda.id" class="underline font-semibold">Gabung dari dashboard POS-mu</a>.</p>
        </div>
    </section>
@endsection
