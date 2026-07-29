<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tentang Kami — Mooda</title>
    <meta name="description" content="Profil perusahaan Mooda — solusi Point of Sales (POS) berbasis cloud untuk UMKM F&B Indonesia.">
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/mooda-mark-192.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        :root{--indigo:#4f46e5;--indigo2:#7c3aed;--ink:#0f172a;--muted:#475569;--soft:#64748b;--line:#e7e8f2;
              --dark1:#070b26;--dark2:#0d1240;--dark3:#181c56;--light:#f6f6fc}
        html{scroll-behavior:smooth}
        body{font-family:'Plus Jakarta Sans',system-ui,-apple-system,sans-serif;color:var(--ink);background:#fff;line-height:1.6;-webkit-font-smoothing:antialiased;overflow-x:hidden}
        img{max-width:100%;display:block}
        a{color:inherit;text-decoration:none}
        .wrap{max-width:1100px;margin:0 auto;padding:0 24px;position:relative;z-index:5}

        /* ============ EFEK BACKGROUND GELAP (rekonstruksi gambar 2) ============ */
        .dark-sec{position:relative;overflow:hidden;background:linear-gradient(160deg,var(--dark1) 0%,var(--dark2) 45%,#10143f 100%);color:#fff}
        .fx{position:absolute;inset:0;z-index:0;pointer-events:none}
        .glow{position:absolute;border-radius:50%;filter:blur(70px)}
        .glow.purpleL{width:420px;height:420px;background:#6d28d9;opacity:.5;left:-160px;bottom:-180px}
        .glow.purpleR{width:380px;height:380px;background:#7c3aed;opacity:.55;right:-120px;top:12%}
        .glow.blueC{width:520px;height:300px;background:#1e3a8a;opacity:.5;left:30%;bottom:-140px;border-radius:50%}
        .orb{position:absolute;border-radius:50%;background:radial-gradient(circle at 35% 30%,#fff,#a5c8ff 45%,rgba(99,102,241,0) 75%);box-shadow:0 0 26px 6px rgba(147,197,253,.55)}
        .star{position:absolute;width:5px;height:5px;border-radius:50%;background:#dbe4ff;box-shadow:0 0 10px 3px rgba(191,209,255,.75);opacity:.9}
        .dots{position:absolute;background-image:radial-gradient(rgba(140,151,255,.55) 1.5px,transparent 1.6px);background-size:15px 15px;opacity:.5}
        .ribbons{position:absolute;inset:0;width:100%;height:100%}

        /* ============ EFEK BACKGROUND TERANG (rekonstruksi gambar 3) ============ */
        .light-sec{position:relative;overflow:hidden;background:
            radial-gradient(560px 360px at 88% 8%,rgba(199,210,254,.5),transparent 70%),
            radial-gradient(480px 320px at 4% 30%,rgba(199,210,254,.42),transparent 70%),
            radial-gradient(620px 420px at 70% 100%,rgba(221,214,254,.38),transparent 70%),
            var(--light)}
        .light-sec .dots{background-image:radial-gradient(rgba(129,140,248,.4) 1.5px,transparent 1.6px);opacity:.55}
        .lwave{position:absolute;inset:0;pointer-events:none;z-index:0}

        /* ============ WAVE PEMBATAS ============ */
        .wave{position:relative;display:block;width:100%;line-height:0;z-index:4}
        .wave svg{display:block;width:100%;height:96px}
        @media(max-width:640px){.wave svg{height:56px}}

        /* ============ NAVBAR ============ */
        .nav{position:relative;z-index:10}
        .nav .wrap{height:76px;display:flex;align-items:center;justify-content:space-between;gap:16px;max-width:1160px}
        .brand{display:flex;align-items:center;gap:9px}
        .brand .bm{width:36px;height:36px;border-radius:11px;background:#fff;display:grid;place-items:center;box-shadow:0 8px 20px -8px rgba(0,0,0,.6)}
        .brand .bm img{height:22px;width:auto}
        .brand span{font-weight:800;font-size:18px;color:#fff}
        .navlinks{display:flex;align-items:center;gap:4px}
        .navlinks a{font-size:13.5px;font-weight:600;color:#c7d2fe;padding:8px 13px;border-radius:999px;transition:.15s}
        .navlinks a:hover{color:#fff;background:rgba(255,255,255,.08)}
        .navlinks a.on{color:#fff;background:rgba(255,255,255,.14)}
        .backbtn{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:#e0e7ff;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);padding:9px 18px;border-radius:999px;transition:.15s;white-space:nowrap}
        .backbtn:hover{background:rgba(255,255,255,.2)}
        .backbtn i{width:6px;height:6px;border-radius:50%;background:#a5b4fc;display:inline-block}
        @media(max-width:900px){.navlinks{display:none}}

        /* ============ HERO ============ */
        .hero{text-align:center;padding:64px 24px 150px}
        .hero .eyebrow{font-size:12px;font-weight:800;letter-spacing:.3em;color:#a5b4fc;text-transform:uppercase}
        .hero h1{font-size:clamp(34px,6vw,54px);font-weight:800;letter-spacing:-.02em;margin:12px 0 16px;color:#fff}
        .hero p{color:#c7d2fe;max-width:620px;margin:0 auto 30px;font-size:15.5px}
        .cta{display:inline-flex;align-items:center;gap:9px;background:linear-gradient(120deg,#6366f1,#7c3aed);color:#fff;font-weight:700;font-size:14px;padding:13px 26px;border-radius:999px;box-shadow:0 18px 40px -14px rgba(99,102,241,.8);transition:.18s}
        .cta:hover{transform:translateY(-2px);box-shadow:0 24px 48px -14px rgba(99,102,241,.9)}

        /* ============ HEADING SECTION ============ */
        .sec-head{display:flex;align-items:center;gap:12px;margin-bottom:28px}
        .sec-head .bar{width:5px;height:26px;border-radius:3px;background:linear-gradient(180deg,var(--indigo),var(--indigo2));flex:0 0 auto}
        .sec-head h2{font-size:24px;font-weight:800;letter-spacing:-.01em}
        .sec-head.center{justify-content:center}

        /* ============ IDENTITAS ============ */
        .ident{padding:64px 0 84px}
        .ident-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:44px;align-items:center}
        @media(max-width:880px){.ident-grid{grid-template-columns:1fr}}
        .idrows{border:1px solid var(--line);border-radius:18px;overflow:hidden;background:#fff;box-shadow:0 24px 60px -38px rgba(49,46,129,.5)}
        .idrow{display:grid;grid-template-columns:190px 1fr;border-top:1px solid var(--line)}
        .idrow:first-child{border-top:0}
        .idrow .k{background:#f4f5fb;font-weight:700;font-size:13.5px;padding:14px 18px;color:#334155}
        .idrow .v{padding:14px 18px;font-size:14px;color:var(--muted)}
        @media(max-width:560px){.idrow{grid-template-columns:1fr}.idrow .k{border-bottom:1px solid var(--line)}}
        .device{position:relative}
        .device img{width:100%;height:auto;border-radius:16px;filter:drop-shadow(0 34px 50px rgba(49,46,129,.35))}

        /* ============ STRUKTUR ORGANISASI ============ */
        .org{padding:60px 0 84px}
        .org .sec-head h2{color:#fff;font-size:25px}
        .org .sec-head .bar{background:linear-gradient(180deg,#c4b5fd,#f0abfc)}
        .org-sub{text-align:center;color:#b6c2fe;max-width:560px;margin:-12px auto 42px;font-size:14px}
        .team{display:flex;align-items:stretch;justify-content:center;gap:26px}
        .member{background:linear-gradient(165deg,#ffffff,#eef0ff);border-radius:22px;padding:22px 22px 18px;width:330px;
            box-shadow:0 30px 60px -26px rgba(0,0,0,.65);transition:transform .22s;display:flex;flex-direction:column}
        .member:hover{transform:translateY(-6px)}
        .m-head{display:flex;align-items:center;gap:15px;margin-bottom:12px}
        .frame{width:82px;height:82px;border-radius:50%;padding:3px;flex:0 0 auto;background:linear-gradient(135deg,#818cf8,#c084fc)}
        .photo{width:100%;height:100%;border-radius:50%;overflow:hidden;background:linear-gradient(135deg,#e0e7ff,#f3e8ff);display:grid;place-items:center;border:3px solid #fff}
        .photo img{width:100%;height:100%;object-fit:cover}
        .ph-empty{color:#a5b4fc;text-align:center}
        .ph-empty svg{width:34px;height:34px;margin:0 auto}
        .m-head .name{font-weight:800;font-size:15.5px;color:var(--ink);line-height:1.3}
        .pos{display:inline-block;margin-top:7px;font-size:10.5px;font-weight:800;letter-spacing:.05em;color:#fff;padding:4px 12px;border-radius:999px}
        .m-center .pos{background:linear-gradient(135deg,#6366f1,#a855f7)}
        .m-left  .pos{background:linear-gradient(135deg,#ec4899,#a855f7)}
        .m-right .pos{background:linear-gradient(135deg,#06b6d4,#10b981)}
        .member .bio{font-size:12.5px;color:var(--soft);line-height:1.6;flex:1}
        .socials{display:flex;gap:8px;margin-top:14px}
        .socials a{width:30px;height:30px;border-radius:50%;display:grid;place-items:center;background:#eef2ff;color:#4f46e5;transition:.15s}
        .socials a:hover{background:var(--indigo);color:#fff}
        .socials svg{width:14px;height:14px}
        @media(min-width:881px){
            .m-left{order:1}.m-center{order:2;transform:translateY(-10px)}.m-right{order:3}
            .m-center:hover{transform:translateY(-16px)}
        }
        @media(max-width:880px){
            .team{flex-direction:column;align-items:center;gap:22px}
            .member{order:0!important;width:100%;max-width:380px;transform:none!important}
        }

        /* ============ PROFIL ============ */
        .profsec{padding:70px 0 10px}
        .pcard{position:relative;display:grid;grid-template-columns:86px 1fr;gap:0;border-radius:18px;background:#fff;border:1px solid var(--line);
            box-shadow:0 22px 50px -38px rgba(49,46,129,.55);margin-bottom:18px;overflow:hidden}
        .pcard .ic{display:grid;place-items:center;background:#f4f5fd;border-right:1px solid var(--line)}
        .pcard .ic svg{width:34px;height:34px;color:var(--indigo)}
        .pcard .tx{padding:20px 24px}
        .pcard h3{font-size:14.5px;font-weight:800;color:var(--indigo);margin-bottom:6px}
        .pcard p{font-size:13.8px;color:var(--muted)}
        @media(max-width:560px){.pcard{grid-template-columns:64px 1fr}}

        /* ============ VISI MISI ============ */
        .vmsec{padding:44px 0 26px}
        .vm{display:grid;grid-template-columns:1fr 1fr;gap:22px}
        @media(max-width:760px){.vm{grid-template-columns:1fr}}
        .vmbox{border-radius:20px;padding:26px 26px 28px;position:relative;overflow:hidden}
        .vmbox.visi{background:linear-gradient(150deg,#eef0ff,#e7e4fb);border:1px solid #dfe0f8}
        .vmbox.misi{background:linear-gradient(150deg,#ecfdf5,#e2f8ec);border:1px solid #d3f1e2}
        .vmbox .vic{width:46px;height:46px;border-radius:13px;display:grid;place-items:center;margin-bottom:14px}
        .visi .vic{background:#e0e2ff;color:#4f46e5}
        .misi .vic{background:#d3f5e4;color:#059669}
        .vmbox .vic svg{width:24px;height:24px}
        .vmbox h3{font-size:13px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;margin-bottom:10px}
        .visi h3{color:#4f46e5}.misi h3{color:#059669}
        .vmbox p{font-size:14px;color:#3f4658}
        .misi ul{list-style:none}
        .misi li{display:flex;gap:10px;font-size:13.8px;color:#3f4658;margin-bottom:10px}
        .misi li .ck{flex:0 0 auto;width:19px;height:19px;border-radius:50%;background:#10b981;color:#fff;display:grid;place-items:center;margin-top:2px}
        .misi li .ck svg{width:11px;height:11px}

        /* ============ STRIP FITUR ============ */
        .strip{padding:26px 0 84px}
        .strip-card{display:flex;flex-wrap:wrap;justify-content:space-around;gap:6px;background:#fff;border:1px solid var(--line);border-radius:18px;padding:18px 14px;box-shadow:0 22px 50px -38px rgba(49,46,129,.5)}
        .strip-item{display:flex;align-items:center;gap:10px;font-size:13px;font-weight:700;color:#334155;padding:6px 14px}
        .strip-item svg{width:19px;height:19px;color:var(--indigo)}
        .strip-item + .strip-item{border-left:1px solid var(--line)}
        @media(max-width:760px){.strip-card{flex-direction:column;align-items:flex-start}.strip-item + .strip-item{border-left:0;border-top:1px solid var(--line);width:100%;padding-top:12px}}

        /* ============ FOOTER ============ */
        .foot{padding:64px 0 0}
        .fgrid{display:grid;grid-template-columns:1.4fr 1fr 1.1fr 1.1fr;gap:34px}
        @media(max-width:860px){.fgrid{grid-template-columns:1fr 1fr}}
        @media(max-width:540px){.fgrid{grid-template-columns:1fr}}
        .fbrand{display:flex;align-items:center;gap:9px;margin-bottom:12px}
        .fbrand .bm{width:34px;height:34px;border-radius:10px;background:#fff;display:grid;place-items:center}
        .fbrand .bm img{height:20px;width:auto}
        .fbrand span{font-weight:800;font-size:17px;color:#fff}
        .foot p,.foot a,.foot li{font-size:13px;color:#9aa7d9}
        .foot h4{font-size:12px;font-weight:800;letter-spacing:.1em;color:#e0e7ff;text-transform:uppercase;margin-bottom:14px}
        .foot ul{list-style:none}
        .foot li{margin-bottom:9px}
        .foot a:hover{color:#fff}
        .fk{display:flex;gap:9px;align-items:flex-start;margin-bottom:9px}
        .fk svg{width:15px;height:15px;color:#818cf8;flex:0 0 auto;margin-top:3px}
        .fbottom{border-top:1px solid rgba(255,255,255,.1);margin-top:44px;padding:20px 0;text-align:center;font-size:12.5px;color:#8892c4}
    </style>
</head>
<body>
    @php
        $identitas = [
            ['Nama Perusahaan', 'CV Mooda Teknologi Indonesia'],
            ['Bidang', 'Teknologi / Software (SaaS) — Point of Sales'],
            ['Alamat Lengkap', 'Jl. Tengku Raja Muda No.23, Lubuk Pakam'],
            ['Telepon &amp; Email', '0857-6036-6666 &nbsp;·&nbsp; hello@mooda.id'],
            ['Website', 'www.mooda.id'],
        ];
        $icLinkedin = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1s2.48 1.12 2.48 2.5zM.22 8.09h4.56V23H.22V8.09zM8.34 8.09h4.37v2.03h.06c.61-1.15 2.1-2.37 4.32-2.37 4.62 0 5.47 3.04 5.47 6.99V23h-4.55v-7.32c0-1.75-.03-4-2.44-4-2.44 0-2.81 1.9-2.81 3.87V23H8.34V8.09z"/></svg>';
        $icInstagram = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2.5" y="2.5" width="19" height="19" rx="5.5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.6" cy="6.4" r="1.3" fill="currentColor" stroke="none"/></svg>';
        $icWa = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.08-.3-.15-1.26-.47-2.39-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.44-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.06 2.87 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.62.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41-.08-.13-.28-.2-.57-.35zM12.05 21.79h-.01a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 01-1.51-5.26c0-5.45 4.44-9.88 9.9-9.88a9.82 9.82 0 019.88 9.9c0 5.45-4.43 9.87-9.89 9.87zm8.42-18.3A11.82 11.82 0 0012.05 0C5.5 0 .16 5.34.16 11.9c0 2.1.55 4.14 1.59 5.94L.06 24l6.3-1.65a11.88 11.88 0 005.68 1.45c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.16-3.47-8.41z"/></svg>';
    @endphp

    {{-- ============================ AREA GELAP ATAS: NAV + HERO ============================ --}}
    <div class="dark-sec">
        <div class="fx">
            {{-- ribbon halus (kurva transparan seperti gambar 2) --}}
            <svg class="ribbons" viewBox="0 0 1440 620" preserveAspectRatio="none" fill="none">
                <defs>
                    <linearGradient id="rg1" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#a5b4fc" stop-opacity=".5"/><stop offset="1" stop-color="#7c3aed" stop-opacity=".05"/>
                    </linearGradient>
                    <linearGradient id="rg2" x1="1" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#c4b5fd" stop-opacity=".45"/><stop offset="1" stop-color="#4338ca" stop-opacity=".04"/>
                    </linearGradient>
                </defs>
                <path d="M-80 140 C 220 20, 420 260, 300 420 C 220 530, 40 560, -80 500 Z" fill="url(#rg1)" opacity=".28"/>
                <path d="M-60 170 C 240 60, 430 280, 310 430" stroke="#b9c4ff" stroke-opacity=".5" stroke-width="1.6"/>
                <path d="M1520 120 C 1260 90, 1180 320, 1300 450 C 1380 540, 1520 540, 1560 480 Z" fill="url(#rg2)" opacity=".32"/>
                <path d="M1510 150 C 1280 120, 1200 330, 1315 445" stroke="#cdb8ff" stroke-opacity=".5" stroke-width="1.6"/>
                <path d="M-40 520 C 360 400, 900 620, 1480 470" stroke="#8ea0ff" stroke-opacity=".28" stroke-width="1.4"/>
                <path d="M-40 555 C 380 440, 920 650, 1480 505" stroke="#8ea0ff" stroke-opacity=".18" stroke-width="1.2"/>
            </svg>
            <span class="glow purpleL"></span><span class="glow purpleR"></span><span class="glow blueC"></span>
            <span class="orb" style="width:34px;height:34px;top:250px;left:15%"></span>
            <span class="orb" style="width:26px;height:26px;top:56%;right:12%"></span>
            <span class="star" style="top:110px;left:32%"></span>
            <span class="star" style="top:210px;left:56%;width:4px;height:4px"></span>
            <span class="star" style="top:330px;right:28%"></span>
            <span class="star" style="top:64%;left:44%;width:4px;height:4px"></span>
            <span class="star" style="top:40%;left:8%;width:4px;height:4px"></span>
            <span class="dots" style="width:150px;height:110px;top:70px;right:6%"></span>
            <span class="dots" style="width:150px;height:110px;bottom:120px;left:4%"></span>
        </div>

        {{-- NAVBAR --}}
        <div class="nav">
            <div class="wrap">
                <a class="brand" href="{{ route('landing') }}">
                    <span class="bm"><img src="{{ asset('assets/media/logos/mooda-mark-192.png') }}" alt="Mooda"></span>
                    <span>mooda</span>
                </a>
                <nav class="navlinks">
                    <a href="{{ route('landing') }}">Beranda</a>
                    <a class="on" href="{{ route('tentang') }}">Tentang Kami</a>
                    <a href="{{ route('landing') }}#fitur">Layanan</a>
                    <a href="{{ route('landing') }}#partner">Portofolio</a>
                    <a href="https://blog.mooda.id">Blog</a>
                    <a href="https://wa.me/6285760366666" target="_blank" rel="noopener">Kontak</a>
                </nav>
                <a class="backbtn" href="{{ route('landing') }}"><i></i> Kembali ke Beranda</a>
            </div>
        </div>

        {{-- HERO --}}
        <div class="hero wrap">
            <div class="eyebrow">Profil Perusahaan</div>
            <h1>Tentang Mooda</h1>
            <p>Solusi Point of Sales (POS) berbasis cloud untuk UMKM F&amp;B Indonesia — terjangkau, mudah, dan membantu usaha naik kelas.</p>
            <a class="cta" href="#identitas">Jelajahi Solusi Kami
                <svg width="15" height="15" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h9.19L9.72 6.03a.75.75 0 111.06-1.06l4.5 4.5a.75.75 0 010 1.06l-4.5 4.5a.75.75 0 11-1.06-1.06l3.22-3.22H3.75A.75.75 0 013 10z" clip-rule="evenodd"/></svg>
            </a>
        </div>

        {{-- wave keluar ke terang --}}
        <div class="wave" style="margin-bottom:-1px">
            <svg viewBox="0 0 1440 96" preserveAspectRatio="none"><path fill="#f6f6fc" d="M0,96 L1440,96 L1440,54 C1200,-10 960,80 720,52 C480,24 220,-18 0,58 Z"/></svg>
        </div>
    </div>

    {{-- ============================ IDENTITAS (terang) ============================ --}}
    <div class="light-sec" id="identitas">
        <span class="dots" style="width:140px;height:100px;top:56px;left:3%"></span>
        <span class="dots" style="width:140px;height:100px;bottom:60px;right:4%"></span>
        <section class="ident">
            <div class="wrap">
                <div class="sec-head"><span class="bar"></span><h2>Identitas Perusahaan</h2></div>
                <div class="ident-grid">
                    <div class="idrows">
                        @foreach ($identitas as [$k, $v])
                            <div class="idrow"><div class="k">{!! $k !!}</div><div class="v">{!! $v !!}</div></div>
                        @endforeach
                    </div>
                    <div class="device">
                        <img src="{{ sc_img('landing','dashboard_img','assets/media/landing/section2.webp') }}" alt="Aplikasi Mooda POS" loading="lazy">
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- ============================ STRUKTUR ORGANISASI (gelap) ============================ --}}
    <div class="dark-sec">
        <div class="fx">
            <svg class="ribbons" viewBox="0 0 1440 520" preserveAspectRatio="none" fill="none">
                <path d="M-60 380 C 320 250, 860 470, 1500 320" stroke="#8ea0ff" stroke-opacity=".25" stroke-width="1.4"/>
                <path d="M-60 420 C 340 300, 880 500, 1500 365" stroke="#8ea0ff" stroke-opacity=".15" stroke-width="1.2"/>
                <path d="M1520 60 C 1300 40, 1220 220, 1330 330 C 1410 400, 1520 390, 1560 340 Z" fill="#7c3aed" opacity=".14"/>
            </svg>
            <span class="glow purpleL" style="left:-180px;top:30%"></span>
            <span class="glow purpleR" style="top:auto;bottom:-120px"></span>
            <span class="orb" style="width:24px;height:24px;top:120px;right:20%"></span>
            <span class="star" style="top:100px;left:22%"></span>
            <span class="star" style="bottom:120px;left:12%;width:4px;height:4px"></span>
            <span class="star" style="top:55%;right:8%"></span>
            <span class="dots" style="width:130px;height:96px;top:60px;right:4%"></span>
            <span class="dots" style="width:130px;height:96px;bottom:70px;left:3%"></span>
        </div>

        {{-- wave masuk dari terang --}}
        <div class="wave" style="transform:rotate(180deg);margin-top:-1px">
            <svg viewBox="0 0 1440 96" preserveAspectRatio="none"><path fill="#f6f6fc" d="M0,96 L1440,96 L1440,54 C1200,-10 960,80 720,52 C480,24 220,-18 0,58 Z"/></svg>
        </div>

        <section class="org">
            <div class="wrap">
                <div class="sec-head center"><span class="bar"></span><h2>Struktur Organisasi</h2></div>
                <p class="org-sub">Tim inti di balik Mooda — perpaduan latar belakang F&amp;B, teknologi, dan sales.</p>
                <div class="team">
                    @foreach ($founders as $f)
                        @php $cls = ['m-center', 'm-left', 'm-right'][$loop->index] ?? ''; @endphp
                        <div class="member {{ $cls }}">
                            <div class="m-head">
                                <div class="frame">
                                    <div class="photo">
                                        @if ($f->photoUrl())
                                            <img src="{{ $f->photoUrl() }}" alt="{{ $f->name }}">
                                        @else
                                            <div class="ph-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 20c0-4 3.6-6 8-6s8 2 8 6"/></svg></div>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <div class="name">{{ $f->name }}</div>
                                    <span class="pos">{{ $f->position }}</span>
                                </div>
                            </div>
                            @if ($f->bio)<p class="bio">{{ $f->bio }}</p>@endif
                            <div class="socials">
                                <a href="#" aria-label="LinkedIn">{!! $icLinkedin !!}</a>
                                <a href="#" aria-label="Instagram">{!! $icInstagram !!}</a>
                                <a href="https://wa.me/6285760366666" target="_blank" rel="noopener" aria-label="WhatsApp">{!! $icWa !!}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- wave keluar ke terang --}}
        <div class="wave" style="margin-bottom:-1px">
            <svg viewBox="0 0 1440 96" preserveAspectRatio="none"><path fill="#f6f6fc" d="M0,96 L1440,96 L1440,44 C1180,110 900,-8 660,40 C420,88 200,10 0,50 Z"/></svg>
        </div>
    </div>

    {{-- ============================ PROFIL + VISI MISI + STRIP (terang) ============================ --}}
    <div class="light-sec">
        <span class="dots" style="width:140px;height:100px;top:80px;right:3%"></span>
        <span class="dots" style="width:140px;height:100px;bottom:340px;left:3%"></span>

        <section class="profsec">
            <div class="wrap">
                <div class="sec-head"><span class="bar"></span><h2>Profil Perusahaan</h2></div>

                <div class="pcard">
                    <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.63 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.58-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/></svg></div>
                    <div class="tx">
                        <h3>Sejarah Singkat Berdiri</h3>
                        <p>Mooda didirikan pada tahun 2026 oleh tiga founder dengan latar belakang F&amp;B, teknologi, dan sales. Berawal dari keprihatinan terhadap masih banyaknya UMKM F&amp;B yang mencatat transaksi secara manual, tim Mooda membangun solusi POS berbasis cloud yang terjangkau dan mudah digunakan.</p>
                    </div>
                </div>

                <div class="pcard">
                    <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
                    <div class="tx">
                        <h3>Latar Belakang Bisnis</h3>
                        <p>Mooda lahir dari pengalaman langsung founder di industri F&amp;B yang merasakan kesulitan mengelola operasional toko, stok, dan keuangan secara manual. Melihat potensi besar digitalisasi UMKM di Indonesia, tim Mooda membangun solusi POS tanpa biaya instalasi dan menawarkan model pay-per-use sebagai pintu masuk bagi UMKM yang belum siap komitmen bulanan.</p>
                    </div>
                </div>

                <div class="pcard">
                    <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg></div>
                    <div class="tx">
                        <h3>Bidang Usaha</h3>
                        <p>Penyedia solusi Point of Sales (POS) berbasis cloud untuk UMKM F&amp;B (Food and Beverage) di Indonesia dengan model bisnis SaaS (Software as a Service).</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="vmsec">
            <div class="wrap">
                <div class="sec-head"><span class="bar"></span><h2>Visi &amp; Misi</h2></div>
                <div class="vm">
                    <div class="vmbox visi">
                        <div class="vic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/></svg></div>
                        <h3>Visi</h3>
                        <p>Menjadi mitra digital terpercaya bagi UMKM F&amp;B Indonesia dalam bertransformasi menuju bisnis modern yang efisien dan naik kelas.</p>
                    </div>
                    <div class="vmbox misi">
                        <div class="vic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/></svg></div>
                        <h3>Misi</h3>
                        <ul>
                            <li><span class="ck"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg></span> Menyediakan solusi POS yang terjangkau dan mudah digunakan oleh UMKM mikro.</li>
                            <li><span class="ck"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg></span> Membantu UMKM meningkatkan efisiensi operasional melalui digitalisasi.</li>
                            <li><span class="ck"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg></span> Memberdayakan pelaku usaha F&amp;B dengan data dan insight untuk pengambilan keputusan.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="strip">
            <div class="wrap">
                <div class="strip-card">
                    <div class="strip-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="3.5"/><path stroke-linecap="round" d="M5 20c0-3.5 3.1-5.5 7-5.5s7 2 7 5.5"/></svg> Mudah Digunakan</div>
                    <div class="strip-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z"/></svg> Cloud &amp; Aman</div>
                    <div class="strip-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/></svg> Dukungan Lokal</div>
                    <div class="strip-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg> Untuk UMKM Indonesia</div>
                </div>
            </div>
        </section>
    </div>

    {{-- ============================ FOOTER (gelap) ============================ --}}
    <div class="dark-sec">
        <div class="fx">
            <span class="glow purpleR" style="opacity:.3"></span>
            <span class="dots" style="width:120px;height:90px;bottom:40px;right:4%"></span>
        </div>
        <div class="wave" style="transform:rotate(180deg);margin-top:-1px">
            <svg viewBox="0 0 1440 96" preserveAspectRatio="none"><path fill="#f6f6fc" d="M0,96 L1440,96 L1440,44 C1180,110 900,-8 660,40 C420,88 200,10 0,50 Z"/></svg>
        </div>
        <footer class="foot">
            <div class="wrap">
                <div class="fgrid">
                    <div>
                        <div class="fbrand">
                            <span class="bm"><img src="{{ asset('assets/media/logos/mooda-mark-192.png') }}" alt="Mooda"></span>
                            <span>mooda</span>
                        </div>
                        <p>Solusi POS Cloud untuk UMKM F&amp;B Indonesia.</p>
                    </div>
                    <div>
                        <h4>Tautan Cepat</h4>
                        <ul>
                            <li><a href="{{ route('landing') }}">Beranda</a></li>
                            <li><a href="{{ route('tentang') }}">Tentang Kami</a></li>
                            <li><a href="{{ route('landing') }}#fitur">Layanan</a></li>
                            <li><a href="{{ route('landing') }}#partner">Portofolio</a></li>
                            <li><a href="https://blog.mooda.id">Blog</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4>Alamat</h4>
                        <p>Jl. Tengku Raja Muda No.23,<br>Lubuk Pakam, Deli Serdang,<br>Sumatera Utara</p>
                    </div>
                    <div>
                        <h4>Kontak</h4>
                        <div class="fk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg><a href="https://wa.me/6285760366666" target="_blank" rel="noopener">0857-6036-6666</a></div>
                        <div class="fk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg><a href="mailto:hello@mooda.id">hello@mooda.id</a></div>
                        <div class="fk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0a8.949 8.949 0 004.951-1.488A3.987 3.987 0 0013 16.5h-2a3.987 3.987 0 00-3.951 3.012A8.949 8.949 0 0012 21zm3-12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><a href="https://mooda.id">www.mooda.id</a></div>
                    </div>
                </div>
                <div class="fbottom">&copy; {{ date('Y') }} CV Mooda Teknologi Indonesia. Seluruh hak cipta dilindungi.</div>
            </div>
        </footer>
    </div>
</body>
</html>
