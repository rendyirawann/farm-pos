# farm.mooda.id — Farm POS

Klon `stakko-pos` yang dikustomisasi untuk **satu tenant**. Berdiri sendiri:
folder, database, index Redis, service, dan vhost terpisah dari mooda.id.

> Jangan menaruh rahasia di berkas ini — ia berada di dalam repo & web root.

## Asal-usul & alur update

```
upstream  git@github.com:rendyirawann/stakko-pos.git   (fetch saja, push DIMATIKAN)
origin    git@github.com:rendyirawann/farm-pos.git     (repo kustomisasi ini)
```

Ambil perbaikan dari produk induk:
```bash
git fetch upstream
git merge upstream/main        # selesaikan konflik pada berkas yang dikustomisasi
git push origin main
```
Push ke `upstream` sengaja dimatikan supaya kustomisasi tenant ini tidak pernah
bocor ke repo produk induk.

## Alokasi sumber daya

| Sumber daya | mooda.id | dev.mooda.id | **farm.mooda.id** |
|---|---|---|---|
| Folder | `stakko-pos` | `dine-sync-pos-v2` | **`farm-pos`** |
| Octane | 8044 | 8055 | **8066** |
| Reverb | 8080 | 8081 | **8082** |
| RoadRunner metrics | — | 2112 | **2114** |
| Database | `stakko_pos` | `dinesync_pos` | **`farm_pos`** |
| Redis session / cache | db0 / db1 | db2 / db3 | **db4 / db5** |
| Prefix Redis | `mooda_` | `dinesync_` | **`farm_`** |
| Cookie sesi | `mooda-session` | `dinesync_session` | **`farm-session`** |

## Service (systemd)

```bash
systemctl status  octane-farm-pos reverb-farm-pos worker-farm-pos
systemctl restart octane-farm-pos
journalctl -u octane-farm-pos -f
```

## Alur deploy

```bash
cd /var/www/html/farm-pos
git pull --ff-only origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # WAJIB: public/build tidak ikut git
php artisan migrate --force

php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache public/build

php artisan octane:reload
php artisan queue:restart
```

## Yang sengaja TIDAK diaktifkan

- **Kredensial pembayaran** (Midtrans/Tripay/DOKU) dikosongkan — tidak disalin dari
  mooda.id. Isi sendiri bila instance ini perlu menerima pembayaran online.
- **SMTP** masih `MAIL_MAILER=log`; reset password belum mengirim email sungguhan.
- **Seeder data demo** (TenantSeeder, UserSeeder, TerraCoffeeSeeder) tidak dijalankan.
  Yang dijalankan hanya RolePermissionSeeder, SuperAdminSeeder, DepositTierSeeder.

## Jangan lakukan

- `redis-cli FLUSHALL` / `FLUSHDB` tanpa `-n` → menghapus sesi aplikasi lain.
  Untuk instance ini: `redis-cli -n 5 --scan --pattern 'farm_*'`, hapus yang perlu saja.
- Menjalankan ulang `SuperAdminSeeder` di server ini (mengembalikan password default).
