{{-- Partial detail read-only (di-inject ke #DetailPostBody). --}}
@if ($post->cover)
    <img src="{{ asset('storage/blog/' . $post->cover) }}" class="rounded mb-4 w-100" style="max-height:240px;object-fit:cover;">
@endif

<div class="d-flex flex-wrap gap-2 mb-3">
    @if ($post->status === 'published')
        <span class="badge badge-light-success">Terbit</span>
    @else
        <span class="badge badge-light-warning">Draf</span>
    @endif
    @if ($post->category)
        <span class="badge badge-light-primary">{{ $post->category->name }}</span>
    @endif
    @if ($post->published_at)
        <span class="badge badge-light">{{ \Carbon\Carbon::parse($post->published_at)->locale('id')->translatedFormat('d F Y, H:i') }}</span>
    @endif
</div>

<h3 class="fw-bold text-gray-900 mb-1">{{ $post->title }}</h3>
<div class="text-muted fs-8 mb-2">/{{ $post->slug }}
    @if ($post->author) • oleh {{ $post->author->name }}@endif
</div>

@if ($post->excerpt)
    <div class="text-gray-700 fst-italic mb-4">{{ $post->excerpt }}</div>
@endif

<div class="separator my-4"></div>

{{-- body sudah disanitasi purifier --}}
<div class="fs-6 text-gray-800">{!! $post->body ?: '<span class="text-muted">(Belum ada isi)</span>' !!}</div>
