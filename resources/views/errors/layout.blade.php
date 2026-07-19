{{-- Layout halaman error ber-branding Mooda. STANDALONE: tanpa akses DB/komponen app,
     agar tetap tampil rapi walau saat error 500/503. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · @yield('title') — {{ config('app.name', 'Mooda') }}</title>
    <link rel="icon" href="{{ asset('assets/media/logos/mooda-logo.png') }}">
    <style>
        :root { --brand:#5b4bdb; --brand2:#7c3aed; --ink:#1e2233; --muted:#7a819b; }
        * { box-sizing:border-box; }
        html,body { height:100%; margin:0; }
        body {
            font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
            background:radial-gradient(1200px 600px at 50% -10%, #eef0ff 0%, #f6f7fb 55%);
            color:var(--ink); display:flex; align-items:center; justify-content:center;
            min-height:100vh; padding:24px;
        }
        .wrap { width:100%; max-width:520px; text-align:center; }
        .logo { height:42px; margin-bottom:26px; }
        .card {
            background:#fff; border-radius:22px; padding:46px 36px;
            box-shadow:0 24px 60px rgba(43,42,99,.12); position:relative; overflow:hidden;
        }
        .card::before {
            content:""; position:absolute; inset:0 0 auto 0; height:5px;
            background:linear-gradient(90deg,var(--brand),var(--brand2));
        }
        .code {
            font-size:86px; font-weight:800; line-height:1; letter-spacing:-2px; margin:6px 0 0;
            background:linear-gradient(135deg,var(--brand),var(--brand2));
            -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent;
        }
        .icon { font-size:40px; margin:2px 0 4px; }
        h1 { font-size:22px; font-weight:700; margin:8px 0 10px; }
        p  { color:var(--muted); font-size:15px; line-height:1.6; margin:0 0 26px; }
        .actions { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }
        .btn {
            display:inline-flex; align-items:center; gap:8px; text-decoration:none; cursor:pointer;
            border:0; border-radius:12px; padding:12px 22px; font-size:14px; font-weight:600;
            font-family:inherit;
        }
        .btn-primary { background:linear-gradient(135deg,var(--brand),var(--brand2)); color:#fff; }
        .btn-primary:hover { filter:brightness(1.05); }
        .btn-ghost { background:#eef0f8; color:var(--ink); }
        .btn-ghost:hover { background:#e4e7f4; }
        .foot { margin-top:22px; color:#9aa0b9; font-size:12px; }
    </style>
</head>
<body>
    <div class="wrap">
        <img src="{{ asset('assets/media/logos/mooda-logo.png') }}" alt="Mooda" class="logo">
        <div class="card">
            <div class="code">@yield('code')</div>
            <div class="icon">@yield('icon')</div>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>
            <div class="actions">@yield('actions')</div>
        </div>
        <div class="foot">© {{ date('Y') }} {{ config('app.name', 'Mooda') }} — Aplikasi Kasir POS</div>
    </div>
</body>
</html>
