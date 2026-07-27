<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun Mooda</title>
</head>
<body style="margin:0; padding:0; background-color:#eef2ff; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2ff; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 20px 50px -24px rgba(79,70,229,.45);">

                    {{-- Header --}}
                    <tr>
                        <td align="center" style="background:linear-gradient(120deg,#4f46e5,#7c3aed); padding:32px 24px;">
                            <img src="{{ asset('assets/media/logos/mooda-logo-white.png') }}" alt="Mooda" height="40" style="height:40px; width:auto; display:block;">
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:36px 36px 8px;">
                            <h1 style="margin:0 0 6px; font-size:22px; font-weight:800; color:#0f172a;">Selamat datang di Mooda! 🎉</h1>
                            <p style="margin:0 0 18px; font-size:15px; line-height:1.65; color:#475569;">
                                Hai <b>{{ $user->name }}</b>, satu langkah lagi. Klik tombol di bawah untuk <b>mengaktifkan akun</b> Anda.
                            </p>

                            {{-- Bonus box --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f3ff; border:1px solid #e0e7ff; border-radius:12px; margin:0 0 24px;">
                                <tr>
                                    <td style="padding:16px 18px; font-size:14px; line-height:1.6; color:#4338ca;">
                                        🎁 Begitu aktif, akun Anda langsung jadi paket <b>Starter</b> dengan <b>saldo Rp2.000 gratis</b> untuk mulai bertransaksi.
                                    </td>
                                </tr>
                            </table>

                            {{-- CTA button --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td align="center" style="border-radius:12px; background:linear-gradient(120deg,#4f46e5,#7c3aed);">
                                        <a href="{!! $url !!}" target="_blank"
                                           style="display:inline-block; padding:14px 34px; font-size:16px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:12px;">
                                            Aktifkan Akun Saya
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px; font-size:13px; line-height:1.6; color:#94a3b8;">
                                Tombol tidak berfungsi? Salin &amp; tempel tautan ini ke browser:
                            </p>
                            <p style="margin:0 0 20px; font-size:12px; line-height:1.5; word-break:break-all;">
                                <a href="{!! $url !!}" target="_blank" style="color:#4f46e5;">{{ $url }}</a>
                            </p>

                            <p style="margin:0; font-size:13px; line-height:1.6; color:#94a3b8;">
                                Jika Anda tidak merasa mendaftar di Mooda, abaikan email ini.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 36px 32px; border-top:1px solid #f1f5f9;">
                            <p style="margin:0; font-size:12px; line-height:1.6; color:#94a3b8;">
                                &copy; {{ date('Y') }} Mooda — POS modern untuk Cafe, Resto, Coffee Shop, Bakery &amp; UMKM.<br>
                                Email ini dikirim otomatis, mohon tidak membalas.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
