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

@php
    // Item navigasi (dipakai bersama sidebar & dock). [anchor, label, svg-path]
    $nav = [
        ['#top',          'Beranda',        'M2.25 12l8.954-8.955a1.5 1.5 0 012.12 0L22.28 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75'],
        ['#sec-link',     'Link Referral',  'M13.19 8.688a4.5 4.5 0 011.242 7.244l-3 3a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-3 3a4.5 4.5 0 00-1.242 7.244'],
        ['#sec-referral', 'Referral Saya',  'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
        ['#sec-komisi',   'Komisi',         'M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9v3'],
    ];
@endphp

{{-- ===== SIDEBAR (desktop) ===== --}}
<aside class="hidden lg:flex fixed inset-y-0 left-0 z-40 w-64 flex-col border-r border-slate-200 bg-white">
    <div class="flex items-center gap-2.5 px-5 h-16 border-b border-slate-100">
        <span class="grid place-items-center w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 text-white font-black text-lg shadow-lg shadow-indigo-500/25">M</span>
        <div class="leading-tight">
            <div class="font-extrabold text-slate-900">Mooda <span class="text-indigo-600">Affiliate</span></div>
            <div class="text-[11px] font-semibold text-slate-400 -mt-0.5">Dashboard Afiliator</div>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        @foreach ($nav as [$href, $label, $d])
            <a href="{{ $href }}" data-navlink="{{ $href }}"
               class="nav-item flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-indigo-50 hover:text-indigo-700 transition">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}"/></svg>
                {{ $label }}
            </a>
        @endforeach

        <div class="pt-3 mt-3 border-t border-slate-100">
            <a href="https://mooda.id" target="_blank" rel="noopener"
               class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-slate-800 transition">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                Situs Mooda
            </a>
        </div>
    </nav>

    <div class="border-t border-slate-100 p-3">
        <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2.5">
            <span class="grid place-items-center w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-bold text-slate-800 truncate">{{ auth()->user()->name }}</div>
                <div class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</div>
            </div>
            <form method="POST" action="{{ route('affiliate.logout') }}">@csrf
                <button title="Keluar" class="grid place-items-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- ===== KONTEN ===== --}}
<div class="lg:pl-64">
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
            {{-- Brand (mobile) --}}
            <div class="flex items-center gap-2.5 lg:hidden">
                <span class="grid place-items-center w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 text-white font-black text-lg shadow-lg shadow-indigo-500/25">M</span>
                <span class="font-extrabold text-slate-900">Mooda <span class="text-indigo-600">Affiliate</span></span>
            </div>
            <div class="hidden lg:block text-sm font-semibold text-slate-400">Dashboard</div>

            <div class="flex items-center gap-2">
                <a href="https://mooda.id" target="_blank" rel="noopener"
                   class="hidden sm:inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-indigo-50 hover:text-indigo-700 transition">↗ Situs Mooda</a>
                <div class="relative">
                    <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')"
                            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 hover:bg-slate-50 transition">
                        <span class="grid place-items-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="hidden sm:block max-w-[140px] truncate text-sm font-semibold text-slate-700">{{ auth()->user()->name }}</span>
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z"/></svg>
                    </button>
                    <div class="hidden absolute right-0 mt-2 w-52 rounded-xl border border-slate-200 bg-white py-1.5 shadow-lg shadow-slate-200/60 z-50">
                        <div class="border-b border-slate-100 px-4 py-2">
                            <div class="truncate text-sm font-bold text-slate-800">{{ auth()->user()->name }}</div>
                            <div class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</div>
                        </div>
                        <a href="#top" class="block px-4 py-2 text-sm text-slate-600 hover:bg-indigo-50 hover:text-indigo-700">Dashboard</a>
                        <form method="POST" action="{{ route('affiliate.logout') }}">@csrf
                            <button class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="pb-28 lg:pb-10">
        @yield('content')
    </main>
</div>

{{-- ===== FLOATING DOCK (mobile/tablet, gaya marketplace) ===== --}}
<nav class="lg:hidden fixed bottom-3 left-1/2 z-50 w-[94%] max-w-lg -translate-x-1/2">
    <div class="flex items-stretch justify-around gap-1 rounded-2xl border border-slate-200 bg-white/95 px-2 py-2 shadow-2xl shadow-slate-400/30 backdrop-blur">
        @foreach ($nav as [$href, $label, $d])
            <a href="{{ $href }}" data-navlink="{{ $href }}"
               class="dock-item flex flex-1 flex-col items-center gap-1 rounded-xl px-1 py-1.5 text-[11px] font-semibold text-slate-500 transition">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}"/></svg>
                <span class="leading-none">{{ $label }}</span>
            </a>
        @endforeach
        <form method="POST" action="{{ route('affiliate.logout') }}" class="flex-1">@csrf
            <button class="flex w-full flex-col items-center gap-1 rounded-xl px-1 py-1.5 text-[11px] font-semibold text-red-500 hover:bg-red-50">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                <span class="leading-none">Keluar</span>
            </button>
        </form>
    </div>
</nav>

<style>
    .nav-item.active { background:#eef2ff; color:#4338ca; }
    .dock-item.active { color:#4f46e5; }
    .dock-item.active svg { transform: scale(1.08); }
</style>
<script>
    // Tutup menu akun saat klik di luar
    document.addEventListener('click', function (e) {
        document.querySelectorAll('[onclick^="this.nextElementSibling"]').forEach(function (btn) {
            var menu = btn.nextElementSibling;
            if (menu && !menu.classList.contains('hidden') && !btn.parentElement.contains(e.target)) menu.classList.add('hidden');
        });
    });

    // Scrollspy: tandai menu aktif sesuai section yang terlihat
    (function () {
        var ids = ['top', 'sec-link', 'sec-referral', 'sec-komisi'];
        var sections = ids.map(function (id) { return document.getElementById(id); }).filter(Boolean);
        if (!sections.length) return;
        function setActive(hash) {
            document.querySelectorAll('[data-navlink]').forEach(function (el) {
                el.classList.toggle('active', el.getAttribute('data-navlink') === hash);
            });
        }
        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) { if (en.isIntersecting) setActive('#' + en.target.id); });
        }, { rootMargin: '-45% 0px -50% 0px', threshold: 0 });
        sections.forEach(function (s) { obs.observe(s); });
        setActive('#top');
    })();
</script>
</body>
</html>
