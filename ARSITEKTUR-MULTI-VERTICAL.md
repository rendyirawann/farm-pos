# Arsitektur Multi-Vertical — Mooda (F&B → Laundry → Retail)

> Dokumen **rencana**, belum diimplementasikan. Berisi keputusan arsitektur untuk
> mengembangkan Mooda POS (saat ini khusus **F&B**) ke vertical lain: **Laundry** & **Retail**.
> Dibuat: 2026-07-30.

---

## 0. Ringkasan keputusan

| Pertanyaan | Keputusan | Alasan singkat |
|---|---|---|
| Database dipisah? | **TIDAK — satu DB** (`stakko_pos`) | Superadmin harus lihat keseluruhan (analitik, billing, affiliate) |
| Repo/folder dipisah? | **TIDAK — satu repo** (`stakko-pos`) | ~70% kode identik; fork = perbaikan bug 3× |
| Subdomain dipisah? | **YA** | Branding & UX per industri jelas terpisah |
| Plan/paket dipisah? | **YA** (per vertical) | Kebutuhan & harga tiap industri beda |

**Inti:** satu aplikasi, satu database, dibedakan oleh kolom **`vertical`** + **subdomain**.

---

## 1. Kenapa DB harus SAMA

Kalau DB dipisah per vertical, yang rusak:

- **Dashboard analitik platform** — omzet/tenant/transaksi harus di-query lintas-DB (lambat & ribet).
- **Billing & langganan** — jadi 3 sistem Tripay terpisah, rekonsiliasi manual.
- **Affiliate & komisi** — afiliator tak bisa dapat komisi lintas-vertical.
- **Tenant multi-usaha** — pemilik cafe + laundry harus punya 2 akun terpisah.
- **User & role** — duplikasi akun untuk orang yang sama.

Dengan satu DB, semua itu tetap satu sistem; pembeda cukup satu kolom.

---

## 2. Kenapa REPO juga sama

Yang **dipakai bersama** (tak perlu ditulis ulang):

```
auth & registrasi        role & permission (Spatie)   tenant & multi-tenancy
langganan / billing      Tripay + webhook             deposit / pay-as-you-go
affiliate & komisi       withdraw                     user management
kasir (inti transaksi)   struk / printer              laporan penjualan
setelan situs (CMS)      blog                         log activity
```

Yang **beda per vertical** — hanya lapisan atas:

| Vertical | Objek jualan | Alur khas |
|---|---|---|
| **F&B** | Menu, kategori, add-on | Pesan → dapur (KDS) → saji → bayar |
| **Laundry** | Layanan (kiloan/satuan/express) | Terima → cuci → kering → setrika → siap → ambil |
| **Retail** | Produk, SKU, varian | Scan barcode → bayar → stok berkurang |

**Pelajaran dari event-mooda:** repo itu hasil fork stakko-pos, akibatnya tiap perbaikan
(ganti nomor WA, preloader tombol, template email) harus dikerjakan **dua kali**.
Jangan ulangi pola itu untuk 3 vertical.

---

## 3. Struktur yang diusulkan

### 3.1 Database

```sql
-- Kolom penanda industri di tenant
ALTER TABLE tenants ADD COLUMN vertical VARCHAR(20) DEFAULT 'fnb';
-- nilai: 'fnb' | 'laundry' | 'retail'

-- Paket per vertical (memakai tabel yang SUDAH ADA)
ALTER TABLE plan_settings ADD COLUMN vertical VARCHAR(20) DEFAULT 'fnb';
ALTER TABLE plan_promos   ADD COLUMN vertical VARCHAR(20) DEFAULT 'fnb';
```

Tabel khas vertical dibuat terpisah agar tidak saling mengganggu:

```
F&B      : menus, categories, menu_addons, orders, order_details, tables   (SUDAH ADA)
Laundry  : laundry_services, laundry_orders, laundry_order_items, laundry_status_logs
Retail   : products, product_variants, stocks, retail_orders, retail_order_items
```

> Alternatif "satu tabel `orders` untuk semua" **tidak disarankan** — kolomnya akan
> saling bertabrakan (berat kiloan vs jumlah porsi vs SKU) dan bikin query berat.

### 3.2 Subdomain & routing

| Host | Vertical | Keterangan |
|---|---|---|
| `mooda.id` | `fnb` | Existing, tidak berubah |
| `laundry.mooda.id` | `laundry` | Baru |
| `retail.mooda.id` | `retail` | Baru |

Middleware `ResolveVertical`:
1. Baca host → tentukan vertical aktif.
2. Simpan di container/`config('app.vertical')`.
3. Setelah login: kalau `tenant.vertical` ≠ vertical host → **redirect** ke subdomain yang benar.

Superadmin (tanpa tenant) boleh akses semua subdomain.

### 3.3 Struktur folder (dalam `stakko-pos`)

