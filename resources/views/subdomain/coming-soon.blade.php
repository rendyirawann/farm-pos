<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brand }} — Segera Hadir</title>
    <meta name="robots" content="noindex">
    <style>
        :root { color-scheme: light dark; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            text-align: center; padding: 1.5rem;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
        }
        .wrap { max-width: 560px; }
        .icon { font-size: 4rem; margin-bottom: 1rem; }
        .badge {
            display: inline-block; font-size: .75rem; font-weight: 700; letter-spacing: .08em;
            text-transform: uppercase; color: #22c55e; background: rgba(34,197,94,.12);
            border: 1px solid rgba(34,197,94,.35); padding: .35rem .8rem; border-radius: 999px;
            margin-bottom: 1.25rem;
        }
        h1 { font-size: clamp(1.8rem, 5vw, 2.6rem); font-weight: 800; color: #fff; margin-bottom: .75rem; }
        p.tagline { font-size: 1.05rem; line-height: 1.6; color: #94a3b8; margin-bottom: 2rem; }
        .host { font-family: ui-monospace, monospace; font-size: .85rem; color: #64748b; margin-bottom: 2rem; }
        a.home {
            display: inline-flex; align-items: center; gap: .5rem; text-decoration: none;
            font-weight: 700; color: #0f172a; background: #22c55e;
            padding: .8rem 1.6rem; border-radius: .7rem; transition: transform .15s ease, filter .15s ease;
        }
        a.home:hover { transform: translateY(-2px); filter: brightness(1.05); }
        footer { margin-top: 2.5rem; font-size: .8rem; color: #475569; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="icon">{{ $icon }}</div>
        <span class="badge">Segera Hadir</span>
        <h1>{{ $brand }}</h1>
        <p class="tagline">{{ $tagline }}</p>
        <div class="host">{{ $subdomain }}</div>
        <a class="home" href="https://mooda.id">← Kembali ke Mooda</a>
        <footer>&copy; {{ date('Y') }} Mooda — Aplikasi Kasir &amp; POS</footer>
    </div>
</body>
</html>
