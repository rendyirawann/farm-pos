<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard Affiliate — Mooda')</title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>body { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; }</style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    {{-- App shell: topbar aplikasi (bukan navbar landing) --}}
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 h-16 flex items-center justify-between gap-4">
            <div class="flex items-center gap-2.5">
                <span class="grid place-items-center w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 text-white font-black text-lg shadow-lg shadow-indigo-500/25">M</span>
                <div class="leading-tight">
                    <div class="font-extrabold text-slate-900">Mooda <span class="text-indigo-600">Affiliate</span></div>
                    <div class="text-[11px] font-semibold text-slate-400 -mt-0.5">Dashboard Afiliator</div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="https://mooda.id" target="_blank" rel="noopener"
                   class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-indigo-700 px-3 py-2 rounded-lg hover:bg-indigo-50 transition">
                    ↗ Situs Mooda
                </a>

                {{-- Menu akun --}}
                <div class="relative" x-data="{open:false}">
                    <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')"
                            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 hover:bg-slate-50 transition">
                        <span class="grid place-items-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="hidden sm:block text-sm font-semibold text-slate-700 max-w-[140px] truncate">{{ auth()->user()->name }}</span>
                        <svg class="w-4 h-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z"/></svg>
                    </button>
                    <div class="hidden absolute right-0 mt-2 w-52 rounded-xl border border-slate-200 bg-white shadow-lg shadow-slate-200/60 py-1.5 z-50">
                        <div class="px-4 py-2 border-b border-slate-100">
                            <div class="text-sm font-bold text-slate-800 truncate">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</div>
                        </div>
                        <a href="{{ route('affiliate.dashboard') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-indigo-50 hover:text-indigo-700">Dashboard</a>
                        <form method="POST" action="{{ route('affiliate.logout') }}">@csrf
                            <button class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="w-full">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 mt-10">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-5 text-xs text-slate-400 flex flex-wrap items-center justify-between gap-2">
            <span>&copy; {{ date('Y') }} Mooda Affiliate</span>
            <a href="https://affiliate.mooda.id" class="hover:text-indigo-600">affiliate.mooda.id</a>
        </div>
    </footer>

    {{-- Tutup menu akun saat klik di luar --}}
    <script>
        document.addEventListener('click', function (e) {
            document.querySelectorAll('[onclick^="this.nextElementSibling"]').forEach(function (btn) {
                var menu = btn.nextElementSibling;
                if (!menu.classList.contains('hidden') && !btn.parentElement.contains(e.target)) menu.classList.add('hidden');
            });
        });
    </script>
</body>
</html>
