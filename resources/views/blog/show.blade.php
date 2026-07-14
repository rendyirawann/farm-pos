@extends('blog.layout')

@php
    $metaTitle = $post->meta_title ?: $post->title;
    $metaDesc  = \Illuminate\Support\Str::limit(strip_tags($post->meta_description ?: ($post->excerpt ?: $post->body)), 155);
    $url       = route('blog.show', $post->slug);
@endphp

@section('title', e($metaTitle) . ' — Blog Mooda')
@section('meta_description', e($metaDesc))
@section('canonical', $url)

@push('head')
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDesc }}">
    <meta property="og:url" content="{{ $url }}">
    @if ($post->cover)<meta property="og:image" content="{{ $post->cover_url }}">@endif
    <meta name="twitter:card" content="{{ $post->cover ? 'summary_large_image' : 'summary' }}">
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org', '@type' => 'BlogPosting',
        'headline' => $post->title, 'description' => $metaDesc,
        'image' => $post->cover ? $post->cover_url : null,
        'datePublished' => optional($post->published_at)->toAtomString(),
        'dateModified' => optional($post->updated_at)->toAtomString(),
        'author' => ['@type' => 'Organization', 'name' => 'Mooda'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Mooda'],
        'mainEntityOfPage' => $url,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    {{-- Header artikel --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 pt-12 sm:pt-16">
        <div class="flex items-center gap-2 text-sm mb-5">
            <a href="{{ route('blog.home') }}" class="font-semibold text-indigo-600 hover:underline">Blog</a>
            @if ($post->category)
                <span class="text-slate-300">/</span>
                <a href="{{ route('blog.category', $post->category->slug) }}" class="font-semibold text-indigo-600 hover:underline">{{ $post->category->name }}</a>
            @endif
        </div>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 leading-tight tracking-tight mb-5">{{ $post->title }}</h1>
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
            <span>{{ optional($post->published_at)->locale('id')->translatedFormat('d F Y') }}</span>
            @if ($post->author)<span class="text-slate-300">•</span><span>oleh {{ $post->author->name }}</span>@endif
        </div>
    </div>

    @if ($post->cover)
        <div class="max-w-5xl mx-auto px-4 sm:px-6 mt-8">
            <img src="{{ $post->cover_url }}" alt="{{ $post->title }}" class="w-full rounded-2xl sm:rounded-3xl shadow-xl shadow-slate-200/70" style="max-height:460px;object-fit:cover;">
        </div>
    @endif

    {{-- Isi --}}
    <article class="max-w-3xl mx-auto px-4 sm:px-6 mt-10">
        <div class="article-body">{!! $post->body !!}</div>
    </article>

    {{-- CTA band (gradient signature landing) --}}
    <div class="max-w-5xl mx-auto px-4 sm:px-6 mt-14">
        <div class="rounded-2xl sm:rounded-3xl bg-gradient-to-br from-indigo-600 to-emerald-500 px-6 py-8 sm:px-12 sm:py-12 text-white text-center sm:text-left sm:flex items-center justify-between gap-6">
            <div>
                <h3 class="text-xl sm:text-2xl font-extrabold mb-1">Siap digitalkan bisnismu?</h3>
                <p class="text-indigo-50">Kelola kasir, laporan, & operasional dalam satu aplikasi bersama Mooda.</p>
            </div>
            <a href="https://mooda.id" class="mt-5 sm:mt-0 shrink-0 inline-flex justify-center bg-white text-indigo-700 font-bold rounded-xl px-6 py-3 hover:bg-indigo-50 transition">Coba gratis →</a>
        </div>
    </div>

    {{-- Related --}}
    @if ($related->count())
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 mt-16">
            <h2 class="text-2xl font-extrabold text-slate-900 mb-6">Baca juga</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($related as $r)
                    @include('blog._card', ['post' => $r])
                @endforeach
            </div>
        </div>
    @endif
@endsection
