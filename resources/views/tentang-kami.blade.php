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
        :root{--indigo:#4f46e5;--indigo2:#7c3aed;--ink:#0f172a;--muted:#475569;--soft:#64748b;--line:#e6e8f0;--bg:#f7f8fc}
        body{font-family:'Plus Jakarta Sans',system-ui,-apple-system,sans-serif;color:var(--ink);background:#fff;line-height:1.6;-webkit-font-smoothing:antialiased;overflow-x:hidden}
        img{max-width:100%;display:block}
        a{color:inherit;text-decoration:none}
        .wrap{max-width:1080px;margin:0 auto;padding:0 22px}
        /* Topbar */
        .top{position:sticky;top:0;z-index:20;background:rgba(255,255,255,.9);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
        .top .wrap{height:66px;display:flex;align-items:center;justify-content:space-between}
        .top img{height:32px;width:auto}
        .back{display:inline-flex;align-items:center;gap:7px;font-weight:700;font-size:13.5px;color:var(--indigo);border:1px solid #dfe1f1;padding:8px 14px;border-radius:11px;transition:.15s}
        .back:hover{background:#eef2ff}
        /* Hero */
        .hero{background:linear-gradient(135deg,#4f46e5 0%,#6d28d9 55%,#7c3aed 100%);color:#fff;text-align:center;padding:56px 22px 64px;position:relative;overflow:hidden}
        .hero::after{content:"";position:absolute;right:-80px;top:-80px;width:280px;height:280px;border-radius:50%;background:rgba(255,255,255,.08)}
        .hero .mark{width:74px;height:74px;margin:0 auto 18px;background:#fff;border-radius:20px;display:grid;place-items:center;box-shadow:0 18px 40px -18px rgba(0,0,0,.5)}
        .hero .mark img{height:44px;width:auto}
        .hero .eyebrow{font-size:12px;font-weight:800;letter-spacing:.22em;color:#c7d2fe;text-transform:uppercase}
        .hero h1{font-size:clamp(28px,5vw,42px);font-weight:800;letter-spacing:-.02em;margin:8px 0 10px}
        .hero p{color:#e0e7ff;max-width:640px;margin:0 auto;font-size:15.5px}
        /* Section umum */
        section{padding:52px 0}
        .sec-head{display:flex;align-items:center;gap:12px;margin-bottom:26px}
        .sec-head .bar{width:5px;height:26px;border-radius:3px;background:linear-gradient(180deg,var(--indigo),var(--indigo2))}
        .sec-head h2{font-size:23px;font-weight:800;letter-spacing:-.01em}
        .sec-head.center{justify-content:center}
        .plain{background:var(--bg)}
        /* Identitas */
        .idrows{border:1px solid var(--line);border-radius:16px;overflow:hidden;background:#fff}
        .idrow{display:grid;grid-template-columns:230px 1fr;border-top:1px solid var(--line)}
        .idrow:first-child{border-top:0}
        .idrow .k{background:#f4f5fb;font-weight:700;font-size:14px;padding:15px 18px;color:#334155}
        .idrow .v{padding:15px 18px;font-size:14.5px;color:var(--muted)}
        @media(max-width:640px){.idrow{grid-template-columns:1fr}.idrow .k{border-bottom:1px solid var(--line)}}
        /* Profil cards */
        .prof{display:grid;gap:16px}
        .card{border:1px solid var(--line);border-radius:16px;padding:22px 24px;background:#fff;box-shadow:0 10px 30px -26px rgba(15,23,42,.5)}
        .card h3{font-size:15px;font-weight:800;color:var(--indigo);margin-bottom:7px}
        .card p{color:var(--muted);font-size:14.5px}
        /* Visi misi */
        .vm{display:grid;grid-template-columns:1fr 1fr;gap:18px}
        @media(max-width:760px){.vm{grid-template-columns:1fr}}
        .vm .box{border:1px solid var(--line);border-radius:18px;padding:24px;background:#fff}
        .vm .box.visi{background:linear-gradient(135deg,#eef2ff,#faf5ff);border-color:#e0e7ff}
        .vm .tag{display:inline-block;font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--indigo);margin-bottom:10px}
        .vm .box p{color:var(--muted);font-size:14.5px}
        .vm ol{margin:6px 0 0 18px;color:var(--muted);font-size:14.5px}
        .vm ol li{margin-bottom:8px}

        /* ====== STRUKTUR ORGANISASI (colourful, gelap -> terang, wave + bubble) ====== */
        .org{position:relative;overflow:hidden;padding:104px 0 112px;color:#fff;
            background:linear-gradient(180deg,#0b0a2e 0%,#221a63 45%,#4f46e5 100%)}
        .org .sec-head{justify-content:center}
        .org .sec-head h2{color:#fff;font-size:26px}
        .org .sec-head .bar{background:linear-gradient(180deg,#c4b5fd,#f0abfc)}
        .org-sub{text-align:center;color:#c7d2fe;max-width:560px;margin:-14px auto 46px;font-size:14.5px}
        .org .wrap{position:relative;z-index:3}
        /* wave atas & bawah (putih -> menyatu dgn section putih di sekitarnya) */
        .wave{position:absolute;left:0;width:100%;line-height:0;z-index:2;pointer-events:none}
        .wave.top{top:-1px}
        .wave.bot{bottom:-1px}
        .wave svg{display:block;width:100%;height:64px}
        /* bubble warna-warni */
        .bub{position:absolute;border-radius:50%;z-index:1;pointer-events:none;filter:blur(1px)}
        .b1{width:130px;height:130px;background:#f472b6;opacity:.45;top:120px;left:5%}
        .b2{width:80px;height:80px;background:#22d3ee;opacity:.4;top:62%;left:12%}
        .b3{width:160px;height:160px;background:#a78bfa;opacity:.4;bottom:70px;right:6%}
        .b4{width:66px;height:66px;background:#34d399;opacity:.4;top:150px;right:15%}
        .b5{width:52px;height:52px;background:#fbbf24;opacity:.45;bottom:130px;left:42%}
        .b6{width:96px;height:96px;background:#818cf8;opacity:.35;top:48%;right:30%}
        .b7{width:40px;height:40px;background:#f0abfc;opacity:.5;top:220px;left:46%}
        /* team */
        .team{display:flex;flex-wrap:nowrap;align-items:center;justify-content:center;gap:40px}
        .member{width:280px;text-align:center;transition:transform .22s}
        .member:hover{transform:translateY(-6px)}
        .frame{width:186px;height:186px;border-radius:50%;margin:0 auto;padding:5px;
            background:linear-gradient(135deg,#c4b5fd,#f0abfc);box-shadow:0 28px 55px -18px rgba(0,0,0,.6)}
        .photo{width:100%;height:100%;border-radius:50%;overflow:hidden;display:grid;place-items:center;
            background:linear-gradient(135deg,#1e1b4b,#312e81);border:4px solid rgba(255,255,255,.92)}
        .photo img{width:100%;height:100%;object-fit:cover}
        .ph-empty{text-align:center;color:#a5b4fc;padding:10px}
        .ph-empty svg{width:52px;height:52px;margin:0 auto 6px}
        .ph-empty span{font-size:10.5px;font-weight:700;color:#c7d2fe}
        .member .info{margin-top:18px}
        .member .name{font-weight:800;font-size:17.5px;color:#fff}
        .member .pos{display:inline-block;margin-top:9px;font-size:11.5px;font-weight:800;letter-spacing:.04em;color:#fff;padding:5px 15px;border-radius:999px;box-shadow:0 10px 22px -10px rgba(0,0,0,.5)}
        .member .bio{margin:13px auto 0;font-size:13px;color:#cbd5f5;line-height:1.55;max-width:250px}
        /* badge colourful per posisi */
        .m-center .pos{background:linear-gradient(135deg,#6366f1,#a855f7)}
        .m-left  .pos{background:linear-gradient(135deg,#ec4899,#a855f7)}
        .m-right .pos{background:linear-gradient(135deg,#06b6d4,#10b981)}
        /* desktop: Rizky kiri, Prasti tengah (besar), Rendy kanan */
        @media(min-width:821px){
            .m-left{order:1}.m-center{order:2}.m-right{order:3}
            .m-center{transform:translateY(-14px)}
            .m-center:hover{transform:translateY(-20px)}
            .m-center .frame{width:228px;height:228px}
        }
        /* mobile: Prasti, Rizky, Rendy */
        @media(max-width:820px){
            .org{padding:80px 0 88px}
            .team{flex-direction:column;gap:34px}
            .member{order:0!important;width:100%;max-width:320px;transform:none!important}
        }
        /* Footer */
        footer{background:#0b1020;color:#94a3b8;text-align:center;padding:30px 22px;font-size:13px}
        footer b{color:#e2e8f0}
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
    @endphp

    <div class="top">
        <div class="wrap">
            <a href="{{ route('landing') }}"><img src="{{ asset('assets/media/logos/mooda-logo.png') }}" alt="Mooda"></a>
            <a class="back" href="{{ route('landing') }}">← Kembali ke Beranda</a>
        </div>
    </div>

    <div class="hero">
        <div class="mark"><img src="{{ asset('assets/media/logos/mooda-mark-192.png') }}" alt="Mooda"></div>
        <div class="eyebrow">Profil Perusahaan</div>
        <h1>Tentang Mooda</h1>
        <p>Solusi Point of Sales (POS) berbasis cloud untuk UMKM F&amp;B Indonesia — terjangkau, mudah, dan membantu usaha naik kelas.</p>
    </div>

    {{-- Identitas --}}
    <section>
        <div class="wrap">
            <div class="sec-head"><span class="bar"></span><h2>Identitas Perusahaan</h2></div>
            <div class="idrows">
                @foreach ($identitas as [$k, $v])
                    <div class="idrow"><div class="k">{!! $k !!}</div><div class="v">{!! $v !!}</div></div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Struktur Organisasi (section kedua, colourful) --}}
    <section class="org">
        <div class="wave top">
            <svg viewBox="0 0 1440 64" preserveAspectRatio="none"><path fill="#ffffff" d="M0,0 L1440,0 L1440,26 C1140,66 900,6 720,30 C540,54 260,72 0,30 Z"/></svg>
        </div>
        <span class="bub b1"></span><span class="bub b2"></span><span class="bub b3"></span>
        <span class="bub b4"></span><span class="bub b5"></span><span class="bub b6"></span><span class="bub b7"></span>

        <div class="wrap">
            <div class="sec-head"><span class="bar"></span><h2>Struktur Organisasi</h2></div>
            <p class="org-sub">Tim inti di balik Mooda — perpaduan latar belakang F&amp;B, teknologi, dan sales.</p>
            <div class="team">
                @foreach ($founders as $f)
                    @php $cls = ['m-center', 'm-left', 'm-right'][$loop->index] ?? ''; @endphp
                    <div class="member {{ $cls }}">
                        <div class="frame">
                            <div class="photo">
                                @if ($f->photoUrl())
                                    <img src="{{ $f->photoUrl() }}" alt="{{ $f->name }}">
                                @else
                                    <div class="ph-empty">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 20c0-4 3.6-6 8-6s8 2 8 6"/></svg>
                                        <span>Foto belum diunggah</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="info">
                            <div class="name">{{ $f->name }}</div>
                            <div class="pos">{{ $f->position }}</div>
                            @if ($f->bio)<p class="bio">{{ $f->bio }}</p>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="wave bot">
            <svg viewBox="0 0 1440 64" preserveAspectRatio="none"><path fill="#ffffff" d="M0,64 L1440,64 L1440,38 C1140,-2 900,58 720,34 C540,10 260,-8 0,34 Z"/></svg>
        </div>
    </section>

    {{-- Profil --}}
    <section>
        <div class="wrap">
            <div class="sec-head"><span class="bar"></span><h2>Profil</h2></div>
            <div class="prof">
                <div class="card">
                    <h3>Sejarah Singkat Berdiri</h3>
                    <p>Mooda didirikan pada tahun 2026 oleh tiga founder dengan latar belakang F&amp;B, teknologi, dan sales. Berawal dari keprihatinan terhadap masih banyaknya UMKM F&amp;B yang mencatat transaksi secara manual, tim Mooda membangun solusi POS berbasis cloud yang terjangkau dan mudah digunakan.</p>
                </div>
                <div class="card">
                    <h3>Latar Belakang Bisnis</h3>
                    <p>Mooda lahir dari pengalaman langsung founder di industri F&amp;B yang merasakan kesulitan mengelola operasional toko, stok, dan keuangan secara manual. Melihat potensi besar digitalisasi UMKM di Indonesia, tim Mooda membangun solusi POS tanpa biaya instalasi dan menawarkan model pay-per-use sebagai pintu masuk bagi UMKM yang belum siap komitmen bulanan.</p>
                </div>
                <div class="card">
                    <h3>Bidang Usaha</h3>
                    <p>Penyedia solusi Point of Sales (POS) berbasis cloud untuk UMKM F&amp;B (Food and Beverage) di Indonesia dengan model bisnis SaaS (Software as a Service).</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Visi & Misi --}}
    <section class="plain">
        <div class="wrap">
            <div class="sec-head"><span class="bar"></span><h2>Visi &amp; Misi</h2></div>
            <div class="vm">
                <div class="box visi">
                    <span class="tag">Visi</span>
                    <p>Menjadi mitra digital terpercaya bagi UMKM F&amp;B Indonesia dalam bertransformasi menuju bisnis modern yang efisien dan naik kelas.</p>
                </div>
                <div class="box">
                    <span class="tag">Misi</span>
                    <ol>
                        <li>Menyediakan solusi POS yang terjangkau dan mudah digunakan oleh UMKM mikro.</li>
                        <li>Membantu UMKM meningkatkan efisiensi operasional melalui digitalisasi.</li>
                        <li>Memberdayakan pelaku usaha F&amp;B dengan data dan insight untuk pengambilan keputusan.</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <div style="height:4px;background:linear-gradient(90deg,#4f46e5,#10b981)"></div>
    <footer>
        <b>Mooda</b> — Solusi POS Cloud untuk UMKM F&amp;B &nbsp;·&nbsp; www.mooda.id<br>
        &copy; {{ date('Y') }} CV Mooda Teknologi Indonesia. Seluruh hak cipta dilindungi.
    </footer>
</body>
</html>
