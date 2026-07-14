@extends('blog.layout')
@section('title', ($activeCat ? $activeCat->name . ' — ' : '') . 'Blog Mooda')
@section('meta_description', 'Tips, panduan, & cerita seputar bisnis kuliner dan sistem kasir dari Mooda.')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-2">
            {{ $activeCat ? $activeCat->name : 'Blog Mooda' }}
        </h1>
        <p class="text-slate-500">Tips, panduan, & cerita seputar bisnis kuliner dan sistem kasir.</p>
    </div>

    {{-- Filter kategori --}}
    @if ($categories->count())
        <div class="flex flex-wrap gap-2 mb-8">
            <a href="{{ route('blog.home') }}"
               class="px-4 py-1.5 rounded-full text-sm font-semibold {{ $activeCat ? 'bg-slate-100 text-slate-600 hover:bg-slate-200' : 'bg-emerald-500 text-white' }}">Semua</a>
            @foreach ($categories as $c)
                <a href="{{ route('blog.category', $c->slug) }}"
                   class="px-4 py-1.5 rounded-full text-sm font-semibold {{ $activeCat && $activeCat->id === $c->id ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $c->name }}</a>
            @endforeach
        </div>
    @endif

    @if ($posts->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($posts as $post)
                <a href="{{ route('blog.show', $post->slug) }}"
                   class="group flex flex-col rounded-2xl border border-slate-100 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition">
                    <div class="aspect-[16/10] bg-slate-100 overflow-hidden">
                        @if ($post->cover)
                            <img src="{{ $post->cover_url }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition" loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-300 text-4xl font-black">Mooda</div>
                        @endif
                    </div>
                    <div class="p-5 flex flex-col grow">
                        @if ($post->category)
                            <span class="text-xs font-bold uppercase tracking-wide text-emerald-600 mb-2">{{ $post->category->name }}</span>
                        @endif
                        <h2 class="font-bold text-lg text-slate-900 leading-snug mb-2 group-hover:text-emerald-600">{{ $post->title }}</h2>
                        @if ($post->excerpt)
                            <p class="text-sm text-slate-500 line-clamp-3 grow">{{ $post->excerpt }}</p>
                        @endif
                        <span class="text-xs text-slate-400 mt-4">{{ optional($post->published_at)->locale('id')->translatedFormat('d M Y') }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination (kelas Tailwind eksplisit agar ikut ter-scan build) --}}
        @if ($posts->hasPages())
            <div class="flex justify-center items-center gap-2 mt-12">
                @if ($posts->onFirstPage())
                    <span class="px-4 py-2 rounded-lg bg-slate-100 text-slate-400 text-sm">← Sebelumnya</span>
                @else
                    <a href="{{ $posts->previousPageUrl() }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-emerald-500 hover:text-white text-sm font-semibold">← Sebelumnya</a>
                @endif
                <span class="px-4 py-2 text-slate-500 text-sm">Hal {{ $posts->currentPage() }} / {{ $posts->lastPage() }}</span>
                @if ($posts->hasMorePages())
                    <a href="{{ $posts->nextPageUrl() }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 hover:bg-emerald-500 hover:text-white text-sm font-semibold">Berikutnya →</a>
                @else
                    <span class="px-4 py-2 rounded-lg bg-slate-100 text-slate-400 text-sm">Berikutnya →</span>
                @endif
            </div>
        @endif
    @else
        <div class="text-center py-20 text-slate-400">
            <div class="text-5xl mb-3">📝</div>
            <p class="font-semibold">Belum ada artikel{{ $activeCat ? ' di kategori ini' : '' }}.</p>
        </div>
    @endif
@endsection
