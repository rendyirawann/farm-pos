{{-- Kartu artikel reusable. $post wajib. Gaya selaras landing (indigo). --}}
<a href="{{ route('blog.show', $post->slug) }}"
   class="group flex flex-col rounded-2xl border border-slate-200 bg-white overflow-hidden hover:-translate-y-1 hover:border-indigo-200 hover:shadow-xl hover:shadow-indigo-100 transition duration-300">
    <div class="aspect-[16/9] overflow-hidden {{ $post->cover ? 'bg-slate-100' : 'bg-gradient-to-br from-indigo-600 to-blue-600' }}">
        @if ($post->cover)
            <img src="{{ $post->cover_url }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
        @else
            <div class="w-full h-full grid place-items-center text-white/90 font-black text-2xl tracking-wide">Mooda</div>
        @endif
    </div>
    <div class="p-6 flex flex-col grow">
        @if ($post->category)
            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-2">{{ $post->category->name }}</span>
        @endif
        <h3 class="font-bold text-lg text-slate-900 leading-snug mb-2 group-hover:text-indigo-700 line-clamp-2">{{ $post->title }}</h3>
        @if ($post->excerpt)
            <p class="text-sm text-slate-500 leading-relaxed line-clamp-3 grow">{{ $post->excerpt }}</p>
        @endif
        <div class="flex items-center justify-between text-xs text-slate-400 mt-5 pt-4 border-t border-slate-100">
            <span>{{ optional($post->published_at)->locale('id')->translatedFormat('d M Y') }}</span>
            <span class="text-indigo-600 font-semibold group-hover:translate-x-0.5 transition">Baca →</span>
        </div>
    </div>
</a>
