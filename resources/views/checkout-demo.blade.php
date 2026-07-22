<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout Pembayaran — Mooda</title>
    <meta name="description" content="Halaman checkout pembayaran Mooda — top-up saldo / langganan via Virtual Account & QRIS.">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <style>
        :root { --ind:#4f46e5; --ind2:#4338ca; --ink:#0f172a; --mut:#64748b; --line:#e5e7eb; --bg:#f6f7fc; --ok:#059669; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',system-ui,-apple-system,Roboto,Helvetica,Arial,sans-serif; background:var(--bg); color:var(--ink); line-height:1.55; }
        .top { background:#fff; border-bottom:1px solid var(--line); }
        .top-in { max-width:960px; margin:0 auto; padding:14px 20px; display:flex; align-items:center; justify-content:space-between; }
        .brand { display:flex; align-items:center; gap:10px; font-weight:800; font-size:18px; }
        .brand img { height:30px; }
        .secure { font-size:12.5px; color:var(--ok); font-weight:700; display:flex; align-items:center; gap:6px; }
        .wrap { max-width:960px; margin:0 auto; padding:26px 20px 60px; }
        .h1 { font-size:22px; font-weight:800; margin-bottom:4px; }
        .sub { color:var(--mut); font-size:14px; margin-bottom:22px; }
        .grid { display:grid; grid-template-columns:1fr 340px; gap:22px; align-items:start; }
        @media (max-width:760px){ .grid{ grid-template-columns:1fr; } }
        .card { background:#fff; border:1px solid var(--line); border-radius:16px; padding:20px; box-shadow:0 10px 30px -24px rgba(15,23,42,.4); }
        .card h2 { font-size:15px; font-weight:800; margin-bottom:14px; }
        .muted { color:var(--mut); font-size:13px; }
        /* order summary */
        .sum-row { display:flex; justify-content:space-between; padding:9px 0; border-bottom:1px dashed var(--line); font-size:14px; }
        .sum-row:last-of-type { border-bottom:0; }
        .sum-total { display:flex; justify-content:space-between; margin-top:12px; padding-top:12px; border-top:2px solid var(--line); font-weight:800; font-size:18px; }
        .pill { display:inline-block; background:#eef2ff; color:var(--ind); font-weight:700; font-size:12px; padding:3px 10px; border-radius:999px; }
        /* method list */
        .m-group-label { font-size:12px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--mut); margin:16px 0 8px; }
        .method { display:flex; align-items:center; gap:12px; border:1.5px solid var(--line); border-radius:12px; padding:12px 14px; margin-bottom:10px; cursor:pointer; transition:.15s; }
        .method:hover { border-color:#c7d2fe; background:#fafaff; }
        .method.sel { border-color:var(--ind); background:#f5f5ff; box-shadow:0 0 0 3px #e0e7ff; }
        .m-logo { width:46px; height:30px; border:1px solid var(--line); border-radius:6px; display:grid; place-items:center; font-weight:800; font-size:11px; color:var(--ink); background:#fff; flex:0 0 auto; }
        .m-name { font-weight:700; font-size:14px; }
        .m-fee { font-size:12px; color:var(--mut); }
        .m-radio { margin-left:auto; width:20px; height:20px; border-radius:50%; border:2px solid #cbd5e1; flex:0 0 auto; position:relative; }
        .method.sel .m-radio { border-color:var(--ind); }
        .method.sel .m-radio::after { content:''; position:absolute; inset:4px; border-radius:50%; background:var(--ind); }
        .btn { display:block; width:100%; text-align:center; background:var(--ind); color:#fff; font-weight:800; font-size:15px; border:0; border-radius:12px; padding:14px; cursor:pointer; transition:.15s; }
        .btn:hover { background:var(--ind2); }
        .btn:disabled { background:#c7d2fe; cursor:not-allowed; }
        /* payment instruction (in-app) */
        .pay { display:none; margin-top:18px; }
        .pay.show { display:block; }
        .va-box { background:#0f172a; color:#fff; border-radius:12px; padding:16px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .va-num { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:22px; font-weight:800; letter-spacing:1px; }
        .copy { background:rgba(255,255,255,.15); color:#fff; border:0; border-radius:8px; padding:8px 12px; font-weight:700; cursor:pointer; font-size:13px; }
        .qr { width:190px; height:190px; margin:6px auto; border:1px solid var(--line); border-radius:12px; display:grid; place-items:center; background:
            repeating-conic-gradient(#0f172a 0% 25%, #fff 0% 50%) 50% / 20px 20px; }
        ol.steps { margin:14px 0 0 18px; color:#334155; font-size:13.5px; } ol.steps li{ margin:6px 0; }
        .note { margin-top:22px; font-size:12.5px; color:var(--mut); background:#fff; border:1px dashed var(--line); border-radius:12px; padding:12px 14px; }
        .foot { text-align:center; color:var(--mut); font-size:12.5px; margin-top:30px; }
        .foot a { color:var(--ind); text-decoration:none; }
    </style>
</head>
<body>
    <div class="top">
        <div class="top-in">
            <div class="brand"><img src="{{ asset('assets/media/logos/mooda-logo.png') }}" alt="Mooda"></div>
            <div class="secure">🔒 Pembayaran aman</div>
        </div>
    </div>

    <div class="wrap">
        <div class="h1">Checkout Pembayaran</div>
        <div class="sub">Top-up saldo aplikasi Mooda (POS). Pilih metode pembayaran lalu selesaikan pembayaran.</div>

        <div class="grid">
            {{-- ====== KIRI: metode pembayaran ====== --}}
            <div class="card">
                <h2>Metode Pembayaran</h2>

                <div class="m-group-label">Virtual Account (Transfer Bank)</div>
                @foreach ([['BRI','BRIVA','Rp 4.250'],['BNI','BNIVA','Rp 4.250'],['Mandiri','MANDIRIVA','Rp 4.250'],['BCA','BCAVA','Rp 5.500']] as [$bank,$code,$fee])
                    <label class="method" data-type="va" data-bank="{{ $bank }}">
                        <span class="m-logo">{{ $bank }}</span>
                        <span><span class="m-name">{{ $bank }} Virtual Account</span><br><span class="m-fee">Biaya layanan {{ $fee }}</span></span>
                        <span class="m-radio"></span>
                    </label>
                @endforeach

                <div class="m-group-label">QRIS</div>
                <label class="method" data-type="qris" data-bank="QRIS">
                    <span class="m-logo">QRIS</span>
                    <span><span class="m-name">QRIS (semua e-wallet & m-banking)</span><br><span class="m-fee">Biaya layanan 0,7%</span></span>
                    <span class="m-radio"></span>
                </label>

                {{-- Instruksi pembayaran tampil DI HALAMAN MOODA (in-app) --}}
                <div class="pay" id="pay">
                    <div id="pay-va">
                        <div class="m-group-label" style="margin-top:20px;">Nomor Virtual Account</div>
                        <div class="va-box">
                            <span class="va-num" id="va-num">8808 8210 1234 5678</span>
                            <button class="copy" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('va-num').textContent.replace(/\s/g,''))">Salin</button>
                        </div>
                        <ol class="steps">
                            <li>Buka aplikasi m-banking / ATM bank <b id="va-bank">BRI</b>.</li>
                            <li>Pilih menu <b>Transfer → Virtual Account</b>.</li>
                            <li>Masukkan nomor VA di atas, pastikan nominal <b>Rp 50.000</b>.</li>
                            <li>Konfirmasi &amp; selesaikan pembayaran sebelum batas waktu.</li>
                        </ol>
                    </div>
                    <div id="pay-qris" style="display:none;">
                        <div class="m-group-label" style="margin-top:20px;">Scan QRIS</div>
                        <div class="qr"></div>
                        <p class="muted" style="text-align:center;">Scan dengan aplikasi apa pun yang mendukung QRIS (GoPay, OVO, DANA, ShopeePay, m-banking).</p>
                    </div>
                </div>
            </div>

            {{-- ====== KANAN: ringkasan + bayar ====== --}}
            <div class="card">
                <h2>Ringkasan Pesanan</h2>
                <div class="sum-row"><span>Top-up Saldo Deposit <span class="pill">Paket</span></span><span>Rp 50.000</span></div>
                <div class="sum-row"><span class="muted">Saldo diterima</span><span class="muted">55.000 (bonus Rp5.000)</span></div>
                <div class="sum-row"><span class="muted">Untuk</span><span class="muted">Langganan aplikasi Mooda</span></div>
                <div class="sum-total"><span>Total Bayar</span><span id="total">Rp 50.000</span></div>
                <button class="btn" id="btn" style="margin-top:18px;" disabled>Pilih metode dulu</button>
                <p class="muted" style="margin-top:12px; text-align:center;">Diproses aman via <b>Tripay</b> (VA / QRIS).</p>
            </div>
        </div>

        <div class="note">
            <b>Tentang halaman ini.</b> Ini halaman <b>checkout pembayaran Mooda</b> untuk pemilik usaha melakukan
            top-up saldo / langganan aplikasi. Pembayaran diproses melalui <b>Tripay</b> (Virtual Account &amp; QRIS)
            dan instruksi pembayaran (nomor VA / QRIS) ditampilkan langsung di halaman Mooda. Alur & skema lengkap:
            <a href="{{ asset('mooda-payment-flow.pdf') }}" style="color:var(--ind);">dokumen alur pembayaran</a>.
        </div>

        <div class="foot">© {{ date('Y') }} Mooda — <a href="https://mooda.id">mooda.id</a> · Aplikasi Kasir (POS) untuk restoran, cafe &amp; warung</div>
    </div>

    <script>
        (function () {
            var methods = document.querySelectorAll('.method');
            var btn = document.getElementById('btn');
            var pay = document.getElementById('pay');
            var selected = null;
            methods.forEach(function (m) {
                m.addEventListener('click', function () {
                    methods.forEach(function (x) { x.classList.remove('sel'); });
                    m.classList.add('sel');
                    selected = m;
                    btn.disabled = false;
                    btn.textContent = 'Bayar Sekarang — Rp 50.000';
                    pay.classList.remove('show');
                });
            });
            btn.addEventListener('click', function () {
                if (!selected) return;
                var type = selected.getAttribute('data-type');
                var bank = selected.getAttribute('data-bank');
                document.getElementById('pay-va').style.display = (type === 'va') ? 'block' : 'none';
                document.getElementById('pay-qris').style.display = (type === 'qris') ? 'block' : 'none';
                if (type === 'va') { document.getElementById('va-bank').textContent = bank; }
                pay.classList.add('show');
                pay.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                btn.textContent = 'Menunggu pembayaran…';
            });
        })();
    </script>
</body>
</html>
