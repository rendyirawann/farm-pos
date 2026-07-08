# Roadmap Pembayaran / Billing — Mooda (Stakko POS)

> Dokumen pengingat. **Model saat ini: MANUAL** (Snap per-periode). Fitur auto-recurring
> & GoPay linking di sini adalah rencana pengembangan **nanti**, belum diimplementasikan.
> Terakhir diperbarui: 2026-07-08.

---

## 1. Status saat ini (SUDAH JALAN — manual)

Langganan SaaS Mooda memakai **Midtrans Snap** (pembayaran sekali per periode). Tiap
perpanjangan = transaksi Snap baru; **tidak ada auto-charge**. POS/transaksi kasir **tidak**
memakai payment gateway (Midtrans hanya untuk billing langganan).

**Alur:**
1. Tenant klik **Berlangganan** → `POST /admin/billing/checkout`
   (`BillingController@checkout`) → buat `Subscription` (order_id prefix **`DSP-SUB-`**)
   → `\Midtrans\Snap::getSnapToken()` → popup Snap (client key) → user bayar.
2. Midtrans kirim notifikasi → **gateway** `https://mooda.id/midtrans/notify`
   → verifikasi signature SHA512 → route prefix `DSP-SUB-` → forward ke
   `http://127.0.0.1:8044/api/subscription-webhook` (`BillingController@webhook`)
   → aktifkan langganan.
3. Finish redirect → `https://mooda.id/midtrans/finish` (gateway redirect balik ke
   `/admin/billing`).

**Lokasi konfigurasi penting:**
- Key Midtrans app: `.env` → `MIDTRANS_MERCHANT_ID / CLIENT_KEY / SERVER_KEY /
  IS_PRODUCTION / NOTIFY_URL` (dibaca via `config/services.php` → `services.midtrans.*`).
- Gateway terpusat: `/var/www/html/midtrans-gateway/` (di LUAR repo; `config.php` chmod 600,
  `server_key` HARUS sama dgn app). nginx: `location ~ ^/midtrans/(notify|finish)$` → php-fpm.
- Aktif/nonaktif tombol beli: `config/billing.php` → `BILLING_PURCHASE_ENABLED` (.env).
- `configureMidtrans()` set `Config::$overrideNotifUrl = MIDTRANS_NOTIFY_URL` (gateway) tiap transaksi.

**Dashboard Midtrans yang diisi (Pengaturan → Payment):**
| Kolom | Isi |
|---|---|
| Finish Redirect URL | `https://mooda.id/midtrans/finish` |
| URL notifikasi pembayaran | `https://mooda.id/midtrans/notify` |
| URL notifikasi pembayaran **berulang** | *(kosong — belum dipakai)* |
| URL notifikasi **menghubungkan akun** | *(kosong — belum dipakai)* |

---

## 2. Rencana pengembangan (BELUM dikerjakan)

### Tahap 1 — Reminder & auto-suspend (murah, TANPA Core API) — disarankan lebih dulu
- Kirim reminder (email/WA) H-3 / H-1 sebelum langganan `ends_at`.
- Scheduler auto-suspend tenant saat lewat `ends_at` (sebagian sudah ada:
  `subscriptions:expire` di `routes/console.php`).
- Dampak besar mengurangi "lupa bayar" tanpa integrasi baru.

### Tahap 2 — Recurring / auto-charge kartu (Core API)
**Midtrans:** aktifkan **Core API** + izin recurring.
**Kode Mooda:**
- Simpan token kartu (Snap `save_card` / tokenization) saat bayar pertama.
- Panggil **Subscription API** `POST /v1/subscriptions` (amount, interval bulanan, token).
- **Handler notifikasi recurring** (payload beda: ada `subscription_id`) di gateway
  `/midtrans/notify` + webhook app. order_id auto-generate Midtrans → perlu mapping/prefix.
- UI kelola langganan: **batalkan / jeda / ganti kartu**.
- **Dunning**: retry saat gagal, reminder, suspend.
- Set **"URL notifikasi pembayaran berulang"** → gateway.

### Tahap 3 — GoPay account linking + recurring GoPay (Core API) — opsional
**Midtrans:** Core API + GoPay tokenization.
**Kode Mooda:**
- Tombol **"Hubungkan GoPay"** → `POST /v2/pay/account` → user setujui di app GoPay →
  simpan `gopay_account_id` + token per tenant.
- Charge pakai token (bayar 1-klik) / dipakai sebagai token recurring.
- **Handler notifikasi status akun** (aktif/nonaktif/kedaluwarsa).
- Set **"URL notifikasi menghubungkan akun"** → gateway.

---

## 3. Catatan penting saat mengembangkan
- **Signature**: `sha512(order_id + status_code + gross_amount + server_key)` — server_key
  gateway HARUS sama dgn app. `gross_amount` format Midtrans = `"<amount>.00"`.
- **Jangan** arahkan URL notifikasi recurring/linking ke gateway SEBELUM gateway punya
  handler-nya — payload beda → 403/404 → Midtrans retry (log berisik).
- Gateway forward internal ke `127.0.0.1:8044` (bypass nginx/WAF). Path `/midtrans/*`,
  `/api/subscription-webhook`, `/app/`, `/broadcasting/auth` sudah dikecualikan dari WAF.
- Setelah ubah `.env`/config: `php artisan optimize && systemctl restart octane-stakko-pos`
  (opcache `validate_timestamps=0`).
- Rahasia (server key) TIDAK boleh masuk git.
