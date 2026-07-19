<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Blog Mooda — Wawasan Bisnis & Produk Digital')</title>
    <meta name="description" content="@yield('meta_description', 'Wawasan, tips, dan panduan praktis mengembangkan bisnis Anda di era digital — dari operasional, keuangan, hingga memilih produk digital yang tepat, bersama Mooda.')">
    @hasSection('canonical')<link rel="canonical" href="@yield('canonical')">@endif
    @stack('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        /* Tipografi isi artikel — selaras landing (indigo), mandiri tanpa plugin */
        .article-body { line-height: 1.85; color: #334155; font-size: 1.075rem; }
        .article-body > *:first-child { margin-top: 0; }
        .article-body h2 { font-size: 1.7rem; font-weight: 800; margin: 2.4rem 0 .9rem; color: #0f172a; letter-spacing: -.01em; }
        .article-body h3 { font-size: 1.3rem; font-weight: 700; margin: 1.8rem 0 .6rem; color: #1e293b; }
        .article-body p { margin: 0 0 1.25rem; }
        .article-body ul, .article-body ol { margin: 0 0 1.25rem 1.5rem; }
        .article-body ul { list-style: disc; } .article-body ol { list-style: decimal; }
        .article-body li { margin: .45rem 0; padding-left: .25rem; }
        .article-body a { color: #4f46e5; text-decoration: underline; text-underline-offset: 2px; font-weight: 600; }
        .article-body img { max-width: 100%; height: auto; border-radius: 1rem; margin: 1.75rem 0; }
        .article-body blockquote { border-left: 4px solid #4f46e5; background: #eef2ff; padding: 1rem 1.25rem; border-radius: 0 .75rem .75rem 0; color: #3730a3; font-style: italic; margin: 1.75rem 0; font-size: 1.1rem; }
        .article-body strong { font-weight: 700; color: #0f172a; }
        .article-body table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; display: block; overflow-x: auto; }
        .article-body th, .article-body td { border: 1px solid #e2e8f0; padding: .6rem .85rem; text-align: left; }
        .article-body th { background: #f1f5f9; }
        /* Tipografi artikel di layar kecil (HP) */
        @media (max-width: 640px) {
            .article-body { font-size: 1rem; line-height: 1.75; }
            .article-body h2 { font-size: 1.35rem; margin: 1.8rem 0 .7rem; }
            .article-body h3 { font-size: 1.12rem; }
            .article-body blockquote { font-size: 1rem; padding: .85rem 1rem; }
        }
        img { max-width: 100%; height: auto; }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-white text-slate-800 antialiased selection:bg-indigo-200/60">

    {{-- ===================== NAVBAR ===================== --}}
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-100">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10">
            <div class="h-16 flex items-center justify-between gap-4">
                <a href="{{ route('blog.home') }}" class="flex items-center gap-2 shrink-0">
                    <span class="grid place-items-center w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 text-white font-black text-lg shadow-lg shadow-indigo-500/25">{{ sc('blog','nav_logo_huruf','M') }}</span>
                    <span class="font-extrabold text-lg text-slate-900">{!! sc('blog','nav_brand','Mooda <span class="text-indigo-600">Blog</span>') !!}</span>
                </a>

                <nav class="hidden lg:flex items-center gap-1 text-sm font-semibold text-slate-600">
                    <a href="{{ route('blog.home') }}" class="rounded-full px-4 py-1.5 hover:bg-indigo-50 hover:text-indigo-700 transition {{ request()->routeIs('blog.home') ? 'text-indigo-700 bg-indigo-50' : '' }}">{{ sc('blog','nav_beranda','Beranda') }}</a>
                    @foreach (($navCategories ?? collect())->take(5) as $c)
                        <a href="{{ route('blog.category', $c->slug) }}" class="rounded-full px-4 py-1.5 hover:bg-indigo-50 hover:text-indigo-700 transition">{{ $c->name }}</a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2">
                    <a href="https://mooda.id" class="hidden sm:inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl px-4 py-2.5 shadow-lg shadow-indigo-600/25 transition">
                        {{ sc('blog','nav_langganan','Langganan') }}
                    </a>
                    <button id="navToggle" class="lg:hidden grid place-items-center w-10 h-10 rounded-xl text-slate-700 hover:bg-slate-100 ring-1 ring-black/5" aria-label="Menu">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>

            <div id="navMobile" class="hidden lg:hidden pb-4">
                <div class="flex flex-col gap-1 text-sm font-semibold text-slate-700">
                    <a href="{{ route('blog.home') }}" class="rounded-xl px-4 py-2.5 hover:bg-indigo-50 hover:text-indigo-700">{{ sc('blog','nav_beranda','Beranda') }}</a>
                    @foreach (($navCategories ?? collect()) as $c)
                        <a href="{{ route('blog.category', $c->slug) }}" class="rounded-xl px-4 py-2.5 hover:bg-indigo-50 hover:text-indigo-700">{{ $c->name }}</a>
                    @endforeach
                    <a href="https://mooda.id" class="mt-2 text-center bg-indigo-600 text-white rounded-xl px-4 py-2.5">{{ sc('blog','nav_langganan','Langganan') }}</a>
                </div>
            </div>
        </div>
    </header>

    {{-- ===================== KONTEN ===================== --}}
    <main class="flex-1 w-full">
        @yield('content')
    </main>

    {{-- ===================== FOOTER ===================== --}}
    <footer class="mt-20 bg-slate-950 text-slate-300">
        <div class="h-1 bg-gradient-to-r from-indigo-500 to-emerald-500"></div>
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-14">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
                <div class="sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="grid place-items-center w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 text-white font-black">{{ sc('blog','nav_logo_huruf','M') }}</span>
                        <span class="font-extrabold text-lg text-white">{!! sc('blog','footer_brand','Mooda <span class="text-indigo-400">Blog</span>') !!}</span>
                    </div>
                    <p class="text-sm leading-relaxed text-slate-400 mb-5">{{ sc('blog','footer_deskripsi','Wawasan & tips praktis mengembangkan bisnis Anda di era digital — dari operasional sampai memilih produk digital yang tepat.') }}</p>
                    <div class="flex items-center gap-3">
                        @foreach ([['instagram','IG'],['facebook','FB'],['youtube','YT'],['tiktok','TT']] as [$k,$abbr])
                            <a href="{{ config('mooda.social_' . $k, '#') }}" target="_blank" rel="noopener" class="grid place-items-center w-9 h-9 rounded-full bg-white/10 hover:bg-indigo-600 text-slate-300 hover:text-white text-xs font-bold transition" aria-label="{{ $k }}">{{ $abbr }}</a>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4 text-sm uppercase tracking-wide">{{ sc('blog','footer_kol_kategori','Kategori') }}</h4>
                    <ul class="space-y-2.5 text-sm">
                        @forelse (($navCategories ?? collect())->take(6) as $c)
                            <li><a href="{{ route('blog.category', $c->slug) }}" class="hover:text-indigo-400 transition">{{ $c->name }}</a></li>
                        @empty
                            <li class="text-slate-500">—</li>
                        @endforelse
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4 text-sm uppercase tracking-wide">{{ sc('blog','footer_kol_navigasi','Navigasi') }}</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('blog.home') }}" class="hover:text-indigo-400 transition">{{ sc('blog','footer_nav_semua_artikel','Semua Artikel') }}</a></li>
                        <li><a href="https://mooda.id" class="hover:text-indigo-400 transition">{{ sc('blog','footer_nav_tentang','Tentang Mooda') }}</a></li>
                        <li><a href="https://mooda.id" class="hover:text-indigo-400 transition">{{ sc('blog','footer_nav_produk','Produk & Harga') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4 text-sm uppercase tracking-wide">{{ sc('blog','footer_kol_kontak','Kontak') }}</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li class="flex items-center gap-2"><span>📞</span><a href="https://wa.me/{{ config('mooda.support_wa', '6282362211676') }}" class="hover:text-indigo-400 transition">{{ sc('blog','footer_telepon','0823-6221-1676') }}</a></li>
                        <li class="flex items-center gap-2"><span>✉️</span><a href="mailto:hello@mooda.id" class="hover:text-indigo-400 transition">{{ sc('blog','footer_email','hello@mooda.id') }}</a></li>
                        <li class="flex items-center gap-2"><span>🌐</span><a href="https://blog.mooda.id" class="hover:text-indigo-400 transition">{{ sc('blog','footer_website','blog.mooda.id') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-slate-400">
                <span>&copy; {{ date('Y') }} {{ sc('blog','footer_copyright','Mooda — Produk Digital untuk Bisnis Anda') }}</span>
                <a href="https://mooda.id" class="font-semibold text-slate-300 hover:text-indigo-400">{{ sc('blog','footer_bottom_link','mooda.id') }}</a>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            var btn = document.getElementById('navToggle');
            var menu = document.getElementById('navMobile');
            if (btn && menu) btn.addEventListener('click', function () { menu.classList.toggle('hidden'); });
        })();
    </script>
</body>
</html>
