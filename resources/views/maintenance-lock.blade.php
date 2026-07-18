<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Sedang Pemeliharaan — Mooda</title>
    <link rel="icon" href="{{ asset('assets/media/logos/favicon.ico') }}" />
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
            background:linear-gradient(135deg,#4f46e5 0%,#1b84ff 100%);min-height:100vh;
            display:flex;align-items:center;justify-content:center;padding:20px}
        .card{background:#fff;border-radius:20px;max-width:440px;width:100%;padding:40px 32px;
            text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.25)}
        .icon{width:84px;height:84px;border-radius:50%;background:#fff4de;display:flex;align-items:center;
            justify-content:center;margin:0 auto 22px;font-size:42px}
        h1{font-size:22px;color:#1e2129;margin-bottom:12px;font-weight:800}
        p{color:#5e6278;font-size:15px;line-height:1.6;margin-bottom:28px}
        .btn{display:inline-block;width:100%;border:0;cursor:pointer;background:#4f46e5;color:#fff;
            font-size:16px;font-weight:700;padding:14px 20px;border-radius:12px;transition:.15s}
        .btn:hover{background:#4338ca}
        .note{margin-top:18px;font-size:12px;color:#a1a5b7}
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🛠️</div>
        <h1>Sedang Dalam Pemeliharaan</h1>
        <p>{{ $message ?? 'Aplikasi sedang dalam pemeliharaan. Mohon maaf atas ketidaknyamanannya.' }}</p>

        <form method="POST" action="{{ route('logout') }}" id="logout-form">
            @csrf
            <button type="submit" class="btn">OK, Keluar</button>
        </form>

        <div class="note">Anda akan keluar dari sesi saat ini secara otomatis.</div>
    </div>
</body>
</html>
