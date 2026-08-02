# Mooda Farm (farm.mooda.id) — rancangan sistem

Bukan POS. Ini **sistem inventori & perdagangan ternak**: beli ayam dari supplier,
simpan sebagai stok berbasis lot, jual ke agen dengan harga pokok FIFO.

## 1. Yang membedakan dari POS F&B

| POS F&B | Farm |
|---|---|
| Kasir, meja, dapur | **Tidak ada** — disembunyikan |
| Shift + modal & kembalian | **Buka/Tutup Gudang** (tanpa uang) |
| Menu | **Item ternak**: ayam potong, ayam petelur, telur |
| Penjualan ke pelanggan walk-in | **Stock Out ke Agen** (sering tempo/piutang) |
| Stok bahan opsional | **Stok adalah inti sistem** |

## 2. Dua satuan sekaligus: EKOR dan KG — jangan pilih salah satu

Ayam dibeli per kg, tetapi dihitung per ekor. Kalau sistem hanya menyimpan satu
satuan, **susut bobot tidak akan pernah terlihat**: 100 ekor / 200 kg masuk,
100 ekor / 195 kg keluar — 5 kg hilang tanpa jejak.

Aturan: **setiap** transaksi menyimpan `qty_ekor` DAN `berat_kg`. Harga boleh
berbasis kg atau ekor (`price_basis`), tapi keduanya tetap dicatat.

## 3. Model data

```
suppliers            id, nama, telp, alamat, catatan
agents               id, nama, telp, alamat, plafon_piutang, harga_terakhir
item_categories      ayam_potong | ayam_petelur | telur
items                id, kategori, nama, satuan_utama (kg/ekor/butir)

stock_in             id, no_nota, tanggal, supplier_id, user_id, total, catatan
stock_in_lines       stock_in_id, item_id, qty_ekor, berat_kg, price_basis,
                     harga_satuan, subtotal
                     -> tiap baris menjadi SATU LOT

stock_lots           id, item_id, stock_in_line_id, tanggal, supplier_id,
                     qty_ekor_awal, berat_kg_awal,
                     qty_ekor_sisa, berat_kg_sisa,     <-- dikurangi FIFO
                     harga_pokok_per_kg, harga_pokok_per_ekor

stock_out            id, no_nota, tanggal, agent_id, user_id, total_jual,
                     total_hpp, laba_kotor, status_bayar, jatuh_tempo
stock_out_lines      stock_out_id, item_id, qty_ekor, berat_kg,
                     harga_jual_satuan, hpp (dari lot terpakai), laba
stock_out_lot_usage  stock_out_line_id, lot_id, qty_ekor, berat_kg, hpp
                     -> jejak lot mana yang terpakai (audit FIFO)

egg_production       tanggal, kandang, jumlah_butir, jumlah_tray, user_id
stock_adjustments    tanggal, item_id, lot_id, qty_ekor, berat_kg,
                     alasan (mati|susut|rusak|koreksi_opname), user_id
agent_payments       agent_id, stock_out_id, tanggal, jumlah, metode
warehouse_sessions   dibuka_oleh, dibuka_pada, ditutup_pada,
                     stok_awal_json, stok_akhir_json, selisih_json, catatan
```

## 4. FIFO — cara kerja

Stock Out mengambil dari lot **tertua yang masih bersisa**. Satu baris penjualan
bisa memakan beberapa lot; tiap potongan dicatat di `stock_out_lot_usage` supaya
HPP-nya bisa ditelusuri, bukan sekadar angka rata-rata.

Di layar Stock Out, kasir/gudang melihat **harga beli lot yang akan terpakai**
sebagai pembanding saat menentukan harga jual → margin terlihat SEBELUM disimpan.

## 5. Telur: produksi, bukan pembelian

Telur tidak dibeli dari supplier, jadi **jangan** dimasukkan lewat Stock In.
Kalau dipaksa, harga belinya 0 dan margin telur akan terlihat 100% — menyesatkan.

