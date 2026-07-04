# Panduan Deploy Stakko POS di Server Lain (Octane + Redis + Midtrans Gateway)

Panduan lengkap memasang **Stakko POS** di server Linux baru, termasuk:
sistem pembayaran langganan lewat **Midtrans Central Gateway**, setelan **Octane
(RoadRunner)**, **Redis**, dan (opsional) **Reverb**.

> Stakko POS = **Laravel 12 + PostgreSQL**. Real-time pakai polling → **tidak butuh Reverb**.
> Midtrans **hanya untuk langganan/billing SaaS** (bukan transaksi POS).

Semua file config contoh ada di folder **`deploy/`**:

```
DEPLOY-SERVER.md               <- panduan ini
stakko-pos.conf                <- nginx untuk aplikasi (subfolder & subdomain)
midtrans-gateway.conf          <- nginx untuk gateway Midtrans terpusat
deploy/
  .env.production.example      <- template .env produksi
  systemd/
    octane-stakko-pos.service      <- Octane (RoadRunner)
    stakko-scheduler.service       <- scheduler (oneshot)
    stakko-scheduler.timer         <- timer tiap menit (pengganti cron)
    reverb-stakko-pos.service      <- OPSIONAL, hanya bila pakai WebSocket
  midtrans-gateway/
    index.php                  <- kode gateway (router notifikasi Midtrans)
    config.php.example          <- template rute gateway (isi server key di sini)
    README.md                  <- cara kerja gateway
```

> ⚠️ **JANGAN commit** `.env` asli & `config.php` asli (berisi server key & password).
> File di repo ini semua **template placeholder**. Isi nilai asli langsung di server.

---

## 0) Prasyarat (sekali per server)

```bash
sudo apt update
sudo apt install -y php8.4-fpm php8.4-cli php8.4-pgsql php8.4-mbstring php8.4-xml \
  php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl php8.4-redis \
  postgresql redis-server nginx git unzip curl
# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
# Node 20 (untuk build aset Vite)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt install -y nodejs
# Go TIDAK diperlukan untuk Stakko (hanya Laravel).
sudo systemctl enable --now redis-server postgresql
```

PHP boleh 8.2–8.4; sesuaikan nama paket & path `phpX.Y-fpm.sock`.

---

## 1) Database PostgreSQL

```bash
sudo -u postgres psql <<'SQL'
CREATE DATABASE stakko_pos;
CREATE USER stakko WITH ENCRYPTED PASSWORD 'PASSWORD_KUAT_ANDA';
GRANT ALL PRIVILEGES ON DATABASE stakko_pos TO stakko;
ALTER DATABASE stakko_pos OWNER TO stakko;
SQL
```

## 2) Ambil kode + dependency + build

```bash
cd /var/www/html          # atau /var/www
sudo git clone https://github.com/rendyirawann/stakko-pos.git
cd stakko-pos
sudo chown -R $USER:www-data .

composer install --no-dev --optimize-autoloader
npm install
npm run build             # build aset (Vite)

# Octane + RoadRunner (unduh binary rr):
php artisan octane:install --server=roadrunner
```

## 3) .env

```bash
cp deploy/.env.production.example .env
# edit .env: APP_URL, DB, REDIS, MIDTRANS (lihat komentar di dalamnya)
php artisan key:generate       # kalau APP_KEY belum diisi
```

Poin penting `.env` (detail di `deploy/.env.production.example`):

| Setelan | Nilai |
|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL` + `ASSET_URL` | **wajib** menyertakan subfolder bila dipasang di `/stakko-pos` |
| `DB_*` | Postgres server ini |
| `SESSION_DRIVER` / `CACHE_STORE` | `redis` |
| `QUEUE_CONNECTION` | `database` (worker belum wajib) |
| `BROADCAST_CONNECTION` | `log` (**tanpa Reverb**) |
| `OCTANE_SERVER` | `roadrunner` |
| `MIDTRANS_*` | kunci **production** akun Midtrans + `MIDTRANS_NOTIFY_URL` = URL gateway |

### Redis (isolasi antar-app di 1 server)
Kalau server ini menjalankan >1 aplikasi Laravel yang berbagi Redis, beri tiap app
**DB index + prefix** berbeda agar key tidak bentrok:
```env
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=6            # index unik per-app (0,1,2,... )
REDIS_CACHE_DB=6
REDIS_PREFIX=stakko_pos_
CACHE_PREFIX=stakko_pos_cache
```
Kalau Stakko satu-satunya app di server, default pun aman.

## 4) Storage, migrasi, cache

```bash
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Deploy pertama (DB kosong) — dengan data demo:
php artisan migrate:fresh --seed --force
#   Superadmin: superadmin@gmail.com / 12qwaszx123!!@@##  (GANTI setelah login)
# Produksi bersih (tanpa demo):
#   php artisan migrate:fresh --force
#   php artisan db:seed --class=RolePermissionSeeder --force
#   php artisan db:seed --class=SuperAdminSeeder --force

