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
        /* PENTING: hanya slide SECTION (anak langsung #landing-swiper) yang 100vh.
           Jangan kena carousel harga bersarang (yang juga .swiper-slide) -> pakai selector anak langsung. */
        #landing-swiper,
        #landing-swiper > .swiper-wrapper,
        #landing-swiper > .swiper-wrapper > .swiper-slide { height: 100vh; height: 100dvh; }
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

        /* ===== Harga: CAROUSEL bersarang (2 kartu/tampilan + tombol geser) ===== */
        .mooda-price-shell { position: relative; max-width: 940px; margin-left: auto; margin-right: auto; padding: 0 48px; }
        .mooda-price-carousel { overflow: hidden; padding: 22px 8px 10px; height: auto !important; }
        .mooda-price-carousel .swiper-wrapper { align-items: stretch; height: auto !important; }
        .mooda-price-carousel .swiper-slide { height: auto !important; }   /* kartu setinggi konten (bukan 100vh) */

        /* Tombol navigasi carousel (kiri & kanan) */
        .price-nav { position: absolute; top: 50%; transform: translateY(-50%); z-index: 6;
            width: 40px; height: 40px; border-radius: 999px; border: 1px solid #e2e8f0;
            background: #fff; color: #4f46e5; display: grid; place-items: center; cursor: pointer;
            box-shadow: 0 8px 20px -8px rgba(15,23,42,.3); transition: background .15s, color .15s, transform .12s; }
        .price-nav:hover { background: #4f46e5; color: #fff; border-color: #4f46e5; }
        .price-nav:active { transform: translateY(-50%) scale(.92); }
        .price-prev { left: 0; }
        .price-next { right: 0; }
        .price-nav svg { width: 20px; height: 20px; }

        /* ===== Kartu harga versi RINGKAS: kecilkan padding & ukuran teks (override utility hanya di dalam kartu) ===== */
        .mooda-price-carousel .p-8 { padding: 1.3rem 1.35rem 1.45rem !important; }
        .mooda-price-carousel .rounded-3xl { border-radius: 1.15rem !important; }
        .mooda-price-carousel h3 { font-size: 1.02rem !important; line-height: 1.2 !important; }          /* nama paket */
        .mooda-price-carousel .text-4xl { font-size: 1.55rem !important; line-height: 1.1 !important; }    /* angka harga besar */
        .mooda-price-carousel .text-sm { font-size: .78rem !important; line-height: 1.35 !important; }     /* tagline, /bulan, note, fitur */
        .mooda-price-carousel .min-h-\[40px\] { min-height: 30px !important; }
        .mooda-price-carousel .mt-4 { margin-top: .7rem !important; }
        .mooda-price-carousel .mt-5 { margin-top: .8rem !important; }
        .mooda-price-carousel .mt-7 { margin-top: .95rem !important; }
        .mooda-price-carousel .space-y-2\.5 > :not([hidden]) ~ :not([hidden]) { margin-top: .34rem !important; }
        .mooda-price-carousel ul svg { width: 1rem !important; height: 1rem !important; margin-top: .12rem !important; }
        .mooda-price-carousel .py-3 { padding-top: .58rem !important; padding-bottom: .58rem !important; }
        .mooda-price-carousel .plan-dur-wrap { border-radius: 11px; padding: 4px; gap: 4px; }
        .mooda-price-carousel .plan-dur-btn { padding: 5px 10px; font-size: 11px; }
        .mooda-price-carousel .plan-dur-btn .disc { font-size: 9px; margin-left: 4px; }

        /* MOBILE/TABLET (tanpa Swiper): carousel jadi tumpukan vertikal, tombol disembunyikan.
           Aturan global html.lp-mobile .swiper-wrapper{display:block}/.swiper-slide{width:100%} sudah menumpuk kartu. */
        html.lp-mobile .mooda-price-shell { padding: 0; }
        html.lp-mobile .mooda-price-carousel { overflow: visible; padding: 0; }
        html.lp-mobile .mooda-price-carousel .swiper-slide { margin-bottom: 16px; }
        html.lp-mobile .price-nav { display: none !important; }

        /* (Override desktop dipindah ke bawah — SETELAH aturan lp-mobile — agar tak tertimpa.) */

        /* ===== MODE MOBILE/TABLET: scroll VERTIKAL (bukan swiper horizontal) ===== */
        html.lp-mobile, html.lp-mobile body { height: auto; overflow-x: hidden; overflow-y: auto; }
        html.lp-mobile { scroll-padding-top: 72px; scroll-behavior: smooth; }
        html.lp-mobile #landing-swiper { height: auto !important; width: 100%; overflow: visible; }
        html.lp-mobile .swiper-wrapper { display: block !important; height: auto !important; transform: none !important; }
        html.lp-mobile .swiper-slide { width: 100% !important; height: auto !important; }
        html.lp-mobile .lp-slide { overflow: visible; min-height: 100vh; min-height: 100dvh; }
        html.lp-mobile .lp-content { max-height: none !important; overflow: visible !important; padding-bottom: 2.5rem !important; }
        /* Hero di mobile: konten mepet ke atas (bukan ke tengah) supaya tak ada gap besar di atas teks */
        html.lp-mobile .lp-hero { justify-content: flex-start !important; padding-top: 5.5rem !important; padding-bottom: 3rem !important; }
        html.lp-mobile .lp-hero > :first-child { margin-top: 0 !important; }
        html.lp-mobile .lp-hero > :last-child { margin-bottom: 0 !important; }
        html.lp-mobile .landing-pagination,
        html.lp-mobile .landing-prev,
        html.lp-mobile .landing-next,
        html.lp-mobile .lp-scrollhint { display: none !important; }

        /* ===== OVERRIDE DESKTOP (>=992px) — diletakkan SETELAH aturan lp-mobile agar menang ===== */
        @media (min-width: 992px) {
            /* Kartu harga BERJAJAR (grid responsif) di tengah, bukan menumpuk 1 kolom. */
            html.lp-mobile .mooda-price-carousel .swiper-wrapper { display: flex !important; flex-wrap: wrap; justify-content: center; align-items: stretch; gap: 22px; }
            html.lp-mobile .mooda-price-carousel .swiper-slide { width: 330px !important; max-width: 90vw; margin-bottom: 0 !important; }

            /* Section ikut TINGGI KONTEN (tak dipaksa setinggi layar) -> hilangkan gap putih berlebih. */
            html.lp-mobile .lp-slide { min-height: auto !important; }
            html.lp-mobile .lp-content { padding-top: 4.5rem !important; padding-bottom: 4.5rem !important; }
            html.lp-mobile .lp-content > :first-child { margin-top: 0 !important; }
            html.lp-mobile .lp-content > :last-child { margin-bottom: 0 !important; }

            /* Hero: teks di TENGAH vertikal (flex center murni; tanpa auto-margin yang bikin turun). */
            html.lp-mobile .lp-hero { min-height: 86vh !important; justify-content: center !important; padding-top: 4rem !important; padding-bottom: 4rem !important; }
            html.lp-mobile .lp-hero > :first-child { margin-top: 0 !important; }
            html.lp-mobile .lp-hero > :last-child { margin-bottom: 0 !important; }
        }

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
                @if (($partnerLogos ?? collect())->isNotEmpty())
                <a href="#partner" class="rounded-full px-4 py-1.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Partner</a>
                @endif
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
                @if (($partnerLogos ?? collect())->isNotEmpty())
                <a href="#partner" class="block rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Partner</a>
                @endif
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

            {{-- SLIDE 0 — HERO (gaya mockup: dua kolom, latar terang, visual device) --}}
            <div class="lp-hero2 relative">
                <style>
                    .lp-hero2 { background: linear-gradient(160deg,#f6f4ff 0%,#eef2ff 48%,#faf5ff 100%); }
                    .lp-hero2-inner { max-width: 1320px; margin: 0 auto; padding: 118px 28px 64px;
                        display: grid; grid-template-columns: 0.9fr 1.1fr; gap: 40px; align-items: center; }
                    @media (max-width: 960px){ .lp-hero2-inner{ grid-template-columns: 1fr; padding: 104px 20px 44px; text-align: center; } }
                    .lp-hero2-badge { display:inline-flex; align-items:center; gap:8px; background:#fff; border:1px solid #e5e7ff;
                        color:#4f46e5; font-weight:700; font-size:12.5px; padding:7px 15px; border-radius:999px; box-shadow:0 6px 18px -8px rgba(79,70,229,.35); }
                    .lp-hero2 h1 { font-size: clamp(30px,4.5vw,52px); font-weight:800; line-height:1.07; letter-spacing:-.025em; color:#0f172a; margin:18px 0 0; text-wrap:balance; }
                    .lp-hero2 h1 .g { background:linear-gradient(100deg,#4f46e5,#7c3aed 55%,#059669); -webkit-background-clip:text; background-clip:text; color:transparent; }
                    .lp-hero2 .sub { margin-top:18px; color:#475569; font-size:16px; line-height:1.65; max-width:50ch; }
                    @media (max-width:900px){ .lp-hero2 .sub{ margin-inline:auto; } }
                    .lp-hero2-actions { margin-top:26px; display:flex; flex-wrap:wrap; gap:12px; }
                    @media (max-width:900px){ .lp-hero2-actions{ justify-content:center; } }
                    .lp-h2btn { display:inline-flex; align-items:center; gap:8px; border-radius:12px; padding:13px 24px; font-weight:700; font-size:15px; text-decoration:none; transition: transform .12s, background .15s; }
                    .lp-h2btn:active { transform:scale(.97); }
                    .lp-h2btn-primary { background:#4f46e5; color:#fff; box-shadow:0 14px 30px -12px rgba(79,70,229,.7); }
                    .lp-h2btn-primary:hover { background:#4338ca; }
                    .lp-h2btn-ghost { background:#fff; color:#334155; border:1px solid #e2e8f0; }
                    .lp-h2btn-ghost:hover { background:#f8fafc; }
                    .lp-hero2-trust { margin-top:22px; display:flex; align-items:center; gap:12px; }
                    @media (max-width:900px){ .lp-hero2-trust{ justify-content:center; } }
                    .lp-hero2-stars { color:#f59e0b; font-size:16px; letter-spacing:2px; }
                    .lp-hero2-trust em { font-style:normal; color:#64748b; font-size:13px; }
                    .lp-hero2-visual { display:flex; align-items:center; gap:16px; }
                    @media (max-width:960px){ .lp-hero2-visual{ flex-direction:column; } }
                    .lp-hero2-visual .dev { flex:1 1 auto; min-width:0; }
                    .lp-hero2-visual .dev img { width:100%; height:auto; display:block; filter: drop-shadow(0 30px 55px rgba(79,70,229,.22)); }
                    .lp-statcol { display:flex; flex-direction:column; gap:12px; flex:0 0 190px; }
                    @media (max-width:960px){ .lp-statcol{ flex-flow:row wrap; justify-content:center; flex-basis:auto; } }
                    .lp-stat { background:#fff; border-radius:14px; padding:12px 14px; box-shadow:0 16px 40px -20px rgba(15,23,42,.32); display:flex; align-items:center; gap:10px; }
                    .lp-stat .ic { width:36px; height:36px; border-radius:10px; display:grid; place-items:center; color:#fff; font-size:16px; flex:0 0 auto; }
                    .lp-stat .t { font-size:11px; color:#94a3b8; line-height:1; margin-bottom:3px; }
                    .lp-stat .v { font-size:14px; font-weight:800; color:#0f172a; line-height:1.15; }
                </style>
                <div class="lp-hero2-inner">
                    <div>
                        <span class="lp-hero2-badge">★ POS Modern untuk Cafe, Resto &amp; UMKM</span>
                        <h1>Dengan Mooda <span class="g">Usaha jadi Lebih Mudah</span></h1>
                        <p class="sub">Aplikasi POS lengkap untuk membantu kamu mengelola kasir, pesanan, inventori, hingga laporan bisnis dalam satu platform yang mudah digunakan.</p>
                        <div class="lp-hero2-actions">
                            @if (\App\Tenancy\Plan::maintenance())
                                <span class="mooda-soon-btn">{{ \App\Tenancy\Plan::maintenanceText() }}</span>
                            @else
                                <a href="{{ route('register') }}" class="lp-h2btn lp-h2btn-primary">Coba Gratis Sekarang →</a>
                            @endif
                            <a href="#harga" class="lp-h2btn lp-h2btn-ghost">Coba Demo</a>
                        </div>
                        <div class="lp-hero2-trust">
                            <span class="lp-hero2-stars">★★★★★</span>
                            <em>Dipercaya ratusan bisnis di Indonesia</em>
                        </div>
                    </div>
                    <div class="lp-hero2-visual">
                        <div class="dev"><img src="{{ asset('assets/media/landing/hero.webp') }}" alt="Aplikasi Mooda di tablet & ponsel" loading="eager" decoding="async" draggable="false"></div>
                        <div class="lp-statcol">
                            <div class="lp-stat"><div class="ic" style="background:#4f46e5">🛒</div><div><div class="t">Order Masuk</div><div class="v">12</div></div></div>
                            <div class="lp-stat"><div class="ic" style="background:#059669">↑</div><div><div class="t">Omzet Hari Ini</div><div class="v">Rp 18.520.000</div></div></div>
                            <div class="lp-stat"><div class="ic" style="background:#f59e0b">🍳</div><div><div class="t">Kitchen Ready</div><div class="v">8 Pesanan</div></div></div>
                            <div class="lp-stat"><div class="ic" style="background:#7c3aed">▦</div><div><div class="t">QRIS</div><div class="v">Connected</div></div></div>
                        </div>
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

            {{-- ===== SECTION MOCKUP: Dashboard + Showcase + Kenapa + Integrasi (pakai aset WebP) ===== --}}
            <div class="lp-mk">
                <style>
                    .lp-mk { background:#fff; }
                    .lp-mk-wrap { max-width:1320px; margin:0 auto; padding:72px 28px; }
                    .lp-mk-eyebrow { font-size:12px; font-weight:800; letter-spacing:.14em; text-transform:uppercase; color:#4f46e5; }
                    .lp-mk h2 { font-size:clamp(24px,3.4vw,38px); font-weight:800; letter-spacing:-.02em; color:#0f172a; margin:8px 0 0; text-wrap:balance; }
                    .lp-mk .lead { margin-top:10px; color:#64748b; font-size:16px; max-width:60ch; line-height:1.6; }
                    .lp-dash { display:grid; grid-template-columns:1fr 1.1fr; gap:44px; align-items:center; }
                    @media (max-width:900px){ .lp-dash{ grid-template-columns:1fr; gap:28px; } }
                    .lp-dash img { width:100%; height:auto; border-radius:16px; }
                    .lp-check { margin-top:22px; display:grid; gap:12px; padding:0; }
                    .lp-check li { list-style:none; display:flex; align-items:center; gap:12px; color:#334155; font-weight:600; font-size:15px; }
                    .lp-check .ck { width:26px; height:26px; border-radius:8px; background:#eef2ff; color:#4f46e5; display:grid; place-items:center; flex:0 0 auto; }
                    .lp-show { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:18px; }
                    @media (max-width:760px){ .lp-show{ grid-template-columns:1fr; } }
                    .lp-show figure { margin:0; background:linear-gradient(160deg,#f6f4ff,#eef2ff); border-radius:20px; padding:18px; box-shadow:0 24px 50px -28px rgba(15,23,42,.35); }
                    .lp-show img { width:100%; height:auto; border-radius:12px; display:block; }
                    .lp-show figcaption { margin-top:12px; font-weight:700; color:#0f172a; }
                    .lp-show figcaption span { display:block; font-weight:500; color:#64748b; font-size:13px; margin-top:2px; }
                    .lp-why { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-top:26px; }
                    @media (max-width:900px){ .lp-why{ grid-template-columns:1fr 1fr; } }
                    @media (max-width:520px){ .lp-why{ grid-template-columns:1fr; } }
                    .lp-why-card { border:1px solid #eef0f6; border-radius:16px; padding:22px; background:#fff; transition:transform .15s, box-shadow .2s, border-color .2s; }
                    .lp-why-card:hover { transform:translateY(-3px); box-shadow:0 20px 40px -22px rgba(79,70,229,.35); border-color:#e0e7ff; }
                    .lp-why-ic { width:44px; height:44px; border-radius:12px; display:grid; place-items:center; color:#fff; font-size:20px; }
                    .lp-why-card h3 { margin:14px 0 4px; font-size:16px; font-weight:800; color:#0f172a; }
                    .lp-why-card p { margin:0; font-size:13.5px; color:#64748b; line-height:1.55; }
                    .lp-integ { display:flex; flex-wrap:wrap; gap:12px; margin-top:22px; }
                    .lp-integ span { display:inline-flex; align-items:center; gap:8px; background:#f8fafc; border:1px solid #eef0f6; color:#334155; font-weight:600; font-size:14px; padding:10px 16px; border-radius:12px; }
                </style>

                <div class="lp-mk-wrap">
                    <div class="lp-dash">
                        <div>
                            <div class="lp-mk-eyebrow">Dashboard</div>
                            <h2>Pantau Bisnis Anda dari Satu Dashboard</h2>
                            <p class="lead">Semua angka penting — omzet, transaksi, produk terlaris, hingga target harian — dalam satu layar yang mudah dibaca.</p>
                            <ul class="lp-check">
                                @foreach (['Penjualan & Omzet real-time','Produk & Inventori','Laporan Keuangan','Multi Outlet','Manajemen User & Hak Akses'] as $c)
                                    <li><span class="ck"><svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.1 3.1 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg></span>{{ $c }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <img src="{{ asset('assets/media/landing/section2.webp') }}" alt="Dashboard Mooda di laptop & ponsel" loading="lazy" decoding="async" draggable="false">
                    </div>
                </div>

                <div class="lp-mk-wrap" style="padding-top:0;">
                    <div class="lp-mk-eyebrow">Lihat Aplikasinya</div>
                    <h2>Kasir cepat, tampilan bersih</h2>
                    <p class="lead">Antarmuka POS satu layar — pilih menu, tambah keranjang, bayar. Dibuat untuk jam sibuk.</p>
                    <div class="lp-show">
                        <figure>
                            <img src="{{ asset('assets/media/landing/section3.webp') }}" alt="Layar kasir Mooda di tablet" loading="lazy" decoding="async" draggable="false">
                            <figcaption>Kasir / POS di Tablet <span>Pilih menu &amp; bayar dalam satu layar.</span></figcaption>
                        </figure>
                        <figure>
                            <img src="{{ asset('assets/media/landing/section3_1.webp') }}" alt="Mooda di perangkat mobile" loading="lazy" decoding="async" draggable="false">
                            <figcaption>Jalan di HP &amp; Tablet <span>Buka lewat aplikasi atau browser, di mana saja.</span></figcaption>
                        </figure>
                    </div>
                </div>

                <div class="lp-mk-wrap" style="padding-top:0;">
                    <div class="lp-mk-eyebrow">Kenapa Mooda</div>
                    <h2>Kenapa Memilih Mooda?</h2>
                    <div class="lp-why">
                        @php $whys = [
                            ['⚡','#4f46e5','Cepat & Mudah','Antarmuka simpel, transaksi lebih cepat & efisien.'],
                            ['🛡️','#059669','Aman & Terpercaya','Data aman di cloud dengan backup otomatis.'],
                            ['📈','#f59e0b','Bertumbuh Bersama','Fitur lengkap untuk dukung bisnismu berkembang.'],
                            ['🌐','#7c3aed','Akses Dimana Saja','Kelola bisnis dari mana saja lewat semua perangkat.'],
                        ]; @endphp
                        @foreach ($whys as [$ic,$bg,$t,$d])
                            <div class="lp-why-card">
                                <div class="lp-why-ic" style="background:{{ $bg }}">{{ $ic }}</div>
                                <h3>{{ $t }}</h3><p>{{ $d }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="lp-mk-wrap" style="padding-top:0;">
                    <div class="lp-mk-eyebrow">Integrasi</div>
                    <h2>Integrasi Lengkap</h2>
                    <p class="lead">Mooda terhubung dengan berbagai layanan untuk memudahkan bisnismu.</p>
                    <div class="lp-integ">
                        @foreach (['QRIS','Midtrans','DOKU','WhatsApp','Printer Thermal','Google Drive','Cloud Backup'] as $ig)
                            <span>✓ {{ $ig }}</span>
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
                    <div class="mooda-price-shell mt-6">
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

                        <div class="mooda-price-carousel swiper">
                            <div class="swiper-wrapper">

                        {{-- 1) STARTER — akun Deposit (bayar sesuai pemakaian) --}}
                        <div class="swiper-slide relative flex flex-col rounded-3xl border border-slate-200 bg-white p-8">
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
                            <div class="swiper-slide relative flex flex-col rounded-3xl border bg-white p-8 {{ $mp['pop'] ? 'border-slate-200 ring-2 ring-indigo-500 shadow-xl' : 'border-slate-200' }}">
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
                        <div class="swiper-slide relative flex flex-col rounded-3xl border-2 border-emerald-400 bg-white p-8 shadow-xl shadow-emerald-100">
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

                            </div>{{-- /swiper-wrapper --}}
                        </div>{{-- /mooda-price-carousel --}}

                        <button type="button" class="price-nav price-prev" aria-label="Kartu sebelumnya">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                        </button>
                        <button type="button" class="price-nav price-next" aria-label="Kartu berikutnya">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                        </button>
                    </div>{{-- /mooda-price-shell --}}
                </div>
            </div>

            {{-- SLIDE PARTNER — logo tenant berlangganan (marquee). Hanya tampil bila ada logo. --}}
            @php $partnerLogos = $partnerLogos ?? collect(); @endphp
            @if ($partnerLogos->isNotEmpty())
            <style>
                .lp-marquee { overflow: hidden; width: 100%; -webkit-mask-image: linear-gradient(90deg, transparent, #000 7%, #000 93%, transparent); mask-image: linear-gradient(90deg, transparent, #000 7%, #000 93%, transparent); }
                .lp-marquee-track { display: flex; width: max-content; gap: 3.5rem; align-items: center; animation: lp-marquee-scroll 45s linear infinite; }
                .lp-marquee:hover .lp-marquee-track { animation-play-state: paused; }
                @keyframes lp-marquee-scroll { from { transform: translateX(0); } to { transform: translateX(-50%); } }
                .lp-partner { position: relative; display: grid; place-items: center; height: 96px; width: 168px; flex: 0 0 auto; }
                /* Semua logo dipaksa monokrom PUTIH (brightness 0 -> hitam, invert -> putih) agar
                   apa pun warna asli logonya tetap terlihat & seragam di latar gelap. Transparansi tetap. */
                /* Latar PUTIH, logo WARNA ASLI (tanpa grayscale). Logo berlatar putih menyatu ke latar. */
                .lp-partner-img { max-height: 70px; max-width: 150px; object-fit: contain; opacity: .92; transition: opacity .25s, transform .25s; }
                .lp-partner:hover .lp-partner-img { opacity: 1; transform: scale(1.08); }
                .lp-partner-name { position: absolute; bottom: 2px; left: 50%; transform: translateX(-50%) translateY(6px); white-space: nowrap; background: #1e293b; color: #fff; font-size: .72rem; font-weight: 600; padding: 3px 12px; border-radius: 999px; opacity: 0; transition: opacity .2s, transform .2s; pointer-events: none; box-shadow: 0 6px 16px rgba(0,0,0,.18); }
                .lp-partner:hover .lp-partner-name { opacity: 1; transform: translateX(-50%) translateY(0); }
            </style>
            <div id="partner" class="swiper-slide lp-slide relative bg-white">
                <div class="lp-content relative z-10 w-full py-24 text-center">
                    <div class="mx-auto max-w-3xl px-6">
                        <span class="text-sm font-bold uppercase tracking-wider text-indigo-600">Partner Kami</span>
                        <h2 class="mt-3 text-balance text-3xl font-extrabold text-slate-900 sm:text-4xl">Sudah berlangganan bersama Mooda</h2>
                        <p class="mx-auto mt-4 max-w-xl text-lg text-slate-500">Bisnis kuliner yang telah mempercayakan operasional hariannya ke Mooda.</p>
                    </div>
                    @php $reps = max(1, (int) ceil(10 / max(1, $partnerLogos->count()))); @endphp
                    <div class="lp-marquee mt-14">
                        {{-- 2 "setengah" identik; tiap setengah diulang $reps kali agar cukup lebar ->
                             animasi translateX -50% looping MULUS tanpa gap kosong. --}}
                        <div class="lp-marquee-track">
                            @for ($half = 0; $half < 2; $half++)
                                @for ($r = 0; $r < $reps; $r++)
                                    @foreach ($partnerLogos as $p)
                                        <div class="lp-partner" @if($half) aria-hidden="true" @endif>
                                            <img src="{{ $p->image_url }}" alt="{{ $half ? '' : $p->name }}" class="lp-partner-img" loading="lazy" draggable="false">
                                            <span class="lp-partner-name">{{ $p->name }}</span>
                                        </div>
                                    @endforeach
                                @endfor
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ===== CTA + FOOTER (memanjang ke bawah + sosial media) ===== --}}
            <div class="mooda-footer-block" data-nav-dark>
                <style>
                    .mooda-footer-block { background: #0b1020; }
                    .mf-wrap { max-width: 1120px; margin: 0 auto; padding: 0 20px; }
                    .mf-cta-card { position: relative; overflow: hidden; border-radius: 26px; margin-top: 8px;
                        background: linear-gradient(110deg, #4f46e5 0%, #6d28d9 55%, #7c3aed 100%); color: #fff;
                        padding: 36px 34px; display: flex; flex-wrap: wrap; gap: 22px; align-items: center; justify-content: space-between;
                        box-shadow: 0 34px 70px -30px rgba(79,70,229,.65); }
                    .mf-cta-card::after { content: ""; position: absolute; right: -60px; top: -60px; width: 240px; height: 240px;
                        border-radius: 50%; background: rgba(255,255,255,.08); }
                    .mf-cta-card h3 { font-size: clamp(21px, 3vw, 32px); font-weight: 800; line-height: 1.12; margin: 0; letter-spacing: -.02em; }
                    .mf-cta-card p { margin: .55rem 0 0; color: #e0e7ff; font-size: 14.5px; max-width: 46ch; }
                    .mf-cta-actions { display: flex; flex-wrap: wrap; gap: 12px; position: relative; z-index: 1; }
                    .mf-btn { display: inline-flex; align-items: center; gap: 8px; border-radius: 12px; padding: 12px 22px;
                        font-weight: 700; font-size: 14px; text-decoration: none; transition: transform .12s, background .15s, opacity .15s; white-space: nowrap; }
                    .mf-btn:active { transform: scale(.97); }
                    .mf-btn-light { background: #fff; color: #4338ca; }
                    .mf-btn-light:hover { background: #eef2ff; }
                    .mf-btn-ghost { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.4); }
                    .mf-btn-ghost:hover { background: rgba(255,255,255,.22); }

                    .mf-foot { padding: 56px 0 28px; color: #cbd5e1; }
                    .mf-foot-grid { display: grid; grid-template-columns: 1.7fr 1fr 1.2fr 1fr; gap: 30px; }
                    @media (max-width: 860px) { .mf-foot-grid { grid-template-columns: 1fr 1fr; } }
                    @media (max-width: 520px) { .mf-foot-grid { grid-template-columns: 1fr; } }
                    .mf-brand-logo { height: 34px; width: auto; }
                    .mf-tagline { margin-top: 12px; font-size: 13px; line-height: 1.65; color: #94a3b8; max-width: 34ch; }
                    .mf-col-title { font-size: 11px; text-transform: uppercase; letter-spacing: .12em; color: #64748b; font-weight: 800; margin-bottom: 10px; }
                    .mf-col a, .mf-col span { display: block; color: #cbd5e1; text-decoration: none; font-size: 14px; margin-bottom: 6px; word-break: break-word; }
                    .mf-col a:hover { color: #fff; }
                    .mf-social { display: flex; gap: 10px; margin-top: 18px; }
                    .mf-social a { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 10px;
                        background: rgba(255,255,255,.08); color: #cbd5e1; transition: background .15s, color .15s, transform .12s; }
                    .mf-social a:hover { background: #4f46e5; color: #fff; transform: translateY(-2px); }
                    .mf-social svg { width: 18px; height: 18px; }
                    .mf-bottom { border-top: 1px solid rgba(255,255,255,.08); margin-top: 40px; padding-top: 20px; font-size: 12px;
                        color: #64748b; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
                </style>

                <div class="mf-wrap" style="padding-top:48px;">
                    <div class="mf-cta-card">
                        <div>
                            <h3>Siap Membuat Bisnis Anda Lebih Mudah?</h3>
                            <p>Bergabung dengan ratusan bisnis lainnya dan rasakan kemudahan menggunakan Mooda.</p>
                        </div>
                        <div class="mf-cta-actions">
                            @if (\App\Tenancy\Plan::maintenance())
                                <span class="mooda-soon-btn">{{ \App\Tenancy\Plan::maintenanceText() }}</span>
                            @else
                                <a href="{{ route('register') }}" class="mf-btn mf-btn-light">Mulai Gratis Sekarang →</a>
                            @endif
                            <a href="https://wa.me/6282362211676?text={{ rawurlencode('Halo, saya ingin tanya tentang Mooda POS') }}" target="_blank" rel="noopener" class="mf-btn mf-btn-ghost">Hubungi Kami</a>
                        </div>
                    </div>
                </div>

                <div class="mf-wrap mf-foot">
                    <div class="mf-foot-grid">
                        <div>
                            <img src="{{ asset('assets/media/logos/mooda-logo-white.png') }}" alt="Mooda" class="mf-brand-logo">
                            <p class="mf-tagline">POS modern untuk Cafe, Restoran, Coffee Shop, Bakery dan UMKM.</p>
                            <div class="mf-social">
                                <a href="{{ config('mooda.social_instagram', '#') }}" target="_blank" rel="noopener" aria-label="Instagram">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.2c3.2 0 3.6 0 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s0 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58 0-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.7 3.7 0 0 1-1.38-.9 3.7 3.7 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.21 15.58 2.2 15.2 2.2 12s0-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.21 8.8 2.2 12 2.2Zm0 1.8c-3.15 0-3.5 0-4.74.07-.9.04-1.38.19-1.7.31-.43.17-.74.37-1.06.69-.32.32-.52.63-.69 1.06-.12.32-.27.8-.31 1.7C3.4 8.86 3.4 9.2 3.4 12s0 3.14.06 4.39c.04.9.19 1.38.31 1.7.17.43.37.74.69 1.06.32.32.63.52 1.06.69.32.12.8.27 1.7.31 1.24.06 1.59.07 4.74.07s3.5 0 4.74-.07c.9-.04 1.38-.19 1.7-.31.43-.17.74-.37 1.06-.69.32-.32.52-.63.69-1.06.12-.32.27-.8.31-1.7.06-1.25.07-1.59.07-4.39s0-3.14-.07-4.39c-.04-.9-.19-1.38-.31-1.7a2.85 2.85 0 0 0-.69-1.06 2.85 2.85 0 0 0-1.06-.69c-.32-.12-.8-.27-1.7-.31C15.5 4 15.15 4 12 4Zm0 3.07a4.93 4.93 0 1 1 0 9.86 4.93 4.93 0 0 1 0-9.86Zm0 1.8a3.13 3.13 0 1 0 0 6.26 3.13 3.13 0 0 0 0-6.26Zm5.13-.87a1.15 1.15 0 1 1 0 2.3 1.15 1.15 0 0 1 0-2.3Z"/></svg>
                                </a>
                                <a href="{{ config('mooda.social_facebook', '#') }}" target="_blank" rel="noopener" aria-label="Facebook">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z"/></svg>
                                </a>
                                <a href="{{ config('mooda.social_youtube', '#') }}" target="_blank" rel="noopener" aria-label="YouTube">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.12C19.5 3.55 12 3.55 12 3.55s-7.5 0-9.4.53A3 3 0 0 0 .5 6.2 31.2 31.2 0 0 0 0 12a31.2 31.2 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.12c1.9.53 9.4.53 9.4.53s7.5 0 9.4-.53a3 3 0 0 0 2.1-2.12A31.2 31.2 0 0 0 24 12a31.2 31.2 0 0 0-.5-5.8ZM9.55 15.57V8.43L15.82 12l-6.27 3.57Z"/></svg>
                                </a>
                                <a href="{{ config('mooda.social_tiktok', '#') }}" target="_blank" rel="noopener" aria-label="TikTok">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 2h-3.1v13.2a2.5 2.5 0 1 1-2.1-2.46V9.6a5.6 5.6 0 1 0 5.2 5.58V8.63a7 7 0 0 0 4 1.27V6.77a3.9 3.9 0 0 1-2.85-1.32A3.9 3.9 0 0 1 16.5 2Z"/></svg>
                                </a>
                            </div>
                        </div>
                        <div class="mf-col">
                            <div class="mf-col-title">Kontak (CP)</div>
                            <a href="https://wa.me/6282362211676" target="_blank" rel="noopener">0823-6221-1676</a>
                        </div>
                        <div class="mf-col">
                            <div class="mf-col-title">Email</div>
                            <a href="mailto:admin.moodaid@gmail.com">admin.moodaid@gmail.com</a>
                        </div>
                        <div class="mf-col">
                            <div class="mf-col-title">Website</div>
                            <a href="{{ url('/') }}">mooda.id</a>
                        </div>
                    </div>
                    <div class="mf-bottom">
                        <span>© {{ date('Y') }} Mooda. Seluruh hak cipta dilindungi.</span>
                        <span>POS modern untuk Cafe, Resto, Coffee Shop, Bakery &amp; UMKM</span>
                    </div>
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
