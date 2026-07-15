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
            <nav class="flex items-center gap-2 text-sm font-semibold">
                @if ($isAff)
                    <a href="{{ route('affiliate.dashboard') }}" class="px-3 py-2 rounded-lg text-slate-600 hover:bg-indigo-50 hover:text-indigo-700">Dashboard</a>
                    <form method="POST" action="{{ route('affiliate.logout') }}">@csrf
                        <button class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('affiliate.login') }}" class="px-3 py-2 rounded-lg text-slate-600 hover:bg-indigo-50 hover:text-indigo-700">Masuk</a>
                    <a href="{{ route('affiliate.register') }}" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg shadow-indigo-600/25">Daftar Gratis</a>
                @endif
            </nav>
        </div>
    </header>

    <main class="flex-1 w-full">
        @yield('content')
    </main>

    <footer class="mt-20 bg-slate-950 text-slate-300">
        <div class="h-1 bg-gradient-to-r from-indigo-500 to-emerald-500"></div>
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm">
            <div class="flex items-center gap-2">
                <span class="grid place-items-center w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-blue-500 text-white font-black">M</span>
                <span class="font-bold text-white">Mooda Affiliate</span>
            </div>
            <div class="flex items-center gap-4 text-slate-400">
                <a href="https://mooda.id" class="hover:text-indigo-400">mooda.id</a>
                <a href="https://blog.mooda.id" class="hover:text-indigo-400">Blog</a>
                <span>&copy; {{ date('Y') }} Mooda</span>
            </div>
        </div>
    </footer>
</body>
</html>
