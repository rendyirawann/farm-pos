@extends('blog.layout')
@section('title', ($activeCat ? $activeCat->name . ' — ' : '') . 'Blog Mooda — Insight & Inspirasi untuk Bisnis Modern')
@section('meta_description', $activeCat ? ('Artikel kategori ' . $activeCat->name . ' di Blog Mooda.') : 'Insight, tips praktis & berita seputar dunia bisnis, POS, dan manajemen usaha bersama Mooda.')

@php
    // Estimasi waktu baca dari jumlah kata body (~200 kata/menit).
    $readTime = fn ($p) => max(1, (int) ceil(str_word_count(strip_tags($p->body ?? '')) / 200));
    // Warna badge kategori bergilir (biar mirip mockup yang warna-warni).
    $catColors = ['bg-indigo-100 text-indigo-700', 'bg-emerald-100 text-emerald-700', 'bg-rose-100 text-rose-600', 'bg-sky-100 text-sky-700', 'bg-amber-100 text-amber-700', 'bg-violet-100 text-violet-700'];
    $catTint   = ['bg-indigo-50 text-indigo-600', 'bg-emerald-50 text-emerald-600', 'bg-rose-50 text-rose-500', 'bg-sky-50 text-sky-600', 'bg-amber-50 text-amber-600', 'bg-violet-50 text-violet-600'];
    $catIcons  = ['📰','💡','🏷️','📊','📦','✨'];
    $all       = $posts->getCollection();
    $trending  = $all->take(4);
    $popular   = $all->slice(4, 4)->values();
    $spotlight = $all->count() > 8 ? $all->get(8) : $all->first();
@endphp

@section('content')

@if ($activeCat)
    {{-- ============ HALAMAN KATEGORI (list sederhana) ============ --}}
    <section class="bg-gradient-to-b from-indigo-50/70 to-white border-b border-slate-100">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-14">
            <a href="{{ route('blog.home') }}" class="inline-flex items-center gap-1 text-indigo-600 text-sm font-semibold hover:text-indigo-800 mb-4">{{ sc('blog','kat_link_semua','← Semua artikel') }}</a>
            <span class="inline-block rounded-full bg-indigo-100 text-indigo-700 px-4 py-1.5 text-sm font-bold mb-4">{{ sc('blog','kat_badge','Kategori') }}</span>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-slate-900">{{ $activeCat->name }}</h1>
            <p class="text-slate-500 mt-3 text-lg">{{ sc('blog','kat_subjudul','Kumpulan artikel di kategori ini.') }}</p>
        </div>
    </section>
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-12">
        @if ($posts->count())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($posts as $post)
                    @include('blog._card', ['post' => $post])
                @endforeach
            </div>
            @if ($posts->hasPages())
                <div class="flex justify-center items-center gap-2 mt-14">
                    @if (! $posts->onFirstPage())<a href="{{ $posts->previousPageUrl() }}" class="px-5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-indigo-600 hover:text-white text-sm font-semibold transition">← Sebelumnya</a>@endif
                    <span class="px-5 py-2.5 text-slate-500 text-sm font-semibold">Hal {{ $posts->currentPage() }}/{{ $posts->lastPage() }}</span>
                    @if ($posts->hasMorePages())<a href="{{ $posts->nextPageUrl() }}" class="px-5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-indigo-600 hover:text-white text-sm font-semibold transition">Berikutnya →</a>@endif
                </div>
            @endif
        @else
            <div class="text-center py-24 text-slate-500">{{ sc('blog','kat_kosong','Belum ada artikel di kategori ini.') }}</div>
        @endif
    </div>
