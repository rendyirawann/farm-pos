<!DOCTYPE html>
<!--
Author: Rendy Irawan
Product Name: Mooda
Website: http://www.mooda.id
Contact: support@mooda.id
License: Proprietary - Mooda System
-->
<html lang="en">
<!--begin::Head-->

<head>

    <title>@yield('title')</title>
    <meta charset="utf-8" />
    <meta name="description" content="Mooda - Solusi Manajemen Restoran Modern, Kasir Pintar, dan Sistem Antrian Terintegrasi." />
    <meta name="keywords" content="pos, point of sale, kasir restoran, manajemen meja, kasir pintar, mooda, aplikasi restoran" />
    <meta name="author" content="Rendy Irawan" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="id_ID" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="Mooda - Powering Your Restaurant Operations" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:site_name" content="Mooda" />
    <link rel="canonical" href="{{ url()->current() }}" />
    <link rel="icon" type="image/png" href="{{ asset('assets/media/logos/mooda-mark-192.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('assets/media/logos/mooda-mark-192.png') }}" />
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/mooda-mark-192.png') }}" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700&display=swap" media="print" onload="this.media='all'" />
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700&display=swap" /></noscript>
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/mooda-brand.css') }}" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->
    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking)
        if (window.top != window.self) {
            window.top.location.replace(window.self.location.href);
        }
    </script>

    @stack('stylesheets')
</head>
<!--end::Head-->
<!--begin::Body-->

<body id="kt_body" class="app-blank bgi-size-cover bgi-attachment-fixed bgi-position-center">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>
    <!--end::Theme mode setup on page load-->
    <!--begin::Root-->
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <!--begin::Page bg image-->
        <style>
            body {
                background:
                    radial-gradient(1100px 550px at 100% 0%, #eef2ff 0%, rgba(238, 242, 255, 0) 60%),
                    radial-gradient(900px 500px at 0% 100%, #f5f3ff 0%, rgba(245, 243, 255, 0) 55%),
                    #fbfbff;
            }

            [data-bs-theme="dark"] body {
                background:
                    radial-gradient(1100px 550px at 100% 0%, #1e1b3a 0%, rgba(30, 27, 58, 0) 60%),
                    #0f0f1a;
            }
        </style>
        <!--end::Page bg image-->
        <!--begin::Authentication - Sign-in -->
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <!--begin::Body-->
            @yield('content')
            <!--end::Body-->
            <!--begin::Aside-->
            <div class="d-flex flex-lg-row-fluid">
                <!--begin::Content-->
                <div class="d-flex flex-column flex-center pb-0 pb-lg-10 p-10 w-100">
                    <!--begin::Image-->
                    <img class="theme-light-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20"
                        src="{{ asset('assets/media/logos/mooda-logo.png') }}" alt="Mooda" />

                    <img class="theme-dark-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20"
                        src="{{ asset('assets/media/logos/mooda-logo-white.png') }}" alt="Mooda" />

                    <!--end::Image-->
                    <!--begin::Title-->
                    <!-- <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7">Fast, Efficient and Productive</h1> -->
                    <!--end::Title-->
                    <!--begin::Text-->
                    @php
                        // Copy menyesuaikan VERTICAL host (mooda.id = F&B, laundry.mooda.id = Laundry).
                        $vAuth = \App\Verticals\VerticalRegistry::current();
                        $vHead = [
                            'fnb'     => 'Mooda <br> Restaurant POS System',
                            'laundry' => 'Mooda <br> Laundry POS System',
                            'retail'  => 'Mooda <br> Retail POS System',
                            // Peternakan bukan point-of-sale: intinya persediaan &
                            // perdagangan (beli -> lot -> jual bermargin), sehingga
                            // menyebutnya "POS" salah menggambarkan isinya.
                            'farm'    => 'Mooda <br> Stock System',
                        ][$vAuth] ?? 'Mooda <br> POS System';
                        $vDesc = [
                            'fnb'     => 'Aplikasi Point of Sale cerdas untuk membantu restoran Anda dalam mengelola pesanan, manajemen meja, dan mempercepat pelayanan dapur.',
                            'laundry' => 'Aplikasi Point of Sale untuk usaha laundry — kelola layanan kiloan/satuan, pantau status cucian, dan cetak nota otomatis.',
                            'retail'  => 'Aplikasi Point of Sale untuk usaha retail — kelola produk, stok, dan transaksi dalam satu layar.',
                            'farm'    => 'Aplikasi Stock System untuk usaha Anda — catat barang masuk & keluar, pantau stok dan harga pokok, sampai laba per hari.',
                        ][$vAuth] ?? 'Aplikasi Point of Sale cerdas untuk usaha Anda.';
                    @endphp
                    <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7">
                        {!! $vHead !!}
                    </h1>
                    <div class="text-gray-600 fs-base text-center fw-semibold">
                        {{ $vDesc }}
                        <br /><br />
                        <span class="badge badge-light-primary fs-7 fw-bold">Mooda Teknologi Indonesia</span>
                    </div>
                    <!--end::Text-->
                </div>
                <!--end::Content-->
            </div>
            <!--end::Aside-->
        </div>
        <!--end::Authentication - Sign-in-->
    </div>
    <!--end::Root-->
    <!--begin::Javascript-->

    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <!--end::Global Javascript Bundle-->
    @stack('scripts')
    <!--end::Javascript-->
</body>
<!--end::Body-->

</html>
