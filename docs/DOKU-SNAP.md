# Integrasi DOKU SNAP (Virtual Account) — Panduan & Go-Live

Payment gateway **DOKU SNAP (BI Standard) Virtual Account** untuk billing langganan tenant
dan top-up deposit. Menggantikan Midtrans. **Dirakit sendiri tanpa SDK** (pakai `openssl` +
`hash_hmac` bawaan PHP) — tanpa dependensi pihak ketiga.

> **Status (2026-07-15): SANDBOX terbukti jalan end-to-end.** Outbound (token + create VA) dan
> inbound (Token URL → JWT → notifikasi → verifikasi → routing) sudah diuji, termasuk **notifikasi
> pembayaran ASLI** dari simulator BCA SNAP. Belum production (lihat Go-Live).

---

## 1. Isolasi jalur publik (WAJIB dipahami)

`config/billing.php` → `driver` = `env('BILLING_DRIVER', 'midtrans')`.

- **`midtrans` (default, TERKUNCI sekarang):** checkout langganan & top-up deposit publik memakai
  Midtrans production — TIDAK berubah, TIDAK pernah menyentuh DOKU sandbox.
- **`doku`:** memakai DOKU SNAP VA. **Jangan diaktifkan sampai akun DOKU production siap** (lihat Go-Live).

Selama `BILLING_DRIVER=midtrans`, seluruh kode DOKU dorман (tidak aktif di jalur publik). Aman.

---

## 2. Arsitektur

```
OUTBOUND (buat tagihan)                    INBOUND (DOKU beri tahu sudah dibayar)
app (Octane)                                DOKU  ─┐
  │ DokuSnap::getAccessToken()                     │ 1. POST /doku/snap/access-token
  │   (tanda tangan asimetris RSA-SHA256)          │    (DOKU tandatangani asimetris)
  │ DokuSnap::createVa()                    doku-gateway (php-fpm, BUKAN Octane)
  │   (tanda tangan simetris HMAC-SHA512)          │    verifikasi pakai DOKU Public Key
  ▼                                                │    → terbitkan JWT RS256 (private key merchant)
DOKU api-sandbox/api.doku.com                      │ 2. POST /doku/snap/notify (bawa Bearer JWT)
                                                   │    gateway verifikasi JWT (public key merchant)
                                                   │    → route by PREFIX trxId
                                                   ▼
                                          POST http://127.0.0.1:8044/api/doku-webhook (Octane)
                                            BillingController::dokuWebhook()
                                            → cocokkan order (midtrans_order_id = trxId)
                                            → verifikasi nominal → aktivasi (idempoten)
```

**Gateway terpusat** (`/var/www/html/doku-gateway/`) meniru `midtrans-gateway`: **satu modul untuk
semua project** yang berbagi 1 akun DOKU. Tambah project = tambah prefix di `config.php → routes`.
Gateway MANDIRI (baca kunci dari `keys/` sendiri, tidak baca `.env`; client_id dari header X-CLIENT-KEY).

---

## 3. Peta file

| File | Fungsi |
|---|---|
| `app/Services/Doku/DokuSnap.php` | Klien inti: token B2B, tanda tangan simetris, createVa, JWT, verifikasi tanda tangan DOKU |
| `app/Console/Commands/DokuSelfTest.php` | `php artisan doku:selftest` — uji kripto offline (11 tes) |
| `app/Console/Commands/DokuSandboxTest.php` | `php artisan doku:sandbox-test` — uji token + create VA ke sandbox (terisolasi) |
| `config/services.php` → `doku` | Kredensial & konfigurasi (via env) |
| `config/billing.php` → `driver` | Switch `midtrans`/`doku` |
| `BillingController::dokuWebhook()` / `checkoutDoku()` | Webhook notifikasi + checkout langganan DOKU |
| `DepositController::checkoutDoku()` | Checkout top-up deposit DOKU |
| `routes/web.php` → `/api/doku-webhook` | Endpoint webhook (CSRF-excluded di `bootstrap/app.php`) |
| `storage/app/doku/{private,public,doku_public}.pem` | Kunci RSA (600/644) |
| `/var/www/html/doku-gateway/` | Gateway terpusat (public/index.php, config.php, keys/, logs/) |

**Config server (DI LUAR git):** nginx block `/doku/snap/(access-token\|notify)` di
`mooda.id.conf`; pengecualian WAF `/etc/nginx/modsec/exclusions.conf` id:1004; sertifikat SSL.

---

## 4. Variabel `.env` (nilai rahasia TIDAK ditulis di sini)

```
BILLING_DRIVER=midtrans            # 'doku' saat go-live
DOKU_IS_PRODUCTION=false           # true saat go-live
DOKU_CLIENT_ID=...                 # SANDBOX: BRN-0226-...  PRODUCTION: BRN-0219-... (BEDA!)
DOKU_SECRET_KEY=...                # dari dashboard (Reveal Key)
DOKU_PRIVATE_KEY=/var/www/html/stakko-pos/storage/app/doku/private.pem
DOKU_OWN_PUBLIC_KEY=/var/www/html/stakko-pos/storage/app/doku/public.pem
DOKU_PUBLIC_KEY=/var/www/html/stakko-pos/storage/app/doku/doku_public.pem   # DOKU Public Key
DOKU_PARTNER_SERVICE_ID=190089     # = "Merchant BIN" penuh (BUKAN "Partner Service ID" 19008!)
DOKU_VA_CHANNEL=VIRTUAL_ACCOUNT_BCA
```