@else
    {{-- ============ BERANDA BLOG (gaya mockup) ============ --}}

    {{-- ===== HERO ===== --}}
    <section class="relative overflow-hidden">
        <div class="absolute -top-24 -right-24 w-[520px] h-[520px] rounded-full bg-indigo-100/60 blur-3xl -z-10"></div>
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 pt-14 pb-16 lg:pt-20 lg:pb-24 grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="inline-block rounded-full bg-indigo-50 text-indigo-600 px-4 py-1.5 text-sm font-bold mb-6">{{ sc('blog','hero_badge','Blog Resmi Mooda') }}</span>
                <h1 class="text-4xl sm:text-5xl lg:text-[3.4rem] font-extrabold leading-[1.1] tracking-tight text-slate-900">
                    {!! sc('blog','hero_judul','Insight &amp; Inspirasi<br>untuk <span class="text-indigo-600">Bisnis Modern</span>') !!}
                </h1>
                <p class="text-lg text-slate-500 leading-relaxed mt-6 max-w-lg">
                    {{ sc('blog','hero_deskripsi','Dapatkan informasi terbaru, tips praktis, dan berita menarik seputar dunia bisnis, POS, dan manajemen usaha.') }}
                </p>
                <div class="flex flex-wrap gap-3 mt-8">
                    <a href="#trending" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl px-6 py-3.5 shadow-lg shadow-indigo-600/25 transition">
                        {{ sc('blog','hero_tombol_utama','Jelajahi Artikel') }} <span class="grid place-items-center w-6 h-6 rounded-full bg-white/20">→</span>
                    </a>
                    <a href="https://mooda.id" class="inline-flex items-center gap-2 bg-white border border-slate-200 text-slate-700 hover:border-indigo-300 hover:text-indigo-700 font-semibold rounded-xl px-6 py-3.5 transition">
                        {{ sc('blog','hero_tombol_kedua','Tentang Mooda') }} <span class="grid place-items-center w-6 h-6 rounded-full bg-indigo-50 text-indigo-600">→</span>
                    </a>
                </div>
            </div>
            {{-- Preview "browser" dari artikel terbaru (placeholder device) --}}
            <div class="relative">
                <div class="rounded-2xl bg-white border border-slate-200 shadow-2xl shadow-indigo-200/40 overflow-hidden rotate-1">
                    <div class="flex items-center gap-1.5 px-4 py-3 border-b border-slate-100 bg-slate-50">
                        <span class="w-3 h-3 rounded-full bg-rose-300"></span><span class="w-3 h-3 rounded-full bg-amber-300"></span><span class="w-3 h-3 rounded-full bg-emerald-300"></span>
                        <span class="ml-3 text-xs text-slate-400 font-medium">{{ sc('blog','hero_preview_url','blog.mooda.id') }}</span>
                    </div>
                    <div class="p-4">
                        <div class="text-sm font-bold text-slate-800 mb-3">{{ sc('blog','hero_preview_judul','Artikel Terbaru') }}</div>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($all->take(4) as $i => $p)
                                <div class="rounded-xl overflow-hidden border border-slate-100">
                                    <div class="aspect-[16/10] {{ $p->cover ? '' : 'bg-gradient-to-br from-indigo-500 to-blue-500' }}">
                                        @if ($p->cover)<img src="{{ $p->cover_url }}" class="w-full h-full object-cover" alt="">@endif
                                    </div>
                                    <div class="p-2">
                                        <div class="h-2 rounded bg-slate-200 mb-1.5 w-4/5"></div>
                                        <div class="h-2 rounded bg-slate-100 w-3/5"></div>
                                    </div>
                                </div>
                            @endforeach
                            @if ($all->count() === 0)
                                @for ($i=0;$i<4;$i++)
                                    <div class="rounded-xl overflow-hidden border border-slate-100"><div class="aspect-[16/10] bg-gradient-to-br from-indigo-500 to-blue-500"></div><div class="p-2"><div class="h-2 rounded bg-slate-200 mb-1.5 w-4/5"></div><div class="h-2 rounded bg-slate-100 w-3/5"></div></div></div>
                                @endfor
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($posts->count())
    {{-- ===== TRENDING / ARTIKEL PILIHAN ===== --}}
    <section id="trending" class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-6 lg:py-10">
        <div class="text-xs font-bold uppercase tracking-widest text-indigo-500 mb-1">{{ sc('blog','trending_eyebrow','Trending Now') }}</div>
        <div class="flex items-end justify-between gap-4 mb-8">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ sc('blog','trending_judul','Artikel Pilihan Minggu Ini') }}</h2>
            <a href="#semua" class="hidden sm:inline-flex items-center gap-1 text-indigo-600 font-semibold text-sm hover:text-indigo-800 shrink-0">{{ sc('blog','trending_link','Lihat Semua Artikel →') }}</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($trending as $i => $post)
                <a href="{{ route('blog.show', $post->slug) }}" class="group flex flex-col rounded-2xl border border-slate-200 bg-white overflow-hidden hover:-translate-y-1 hover:shadow-xl hover:shadow-indigo-100 transition duration-300">
                    <div class="relative aspect-[16/10] overflow-hidden {{ $post->cover ? 'bg-slate-100' : 'bg-gradient-to-br from-indigo-600 to-blue-600' }}">
                        @if ($post->cover)<img src="{{ $post->cover_url }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">@else<div class="w-full h-full grid place-items-center text-white/90 font-black text-xl">Mooda</div>@endif
                        @if ($post->category)<span class="absolute top-3 left-3 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $catColors[$i % count($catColors)] }}">{{ $post->category->name }}</span>@endif
                    </div>
                    <div class="p-5 flex flex-col grow">
                        <h3 class="font-bold text-[15px] text-slate-900 leading-snug mb-2 group-hover:text-indigo-700 line-clamp-2">{{ $post->title }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed line-clamp-2 grow">{{ $post->excerpt }}</p>
                        <div class="flex items-center gap-2 text-xs text-slate-400 mt-4">
                            <span>{{ optional($post->published_at)->locale('id')->translatedFormat('d M Y') }}</span><span>·</span><span>{{ $readTime($post) }} {{ sc('blog','label_menit_baca','menit baca') }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ===== JELAJAHI KATEGORI ===== --}}
    @if (($navCategories ?? collect())->count())
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-10">
        <h2 class="text-2xl font-extrabold text-slate-900 mb-6">{{ sc('blog','kategori_judul','Jelajahi Berdasarkan Kategori') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            @foreach ($navCategories->take(5) as $i => $c)
                <a href="{{ route('blog.category', $c->slug) }}" class="group flex items-center gap-3 rounded-2xl border border-slate-100 p-4 hover:border-indigo-200 hover:shadow-lg hover:shadow-indigo-50 transition">
                    <span class="grid place-items-center w-11 h-11 rounded-xl text-xl {{ $catTint[$i % count($catTint)] }}">{{ $catIcons[$i % count($catIcons)] }}</span>
                    <div>
                        <div class="font-bold text-slate-800 text-sm leading-tight group-hover:text-indigo-700">{{ $c->name }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">{{ $c->published_count ?? 0 }} {{ sc('blog','kategori_satuan_artikel','Artikel') }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ===== ARTIKEL POPULER ===== --}}
    <section id="semua" class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-10 grid lg:grid-cols-2 gap-10">
        {{-- kiri: daftar bernomor --}}
        <div>
            <div class="text-xs font-bold uppercase tracking-widest text-indigo-500 mb-1">{{ sc('blog','populer_eyebrow','Artikel Populer') }}</div>
            <h2 class="text-2xl font-extrabold text-slate-900 mb-6">{{ sc('blog','populer_judul','Paling Banyak Dibaca') }}</h2>
            <div class="divide-y divide-slate-100">
                @foreach (($popular->count() ? $popular : $trending) as $i => $post)
                    <a href="{{ route('blog.show', $post->slug) }}" class="group flex items-start gap-4 py-4">
                        <div class="shrink-0 w-16 h-16 rounded-xl overflow-hidden {{ $post->cover ? 'bg-slate-100' : 'bg-gradient-to-br from-indigo-500 to-blue-500' }}">
                            @if ($post->cover)<img src="{{ $post->cover_url }}" class="w-full h-full object-cover" alt="" loading="lazy">@endif
                        </div>
                        <div class="min-w-0">
                            <div class="text-lg font-black text-slate-200 leading-none float-left mr-2 -mt-0.5">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
                            <h3 class="font-bold text-slate-800 leading-snug line-clamp-2 group-hover:text-indigo-700">{{ $post->title }}</h3>
                            <div class="text-xs text-slate-400 mt-1">{{ optional($post->published_at)->locale('id')->translatedFormat('d M Y') }} · {{ $readTime($post) }} {{ sc('blog','label_menit_baca','menit baca') }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        {{-- kanan: spotlight besar --}}
        @if ($spotlight)
        <a href="{{ route('blog.show', $spotlight->slug) }}" class="group relative rounded-3xl overflow-hidden min-h-[340px] flex items-end {{ $spotlight->cover ? 'bg-slate-900' : 'bg-gradient-to-br from-indigo-700 to-slate-900' }}">
            @if ($spotlight->cover)<img src="{{ $spotlight->cover_url }}" alt="{{ $spotlight->title }}" class="absolute inset-0 w-full h-full object-cover opacity-70 group-hover:scale-105 transition duration-700">@endif
            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent"></div>
            <div class="relative p-7 sm:p-9 text-white">
                @if ($spotlight->category)<span class="inline-block rounded-full bg-indigo-600 px-3 py-1 text-xs font-bold mb-3">{{ $spotlight->category->name }}</span>@endif
                <h3 class="text-2xl sm:text-3xl font-extrabold leading-tight mb-3">{{ $spotlight->title }}</h3>
                <p class="text-slate-200 leading-relaxed line-clamp-2 mb-4 max-w-xl">{{ $spotlight->excerpt }}</p>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-slate-300">{{ optional($spotlight->published_at)->locale('id')->translatedFormat('d M Y') }} · {{ $readTime($spotlight) }} {{ sc('blog','label_menit_baca','menit baca') }}</span>
                    <span class="ml-auto inline-flex items-center gap-2 bg-indigo-600 group-hover:bg-indigo-500 rounded-xl px-4 py-2 font-semibold transition">{{ sc('blog','populer_baca_selengkapnya','Baca Selengkapnya →') }}</span>
                </div>
            </div>
        </a>
        @endif
    </section>

    {{-- ===== SEMUA ARTIKEL (grid + pagination) ===== --}}
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-10">
        <h2 class="text-2xl font-extrabold text-slate-900 mb-6">{{ sc('blog','semua_judul','Semua Artikel') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($all as $post)
                @include('blog._card', ['post' => $post])
            @endforeach
        </div>
        @if ($posts->hasPages())
            <div class="flex justify-center items-center gap-2 mt-12">
                @if (! $posts->onFirstPage())<a href="{{ $posts->previousPageUrl() }}" class="px-5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-indigo-600 hover:text-white text-sm font-semibold transition">← Sebelumnya</a>@endif
                <span class="px-5 py-2.5 text-slate-500 text-sm font-semibold">Hal {{ $posts->currentPage() }}/{{ $posts->lastPage() }}</span>
                @if ($posts->hasMorePages())<a href="{{ $posts->nextPageUrl() }}" class="px-5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-indigo-600 hover:text-white text-sm font-semibold transition">Berikutnya →</a>@endif
            </div>
        @endif
    </section>

    {{-- ===== NEWSLETTER ===== --}}
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-10">
        <div class="rounded-3xl bg-indigo-50 p-8 sm:p-12 flex flex-col lg:flex-row items-center gap-8">
            <div class="text-6xl">✉️</div>
            <div class="flex-1 text-center lg:text-left">
                <h2 class="text-2xl font-extrabold text-indigo-700 mb-1">{!! sc('blog','newsletter_judul','Dapatkan Artikel &amp; Tips Terbaru') !!}</h2>
                <p class="text-slate-500">{{ sc('blog','newsletter_deskripsi','Berlangganan newsletter kami dan dapatkan insight terbaru langsung ke email Anda setiap minggunya.') }}</p>
            </div>
            <form onsubmit="event.preventDefault(); this.querySelector('button').textContent='{{ sc('blog','newsletter_sukses','Terima kasih!') }}';" class="flex w-full lg:w-auto gap-2">
                <input type="email" required placeholder="{{ sc('blog','newsletter_placeholder','Masukkan email Anda') }}" class="flex-1 lg:w-64 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl px-6 py-3 text-sm transition whitespace-nowrap">{{ sc('blog','newsletter_tombol','Langganan') }}</button>
            </form>
        </div>
    </section>
    @else
        <div class="text-center py-24">
            <div class="mx-auto w-16 h-16 grid place-items-center rounded-2xl bg-indigo-50 text-indigo-600 text-3xl mb-4">✎</div>
            <h2 class="text-xl font-bold text-slate-900 mb-1">{{ sc('blog','kosong_judul','Belum ada artikel') }}</h2>
            <p class="text-slate-500">{{ sc('blog','kosong_deskripsi','Nantikan tulisan-tulisan bermanfaat dari tim Mooda.') }}</p>
        </div>
    @endif
@endif
@endsection
