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
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post->title,
        'description' => $metaDesc,
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
    <article class="max-w-3xl mx-auto">
        <a href="{{ route('blog.home') }}" class="text-sm font-semibold text-emerald-600 hover:underline">← Semua artikel</a>

        <div class="mt-5 mb-6">
            @if ($post->category)
                <a href="{{ route('blog.category', $post->category->slug) }}" class="text-xs font-bold uppercase tracking-wide text-emerald-600">{{ $post->category->name }}</a>
            @endif
            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight mt-2">{{ $post->title }}</h1>
            <div class="flex items-center gap-3 text-sm text-slate-400 mt-3">
                <span>{{ optional($post->published_at)->locale('id')->translatedFormat('d F Y') }}</span>
                @if ($post->author)<span>•</span><span>{{ $post->author->name }}</span>@endif
            </div>
        </div>

        @if ($post->cover)
            <img src="{{ $post->cover_url }}" alt="{{ $post->title }}" class="w-full rounded-2xl mb-8" style="max-height:420px;object-fit:cover;">
        @endif

        {{-- body sudah disanitasi purifier saat simpan --}}
        <div class="article-body">{!! $post->body !!}</div>

        <div class="mt-10 pt-6 border-t border-slate-100 flex items-center justify-between">
            <a href="{{ route('blog.home') }}" class="text-sm font-semibold text-emerald-600 hover:underline">← Semua artikel</a>
            <a href="https://mooda.id" class="text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg px-5 py-2.5 text-sm font-semibold">Coba Mooda gratis</a>
        </div>
    </article>

    @if ($related->count())
        <div class="max-w-3xl mx-auto mt-16">
            <h2 class="text-xl font-extrabold text-slate-900 mb-5">Artikel lainnya</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                @foreach ($related as $r)
                    <a href="{{ route('blog.show', $r->slug) }}" class="group rounded-xl border border-slate-100 overflow-hidden hover:shadow-md transition">
                        <div class="aspect-[16/10] bg-slate-100 overflow-hidden">
                            @if ($r->cover)
                                <img src="{{ $r->cover_url }}" alt="{{ $r->title }}" class="w-full h-full object-cover group-hover:scale-105 transition" loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-300 font-black">Mooda</div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-sm text-slate-900 leading-snug group-hover:text-emerald-600 line-clamp-2">{{ $r->title }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endsection
