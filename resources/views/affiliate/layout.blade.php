<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Program Affiliate Mooda')</title>
    <meta name="description" content="@yield('meta_description', 'Gabung program afiliasi Mooda — bagikan link, ajak bisnis go-digital, dapat komisi.')">
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>body { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; }</style>
</head>
<body class="min-h-screen flex flex-col bg-white text-slate-800 antialiased selection:bg-indigo-200/60">
    @php($isAff = auth()->check() && auth()->user()->hasRole('affiliate'))

    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-100">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 h-16 flex items-center justify-between gap-4">
            <a href="{{ route('affiliate.home') }}" class="flex items-center gap-2">
                <span class="grid place-items-center w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 text-white font-black text-lg shadow-lg shadow-indigo-500/25">M</span>
                <span class="font-extrabold text-lg text-slate-900">Mooda <span class="text-indigo-600">Affiliate</span></span>
            </a>
            <nav class="hidden lg:flex items-center gap-1 text-sm font-semibold text-slate-600">
                <a href="https://mooda.id" class="rounded-full px-4 py-1.5 hover:bg-indigo-50 hover:text-indigo-700 transition">Beranda</a>
                <a href="https://mooda.id/#fitur" class="rounded-full px-4 py-1.5 hover:bg-indigo-50 hover:text-indigo-700 transition">Fitur</a>
                <a href="{{ route('affiliate.home') }}" class="rounded-full px-4 py-1.5 text-indigo-700 bg-indigo-50 transition">Affiliate</a>
                <a href="https://blog.mooda.id" class="rounded-full px-4 py-1.5 hover:bg-indigo-50 hover:text-indigo-700 transition">Blog</a>
                <a href="#cara-kerja" class="rounded-full px-4 py-1.5 hover:bg-indigo-50 hover:text-indigo-700 transition">FAQ</a>
            </nav>
            <div class="flex items-center gap-2 text-sm font-semibold">
                @if ($isAff)
                    <a href="{{ route('affiliate.dashboard') }}" class="px-3 py-2 rounded-lg text-slate-600 hover:bg-indigo-50 hover:text-indigo-700">Dashboard</a>
                    <form method="POST" action="{{ route('affiliate.logout') }}">@csrf
                        <button class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('affiliate.login') }}" class="px-4 py-2 rounded-lg text-slate-600 hover:bg-indigo-50 hover:text-indigo-700">Masuk</a>
                    <a href="{{ route('affiliate.register') }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-600/25">Daftar Affiliate</a>
                @endif
            </div>
        </div>
    </header>

    <main class="flex-1 w-full">
        @yield('content')
    </main>

    <footer class="mt-20 bg-slate-950 text-slate-300">
        <div class="h-1 bg-gradient-to-r from-indigo-500 to-emerald-500"></div>
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-14">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-10">
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="grid place-items-center w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 text-white font-black">M</span>
                        <span class="font-extrabold text-lg text-white">mooda</span>
                    </div>
                    <p class="text-sm leading-relaxed text-slate-400 max-w-xs mb-5">Solusi POS modern untuk Cafe, Resto, Bakery, dan berbagai jenis usaha.</p>
                    <div class="flex items-center gap-3">
                        @foreach ([['instagram','IG'],['facebook','FB'],['youtube','YT'],['tiktok','TT']] as [$k,$abbr])
                            <a href="{{ config('mooda.social_' . $k, '#') }}" target="_blank" rel="noopener" class="grid place-items-center w-9 h-9 rounded-full bg-white/10 hover:bg-indigo-600 text-slate-300 hover:text-white text-xs font-bold transition">{{ $abbr }}</a>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4 text-sm uppercase tracking-wide">Produk</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="https://mooda.id/#fitur" class="hover:text-indigo-400 transition">Fitur POS</a></li>
                        <li><a href="https://mooda.id/#harga" class="hover:text-indigo-400 transition">Harga</a></li>
                        <li><a href="https://mooda.id" class="hover:text-indigo-400 transition">Integrasi</a></li>
                        <li><a href="https://mooda.id" class="hover:text-indigo-400 transition">Update</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4 text-sm uppercase tracking-wide">Perusahaan</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="https://mooda.id" class="hover:text-indigo-400 transition">Tentang Kami</a></li>
                        <li><a href="https://blog.mooda.id" class="hover:text-indigo-400 transition">Blog</a></li>
                        <li><a href="https://mooda.id" class="hover:text-indigo-400 transition">Kontak</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4 text-sm uppercase tracking-wide">Kontak</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li class="flex items-center gap-2"><span>📞</span><a href="https://wa.me/{{ config('mooda.support_wa', '6282362211676') }}" class="hover:text-indigo-400 transition">0823-6221-1676</a></li>
                        <li class="flex items-center gap-2"><span>✉️</span><a href="mailto:hello@mooda.id" class="hover:text-indigo-400 transition">hello@mooda.id</a></li>
                        <li class="flex items-center gap-2"><span>🔗</span><a href="https://affiliate.mooda.id" class="hover:text-indigo-400 transition">affiliate.mooda.id</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-6 text-center text-sm text-slate-400">&copy; {{ date('Y') }} Mooda. All rights reserved.</div>
        </div>
    </footer>
</body>
</html>
