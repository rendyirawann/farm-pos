<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Mooda')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    {{-- Layout MINIMAL (tanpa sidebar/header) khusus untuk di-embed via <iframe>, mis. tab Kategori
         di halaman Menu. Memuat aset yang sama agar DataTable/SweetAlert/AJAX tetap berfungsi. --}}
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/mooda-brand.css') }}" rel="stylesheet" type="text/css" />
    @stack('stylesheets')
    <style>
        html, body { background: transparent !important; }
        body { padding: 0; margin: 0; }
        #kt_app_toolbar { display: none !important; } /* sembunyikan breadcrumb saat di-embed */
    </style>
</head>
<body class="bg-transparent">
    <div class="p-1">
        @yield('content')
    </div>

    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    {{-- Kirim X-CSRF-TOKEN di semua AJAX (DELETE/PUT butuh ini). --}}
    <script>if (window.jQuery) { jQuery.ajaxSetup({ headers: { 'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content') } }); }</script>
    @stack('scripts')
</body>
</html>
