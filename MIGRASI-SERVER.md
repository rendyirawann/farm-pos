# Analisis & Flow Migrasi Server — Mooda / stakko-pos

> Dokumen perencanaan migrasi **dari server lama → server baru**. Berisi inventaris lengkap
> (sistem, konfigurasi, security, IP whitelist, payment gateway) + langkah migrasi berurutan.
> **Rahasia (password/secret key) hanya disebut LOKASINYA, bukan nilainya** — salin langsung dari server lama.
>
> Dibuat: 2026-07-18. Sesuaikan `IP_BARU` di seluruh langkah dengan IP server baru kamu.

---

## 0. RINGKASAN

| | Server LAMA (sumber) | Server BARU (tujuan) |
|---|---|---|
| IPv4 (origin) | **187.77.125.157** (Hostinger, Jakarta) | `IP_BARU` (isi) |
| Domain | mooda.id, www, blog, affiliate — **DNS langsung (A record → IP origin)** | sama (ubah A record ke `IP_BARU` di panel DNS/registrar) |
| OS | Ubuntu 24.04.4 LTS | disarankan sama (Ubuntu 24.04) |

**Sifat migrasi:** angkat-pindah (lift & shift) full-stack: app Laravel + PostgreSQL + Redis + nginx + Octane/RoadRunner + Reverb + 2 payment gateway + konfigurasi security. Cutover DNS = ubah **A record** domain (`mooda.id`, `www`, `blog`, `affiliate`) ke `IP_BARU` di panel DNS/registrar. **Turunkan TTL** jadi 300 detik beberapa jam sebelum cutover agar perpindahan cepat & downtime minimal.

> ✅ **CATATAN GIT:** seluruh pekerjaan terbaru sudah di-**commit & push** ke `main` (commit `9758f53`, 2026-07-18) — integrasi DOKU, logo partner, animasi kasir, deposit, onboarding, dll. Jadi `git clone` sudah lengkap. **Tapi** file di luar git tetap WAJIB disalin manual: `.env`, kunci `.pem` (DOKU), `vendor/`, `public/build/`, `storage/app/`, gateway payment, dan config server (nginx/WAF/systemd/fail2ban). Cara paling aman & lengkap tetap **rsync filesystem** (lihat Tahap B).

---

## 1. INVENTARIS STACK (server lama)

| Komponen | Versi | Catatan |
|---|---|---|
| OS | Ubuntu 24.04.4 LTS | |
| PHP | 8.3.6 (NTS) | + opcache/JIT; `validate_timestamps=0` (CLI) → wajib restart octane tiap deploy |
| PostgreSQL | 16.14 | DB utama `stakko_pos` |
| Redis | 7.0.15 | session + cache (prefix `mooda_`), localhost tanpa password |
| nginx | 1.24.0 | reverse proxy + ModSecurity WAF |
| Node | 22.23.1 | build aset Vite |
| Composer | 2.10.2 | |
| MySQL 8.0 | (aktif di :3306) | **tidak dipakai app** (app pakai PostgreSQL) — boleh diabaikan saat migrasi |