```
app/
  Http/Controllers/Backend/
    Kasir/            ← dipakai bersama (inti transaksi, shift, struk)
    Billing/          ← dipakai bersama
    Affiliate/        ← dipakai bersama
    Fnb/              ← khusus F&B (menu, kategori, KDS)
    Laundry/          ← khusus Laundry (layanan, status cucian, antar-jemput)
    Retail/           ← khusus Retail (produk, stok, barcode)
  Verticals/
    VerticalRegistry.php   ← daftar vertical + modul + label
    Fnb/ Laundry/ Retail/  ← service class per vertical

resources/views/backend/
  kasir/ billing/ ...      ← bersama
  fnb/ laundry/ retail/    ← view khas

config/
  plans.php                ← plan per vertical (atau full dari DB)
  verticals.php            ← definisi vertical, modul, label, ikon
```

**Aturan main:** kode bersama **tidak boleh** meng-`if ($vertical === 'laundry')`.
Kalau butuh percabangan, pakai **registry/strategy** (`VerticalRegistry::for($tenant)->orderFlow()`),
supaya menambah vertical ke-4 tidak menyentuh kode inti.

### 3.4 Plan / paket per vertical

Sudah setengah jalan: tabel `plan_settings` & `plan_promos` (harga dasar, diskon %, label promo,
toggle) **sudah pindah ke DB** dan bisa diatur Superadmin di **Setelan Paket**.
Tinggal tambah kolom `vertical`, lalu halaman Setelan Paket diberi **tab per vertical**.

Contoh perbedaan paket:

| | F&B | Laundry | Retail |
|---|---|---|---|
| Modul khas | Kitchen Display, meja | Status cucian, antar-jemput | Stok, barcode, multi-satuan |
| Batas data | pelanggan | pelanggan + pesanan aktif | SKU / produk |
| Nama paket | Basic / Enterprise | Basic / Pro / Bisnis | menyesuaikan |

Referensi harga Laundry: lihat `Mooda-Laundry-Proposal.pdf`.

---

## 4. Dampak ke Superadmin

Semua tetap **satu panel**, ditambah dimensi vertical:

- **Manajemen Tenant** → kolom + filter **Vertical**.
- **Dashboard analitik** → total keseluruhan, plus rincian per vertical.
- **Setelan Paket** → tab F&B / Laundry / Retail.
- **Platform Menu** → tidak berubah (menu platform sama untuk semua vertical).
- **Affiliate, Pencairan, Tripay, Blog, Situs** → tidak berubah (lintas-vertical).

---

## 5. Rencana implementasi bertahap

| Tahap | Isi | Risiko |
|---|---|---|
| **0. Fondasi** | Kolom `tenants.vertical` (default `fnb`), `config/verticals.php`, `VerticalRegistry` | Rendah — belum mengubah perilaku |
| **1. Routing** | Middleware `ResolveVertical`, nginx + DNS `laundry.mooda.id`, redirect salah-subdomain | Rendah |
| **2. Isolasi modul** | Pindahkan controller/view khas F&B ke `Fnb/`, pastikan kode bersama bebas `if` vertical | **Sedang** — refactor kode existing, wajib uji POS F&B |
| **3. Plan per vertical** | Kolom `vertical` di `plan_settings`/`plan_promos` + tab di Setelan Paket | Rendah (skema sudah siap) |
| **4. Bangun Laundry** | Skema + CRUD layanan + alur status + kasir laundry + laporan | Tinggi (fitur baru) |
| **5. Rilis** | Seed paket Laundry, landing `laundry.mooda.id`, uji bayar Tripay end-to-end | Sedang |
| **6. Retail** | Ulangi pola tahap 4–5 (pola sudah terbukti) | Sedang |

**Catatan penting:** tahap 0–3 tidak boleh mengubah perilaku F&B yang sudah produksi.
Setiap tahap diakhiri uji: login → kasir → bayar → laporan → langganan.

---

## 6. Kalau nanti berubah pikiran (kapan pisah repo/DB)

Pisahkan **hanya** jika salah satu terjadi:

- Tim developer per vertical benar-benar terpisah dan saling mengganggu.
- Beban salah satu vertical menuntut skalabilitas/infra sangat berbeda.
- Vertical dijual/dipisah sebagai badan usaha berbeda.
- Ada regulasi yang mewajibkan data terpisah.

Selama belum, **satu repo + satu DB** jauh lebih hemat untuk tim kecil.

---

## 7. Hal yang masih perlu diputuskan

- [ ] Nama subdomain final (`laundry.mooda.id` vs `mooda.id/laundry`).
- [ ] Boleh tidak satu tenant punya >1 vertical (mis. cafe + laundry dalam satu akun)?
- [ ] Branding: logo/warna beda per vertical, atau seragam Mooda?
- [ ] Harga final paket Laundry (draft ada di `Mooda-Laundry-Proposal.pdf`).
- [ ] Urutan rilis: Laundry dulu atau Retail dulu?