> ⚠️ **partnerServiceId = Merchant BIN penuh (`190089`)**, bukan field "Partner Service ID" (`19008`).
> DOKU meng-assign nomor VA otomatis; pakai `virtualAccountNo` dari respons, cocokkan bayar via `trxId`.

Kunci merchant juga disalin ke `/var/www/html/doku-gateway/keys/`. Bila private key diganti,
upload ulang public key ke dashboard DOKU (Merchant Public Key) & sinkron ke gateway.

---

## 5. Perintah uji

```bash
php artisan doku:selftest                       # kripto offline (tanpa jaringan)
php artisan doku:sandbox-test --token-only        # uji access token B2B
php artisan doku:sandbox-test --amount=50000      # token + create VA
# opsi: --customer= --partner= --prefix= --channel=
```

Endpoint gateway (via nginx): `POST https://mooda.id/doku/snap/access-token` & `/doku/snap/notify`.
Log gateway: `/var/www/html/doku-gateway/logs/doku-gateway-YYYY-MM-DD.log`.

---

## 6. ✅ Checklist GO-LIVE (production)

1. **Akun DOKU production terverifikasi** (banner "Get your account verified" hilang). Ini prasyarat
   transaksi uang asli — 1–3 hari kerja review DOKU.
2. Di dashboard **production** DOKU (bukan sandbox):
   - **Upload Merchant Public Key** (isi `storage/app/doku/public.pem`) → SAVE.
   - **Token URL** = `https://mooda.id/doku/snap/access-token`.
   - **Payment Notification URL** (per channel VA) = `https://mooda.id/doku/snap/notify`.
   - Aktifkan channel VA yang diinginkan (mis. BCA), catat **Merchant BIN** (untuk partnerServiceId).
3. Salin **DOKU Public Key production** → `storage/app/doku/doku_public.pem` +
   `/var/www/html/doku-gateway/keys/doku_public.pem`.
4. **Bangun tampilan VA di checkout** (BELUM ada — lihat §8). Tanpa ini pelanggan tak melihat nomor VA.
5. Update `.env`:
   ```
   BILLING_DRIVER=doku
   DOKU_IS_PRODUCTION=true
   DOKU_CLIENT_ID=<client id PRODUCTION>
   DOKU_SECRET_KEY=<secret PRODUCTION>
   DOKU_PARTNER_SERVICE_ID=<Merchant BIN production>
   ```
6. Deploy:
   ```bash
   cd /var/www/html/stakko-pos
   php artisan config:clear && php artisan optimize
   chown -R www-data:www-data storage/framework bootstrap/cache
   systemctl restart octane-stakko-pos
   ```
7. Uji 1 transaksi kecil nyata (nominal minimal), pastikan VA terbit + notifikasi mengaktifkan order,
   lalu pantau `storage/logs/laravel.log` + log gateway.

---

## 7. Rollback ke Midtrans (instan)

Set `BILLING_DRIVER=midtrans` di `.env` → `php artisan config:cache && chown ... && systemctl restart
octane-stakko-pos`. Jalur publik langsung kembali ke Midtrans. Kode DOKU tidak dihapus, hanya dorман.

---

## 8. ⚠️ Yang BELUM dibangun

- **Tampilan VA di halaman checkout.** Saat `driver=doku`, `checkout()` mengembalikan JSON
  `{va_number, amount, expired_date, channel}`, tapi view billing (`resources/views/backend/billing/`)
  masih memakai pola popup Snap Midtrans. **Perlu UI baru** yang menampilkan nomor VA + instruksi
  bayar + polling status sebelum DOKU dipakai pelanggan nyata.
- (Opsional) Verifikasi tanda tangan simetris X-SIGNATURE di gateway sebagai hardening (butuh secret
  key di gateway). Saat ini notifikasi diamankan JWT (khusus DOKU) + verifikasi nominal + idempoten.
- (Opsional) Uji aktivasi order nyata end-to-end (buat order pending → bayar → webhook aktivasi → bersihkan).

---

## 9. Troubleshooting (kode error DOKU yang pernah ditemui)

| responseCode | Arti & solusi |
|---|---|
| `4017300 Unknown Client` | Client ID tak dikenal di environment ini (mis. pakai creds production di URL sandbox). Pakai creds environment yang benar. |
| `4017300 Invalid Signature` | Merchant Public Key belum diupload di dashboard, atau private key tak cocok. |
| `4032715 ...Combination...` | Format `partnerServiceId`+`customerNo`+`virtualAccountNo` salah (mis. customerNo terlalu panjang). |
| `4032701 ...BIN not configured` | `partnerServiceId` salah — pakai **Merchant BIN penuh** (mis. `190089`), bukan field "Partner Service ID". |
| `2002700 Successful` | Sukses (create VA / notifikasi diterima). |
