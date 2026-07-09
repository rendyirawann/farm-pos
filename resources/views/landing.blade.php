<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    @php
        $seoTitle = 'Mooda — Aplikasi Kasir & POS Restoran, Cafe & Warung';
        $seoDesc = 'Mooda: aplikasi kasir & POS satu layar untuk restoran, cafe, dan warung. Kelola pesanan, kitchen display, add-on menu, nomor antrian di struk, laporan penjualan, hingga multi-outlet. Bayar Tunai & QRIS.';
        $seoUrl = url('/');
        $seoImage = asset('assets/media/og-mooda.jpg');
        $gVerify = config('services.google_site_verification');
        $fbAppId = config('services.facebook.app_id');
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    <meta name="keywords" content="aplikasi kasir, POS restoran, kasir cafe, kasir warung, kitchen display, aplikasi kasir online, point of sale, kasir QRIS, sistem kasir, Mooda">
    <meta name="author" content="Mooda">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ $seoUrl }}">
    @if ($gVerify)
        <meta name="google-site-verification" content="{{ $gVerify }}">
    @endif

    {{-- Favicon (Mooda) --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/logos/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/media/logos/mooda-mark-192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('assets/media/logos/mooda-mark-512.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/media/logos/mooda-mark-192.png') }}">

    {{-- Open Graph (WhatsApp / Facebook / LinkedIn) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Mooda">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ $seoUrl }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:secure_url" content="{{ $seoImage }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Mooda — Sistem Kasir & POS Restoran">
    @if ($fbAppId)
        <meta property="fb:app_id" content="{{ $fbAppId }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    {{-- Data terstruktur (JSON-LD) untuk hasil pencarian Google --}}
    <script type="application/ld+json">
        @php
            echo json_encode([
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'Organization',
                        '@id' => $seoUrl . '#organization',
                        'name' => 'Mooda',
                        'url' => $seoUrl,
                        'logo' => asset('assets/media/logos/mooda-mark-512.png'),
                    ],
                    [
                        '@type' => 'WebSite',
                        '@id' => $seoUrl . '#website',
                        'name' => 'Mooda',
                        'url' => $seoUrl,
                        'publisher' => ['@id' => $seoUrl . '#organization'],
                        'inLanguage' => 'id-ID',
                    ],
                    [
                        '@type' => 'SoftwareApplication',
                        'name' => 'Mooda',
                        'applicationCategory' => 'BusinessApplication',
                        'operatingSystem' => 'Web, Android',
                        'url' => $seoUrl,
                        'description' => $seoDesc,
                        'offers' => [
                            '@type' => 'Offer',
                            'price' => '129000',
                            'priceCurrency' => 'IDR',
                        ],
                    ],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        @endphp
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/landing.js'])
    <style>
        :root { --jakarta: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
        html, body { height: 100%; }
        body { font-family: var(--jakarta); overflow: hidden; }
        .text-balance { text-wrap: balance; }
        @keyframes nudge { 0%,100%{transform:translateX(0)} 50%{transform:translateX(6px)} }
        .animate-nudge { animation: nudge 1.4s ease-in-out infinite; }

        /* logo: outline putih agar terlihat di latar gelap tanpa kotak */
        .logo-outline {
            filter:
                drop-shadow(1.5px 0 0 #fff) drop-shadow(-1.5px 0 0 #fff)
                drop-shadow(0 1.5px 0 #fff) drop-shadow(0 -1.5px 0 #fff)
                drop-shadow(0 1px 3px rgba(0,0,0,.3));
        }

        /* Teks gradien dgn fallback: browser lama (tanpa background-clip:text) tetap terlihat,
           bukan jadi transparan/hilang. */
        .lp-gradient-text { color: #c4b5fd; }
        @supports ((-webkit-background-clip: text) or (background-clip: text)) {
            .lp-gradient-text {
                background-image: linear-gradient(to right, #a5b4fc, #c4b5fd, #6ee7b7);
                -webkit-background-clip: text; background-clip: text;
                -webkit-text-fill-color: transparent; color: transparent;
            }
        }

        /* ===== Swiper + centering anti-ketimpa ===== */
        /* 100dvh = tinggi viewport dinamis (akurat di HP dgn bilah alamat) */
        #landing-swiper, .swiper-wrapper, .swiper-slide { height: 100vh; height: 100dvh; }
        /* Slide = 1 layar penuh; bg absolute menutupinya. overflow hidden agar bg tidak ikut menggulir. */
        .lp-slide { display: flex !important; overflow: hidden; }
        /* Konten boleh MENGGULIR vertikal saat lebih tinggi dari layar (anti-terpotong di HP & mode landscape). */
        .lp-content {
            margin-inline: auto !important;
            width: 100%;
            max-height: 100vh; max-height: 100dvh;
            overflow-y: auto; overflow-x: hidden;
            display: flex; flex-direction: column;
            -webkit-overflow-scrolling: touch;
        }
        /* margin auto pada anak pertama/terakhir = center vertikal saat muat, tapi tetap bisa scroll saat tinggi */
        .lp-content > :first-child { margin-top: auto; }
        .lp-content > :last-child { margin-bottom: auto; }
        /* Layar kecil/pendek: kecilkan padding vertikal raksasa (py-24=6rem) & sisakan ruang utk header fixed */
        @media (max-width: 640px), (max-height: 740px) {
            .lp-content { padding-top: 5.25rem !important; padding-bottom: 2rem !important; }
        }

        .landing-pagination {
            position: fixed; left: 0; right: 0; bottom: 22px; z-index: 40;
            display: flex; justify-content: center; gap: 10px;
            mix-blend-mode: difference;
        }
        .landing-pagination .swiper-pagination-bullet {
            margin: 0 !important; width: 8px; height: 8px; background: #fff; opacity: .55;
            border-radius: 9999px; transition: width .25s ease, opacity .25s ease;
        }
        .landing-pagination .swiper-pagination-bullet-active { opacity: 1; width: 28px; }
        .landing-prev.swiper-button-disabled, .landing-next.swiper-button-disabled { opacity: 0; pointer-events: none; }

        /* Kotak pilihan durasi paket: scrollbar tipis yang terlihat + scroll terkurung di kotak */
        /* Pilihan durasi paket (pills) — klik untuk mengubah angka harga (inline CSS, tak butuh build) */
        .plan-dur-wrap { display: inline-flex; flex-wrap: wrap; gap: 6px; background: #f1f5f9; border-radius: 14px; padding: 5px; }
        .plan-dur-btn { border: 0; cursor: pointer; border-radius: 10px; padding: 7px 14px; font-size: 12px; font-weight: 700; color: #64748b; background: transparent; transition: all .15s ease; white-space: nowrap; }
        .plan-dur-btn:hover { color: #0f172a; }
        .plan-dur-btn.is-active { background: #fff; color: #0f172a; box-shadow: 0 1px 3px rgba(15,23,42,.12); }
        .plan-dur-btn .disc { margin-left: 5px; font-size: 10px; font-weight: 800; color: #059669; }

        /* Tombol nonaktif (maintenance) — kontras cukup di latar terang maupun gelap */
        .mooda-soon-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border-radius: 12px; padding: 12px 20px; font-size: 14px; font-weight: 600; cursor: not-allowed; user-select: none; background: #e2e8f0; color: #475569; border: 1px solid #cbd5e1; }
        .mooda-soon-btn.w-full { width: 100%; }
        .mooda-soon-chip { display: inline-block; border-radius: 12px; padding: 8px 16px; font-size: 14px; font-weight: 600; cursor: not-allowed; user-select: none; background: #e2e8f0; color: #64748b; border: 1px solid #cbd5e1; }

        /* Grid harga 4 kartu (pakai CSS inline: kelas grid Tailwind baru tak ada di bundle terkompilasi) */
        .mooda-price-grid { display: grid; gap: 24px; grid-template-columns: 1fr; max-width: 1280px; margin-left: auto; margin-right: auto; }
        @media (min-width: 768px) { .mooda-price-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1280px) { .mooda-price-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }

        /* ===== MODE MOBILE/TABLET: scroll VERTIKAL (bukan swiper horizontal) ===== */
        html.lp-mobile, html.lp-mobile body { height: auto; overflow-x: hidden; overflow-y: auto; }
        html.lp-mobile { scroll-padding-top: 72px; scroll-behavior: smooth; }
        html.lp-mobile #landing-swiper { height: auto !important; width: 100%; overflow: visible; }
        html.lp-mobile .swiper-wrapper { display: block !important; height: auto !important; transform: none !important; }
        html.lp-mobile .swiper-slide { width: 100% !important; height: auto !important; }
        html.lp-mobile .lp-slide { overflow: visible; min-height: 100vh; min-height: 100dvh; }
        html.lp-mobile .lp-content { max-height: none !important; overflow: visible !important; padding-bottom: 2.5rem !important; }
        html.lp-mobile .landing-pagination,
        html.lp-mobile .landing-prev,
        html.lp-mobile .landing-next,
        html.lp-mobile .lp-scrollhint { display: none !important; }

        /* ===== FLOATING BUTTONS (semua device, tersusun atas-bawah di pojok kanan bawah) ===== */
        /* Desktop: ikon bulat, memanjang jadi tombol saat hover. Mobile: pill kecil berteks (tanpa hover). */
        .lp-fab {
            position: fixed; right: 16px; bottom: calc(18px + env(safe-area-inset-bottom, 0px));
            z-index: 60; display: flex; flex-direction: column; align-items: flex-end; gap: 12px;
        }
        .lp-fab a {
            display: inline-flex; align-items: center; justify-content: flex-start; gap: 9px;
            height: 50px; max-width: 50px; padding: 0 15px; overflow: hidden; white-space: nowrap;
            border-radius: 999px; font-weight: 700; font-size: 14px; text-decoration: none; color: #fff;
            box-shadow: 0 10px 26px -8px rgba(0,0,0,.55);
            transition: max-width .32s cubic-bezier(.2,.8,.2,1), box-shadow .2s ease, transform .15s ease;
        }
        .lp-fab a:hover, .lp-fab a:focus-visible { max-width: 260px; box-shadow: 0 14px 32px -8px rgba(0,0,0,.6); }
        .lp-fab a:active { transform: scale(.97); }
        .lp-fab a:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
        .lp-fab-order { background: #4f46e5; }
        .lp-fab-contact { background: #22c55e; }
        .lp-fab svg { width: 20px; height: 20px; flex: 0 0 auto; }
        .lp-fab-txt { opacity: 0; transition: opacity .18s ease; }
        .lp-fab a:hover .lp-fab-txt, .lp-fab a:focus-visible .lp-fab-txt { opacity: 1; }

        /* MOBILE/TABLET (sentuh, tanpa hover) -> tombol kecil, teks selalu tampil */
        html.lp-mobile .lp-fab { right: 14px; bottom: calc(14px + env(safe-area-inset-bottom, 0px)); gap: 9px; align-items: stretch; }
        html.lp-mobile .lp-fab a { height: auto; max-width: none; padding: 9px 15px; font-size: 13px; justify-content: center; gap: 7px; }
        html.lp-mobile .lp-fab-txt { opacity: 1; }
        html.lp-mobile .lp-fab svg { width: 15px; height: 15px; }

        /* ===== TOMBOL KEMBALI KE ATAS (kiri bawah, hanya mobile/tablet) ===== */
        .lp-totop {
            position: fixed; left: 14px; bottom: calc(14px + env(safe-area-inset-bottom, 0px));
            z-index: 60; display: none; align-items: center; justify-content: center;
            width: 44px; height: 44px; border-radius: 999px; border: none; cursor: pointer; color: #fff;
            background: rgba(15,23,42,.72); -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
            box-shadow: 0 8px 20px -6px rgba(0,0,0,.55); transition: transform .15s ease;
        }
        html.lp-mobile .lp-totop { display: flex; }
        .lp-totop:active { transform: scale(.94); }
        .lp-totop svg { width: 22px; height: 22px; }
    </style>
</head>

<body class="bg-slate-950 text-slate-900 antialiased selection:bg-indigo-200/60">

    {{-- ===== FIXED HEADER (navbar tengah) ===== --}}
    <header class="fixed inset-x-0 top-0 z-50">
        <div class="relative mx-auto flex max-w-screen-2xl items-center justify-between px-5 py-4">
            <a href="{{ url('/') }}" class="flex items-center">
                {{-- Logo diganti otomatis: putih di slide gelap, gelap di slide terang (lihat script di bawah) --}}
                <img id="nav-logo-white" src="{{ asset('assets/media/logos/mooda-logo-white.png') }}" alt="Mooda" class="logo-outline h-10 w-auto" draggable="false">
                <img id="nav-logo-dark" src="{{ asset('assets/media/logos/mooda-logo.png') }}" alt="Mooda" class="hidden h-10 w-auto" draggable="false">
            </a>

            <nav class="absolute left-1/2 top-1/2 hidden -translate-x-1/2 -translate-y-1/2 items-center gap-1 rounded-full bg-white/90 px-2 py-1.5 shadow-lg ring-1 ring-black/5 backdrop-blur md:flex">
                <a href="#fitur" class="rounded-full px-4 py-1.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Fitur</a>
                <a href="#galeri" class="rounded-full px-4 py-1.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Galeri</a>
                <a href="#harga" class="rounded-full px-4 py-1.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Harga</a>
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ route('login') }}" class="hidden rounded-xl bg-white/95 px-4 py-2 text-sm font-semibold text-slate-700 shadow-lg ring-1 ring-black/5 backdrop-blur transition hover:bg-white sm:inline-block">Masuk</a>
                @if (\App\Tenancy\Plan::maintenance())
                    <span title="{{ \App\Tenancy\Plan::maintenanceText() }}" class="mooda-soon-chip">Daftar</span>
                @else
                    <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-600/30 transition hover:bg-indigo-700">Daftar</a>
                @endif
                <button id="lp-burger" type="button" aria-label="Menu" aria-expanded="false"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/90 text-slate-700 shadow-lg ring-1 ring-black/5 backdrop-blur md:hidden">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>

            {{-- Menu mobile (di-toggle burger) --}}
            <div id="lp-mobile-menu" class="absolute right-5 top-full mt-2 hidden w-56 overflow-hidden rounded-2xl bg-white/95 p-2 shadow-2xl ring-1 ring-black/5 backdrop-blur md:hidden">
                <a href="#fitur" class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Fitur</a>
                <a href="#galeri" class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Galeri</a>
                <a href="#harga" class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Harga</a>
                <div class="my-1 h-px bg-slate-200"></div>
                <a href="{{ route('login') }}" class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Masuk</a>
                @if (\App\Tenancy\Plan::maintenance())
                    <span class="block cursor-not-allowed select-none rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-400">Daftar — {{ \App\Tenancy\Plan::maintenanceText() }}</span>
                @else
                    <a href="{{ route('register') }}" class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-indigo-600 hover:bg-indigo-50">Daftar</a>
                @endif
            </div>
        </div>
    </header>

    {{-- ===== SWIPER (geser horizontal) ===== --}}
    <div id="landing-swiper" class="swiper h-screen w-screen">
        <div class="swiper-wrapper">

            {{-- SLIDE 0 — HERO --}}
            <div class="swiper-slide lp-slide relative" data-nav-dark>
                <div class="absolute inset-0 bg-cover bg-center" style="background-image:url('{{ asset('assets/media/landing/hero.jpg') }}')"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-slate-950/85 via-slate-900/70 to-indigo-950/80"></div>
                <div class="lp-content relative z-10 w-full max-w-3xl px-6 py-24 text-center text-white">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-sm font-semibold backdrop-blur">
                        <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span></span>
                        Sistem Kasir Restoran All-in-One
                    </span>
                    <h1 class="mt-6 text-balance text-4xl font-extrabold leading-[1.07] tracking-tight sm:text-6xl">
                        Kelola restoran lebih
                        <span class="lp-gradient-text">cepat, rapi &amp; cuan</span>
                    </h1>
                    <p class="mx-auto mt-5 max-w-xl text-balance text-lg text-slate-200">
                        Satukan kasir, dapur (kitchen display), nomor antrian, dan laporan penjualan dalam satu sistem. Untuk restoran, cafe & warung — bisa multi-outlet.
                    </p>
                    <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        @if (\App\Tenancy\Plan::maintenance())
                            <span class="mooda-soon-btn">{{ \App\Tenancy\Plan::maintenanceText() }}</span>
                        @else
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-7 py-3.5 text-base font-semibold text-white shadow-xl shadow-indigo-900/40 transition hover:bg-indigo-700">Mulai Sekarang</a>
                        @endif
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-7 py-3.5 text-base font-semibold text-white backdrop-blur transition hover:bg-white/20">Masuk</a>
                    </div>
                    <div class="lp-scrollhint mt-7 inline-flex items-center gap-2 text-sm text-slate-300">
                        <span>Scroll mouse untuk menjelajah</span>
                        <svg class="animate-nudge h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </div>
                </div>
            </div>

            {{-- SLIDE 1 — FITUR --}}
            <div id="fitur" class="swiper-slide lp-slide relative bg-white">
                <div class="lp-content w-full max-w-6xl px-6 py-24">
                    <div class="max-w-2xl">
                        <span class="text-sm font-bold uppercase tracking-wider text-indigo-600">Fitur Lengkap</span>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Semua kebutuhan restoran modern</h2>
                        <p class="mt-3 text-lg text-slate-600">Dari pelanggan datang sampai laporan akhir bulan, semua tercatat otomatis.</p>
                    </div>
                    @php
                        $features = [
                            ['M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'Kasir / POS Satu Layar', 'Input nama, pilih menu + add-on, dan bayar dalam satu halaman. Cepat & anti ribet.', 'from-indigo-500 to-blue-500'],
                            ['M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m-3.75 0h15a.75.75 0 0 1 .75.75v8.25a.75.75 0 0 1-.75.75h-15a.75.75 0 0 1-.75-.75v-8.25a.75.75 0 0 1 .75-.75Z', 'Kitchen Display', 'Pesanan tampil di layar dapur. Status masak per item terpantau, tidak ada yang terlewat.', 'from-rose-500 to-orange-500'],
                            ['M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m-3.75 0h15a.75.75 0 0 1 .75.75v8.25a.75.75 0 0 1-.75.75h-15a.75.75 0 0 1-.75-.75v-8.25a.75.75 0 0 1 .75-.75Z', 'Menu & Add-on', 'Kelola kategori, menu, foto, promo, dan add-on (tambahan) beserta harganya dengan mudah.', 'from-violet-500 to-purple-500'],
                            ['M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z', 'Tunai & QRIS', 'Bayar di depan (struk lunas) atau di belakang. Tunai hitung kembalian otomatis, atau QRIS.', 'from-emerald-500 to-teal-500'],
                            ['M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z', 'Laporan & Analitik', 'Laporan penjualan, produk terlaris, dan target penjualan harian—tercatat otomatis.', 'from-amber-500 to-yellow-500'],
                            ['M9 12.75 11.25 15 15 9.75m-3-7.036A11.96 11.96 0 0 1 3.6 6.6 12 12 0 0 0 3 8.25c0 5.6 3.82 10.3 9 11.6 5.18-1.3 9-6 9-11.6 0-.56-.04-1.11-.12-1.65a11.96 11.96 0 0 1-8.4-3.89Z', 'Multi-Outlet & Tablet', 'Data tiap bisnis terisolasi penuh, hak akses staf, & bisa dipakai di web maupun aplikasi tablet.', 'from-slate-700 to-slate-900'],
                        ];
                    @endphp
                    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($features as [$path, $title, $desc, $grad])
                            <div class="group rounded-2xl border border-slate-200 bg-white p-6 transition duration-200 hover:-translate-y-1 hover:border-indigo-200 hover:shadow-xl hover:shadow-indigo-100">
                                <div class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br {{ $grad }} text-white shadow-lg">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
                                </div>
                                <h3 class="mt-4 text-lg font-bold">{{ $title }}</h3>
                                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $desc }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- SLIDE 2 — GALERI --}}
            <div id="galeri" class="swiper-slide lp-slide relative bg-slate-50">
                <div class="lp-content w-full max-w-6xl px-6 py-24">
                    <div class="mb-8 max-w-2xl">
                        <span class="text-sm font-bold uppercase tracking-wider text-indigo-600">Galeri</span>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Dibuat untuk dunia kuliner</h2>
                        <p class="mt-3 text-lg text-slate-600">Dari cafe kecil sampai restoran ramai — sistem menyesuaikan alur kerja Anda.</p>
                    </div>
                    @php
                        $gallery = [
                            ['cafe.jpg', 'Cafe & Coffee Shop', 'Layani pelanggan lebih cepat di jam sibuk.', 'sm:col-span-2 sm:row-span-2'],
                            ['kitchen.jpg', 'Dapur Terorganisir', 'Tiket masakan langsung ke layar dapur.', ''],
                            ['food.jpg', 'Menu Menggugah', 'Kelola menu, foto, harga & promo.', ''],
                            ['serving.jpg', 'Pelayanan Prima', 'Pesanan akurat, pelanggan puas.', ''],
                            ['coffee.jpg', 'Detail Tiap Pesanan', 'Catatan khusus per item tercatat rapi.', ''],
                        ];
                    @endphp
                    <div class="grid auto-rows-[140px] grid-cols-2 gap-4 sm:auto-rows-[160px] lg:grid-cols-4">
                        @foreach ($gallery as [$img, $t, $d, $span])
                            <div class="group relative overflow-hidden rounded-2xl shadow-lg ring-1 ring-black/5 {{ $span }}">
                                <img src="{{ asset('assets/media/landing/' . $img) }}" alt="{{ $t }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" draggable="false">
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/10 to-transparent"></div>
                                <div class="absolute inset-x-0 bottom-0 p-4 text-white">
                                    <h3 class="text-base font-bold sm:text-lg">{{ $t }}</h3>
                                    <p class="mt-0.5 text-xs text-slate-200 sm:text-sm">{{ $d }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- SLIDE 3 — CARA KERJA --}}
            <div class="swiper-slide lp-slide relative" data-nav-dark>
                <div class="absolute inset-0 bg-cover bg-center" style="background-image:url('{{ asset('assets/media/landing/coffee.jpg') }}')"></div>
                <div class="absolute inset-0 bg-slate-950/80"></div>
                <div class="lp-content relative z-10 w-full max-w-5xl px-6 py-24 text-white">
                    <div class="mx-auto max-w-2xl text-center">
                        <span class="text-sm font-bold uppercase tracking-wider text-indigo-300">Cara Kerja</span>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Mulai dalam 3 langkah</h2>
                    </div>
                    <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
                        @php
                            $steps = [
                                ['1', 'Daftar akun bisnis', 'Buat akun pemilik & data restoran Anda dalam beberapa menit.'],
                                ['2', 'Pilih paket & bayar', 'Bayar online aman lewat Midtrans, sistem langsung aktif.'],
                                ['3', 'Kelola & berkembang', 'Tambah menu, staf, meja, lalu pantau performa dari dashboard.'],
                            ];
                        @endphp
                        @foreach ($steps as [$n, $t, $d])
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-7 text-center backdrop-blur">
                                <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-gradient-to-br from-indigo-500 to-emerald-500 text-xl font-extrabold text-white shadow-lg">{{ $n }}</div>
                                <h3 class="mt-5 text-lg font-bold">{{ $t }}</h3>
                                <p class="mt-2 text-sm text-slate-300">{{ $d }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- SLIDE 4 — HARGA --}}
            <div id="harga" class="swiper-slide lp-slide relative bg-white">
                <div class="lp-content w-full max-w-6xl px-6 py-14">
                    <div class="mx-auto max-w-2xl text-center">
                        <span class="text-sm font-bold uppercase tracking-wider text-indigo-600">Harga</span>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Paket sederhana & transparan</h2>
                        <p class="mt-3 text-lg text-slate-600">Pilih sesuai skala bisnis — dari deposit bayar-sesuai-pakai hingga enterprise. Tanpa biaya tersembunyi.</p>
                    </div>
                    <div class="mooda-price-grid mt-6">
                        @php
                            $check = '<svg class="mt-0.5 h-5 w-5 flex-none text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 0 1 1.4-1.4l3.1 3.1 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>';
                            $soonSvg = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>';
                            $maint = \App\Tenancy\Plan::maintenance();
                            $maintText = \App\Tenancy\Plan::maintenanceText();
                            $core = [
                                'Kasir / POS satu layar (Tunai & QRIS)',
                                'Kitchen Display (layar dapur)',
                                'Add-on menu & nomor antrian di struk',
                                'Laporan penjualan',
                                'Data master menu & kategori',
                            ];
                            $starterFeatures = array_merge($core, [
                                'Maks 2 User (tambah user Rp 10.000/user)',
                                'Penyimpanan Database Pelanggan (3.000 Data)',
                            ]);
                            $monthlyPlans = [
                                [
                                    'name' => 'Basic', 'pop' => false,
                                    'tagline' => 'Semua yang dibutuhkan untuk mulai jualan dengan rapi & cepat.',
                                    'periods' => \App\Tenancy\Plan::periods('basic'),
                                    'features' => array_merge($core, [
                                        'Maks 3 User (tambah user Rp 10.000/user)',
                                        'Penyimpanan Database Pelanggan (12.000 Data)',
                                    ]),
                                ],
                                [
                                    'name' => 'Enterprise', 'pop' => true,
                                    'tagline' => 'Untuk bisnis berkembang dengan manajemen yang lebih lengkap.',
                                    'periods' => [
                                        ['months' => 1, 'price_per_month' => 399000],
                                        ['months' => 6, 'price_per_month' => 349000],
                                        ['months' => 12, 'price_per_month' => 329000],
                                    ],
                                    'features' => [
                                        'Semua fitur paket Basic',
                                        'Manajemen Pengaturan Meja',
                                        'Menu HPP',
                                        'Laporan Keuangan',
                                        'Maks 5 User (tambah user Rp 10.000/user)',
                                        'Penyimpanan Database Pelanggan (50.000 Data)',
                                    ],
                                ],
                            ];
                            $waCustom = 'https://wa.me/6282362211676?text=' . rawurlencode('Halo, saya tertarik dengan paket Customize Mooda (kontrak 2 tahun). Boleh info fitur & harga rekomendasinya?');
                            $customFeatures = [
                                'Semua fitur Enterprise & Basic',
                                'Tanpa batasan jumlah user',
                                'Penyimpanan Database Pelanggan (Tidak Terbatas)',
                                'VPS & domain sendiri',
                                'QR Menu & Self Order pelanggan',
                                'Payment Gateway + Setting Payment',
                                'Tambah fitur / menu khusus (maks 3; lebih dari itu kena charge tambahan)',
                                'Harga rekomendasi sesuai pilihan fitur',
                                'Konsultasi & support prioritas',
                            ];
                        @endphp

                        {{-- 1) STARTER — akun Deposit (bayar sesuai pemakaian) --}}
                        <div class="relative flex flex-col rounded-3xl border border-slate-200 bg-white p-8">
                            <h3 class="text-xl font-bold text-slate-900">Starter</h3>
                            <p class="mt-1.5 min-h-[40px] text-sm text-slate-500">Bayar sesuai pemakaian (deposit saldo) — cocok untuk baru mulai / musiman.</p>
                            <div class="mt-4">
                                <div class="flex items-end gap-1">
                                    <span class="text-4xl font-extrabold tracking-tight text-slate-900">Deposit</span>
                                    <span class="pb-1 text-sm text-slate-500">/isi saldo</span>
                                </div>
                                <div class="mt-1 text-sm text-slate-500">Top-up mulai Rp 25.000 · potong Rp 169 / transaksi</div>
                            </div>
                            <ul class="mt-5 flex-1 space-y-2.5">
                                @foreach ($starterFeatures as $f)
                                    <li class="flex items-start gap-2.5 text-sm text-slate-700">{!! $check !!}<span>{{ $f }}</span></li>
                                @endforeach
                            </ul>
                            @if ($maint)
                                <span title="{{ $maintText }}" class="mooda-soon-btn w-full mt-7">{!! $soonSvg !!} {{ $maintText }}</span>
                            @else
                                <a href="{{ route('register') }}" class="mt-7 inline-flex w-full items-center justify-center rounded-xl px-6 py-3 text-sm font-semibold transition bg-indigo-600 text-white hover:bg-indigo-700">Mulai Deposit</a>
                            @endif
                        </div>

                        {{-- 2) BASIC & 3) ENTERPRISE — langganan bulanan (pill durasi) --}}
                        @foreach ($monthlyPlans as $mp)
                            @php
                                $periods = $mp['periods'];
                                $basePpm = $periods[0]['price_per_month'] ?? 0;
                                $defIdx = 0; $lowest = PHP_INT_MAX;
                                foreach ($periods as $i => $p) { if ($p['price_per_month'] < $lowest) { $lowest = $p['price_per_month']; $defIdx = $i; } }
                                $def = $periods[$defIdx];
                                $defMonths = (int) $def['months'];
                                $defTotal = (int) $def['price_per_month'] * $defMonths;
                                $defDisc = $basePpm > 0 ? (int) round((1 - $def['price_per_month'] / $basePpm) * 100) : 0;
                                $defNote = $defMonths <= 1 ? 'Tanpa komitmen'
                                    : 'Bayar ' . $defMonths . ' bln di muka · total Rp ' . number_format($defTotal, 0, ',', '.') . ($defDisc > 0 ? ' · Hemat ' . $defDisc . '%' : '');
                            @endphp
                            <div class="relative flex flex-col rounded-3xl border bg-white p-8 {{ $mp['pop'] ? 'border-slate-200 ring-2 ring-indigo-500 shadow-xl' : 'border-slate-200' }}">
                                @if ($mp['pop'])<span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-indigo-500 to-emerald-500 px-4 py-1 text-xs font-bold text-white shadow">Populer</span>@endif
                                <h3 class="text-xl font-bold text-slate-900">{{ $mp['name'] }}</h3>
                                <p class="mt-1.5 min-h-[40px] text-sm text-slate-500">{{ $mp['tagline'] }}</p>
                                <div class="mt-4" data-plan-pricing>
                                    <div class="flex items-end gap-1">
                                        <span class="text-4xl font-extrabold tracking-tight text-slate-900" data-price-display>Rp {{ number_format($def['price_per_month'], 0, ',', '.') }}</span>
                                        <span class="pb-1 text-sm text-slate-500">/bulan</span>
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500" data-price-note>{{ $defNote }}</div>
                                    <div class="plan-dur-wrap mt-4" role="tablist" aria-label="Pilih durasi langganan">
                                        @foreach ($periods as $i => $per)
                                            @php
                                                $ppm = (int) $per['price_per_month']; $pm = (int) $per['months'];
                                                $total = $ppm * $pm;
                                                $disc = $basePpm > 0 ? (int) round((1 - $ppm / $basePpm) * 100) : 0;
                                                $short = $pm == 1 ? 'Bulanan' : $pm . ' Bulan';
                                            @endphp
                                            <button type="button" class="plan-dur-btn {{ $i === $defIdx ? 'is-active' : '' }}"
                                                data-ppm="{{ $ppm }}" data-total="{{ $total }}" data-months="{{ $pm }}" data-disc="{{ $disc }}">{{ $short }}@if ($disc > 0)<span class="disc">-{{ $disc }}%</span>@endif</button>
                                        @endforeach
                                    </div>
                                </div>
                                <ul class="mt-5 flex-1 space-y-2.5">
                                    @foreach ($mp['features'] as $f)
                                        <li class="flex items-start gap-2.5 text-sm text-slate-700">{!! $check !!}<span>{{ $f }}</span></li>
                                    @endforeach
                                </ul>
                                @if ($maint)
                                    <span title="{{ $maintText }}" class="mooda-soon-btn w-full mt-7">{!! $soonSvg !!} {{ $maintText }}</span>
                                @else
                                    <a href="{{ route('register') }}" class="mt-7 inline-flex w-full items-center justify-center rounded-xl px-6 py-3 text-sm font-semibold transition {{ $mp['pop'] ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-900 text-white hover:opacity-90' }}">Pilih {{ $mp['name'] }}</a>
                                @endif
                            </div>
                        @endforeach

                        {{-- 4) CUSTOMIZE — kontrak 2 tahun, konsultasi WhatsApp --}}
                        <div class="relative flex flex-col rounded-3xl border-2 border-emerald-400 bg-white p-8 shadow-xl shadow-emerald-100">
                            <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-indigo-500 to-emerald-500 px-4 py-1 text-xs font-bold text-white shadow">Fleksibel</span>
                            <h3 class="text-xl font-bold text-slate-900">Customize</h3>
                            <p class="mt-1.5 min-h-[40px] text-sm text-slate-500">Rakit paketmu sendiri — kontrak 2 tahun, fitur menyesuaikan bisnis.</p>
                            <div class="mt-4 flex items-end gap-1">
                                <span class="text-4xl font-extrabold tracking-tight text-slate-900">Custom</span>
                                <span class="pb-1 text-sm text-slate-500">/per 2 tahun</span>
                            </div>
                            <ul class="mt-5 flex-1 space-y-2.5">
                                @foreach ($customFeatures as $f)
                                    <li class="flex items-start gap-2.5 text-sm text-slate-700">{!! $check !!}<span>{{ $f }}</span></li>
                                @endforeach
                            </ul>
                            <a href="{{ $waCustom }}" target="_blank" rel="noopener" class="mt-7 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-emerald-500 px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Konsultasi via WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SLIDE 5 — CTA / FOOTER --}}
            <div class="swiper-slide lp-slide relative" data-nav-dark>
                <div class="absolute inset-0 bg-cover bg-center" style="background-image:url('{{ asset('assets/media/landing/interior.jpg') }}')"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-950/90 via-slate-950/85 to-emerald-950/85"></div>
                <div class="lp-content relative z-10 w-full max-w-2xl px-6 py-24 text-center text-white">
                    <img src="{{ asset('assets/media/logos/mooda-logo-white.png') }}" alt="Mooda" class="logo-outline mx-auto mb-8 h-16 w-auto" draggable="false">
                    <h2 class="text-balance text-3xl font-extrabold sm:text-5xl">Siap membuat restoran Anda lebih efisien?</h2>
                    <p class="mx-auto mt-5 max-w-lg text-lg text-slate-200">Bergabung dengan bisnis kuliner yang sudah beralih ke sistem digital modern.</p>
                    <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        @if (\App\Tenancy\Plan::maintenance())
                            <span class="mooda-soon-btn">{{ \App\Tenancy\Plan::maintenanceText() }}</span>
                        @else
                            <a href="{{ route('register') }}" class="rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-indigo-700 shadow-xl transition hover:bg-slate-100">Daftar Sekarang</a>
                        @endif
                        <a href="{{ route('login') }}" class="rounded-xl border border-white/30 bg-white/10 px-8 py-3.5 text-base font-semibold text-white backdrop-blur transition hover:bg-white/20">Masuk ke Akun</a>
                    </div>
                    <p class="mt-12 text-sm text-slate-400">© {{ date('Y') }} Mooda. Seluruh hak cipta dilindungi.</p>
                </div>
            </div>

        </div>
    </div>

    {{-- ===== NAV ARROWS (transparan) ===== --}}
    <button class="landing-prev fixed left-3 top-1/2 z-40 hidden -translate-y-1/2 place-items-center bg-transparent p-3 text-white mix-blend-difference transition hover:opacity-60 md:grid" aria-label="Sebelumnya">
        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
    </button>
    <button class="landing-next fixed right-3 top-1/2 z-40 hidden -translate-y-1/2 place-items-center bg-transparent p-3 text-white mix-blend-difference transition hover:opacity-60 md:grid" aria-label="Berikutnya">
        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
    </button>

    {{-- ===== PROGRESS + PAGINATION ===== --}}
    <div class="fixed inset-x-0 top-0 z-40 h-1 bg-transparent">
        <div id="prog" class="h-full bg-gradient-to-r from-indigo-500 to-emerald-500 transition-[width] duration-150" style="width:0%"></div>
    </div>
    <div class="landing-pagination"></div>

    {{-- ===== TOMBOL KEMBALI KE ATAS (mobile/tablet, kiri bawah) ===== --}}
    <button id="lp-totop" type="button" class="lp-totop" aria-label="Kembali ke section awal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
    </button>

    {{-- ===== FLOATING BUTTONS (mobile/tablet) ===== --}}
    <div class="lp-fab">
        <a class="lp-fab-order" href="#harga">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 15h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0020 6H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
            <span class="lp-fab-txt">Pesan Sekarang</span>
        </a>
        <a class="lp-fab-contact" href="{{ 'https://wa.me/6282362211676?text=' . rawurlencode('Halo, saya tertarik dengan aplikasi POS Mooda ini') }}" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            <span class="lp-fab-txt">Contact Us</span>
        </a>
    </div>

    {{-- Logo navbar adaptif: putih saat slide gelap (hero/cara kerja/CTA), gelap saat slide terang.
         Pakai IntersectionObserver agar bekerja untuk mode swiper (desktop) & tumpukan vertikal (mobile). --}}
    <script>
        (function () {
            // ===== Logo navbar adaptif: putih di slide gelap, gelap di slide terang =====
            var white = document.getElementById('nav-logo-white');
            var dark = document.getElementById('nav-logo-dark');
            function showDark(isDark) {
                if (!white || !dark) return;
                white.classList.toggle('hidden', !isDark);
                dark.classList.toggle('hidden', isDark);
            }
            if (white && dark) {
                var tries = 0;
                var timer = setInterval(function () {
                    tries++;
                    var el = document.getElementById('landing-swiper');
                    var sw = el && el.swiper;
                    if (sw && sw.slides && sw.slides.length) {
                        clearInterval(timer);
                        // Deterministik dari slide aktif Swiper (anti-flicker; IO tak andal dgn transform).
                        var apply = function () {
                            var slide = sw.slides[sw.activeIndex];
                            showDark(!!(slide && slide.hasAttribute('data-nav-dark')));
                        };
                        sw.on('slideChange', apply);
                        sw.on('slideChangeTransitionStart', apply);
                        sw.on('slideChangeTransitionEnd', apply);
                        apply();
                    } else if (tries > 30) {
                        clearInterval(timer);
                        // Fallback (mobile / swiper nonaktif): IntersectionObserver pada slide gelap.
                        var darkSlides = document.querySelectorAll('[data-nav-dark]');
                        if (!('IntersectionObserver' in window) || !darkSlides.length) return;
                        var set = new Set();
                        var io = new IntersectionObserver(function (entries) {
                            entries.forEach(function (e) { if (e.isIntersecting) { set.add(e.target); } else { set.delete(e.target); } });
                            showDark(set.size > 0);
                        }, { rootMargin: '-16px 0px -88% 0px', threshold: 0 });
                        darkSlides.forEach(function (s) { io.observe(s); });
                    }
                }, 100);
            }

            // ===== Pilihan durasi paket: klik pill -> ubah angka harga & catatan =====
            var rp = function (n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); };
            document.querySelectorAll('[data-plan-pricing]').forEach(function (box) {
                var display = box.querySelector('[data-price-display]');
                var note = box.querySelector('[data-price-note]');
                var btns = box.querySelectorAll('.plan-dur-btn');
                btns.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        btns.forEach(function (b) { b.classList.remove('is-active'); });
                        btn.classList.add('is-active');
                        var ppm = +btn.dataset.ppm, total = +btn.dataset.total, months = +btn.dataset.months, disc = +btn.dataset.disc;
                        if (display) display.textContent = rp(ppm);
                        if (note) note.textContent = months <= 1 ? 'Tanpa komitmen'
                            : ('Bayar ' + months + ' bln di muka · total ' + rp(total) + (disc > 0 ? ' · Hemat ' + disc + '%' : ''));
                    });
                });
            });
        })();
    </script>

</body>

</html>
