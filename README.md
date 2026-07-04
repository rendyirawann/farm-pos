# Mooda

**Mooda** adalah sistem *point-of-sale* (POS) untuk sektor F&B, dibangun sebagai **SaaS multi-tenant** (single-database / shared-schema) di atas **Laravel 12** dan **PostgreSQL**. Ditargetkan untuk UMKM dan dioptimalkan untuk dijalankan di **tablet** (responsif juga di HP, laptop, dan desktop).

## Fitur Utama

- **Kasir (POS) satu halaman** — input nama pelanggan, pilih menu per kategori, keranjang, **Add-Ons per menu**, dan checkout di satu layar.
  - Pembayaran **Tunai** (dengan hitung kembalian otomatis) atau **QRIS** — tanpa payment gateway.
  - Bayar **di depan** (struk LUNAS) atau **bayar di belakang**: pesanan masuk daftar berjalan, wajib dibayar saat diselesaikan.
  - Setiap pesanan punya **nomor antrian** harian yang tercetak di struk.
  - Mode **offline (PWA)** dengan Dexie/IndexedDB + service worker; transaksi offline otomatis tersinkron saat online.
- **Kitchen Display System (KDS)** — papan status masak per item/pesanan.
- **Data Master** — kategori, menu (+ add-ons), promo.
- **Laporan** — penjualan & per-item.
- **Shift kasir** — buka/tutup shift + rekonsiliasi kas & target penjualan harian.
- **Multi-tenant & Langganan** — paket Starter/Business, trial 14 hari, gating fitur, manajemen tenant (Superadmin).
- **RBAC** (spatie/laravel-permission) & **audit log** (spatie/laravel-activitylog).

## Tech Stack

- PHP `^8.2`, Laravel `^12`
- PostgreSQL
- Session/Cache/Queue: driver `database`
- Frontend admin: tema Metronic (Bootstrap 5) + jQuery + DataTables; landing page: Vite + Tailwind + Alpine
- PWA offline: Dexie (di-vendor lokal di `public/assets/plugins/custom/dexie`)

## Menjalankan (Development)

```bash
composer install
cp .env.example .env      # sesuaikan koneksi PostgreSQL & kredensial
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run build             # atau: npm run dev
php artisan serve
```

> **Deploy / DB terbaru:** karena file migration & seeder sudah disesuaikan dengan struktur terbaru,
> jalankan `php artisan migrate:fresh --seed` agar skema + data awal ikut ter-update.

### Akun demo (setelah seed)

| Peran      | Email                | Password       |
|------------|----------------------|----------------|
| Superadmin | superadmin@gmail.com | 12qwaszx123!!@@## |
| Owner      | owner@demo.test      | owner12345     |
| Admin      | admin@demo.test      | admin12345     |
| Kasir      | kasir@demo.test      | kasir12345     |
| Kitchen    | kitchen@demo.test    | kitchen12345   |

## Scheduler (langganan)

Tenant yang trial/langganannya kedaluwarsa ditandai otomatis oleh command `subscriptions:expire`
(dijadwalkan harian di `routes/console.php`). Aktifkan scheduler via cron:

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```