php artisan optimize:clear
```

## 5) Octane (RoadRunner) — systemd

`octane:start` dijalankan sebagai service. Salin unit & sesuaikan **port** + path:
```bash
sudo cp deploy/systemd/octane-stakko-pos.service /etc/systemd/system/
# edit: WorkingDirectory, --port (default 8044), phpX.Y
sudo systemctl daemon-reload
sudo systemctl enable --now octane-stakko-pos
sudo systemctl status octane-stakko-pos
```
Setiap habis `git pull` / ubah kode: `sudo systemctl restart octane-stakko-pos`
(Octane menahan app di memori — wajib reload agar kode baru kepakai.)

## 6) Scheduler (auto-expire trial/langganan) — systemd timer

Pengganti cron `* * * * *`:
```bash
sudo cp deploy/systemd/stakko-scheduler.service /etc/systemd/system/
sudo cp deploy/systemd/stakko-scheduler.timer   /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now stakko-scheduler.timer
```

## 7) nginx

Lihat **`stakko-pos.conf`** (ada 2 varian):
- **Subdomain** (disarankan untuk PWA/service worker mulus) — mis. `app.domain.com`.
- **Subfolder** `/stakko-pos` (di belakang server block domain utama, proxy ke Octane).

```bash
# subdomain:
sudo cp stakko-pos.conf /etc/nginx/sites-available/stakko-pos.conf
sudo ln -s /etc/nginx/sites-available/stakko-pos.conf /etc/nginx/sites-enabled/
# subfolder: tempel blok location ke server block domain utama.
sudo nginx -t && sudo systemctl reload nginx
# HTTPS (wajib untuk PWA):
sudo certbot --nginx -d app.domain.com
```

## 8) (OPSIONAL) Reverb — hanya bila mau real-time WebSocket

Stakko **tidak butuh Reverb** (`BROADCAST_CONNECTION=log`). Kalau tetap mau WS:
1. `.env`: `BROADCAST_CONNECTION=reverb` + isi `REVERB_APP_ID/KEY/SECRET`, `REVERB_HOST=127.0.0.1`, `REVERB_PORT=8080`, `REVERB_SCHEME=http`. **Gunakan APP_ID/KEY/SECRET yang UNIK per server** (jangan pakai default repo).
2. Service: `sudo cp deploy/systemd/reverb-stakko-pos.service /etc/systemd/system/ && sudo systemctl enable --now reverb-stakko-pos`.
3. nginx: buka blok WebSocket di `stakko-pos.conf` (bagian `# --- (opsional) REVERB ---`).

---

## 9) Midtrans — sistem Central Gateway

**Konsep:** Midtrans hanya mengizinkan **1 Notification URL per akun**. Bila beberapa
project berbagi 1 akun Midtrans, dipakai **gateway terpusat** yang:
1. menerima semua notifikasi di **satu** URL,
2. verifikasi signature (SHA512),
3. me-**route** ke webhook project yang tepat **berdasarkan prefix `order_id`**.

```
Midtrans ──(notify)──> https://DOMAIN/midtrans/notify  (gateway)
                          │ verifikasi signature (server key production)
                          │ cocokkan prefix order_id
                          ├─ "STK-SUB-" ─> http://127.0.0.1/stakko-pos/api/subscription-webhook
                          ├─ "DSP-SUB-" ─> ...dine-sync-pos/api/subscription-webhook
                          └─ ...prefix lain...
```

### 9a) Pasang gateway (sekali per server/akun Midtrans)
```bash
sudo mkdir -p /var/www/html/midtrans-gateway/public /var/www/html/midtrans-gateway/logs
sudo cp deploy/midtrans-gateway/index.php        /var/www/html/midtrans-gateway/public/index.php
sudo cp deploy/midtrans-gateway/config.php.example /var/www/html/midtrans-gateway/config.php
# EDIT config.php: isi "server_key" (server key PRODUCTION Midtrans) + daftar "routes"/"finish_routes"
sudo chown -R www-data:www-data /var/www/html/midtrans-gateway
sudo chmod 600 /var/www/html/midtrans-gateway/config.php     # rahasia
sudo chown -R www-data:www-data /var/www/html/midtrans-gateway/logs
```
nginx: tempel blok dari **`midtrans-gateway.conf`** ke server block domain utama, `nginx -t && reload`.

