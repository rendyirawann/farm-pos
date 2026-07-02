# Panduan Deploy Stakko POS (VPS Linux + Nginx + PostgreSQL)

Stakko POS = Laravel 12 + PostgreSQL. **Tidak butuh Reverb / WebSocket** (real-time pakai polling + `location.reload`; offline pakai Dexie). Wajib **HTTPS** untuk PWA, mode offline (service worker), Web Bluetooth, dan APK/TWA.

> Disarankan pasang di **root domain / subdomain** (mis. `https://app.beoulve-dev.biz.id`), bukan subpath `/stakko-pos`, agar PWA & service worker mulus.

---

## 0) Prasyarat (install sekali di server)

```bash
sudo apt update
# PHP 8.2+ dan ekstensi yang dibutuhkan
sudo apt install -y php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl \
  postgresql nginx git unzip certbot python3-certbot-nginx

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20 + npm (untuk build landing page / Vite)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

## 1) Buat database PostgreSQL

```bash
sudo -u postgres psql <<'SQL'
CREATE DATABASE stakko_pos;
CREATE USER stakko WITH ENCRYPTED PASSWORD 'PASSWORD_KUAT_ANDA';
GRANT ALL PRIVILEGES ON DATABASE stakko_pos TO stakko;
ALTER DATABASE stakko_pos OWNER TO stakko;
SQL
```

## 2) Ambil kode & dependency

```bash
cd /var/www
sudo git clone https://github.com/rendyirawann/stakko-pos.git
sudo chown -R $USER:www-data stakko-pos
cd stakko-pos

composer install --no-dev --optimize-autoloader
npm install
npm run build          # build aset landing page (Vite)
```

## 3) Konfigurasi .env

```bash
cp .env.example .env
php artisan key:generate
nano .env
```

Isi yang penting di `.env`:

```env
APP_NAME="Stakko POS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.beoulve-dev.biz.id     # domain HTTPS Anda
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=stakko_pos
DB_USERNAME=stakko
DB_PASSWORD=PASSWORD_KUAT_ANDA

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=log      # TIDAK pakai Reverb

# Email (untuk reset password, dll)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=email-anda
MAIL_PASSWORD=app-password-anda
MAIL_FROM_ADDRESS=email-anda

# Midtrans (HANYA untuk pembayaran langganan/billing SaaS, bukan POS)
MIDTRANS_MERCHANT_ID=...
MIDTRANS_CLIENT_KEY=...
MIDTRANS_SERVER_KEY=...
MIDTRANS_IS_PRODUCTION=true
MIDTRANS_NOTIFY_URL=          # kosongkan -> pakai setelan dashboard Midtrans

# Aplikasi tablet (opsional)
MOBILE_APK_URL=
MOBILE_APK_VERSION=1.0.0
```

## 4) Migrasi + data awal

```bash
# DEPLOY PERTAMA (DB kosong) — pilih salah satu:

# a) Lengkap dengan data demo (untuk uji coba): buat Superadmin + tenant "Demo Resto" + akun demo + contoh menu
php artisan migrate:fresh --seed --force

# b) Produksi bersih (tanpa data demo): hanya role/permission + akun Superadmin
php artisan migrate:fresh --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=SuperAdminSeeder --force
```

Login Superadmin default: `superadmin@gmail.com` / `12qwaszx123!!@@##` → **segera ganti password** setelah login.

## 5) Storage & izin folder

```bash
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 6) Cache produksi (mempercepat)

```bash
php artisan optimize          # cache config + route + view sekaligus
# (setara: php artisan config:cache && route:cache && view:cache)
```

## 7) Nginx (document root = folder public/)

`/etc/nginx/sites-available/stakko`:

```nginx
server {
    listen 80;
    server_name app.beoulve-dev.biz.id;
    root /var/www/stakko-pos/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # service worker & manifest boleh diakses
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
    client_max_body_size 20M;
}
```

```bash
sudo ln -s /etc/nginx/sites-available/stakko /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## 8) HTTPS (Let's Encrypt) — WAJIB

```bash
sudo certbot --nginx -d app.beoulve-dev.biz.id
sudo systemctl reload nginx
```

## 9) Cron scheduler (auto-expire trial/langganan)

```bash
crontab -e
# tambahkan:
* * * * * cd /var/www/stakko-pos && php artisan schedule:run >> /dev/null 2>&1
```

## 10) (Opsional) Aplikasi tablet APK

Taruh file APK hasil build (PWABuilder / Android Studio) di:
```
/var/www/stakko-pos/public/downloads/stakko-pos.apk
```
Tombol Download di menu **Aplikasi** otomatis aktif. (Lihat `mobile/README-BUILD-APK.md`.)

---

## Update / redeploy (saat ada perubahan kode baru)

```bash
cd /var/www/stakko-pos
php artisan down                       # maintenance mode (opsional)
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force            # PENTING: 'migrate' saja, JANGAN 'migrate:fresh' (biar data tidak hilang)
php artisan optimize:clear && php artisan optimize
sudo chown -R www-data:www-data storage bootstrap/cache
php artisan up
```

---

## Deploy di SUBFOLDER (mis. `https://beoulve-dev.biz.id/stakko-pos`)

Aplikasi sudah dibuat subfolder-aware. Yang perlu dipastikan:

1. **`APP_URL` WAJIB menyertakan subfolder** — ini kunci utamanya:
   ```env
   APP_URL=https://beoulve-dev.biz.id/stakko-pos
   ASSET_URL=https://beoulve-dev.biz.id/stakko-pos
   ```
   Semua `asset()`, `route()`, `url()`, `@vite`, manifest, service worker, dan tombol kiosk otomatis ikut ke `/stakko-pos/...`.

2. **Nginx** — cara paling mudah: symlink folder `public` ke docroot utama:
   ```bash
   sudo ln -s /var/www/stakko-pos/public /var/www/html/stakko-pos
   ```
   (Server block utama meng-serve `/stakko-pos` apa adanya. Alternatif: pakai `location /stakko-pos { alias ...; }` — lebih rumit.)

3. Setelah ubah `.env`, jalankan ulang: `php artisan optimize:clear && php artisan optimize`.

> Tetap **lebih disarankan subdomain root** (`app.beoulve-dev.biz.id`) bila memungkinkan — lebih sederhana untuk PWA/cookie. Tapi subfolder sudah didukung.

## Catatan penting

- **`migrate:fresh` menghapus SEMUA data** — hanya untuk deploy pertama. Update berikutnya pakai `migrate` saja.
- **HTTPS wajib** untuk PWA, mode offline, Web Bluetooth, dan APK.
- **Tidak perlu Reverb / `reverb:start` / port WebSocket.** `BROADCAST_CONNECTION=log`.
- **Queue worker tidak wajib** (belum ada job aplikasi). Kalau nanti dibutuhkan: `php artisan queue:work` via supervisor.
- Ganti password Superadmin & kredensial demo setelah go-live.