Pakai modul **Produksi Telur** harian. HPP telur dihitung dari
`biaya periode (pakan + obat + tenaga) ÷ jumlah butir periode`, atau di-set manual
oleh admin. Ini membuat laba telur jujur.

## 6. Peran & batas akses

| Peran | Boleh |
|---|---|
| **Superadmin** | platform: kelola tenant, pengguna, langganan |
| **admin** | seluruh modul tenant: master data, harga, hapus/koreksi, semua laporan |
| **supervisor** | input & koreksi transaksi, lihat semua laporan + margin, setujui penyesuaian stok. Tidak boleh hapus master data |
| **gudang** | Stock In / Stock Out / produksi telur / buka-tutup gudang / cetak nota. **Harga beli & margin disembunyikan** |

Menyembunyikan harga beli dari petugas gudang disengaja — itu informasi dagang.

## 7. Alur kerja harian

```
1. BUKA GUDANG            petugas gudang; stok awal terekam otomatis
2. BARANG DATANG          Stock In -> supplier, kategori, ekor + kg, harga
                          -> simpan -> NOTA BELI tercetak
3. AYAM PETELUR           Produksi Telur harian (butir/tray, per kandang)
4. AGEN MENGAMBIL         Stock Out -> agen, item, ekor + kg, harga jual
                          (harga pokok FIFO tampil sebagai pembanding)
                          -> simpan -> NOTA JUAL -> status bayar / tempo
5. ADA YANG MATI/SUSUT    Penyesuaian Stok + alasan (disetujui supervisor)
6. TUTUP GUDANG           hitung fisik; selisih vs sistem tercatat
7. LAPORAN                stok, laba kotor, piutang agen, kartu mutasi
```

## 8. Modul yang WAJIB ada tapi belum disebut

1. **Penyesuaian stok (mati / susut / rusak).** Tanpa ini stok sistem tidak akan
   pernah cocok dengan fisik, dan FIFO ikut melenceng.
2. **Piutang agen + pembayaran.** Dagang ayam hampir selalu tempo. Butuh status
   bayar, jatuh tempo, kartu piutang per agen, dan pengingat.
3. **Kartu stok (mutasi).** Riwayat masuk/keluar/penyesuaian per item — alat
   pertama saat stok dianggap "hilang".
4. **Harga jual per agen.** Tiap agen punya harga sendiri; simpan harga terakhir
   agar terisi otomatis dan mengurangi salah ketik.
5. **Opname saat tutup gudang.** Selisih dicatat, bukan diam-diam ditimpa.

Menyusul bila perlu: biaya operasional (pakan/obat/tenaga) untuk HPP telur & laba
bersih, multi-kandang/multi-gudang, dan grafik tren harga beli-jual.

## 9. Prinsip UI/UX

- **Satu layar satu pekerjaan.** Stock In dan Stock Out masing-masing satu form
  penuh, tanpa keranjang berlapis.
- **Angka besar, tombol besar.** Dipakai di gudang, sambil berdiri, sering lewat
  HP/tablet, tangan kotor.
- **Warna konsisten:** hijau = masuk, oranye = keluar, merah = penyesuaian/susut.
- **Margin tampil saat mengetik**, bukan setelah simpan.
- **Nota tercetak otomatis** setelah simpan; tombol cetak ulang di daftar.
- **Dashboard 4 kartu**: stok ayam (ekor/kg), stok telur, pembelian hari ini,
  penjualan & laba hari ini.

## 10. Urutan pengerjaan yang disarankan

| Fase | Isi |
|---|---|
| 1 | Master data: supplier, agen, kategori, item + sembunyikan modul POS |
| 2 | Stock In + lot + nota beli |
| 3 | Stock Out + FIFO + margin + nota jual |
| 4 | Produksi telur, penyesuaian stok, buka/tutup gudang |
| 5 | Piutang agen & pembayaran |
| 6 | Laporan: kartu stok, laba kotor, piutang, rekap harian |
