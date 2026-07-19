@extends('affiliate.layout')
@section('title', 'Program Affiliate Mooda — Bagikan Mooda, Dapatkan Komisi Setiap Bulan')

@php
    $flat = $commissionType !== 'percent';
    $komisiText = $flat
        ? 'Rp ' . number_format($commissionValue, 0, ',', '.')
        : 'hingga ' . number_format($commissionValue, 0, ',', '.') . '%';
    $komisiStat = $flat ? 'Rp ' . number_format($commissionValue, 0, ',', '.') : number_format($commissionValue, 0, ',', '.') . '%';
@endphp

@section('content')
    {{-- ================= HERO ================= --}}
    <section class="relative overflow-hidden">
        <div class="absolute -top-24 -right-24 w-[520px] h-[520px] rounded-full bg-indigo-100/60 blur-3xl -z-10"></div>
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 pt-14 pb-16 lg:pt-20 lg:pb-24 grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 text-indigo-600 px-4 py-1.5 text-sm font-bold mb-6">⭐ PROGRAM AFFILIATE</span>
                <h1 class="text-4xl sm:text-5xl lg:text-[3.4rem] font-extrabold leading-[1.1] tracking-tight text-slate-900">
                    Bagikan Mooda,<br>Dapatkan <span class="text-indigo-600">Komisi</span><br>Setiap Bulan
                </h1>
                <p class="text-lg text-slate-500 leading-relaxed mt-6 max-w-lg">
                    Promosikan Mooda POS ke audiens Anda dan dapatkan komisi <span class="font-semibold text-indigo-600">{{ $komisiText }}</span>.
                </p>
                <div class="flex flex-wrap gap-3 mt-8">
                    <a href="{{ route('affiliate.register') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl px-6 py-3.5 shadow-lg shadow-indigo-600/25 transition">
                        Daftar Gratis <span class="grid place-items-center w-6 h-6 rounded-full bg-white/20">→</span>
                    </a>
                    <a href="#cara-kerja" class="inline-flex items-center gap-2 bg-white border border-slate-200 text-slate-700 hover:border-indigo-300 hover:text-indigo-700 font-semibold rounded-xl px-6 py-3.5 transition">
                        Lihat Cara Kerja <span class="grid place-items-center w-6 h-6 rounded-full bg-indigo-50 text-indigo-600">▶</span>
                    </a>
                </div>
                <div class="flex items-center gap-3 mt-8">
                    <div class="flex -space-x-2">
                        @foreach (['from-indigo-400 to-blue-500','from-emerald-400 to-teal-500','from-rose-400 to-pink-500','from-amber-400 to-orange-500'] as $g)
                            <span class="w-9 h-9 rounded-full ring-2 ring-white bg-gradient-to-br {{ $g }}"></span>
                        @endforeach
                    </div>
                    <span class="text-sm text-slate-500">Bergabung dengan <b class="text-slate-700">1.200+</b> affiliate lainnya</span>
                </div>
            </div>
            {{-- Placeholder "dashboard affiliate" (device mockup) --}}
            <div class="relative lg:justify-self-end w-full">
                <div class="rounded-2xl bg-white border border-slate-200 shadow-2xl shadow-indigo-200/40 overflow-hidden rotate-1">
                    <div class="flex items-center gap-1.5 px-4 py-3 border-b border-slate-100 bg-slate-50">
                        <span class="w-3 h-3 rounded-full bg-rose-300"></span><span class="w-3 h-3 rounded-full bg-amber-300"></span><span class="w-3 h-3 rounded-full bg-emerald-300"></span>
                        <span class="ml-3 text-xs text-slate-400 font-medium">Affiliate Dashboard</span>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            @foreach ([['Total Komisi','Rp 12.450.000','text-indigo-600'],['Klik','18.230','text-slate-800'],['Konversi','320','text-emerald-600']] as [$l,$v,$c])
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <div class="text-[11px] text-slate-400">{{ $l }}</div>
                                    <div class="font-black text-sm {{ $c }}">{{ $v }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-xs text-slate-400 mb-2">Performa</div>
                            <div class="flex items-end gap-1.5 h-20">
                                @foreach ([40,55,45,70,60,85,75,95,80,100] as $h)
                                    <span class="flex-1 rounded-t bg-gradient-to-t from-indigo-500 to-indigo-300" style="height: {{ $h }}%"></span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= 3 FITUR ================= --}}
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10">
        <div class="rounded-3xl bg-white border border-slate-100 shadow-xl shadow-slate-100 grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100 overflow-hidden">
            @foreach ([
                ['💼','bg-indigo-50 text-indigo-600','Komisi ' . $komisiText, 'Dapatkan komisi menarik setiap bulan.'],
                ['🔗','bg-sky-50 text-sky-600','Tracking Real-time', 'Pantau klik, konversi, dan komisi secara real-time.'],
                ['💳','bg-emerald-50 text-emerald-600','Pembayaran Tepat Waktu', 'Komisi dibayarkan otomatis setiap bulan.'],
            ] as [$icon,$tint,$title,$desc])
                <div class="flex items-start gap-4 p-8">
                    <span class="grid place-items-center w-12 h-12 rounded-2xl text-2xl shrink-0 {{ $tint }}">{{ $icon }}</span>
                    <div>
                        <h3 class="font-bold text-slate-900 mb-1">{{ $title }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ $desc }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ================= CARA KERJA ================= --}}
    <section id="cara-kerja" class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-16 sm:py-20">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 text-center mb-14">Cara Kerja</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 md:gap-4">
            @foreach ([
                ['1','👤','Daftar','Daftar gratis sebagai affiliate Mooda.'],
                ['2','🔗','Bagikan Link','Bagikan link affiliate Anda ke audiens.'],
                ['3','🛒','Dapatkan Konversi','Setiap kali ada yang berlangganan melalui link Anda.'],
                ['4','👛','Terima Komisi','Dapatkan komisi otomatis setiap bulan.'],
            ] as $i => [$n,$icon,$title,$desc])
                <div class="relative text-center">
                    <div class="relative inline-grid place-items-center mb-5">
                        <span class="grid place-items-center w-20 h-20 rounded-2xl bg-indigo-50 text-3xl">{{ $icon }}</span>
                        <span class="absolute -top-2 -left-2 grid place-items-center w-8 h-8 rounded-full bg-indigo-600 text-white font-black text-sm ring-4 ring-white">{{ $n }}</span>
                    </div>
                    <h3 class="font-bold text-slate-900 mb-1">{{ $title }}</h3>
                    <p class="text-sm text-slate-500 leading-relaxed max-w-[220px] mx-auto">{{ $desc }}</p>
                    @if (! $loop->last)
                        <span class="hidden md:block absolute top-10 -right-2 text-indigo-300 text-2xl">→</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- ================= POTENSI PENGHASILAN ================= --}}
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 pb-4">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 text-center mb-12">Potensi Penghasilan Anda</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ([
                ['📈','bg-indigo-50','Komisi Hingga', $komisiStat, 'Setiap Langganan','text-indigo-600'],
                ['👥','bg-emerald-50','', '1.200+', 'Affiliate Aktif','text-slate-900'],
                ['💰','bg-amber-50','', 'Rp 2 Miliar+', 'Total Komisi Dibayarkan','text-indigo-600'],
            ] as [$icon,$tint,$top,$big,$sub,$color])
                <div class="rounded-3xl border border-slate-100 shadow-lg shadow-slate-100 p-8 flex items-center gap-5">
                    <span class="grid place-items-center w-16 h-16 rounded-2xl text-3xl shrink-0 {{ $tint }}">{{ $icon }}</span>
                    <div>
                        @if ($top)<div class="text-sm text-slate-500 font-semibold">{{ $top }}</div>@endif
                        <div class="text-3xl font-black {{ $color }} leading-tight">{{ $big }}</div>
                        <div class="text-sm text-slate-500">{{ $sub }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ================= CTA BANNER ================= --}}
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-16 sm:py-20">
        <div class="rounded-3xl bg-gradient-to-br from-indigo-600 to-indigo-500 px-8 py-12 sm:px-14 text-white flex flex-col lg:flex-row items-center gap-8">
            <div class="text-7xl shrink-0">📣</div>
            <div class="flex-1 text-center lg:text-left">
                <h2 class="text-3xl sm:text-4xl font-extrabold leading-tight mb-2">Mulai Hasilkan<br class="hidden sm:block"> Bersama Mooda!</h2>
                <p class="text-indigo-100 max-w-md">Gabung sekarang dan mulai dapatkan komisi dari setiap langganan.</p>
            </div>
            <a href="{{ route('affiliate.register') }}" class="inline-flex items-center gap-2 bg-white text-indigo-700 font-bold rounded-xl px-8 py-4 text-lg hover:bg-indigo-50 transition shrink-0">
                Daftar Affiliate Gratis <span class="grid place-items-center w-7 h-7 rounded-full bg-indigo-100 text-indigo-700">→</span>
            </a>
        </div>
    </section>
@endsection
