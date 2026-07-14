@extends('blog.layout')
@section('title', ($activeCat ? $activeCat->name . ' — ' : '') . 'Blog Mooda — Wawasan Bisnis & Produk Digital')
@section('meta_description', $activeCat ? ('Artikel kategori ' . $activeCat->name . ' di Blog Mooda.') : 'Wawasan, tips, & panduan praktis mengembangkan bisnis Anda di era digital bersama Mooda.')

@php
    $showFeatured = ! $activeCat && $posts->currentPage() === 1 && $posts->count() > 0;
    $featured = $showFeatured ? $posts->first() : null;
    $gridPosts = $showFeatured ? $posts->slice(1) : $posts->getCollection();
@endphp

@section('content')
    {{-- ===== HERO (full-bleed gelap, selaras landing) ===== --}}
    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950"></div>
        <div class="absolute inset-0 opacity-30" style="background-image:radial-gradient(circle at 15% 20%, #6366f1 0, transparent 40%),radial-gradient(circle at 85% 10%, #10b981 0, transparent 38%);"></div>
        <div class="relative max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-14 sm:py-20 lg:py-24">
            <div class="max-w-3xl">
                @if ($activeCat)
                    <a href="{{ route('blog.home') }}" class="inline-flex items-center gap-1 text-indigo-200 text-sm font-semibold hover:text-white mb-4">← Semua artikel</a>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-sm font-semibold backdrop-blur mb-4">Kategori</span>
                @else
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-sm font-semibold backdrop-blur mb-4">
                        <span class="w-2 h-2 rounded-full bg-gradient-to-r from-indigo-400 to-emerald-400"></span> Blog Mooda
                    </span>
                @endif
                <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold leading-[1.12] sm:leading-[1.05] tracking-tight mb-4 sm:mb-5 break-words">
                    {{ $activeCat ? $activeCat->name : 'Bikin bisnismu naik kelas di era digital.' }}
                </h1>
                <p class="text-base sm:text-lg lg:text-xl text-slate-300 leading-relaxed max-w-2xl">
                    {{ $activeCat ? 'Kumpulan artikel di kategori ini.' : 'Wawasan, tips, & strategi praktis — dari operasional & keuangan usaha sampai memilih produk digital yang tepat.' }}
                </p>
            </div>
        </div>
    </section>

    {{-- ===== FILTER KATEGORI (sticky di bawah navbar) ===== --}}
    @if (($navCategories ?? collect())->count())
        <div class="border-b border-slate-100 bg-white/95 backdrop-blur sticky top-16 z-30">
            <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10">
                <div class="flex gap-2 overflow-x-auto py-4 no-scrollbar">
                    <a href="{{ route('blog.home') }}" class="shrink-0 px-4 py-1.5 rounded-full text-sm font-semibold transition {{ ! $activeCat ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">Semua</a>
                    @foreach ($navCategories as $c)
                        <a href="{{ route('blog.category', $c->slug) }}" class="shrink-0 px-4 py-1.5 rounded-full text-sm font-semibold transition {{ $activeCat && $activeCat->id === $c->id ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $c->name }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-12">
        @if ($posts->count())
            {{-- ===== FEATURED (terbaru, halaman 1) ===== --}}
            @if ($featured)
                <a href="{{ route('blog.show', $featured->slug) }}" class="group grid lg:grid-cols-2 rounded-3xl overflow-hidden border border-slate-200 bg-white hover:border-indigo-200 hover:shadow-2xl hover:shadow-indigo-100 transition duration-300 mb-14">
                    <div class="aspect-[16/10] lg:aspect-auto overflow-hidden {{ $featured->cover ? 'bg-slate-100' : 'bg-gradient-to-br from-indigo-600 to-blue-600' }}">
                        @if ($featured->cover)
                            <img src="{{ $featured->cover_url }}" alt="{{ $featured->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        @else
                            <div class="w-full h-full grid place-items-center text-white/90 font-black text-4xl">Mooda</div>
                        @endif
                    </div>
                    <div class="p-6 sm:p-8 lg:p-12 flex flex-col justify-center">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="text-xs font-bold uppercase tracking-widest text-indigo-600">{{ $featured->category?->name ?? 'Artikel Pilihan' }}</span>
                            <span class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 to-emerald-500">★ Terbaru</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight mb-3 group-hover:text-indigo-700">{{ $featured->title }}</h2>
                        <p class="text-slate-500 leading-relaxed line-clamp-3 mb-6">{{ $featured->excerpt }}</p>
                        <div class="flex items-center gap-3 text-sm text-slate-400">
                            <span>{{ optional($featured->published_at)->locale('id')->translatedFormat('d F Y') }}</span>
                            <span class="text-indigo-600 font-semibold">Baca selengkapnya →</span>
                        </div>
                    </div>
                </a>
            @endif

            {{-- ===== GRID ===== --}}
            @if ($gridPosts->count())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach ($gridPosts as $post)
                        @include('blog._card', ['post' => $post])
                    @endforeach
                </div>
            @endif

            {{-- ===== PAGINATION ===== --}}
            @if ($posts->hasPages())
                <div class="flex flex-wrap justify-center items-center gap-2 mt-14">
                    @if ($posts->onFirstPage())
                        <span class="px-4 sm:px-5 py-2.5 rounded-xl bg-slate-100 text-slate-400 text-sm font-semibold">←<span class="hidden sm:inline"> Sebelumnya</span></span>
                    @else
                        <a href="{{ $posts->previousPageUrl() }}" class="px-4 sm:px-5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 text-sm font-semibold transition">←<span class="hidden sm:inline"> Sebelumnya</span></a>
                    @endif
                    <span class="px-3 sm:px-5 py-2.5 text-slate-500 text-sm font-semibold whitespace-nowrap">Hal {{ $posts->currentPage() }}/{{ $posts->lastPage() }}</span>
                    @if ($posts->hasMorePages())
                        <a href="{{ $posts->nextPageUrl() }}" class="px-4 sm:px-5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 text-sm font-semibold transition"><span class="hidden sm:inline">Berikutnya </span>→</a>
                    @else
                        <span class="px-4 sm:px-5 py-2.5 rounded-xl bg-slate-100 text-slate-400 text-sm font-semibold"><span class="hidden sm:inline">Berikutnya </span>→</span>
                    @endif
                </div>
            @endif
        @else
            {{-- ===== EMPTY ===== --}}
            <div class="text-center py-24">
                <div class="mx-auto w-16 h-16 grid place-items-center rounded-2xl bg-indigo-50 text-indigo-600 text-3xl mb-4">✎</div>
                <h2 class="text-xl font-bold text-slate-900 mb-1">Belum ada artikel{{ $activeCat ? ' di kategori ini' : '' }}</h2>
                <p class="text-slate-500">Nantikan tulisan-tulisan bermanfaat dari tim Mooda.</p>
            </div>
        @endif
    </div>
@endsection