### Service systemd (aktif)
- `octane-stakko-pos.service` — Laravel Octane/**RoadRunner**, `127.0.0.1:8044` (nginx proxy ke sini).
- `reverb-stakko-pos.service` — Laravel Reverb WebSocket, `127.0.0.1:8080` (nginx `/app`).
- `nginx`, `php8.3-fpm` (untuk gateway payment), `postgresql@16-main`, `redis-server`.

### Timer systemd
- `stakko-scheduler.timer` → jalankan Laravel scheduler tiap menit.
- `mooda-db-backup.timer` → **00:00 WIB harian** → `/usr/local/bin/mooda-db-backup.sh` → `pg_dump stakko_pos | gzip` ke **`/home/db-backups/`**, retensi 14 hari.

### Port
- Publik: **80, 443** (nginx), **2707** (SSH).
- Internal (localhost only): 8044 (RoadRunner), 8080 (Reverb), 5432 (PostgreSQL), 6379 (Redis), 3306 (MySQL—tak dipakai).

---

## 2. APLIKASI

- **Path:** `/var/www/html/stakko-pos` (owner `www-data`), root web `public/`.
- **Framework:** Laravel 12 + Octane (RoadRunner). Real-time: Reverb (Echo via `@vite`, channel `orders.{tenantId}`).
- **Git:** `git@github.com:rendyirawann/stakko-pos.git`, branch `main`, commit terakhir `8153b2f` (2026-07-15). Deploy key SSH di `/root/.ssh/github_deploy`.
- **Aset build:** `public/build/` (Vite, **gitignored** → wajib ikut disalin ATAU `npm ci && npm run build` di server baru).
- **`bootstrap/app.php`:** `trustProxies(at:'*')` (wajib karena di belakang Octane/RoadRunner); CSRF except: `api/subscription-webhook`, `api/doku-webhook`.

### ✅ Status git (sudah sinkron)
Semua pekerjaan terbaru sudah ter-commit & push ke `main` (`9758f53`): integrasi DOKU (`app/Services/Doku/DokuSnap.php`, `DokuChannelController`, `DokuVaChannel`), logo partner (`PartnerLogo`, `SiteOption`, `PartnerLogoController`), animasi kasir, deposit Starter, onboarding, gabung tab Kategori, migration `2026_07_16_*`, dll. Jadi kode aman di GitHub.
→ Untuk server baru boleh **`git clone`** (kode lengkap), **tapi wajib tetap** salin manual file di luar git (`.env`, `.pem`, `vendor/`, `public/build/`, `storage/app/`, gateway, config server). Rekomendasi: rsync filesystem apa adanya (Tahap B) — paling lengkap.

---

## 3. DATABASE

- **PostgreSQL** `stakko_pos`, user `stakko` (password di `.env` → `DB_PASSWORD`), host `127.0.0.1:5432`.
- Ukuran ~**12 MB**, **45 tabel**.
- Backup harian sudah ada di `/home/db-backups/*.sql.gz`.

---

## 4. STORAGE & KUNCI (di luar DB — WAJIB disalin)

- `storage/app/public/` (~1.8 MB) — **gambar menu + logo partner**. Terhubung via symlink `public/storage` → `storage/app/public` (jalankan `php artisan storage:link` di server baru).
- **Kunci RSA DOKU** (KRUSIAL, di luar git):
  - `storage/app/doku/private.pem` (600) — private key merchant.
  - `storage/app/doku/public.pem` — public key merchant (yang diupload ke dashboard DOKU).
  - `storage/app/doku/doku_public.pem` — DOKU Public Key (verifikasi notifikasi).
- `.env` (rahasia — di luar git).

---

## 5. 💳 PAYMENT GATEWAY (money-critical — jangan sampai terlewat)

Ada **switch driver** di `config/billing.php` → `BILLING_DRIVER` (`.env`). Saat ini **`midtrans`** (LIVE). DOKU masih `sandbox`.

### 5.1 Midtrans (AKTIF / production)
- `.env`: `MIDTRANS_MERCHANT_ID, MIDTRANS_CLIENT_KEY, MIDTRANS_SERVER_KEY, MIDTRANS_IS_PRODUCTION=true, MIDTRANS_NOTIFY_URL=https://mooda.id/midtrans/notify`.
- **Gateway terpusat:** `/var/www/html/midtrans-gateway/` (dilayani **php-fpm**, bukan Octane).
  - `config.php` (chmod **600**, di luar git) — berisi `server_key` + `routes` (prefix `DSP-SUB-` → `http://127.0.0.1:8044/api/subscription-webhook`) + `finish_routes` + `forward_host`.
  - `public/index.php` (router notifikasi, verifikasi SHA512).
- nginx location: `^/midtrans/(notify|finish)$` → root `/var/www/html/midtrans-gateway/public`.
- Webhook Laravel: `POST /api/subscription-webhook`.

### 5.2 DOKU SNAP (SANDBOX — belum live)
- `.env`: `DOKU_IS_PRODUCTION=false, DOKU_CLIENT_ID, DOKU_SECRET_KEY, DOKU_PRIVATE_KEY, DOKU_OWN_PUBLIC_KEY, DOKU_PUBLIC_KEY (path ke .pem), DOKU_PARTNER_SERVICE_ID, DOKU_VA_CUSTOMER_PREFIX, DOKU_VA_CHANNEL`.
- **Gateway terpusat:** `/var/www/html/doku-gateway/` (php-fpm) — `config.php` (routing prefix `DSP-SUB-`/`DSP-DEP-` → `http://127.0.0.1:8044/api/doku-webhook`) + `public/index.php` + **`keys/`** (`merchant_private.pem`, `merchant_public.pem`, `doku_public.pem` — WAJIB disalin).
- nginx location: `^/doku/snap/(access-token|notify)$` → root `/var/www/html/doku-gateway/public` + `fastcgi_param HTTP_AUTHORIZATION`.
- Webhook Laravel: `POST /api/doku-webhook`. Channel bank dikelola Superadmin (tabel `doku_va_channels`).
- Detail lengkap: lihat `docs/DOKU-SNAP.md`.

> **Webhook URL berbasis DOMAIN** (`https://mooda.id/...`), bukan IP → setelah cutover DNS ke `IP_BARU`, URL webhook TIDAK berubah (tak perlu update di dashboard Midtrans/DOKU). Tapi **pastikan gateway + keys + config.php ikut tersalin** & nginx location terpasang di server baru.

---

## 6. 🔒 SECURITY

### UFW (firewall)
Izinkan: **80, 443, 2707/tcp**. (Sisanya default deny.)

### fail2ban
- Jail aktif: `sshd, nginx-bad-request, nginx-botsearch, nginx-http-auth, nginx-limit-req, recidive`.
- **`ignoreip` (WHITELIST):** `127.0.0.1/8 ::1 103.129.25.159 182.9.34.144`.
- **GOTCHA:** fail2ban DROP (bukan reject) → IP kena ban tampak "timeout".

### ModSecurity WAF (OWASP CRS) — **SecRuleEngine On / BLOCKING**
- Wiring: `/etc/nginx/conf.d/10-modsecurity.conf` → `/etc/nginx/modsec/main.conf`. Audit log `/var/log/nginx/modsec_audit.log`.
- **GOTCHA:** ubah rule WAF → wajib `systemctl restart nginx` (reload tak cukup).
- `crs-setup.conf` rule 900200: `allowed_methods = GET HEAD POST OPTIONS PUT PATCH DELETE` (PUT/PATCH/DELETE ditambah, kalau tidak tombol Hapus/Edit gagal senyap).
- **`/etc/nginx/modsec/exclusions.conf`** (WAJIB disalin) — ruleEngine=Off untuk: `/midtrans/` (id1000), `/api/subscription-webhook` (1001), `/app/` (1002, Reverb WS), `/broadcasting/auth` (1003), `/doku/snap/` (1004); **DetectionOnly** untuk blog (1010/1011).

### SSH
- Port **2707** (22 tertutup). `PermitRootLogin yes`, `PasswordAuthentication yes` (pertimbangkan key-only di server baru). Config: `/etc/ssh/sshd_config.d/99-hardening.conf`.
- 2 kunci di `/root/.ssh/authorized_keys` (+ deploy key `/root/.ssh/github_deploy`).

### Lain-lain
- sysctl hardening `/etc/sysctl.d/99-security-hardening.conf`.
- unattended-upgrades on. Monarx agent (`/etc/monarx-agent.conf` chmod 600).

---

## 7. 🌐 DNS & SSL (koneksi langsung — TANPA proxy/CDN)

- Domain **mooda.id (+www, blog, affiliate)** memakai **A record langsung** ke IP server (saat ini `187.77.125.157`). DNS dikelola di panel registrar (Rumahweb).
- **Cutover:** ubah **A record** keempat host tersebut → `IP_BARU`. Turunkan **TTL ke 300 detik** beberapa jam sebelum cutover supaya propagasi cepat.
- **SSL origin:** certbot Let's Encrypt (`mooda.id` + 3 subdomain, expiry 2026-10-12). Di server baru terbitkan sertifikat baru:
  `sudo certbot --nginx -d mooda.id -d www.mooda.id -d blog.mooda.id -d affiliate.mooda.id` (setelah A record menunjuk ke IP_BARU).
- **Karena tanpa proxy/CDN**, nginx melihat langsung IP asli pengunjung — **tidak perlu** config real-IP (`set_real_ip_from`/`CF-Connecting-IP`). Kalau di server lama masih ada `/etc/nginx/conf.d/00-cloudflare-realip.conf`, **JANGAN disalin** ke server baru (kalau ikut, semua IP klien salah terbaca → WAF/fail2ban/log kacau).
- MX/email `mooda.id` = **Titan** (`mx1/mx2.titan.email`) — hanya record DNS, **tak terpengaruh** migrasi server (email tidak di server ini). Jangan diubah saat cutover.

---

## 8. `.env` — variabel per kategori (nilai salin dari server lama)
- **App:** APP_NAME, APP_ENV=production, **APP_KEY** (WAJIB sama — kalau beda, data terenkripsi & session rusak), APP_URL=https://mooda.id, APP_TIMEZONE.
- **DB:** DB_CONNECTION=pgsql, DB_HOST=127.0.0.1, DB_PORT=5432, DB_DATABASE=stakko_pos, DB_USERNAME=stakko, DB_PASSWORD.
- **Redis/Session/Cache:** REDIS_*, SESSION_DRIVER, CACHE_STORE, CACHE_PREFIX=mooda_, QUEUE_CONNECTION.
- **Octane/Reverb:** OCTANE_SERVER, REVERB_APP_ID/KEY/SECRET/HOST/PORT/SCHEME + VITE_REVERB_*.
- **Mail:** MAIL_MAILER/HOST/PORT/FROM_*.
- **Payment:** MIDTRANS_*, BILLING_PURCHASE_ENABLED, BILLING_DRIVER, DOKU_* (lihat §5).
- **Lain:** MOBILE_APK_URL, SUPPORT_WA, GOOGLE_SITE_VERIFICATION, FACEBOOK_APP_ID.

---

## 9. ✅ FLOW MIGRASI (berurutan)

### Tahap A — Siapkan server baru (belum ganti DNS)
1. Provision `IP_BARU` (Ubuntu 24.04). Amankan SSH: set **Port 2707**, buka UFW `80,443,2707`.
2. Install stack sama persis: PHP 8.3 (+ekstensi: pgsql, redis, gd, mbstring, bcmath, intl, opcache, dll), PostgreSQL 16, Redis 7, nginx 1.24 + **ModSecurity + OWASP CRS**, Node 22, Composer 2, RoadRunner.
3. Buat user PostgreSQL `stakko` + database `stakko_pos` (password sama seperti `.env` lama).

### Tahap B — Pindahkan DATA & FILE (dari server lama)
4. **Database:**
   ```bash
   # di server LAMA
   sudo -u postgres pg_dump stakko_pos | gzip > /tmp/stakko_pos.sql.gz
   scp -P 2707 /tmp/stakko_pos.sql.gz root@IP_BARU:/tmp/
   # di server BARU
   gunzip -c /tmp/stakko_pos.sql.gz | sudo -u postgres psql stakko_pos
   ```
5. **Aplikasi (rsync seluruh folder — termasuk vendor + public/build + storage + .env + keys):**
   ```bash
   rsync -az -e "ssh -p 2707" --delete \
     --exclude '.git' --exclude 'node_modules' \
     /var/www/html/stakko-pos/  root@IP_BARU:/var/www/html/stakko-pos/
   ```
   > sertakan `vendor/`, `public/build/`, `storage/app/`, `.env` (jangan exclude). Kalau tak rsync vendor/build → di server baru jalankan `composer install --no-dev` + `npm ci && npm run build`.
6. **Gateway payment (di luar app):**
   ```bash
   rsync -az -e "ssh -p 2707" /var/www/html/midtrans-gateway/ root@IP_BARU:/var/www/html/midtrans-gateway/
   rsync -az -e "ssh -p 2707" /var/www/html/doku-gateway/     root@IP_BARU:/var/www/html/doku-gateway/
   ```
   > pastikan `midtrans-gateway/config.php` (600) + `doku-gateway/keys/*.pem` ikut. Cek juga `storage/app/doku/*.pem` (ikut lewat rsync app di langkah 5).
7. **Konfigurasi server (di luar git — salin manual):**
   - `/etc/nginx/sites-available/mooda.id.conf` (+ symlink di sites-enabled) — berisi server block, proxy Octane, lokasi `/app` (Reverb), `/midtrans/*`, `/doku/snap/*`.
   - `/etc/nginx/conf.d/*.conf` (modsecurity, compression). **JANGAN salin `00-cloudflare-realip.conf`** bila ada — tak dipakai lagi (tanpa proxy/CDN).
   - `/etc/nginx/modsec/` (main.conf, **exclusions.conf**, crs-setup.conf).
   - systemd unit: `/etc/systemd/system/octane-stakko-pos.service`, `reverb-stakko-pos.service`, `stakko-scheduler.*`, `mooda-db-backup.*` + `/usr/local/bin/mooda-db-backup.sh`.
   - fail2ban: `/etc/fail2ban/jail.local` + `jail.d/*` (**ignoreip whitelist**).
   - `/etc/ssh/sshd_config.d/99-hardening.conf`, `/etc/sysctl.d/99-security-hardening.conf`.

### Tahap C — Konfigurasi & uji di server baru (masih via IP, DNS belum pindah)
8. Ownership: `chown -R www-data:www-data /var/www/html/stakko-pos/storage /var/www/html/stakko-pos/bootstrap/cache`; kunci: `chmod 600 storage/app/doku/private.pem doku-gateway/keys/merchant_private.pem midtrans-gateway/config.php`.
9. `php artisan storage:link`; `php artisan migrate --force` (kalau DB belum ter-restore penuh); `php artisan optimize`.
10. `systemctl daemon-reload` → enable+start `octane-stakko-pos`, `reverb-stakko-pos`, timer. `nginx -t && systemctl restart nginx`. Aktifkan UFW + fail2ban.
11. **Uji lewat IP / /etc/hosts sementara** (arahkan mooda.id → IP_BARU di laptop): landing 200, login, kasir, dashboard, upload gambar, WebSocket (Reverb).

### Tahap D — Cutover DNS + SSL + payment
12. Di **panel DNS/registrar (Rumahweb)** → ubah **A record** `mooda.id`, `www`, `blog`, `affiliate` dari `187.77.125.157` → **`IP_BARU`**. (TTL sudah diturunkan ke 300s sebelumnya.)
13. Terbitkan SSL di server baru: `sudo certbot --nginx -d mooda.id -d www.mooda.id -d blog.mooda.id -d affiliate.mooda.id` (setelah DNS menunjuk ke IP_BARU & port 80 terbuka).
14. Karena webhook berbasis domain, **URL Midtrans/DOKU tak berubah** — tapi **uji**: lakukan 1 transaksi kecil → pastikan notifikasi masuk (`midtrans-gateway/logs`, `doku-gateway/logs`, `storage/logs/laravel.log`).
15. Update **fail2ban ignoreip** bila IP admin berubah; whitelist IP baru bila perlu.

### Tahap E — Pasca-migrasi
16. Pantau 24–48 jam: log nginx/octane/reverb, notifikasi payment, backup harian (`/home/db-backups`).
17. Setelah yakin: matikan/lepas server lama (jangan buru-buru — simpan sebagai rollback beberapa hari).

---

## 10. 🔁 ROLLBACK
Kalau server baru bermasalah: **balikkan A record ke `187.77.125.157`** di panel DNS (server lama masih menyala) → trafik kembali setelah TTL (300s) habis. Karena itu **jangan matikan server lama** sampai server baru terbukti stabil + payment tervalidasi.

---

## 11. CHECKLIST CEPAT
- [ ] Stack terinstall sama (PHP 8.3, PG16, Redis, nginx+ModSecurity, Node22, RoadRunner).
- [ ] DB `stakko_pos` ter-restore (45 tabel).
- [ ] App ter-rsync/clone (kode di `main` `9758f53`) + `vendor/` + `public/build/` + `.env` (APP_KEY sama).
- [ ] `storage/app/public` (gambar) + `storage/app/doku/*.pem` tersalin; `storage:link`.
- [ ] **midtrans-gateway** + **doku-gateway** (config.php 600 + keys/*.pem) tersalin.
- [ ] nginx conf + modsec (**exclusions.conf**) + systemd unit + timer + fail2ban (ignoreip) + sshd hardening tersalin. (JANGAN salin `00-cloudflare-realip.conf`.)
- [ ] Service jalan: octane, reverb, scheduler, db-backup, nginx, pg, redis.
- [ ] UFW (80/443/2707) + fail2ban aktif.
- [ ] Uji via IP dulu → baru ubah A record ke IP_BARU di panel DNS (TTL 300s).
- [ ] **Uji payment 1 transaksi** (webhook masuk) — Midtrans (live) & DOKU (saat go-live).
- [ ] SSL server baru (certbot Let's Encrypt, 4 domain).
- [ ] Server lama tetap nyala untuk rollback (beberapa hari).
