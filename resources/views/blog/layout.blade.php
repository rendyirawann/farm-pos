<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Blog Mooda — Tips Bisnis Kuliner & POS')</title>
    <meta name="description" content="@yield('meta_description', 'Tips, panduan, & cerita seputar bisnis kuliner dan sistem kasir dari Mooda.')">
    @hasSection('canonical')<link rel="canonical" href="@yield('canonical')">@endif
    @stack('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; }
        /* Tipografi isi artikel (mandiri, tak butuh plugin typography) */
        .article-body { line-height: 1.8; color: #334155; font-size: 1.05rem; }
        .article-body h1, .article-body h2 { font-size: 1.6rem; font-weight: 800; margin: 2rem 0 .75rem; color: #0f172a; }
        .article-body h3 { font-size: 1.25rem; font-weight: 700; margin: 1.5rem 0 .5rem; color: #0f172a; }
        .article-body p { margin: 0 0 1.1rem; }
        .article-body ul, .article-body ol { margin: 0 0 1.1rem 1.4rem; }
        .article-body ul { list-style: disc; } .article-body ol { list-style: decimal; }
        .article-body li { margin: .35rem 0; }
        .article-body a { color: #059669; text-decoration: underline; }
        .article-body img { max-width: 100%; height: auto; border-radius: .75rem; margin: 1.25rem 0; }
        .article-body blockquote { border-left: 4px solid #10b981; padding-left: 1rem; color: #475569; font-style: italic; margin: 1.25rem 0; }
        .article-body strong { font-weight: 700; color: #0f172a; }
        .article-body figure { margin: 1.25rem 0; }
        .article-body table { width: 100%; border-collapse: collapse; margin: 1.25rem 0; }
        .article-body th, .article-body td { border: 1px solid #e2e8f0; padding: .5rem .75rem; }
    </style>
</head>
<body class="bg-white text-slate-800 antialiased">
    <header class="border-b border-slate-100 sticky top-0 bg-white/90 backdrop-blur z-30">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <a href="{{ route('blog.home') }}" class="flex items-center gap-2 font-extrabold text-lg text-slate-900">
                <span class="text-emerald-500">Mooda</span><span class="text-slate-400 font-semibold">Blog</span>
            </a>
            <nav class="flex items-center gap-3 text-sm font-semibold">
                <a href="{{ route('blog.home') }}" class="text-slate-600 hover:text-emerald-600 hidden sm:inline">Semua Artikel</a>
                <a href="https://mooda.id" class="text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg px-4 py-2 transition">Coba Mooda</a>
            </nav>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-10 min-h-[60vh]">
        @yield('content')
    </main>

    <footer class="border-t border-slate-100 mt-16">
        <div class="max-w-5xl mx-auto px-4 py-8 text-sm text-slate-500 flex flex-wrap items-center justify-between gap-3">
            <span>&copy; {{ date('Y') }} Mooda — Aplikasi Kasir &amp; POS Restoran, Cafe &amp; Warung</span>
            <a href="https://mooda.id" class="hover:text-emerald-600 font-semibold">mooda.id</a>
        </div>
    </footer>
</body>
</html>