### 9b) Daftarkan Stakko di gateway
Di `config.php` tambahkan (sudah ada contohnya):
```php
"routes" => [
    "STK-SUB-" => "http://127.0.0.1/stakko-pos/api/subscription-webhook",
    // ...project lain...
],
"finish_routes" => [
    "STK-SUB-" => "https://DOMAIN/stakko-pos/admin/billing",
],
```
> Prefix **`STK-SUB-`** dibuat unik untuk Stakko (di `app/Http/Controllers/Backend/Billing/BillingController.php`,
> baris `$orderId = 'STK-SUB-' . ...`). Kalau di server barumu Stakko satu-satunya project
> dan pakai akunnya sendiri, prefix boleh apa saja asal **konsisten** antara app & gateway.
> Kalau `http://127.0.0.1/stakko-pos/...` (subfolder) → sesuaikan ke `http://127.0.0.1:8044/api/subscription-webhook`
> bila pakai subdomain/port langsung, atau `https://app.domain.com/api/subscription-webhook`.

### 9c) .env Stakko → arahkan notifikasi ke gateway
```env
MIDTRANS_MERCHANT_ID=xxxxxxxx
MIDTRANS_CLIENT_KEY=Mid-client-xxxx           # production
MIDTRANS_SERVER_KEY=Mid-server-xxxx           # production (HARUS sama dgn "server_key" gateway)
MIDTRANS_IS_PRODUCTION=true
MIDTRANS_NOTIFY_URL=https://DOMAIN/midtrans/notify
```
`configureMidtrans()` di BillingController otomatis set `Config::$overrideNotifUrl` = `MIDTRANS_NOTIFY_URL`,
jadi tiap transaksi langganan mengarahkan notifikasi ke gateway.

### 9d) Dashboard Midtrans (production)
- **Notification URL** = `https://DOMAIN/midtrans/notify`
- **Finish Redirect URL** = `https://DOMAIN/midtrans/finish` (gateway redirect balik per prefix)
- Tombol "Tes URL notifikasi" akan balas **200** (gateway mengenali `payment_notif_test_*`).

### 9e) Verifikasi signature
Gateway & app **dua-duanya** verifikasi `SHA512(order_id + status_code + gross_amount + server_key)`.
Karena itu **server key gateway == server key app** (satu akun). Kalau app pakai sandbox tapi
gateway pakai production (atau sebaliknya), verifikasi GAGAL. Samakan mode & key.

> **Kalau server baru pakai akun Midtrans SENDIRI (mis. sandbox untuk uji):**
> paling sederhana **tanpa gateway** — set `MIDTRANS_NOTIFY_URL` kosong dan isi
> Notification URL di dashboard Midtrans langsung ke `https://app.domain.com/api/subscription-webhook`.
> Gateway hanya perlu bila **>1 project berbagi 1 akun**.

---

## 10) Update / redeploy (server sudah jalan)

```bash
cd /var/www/html/stakko-pos
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build            # kalau ada perubahan resources/js|css
php artisan migrate --force             # 'migrate' saja, JANGAN migrate:fresh (data aman)
php artisan optimize:clear
sudo chown -R www-data:www-data storage bootstrap/cache public/build
sudo systemctl restart octane-stakko-pos    # WAJIB (Octane in-memory)
# storage:link (kalau public/storage belum ada; kalau sudah & mau segar: rm public/storage dulu)
```

## Ringkasan port & service

| Service | systemd | Port | Catatan |
|---|---|---|---|
| Octane (RoadRunner) | `octane-stakko-pos` | 8044 (bebas) | serve aplikasi |
| Scheduler | `stakko-scheduler.timer` | — | `schedule:run` tiap menit |
| Reverb (opsional) | `reverb-stakko-pos` | 8080 | hanya bila WS dipakai |
| Redis | `redis-server` | 6379 | cache + session |
| Postgres | `postgresql` | 5432 | database |
| Midtrans gateway | (via php-fpm) | — | `/midtrans/notify` + `/midtrans/finish` |
