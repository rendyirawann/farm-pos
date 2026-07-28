<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Kata Sandi {{ $brand ?? 'Mooda' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#eef2ff; font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2ff; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 20px 50px -24px rgba(79,70,229,.45);">

                    {{-- Header --}}
                    <tr>
                        <td align="center" style="background:linear-gradient(120deg,#4f46e5,#7c3aed); padding:32px 24px;">
                            <img src="{{ asset('assets/media/logos/mooda-logo-white.png') }}" alt="{{ $brand ?? 'Mooda' }}" height="40" style="height:40px; width:auto; display:block;">
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:36px 36px 8px;">
                            <h1 style="margin:0 0 6px; font-size:22px; font-weight:800; color:#0f172a;">Reset Kata Sandi 🔒</h1>
                            <p style="margin:0 0 18px; font-size:15px; line-height:1.65; color:#475569;">
                                Hai <b>{{ $user->name ?? 'Pengguna' }}</b>, kami menerima permintaan untuk mengatur ulang kata sandi akun {{ $brand ?? 'Mooda' }} Anda. Klik tombol di bawah untuk membuat kata sandi baru.
                            </p>

                            {{-- CTA button --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 20px;">
                                <tr>
                                    <td align="center" style="border-radius:12px; background:linear-gradient(120deg,#4f46e5,#7c3aed);">
                                        <a href="{!! $url !!}" target="_blank"
                                           style="display:inline-block; padding:14px 34px; font-size:16px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:12px;">
                                            Atur Ulang Kata Sandi
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            {{-- Info kedaluwarsa --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fff7ed; border:1px solid #fed7aa; border-radius:12px; margin:0 0 22px;">
                                <tr>
                                    <td style="padding:14px 18px; font-size:13.5px; line-height:1.6; color:#c2410c;">
                                        ⏱️ Tautan ini berlaku <b>{{ $count ?? 60 }} menit</b>. Setelah itu Anda perlu meminta tautan baru.
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
                                Tidak merasa meminta reset kata sandi? Abaikan email ini — kata sandi Anda tetap aman.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:24px 36px 32px; border-top:1px solid #f1f5f9;">
                            <p style="margin:0; font-size:12px; line-height:1.6; color:#94a3b8;">
                                &copy; {{ date('Y') }} {{ $brand ?? 'Mooda' }} — POS modern untuk Cafe, Resto, Coffee Shop, Bakery &amp; UMKM.<br>
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
