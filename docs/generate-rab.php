<?php
/**
 * Pembuat berkas RAB (Rencana Anggaran Biaya) Mooda Stok — 2 tab:
 *   1. Langganan di server Mooda (subdomain, aplikasi dipakai bukan dimiliki)
 *   2. Jual putus (aplikasi diserahkan, hosting & maintenance di sisi klien)
 *
 * Aturan penyusunan:
 *   - Sel KUNING = asumsi yang boleh diubah klien. Sel ABU = hasil rumus.
 *   - Semua total memakai rumus Excel, bukan angka mati, supaya begitu satu harga
 *     diubah seluruh kesimpulan ikut menyesuaikan tanpa dihitung ulang manual.
 *   - Tidak ada langganan bulanan: hanya paket 1 tahun dan 2 tahun, dan keduanya
 *     dibuat sebagai TABEL TERPISAH agar yang tidak dipakai bisa dihapus utuh.
 *
 * Harga hosting mengikuti daftar harga Hostinger Indonesia (hostinger.com/id),
 * diambil 4 Agustus 2026. Harga promo hanya berlaku pada kontrak pertama.
 *
 * Jalankan: php docs/generate-rab.php
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

const RP     = '"Rp"#,##0';
const HIJAU  = 'FF15803D';
const BIRU   = 'FF1D4ED8';
const COKLAT = 'FF92400E';
const KUNING = 'FFFFF3CD';
const ABU    = 'FFF3F4F6';
const BIRUMD = 'FFDBEAFE';
const MERAHMD = 'FFFEE2E2';
const HIJAUMD = 'FFDCFCE7';

$wb = new Spreadsheet();
$wb->getProperties()
    ->setCreator('Mooda.ID')
    ->setTitle('RAB Mooda Stok — Aplikasi Inventori Ternak & Perkebunan')
    ->setSubject('Rencana Anggaran Biaya')
    ->setDescription('Dua skema: langganan di server Mooda, atau jual putus.');

function judulBagian($sheet, int $baris, string $teks, string $warna = HIJAU): void
{
    $sheet->setCellValue("A{$baris}", $teks);
    $sheet->mergeCells("A{$baris}:F{$baris}");
    $s = $sheet->getStyle("A{$baris}:F{$baris}");
    $s->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
    $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($warna);
    $s->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension($baris)->setRowHeight(22);
}

/** Baris tabel biasa: uraian, satuan, volume, harga satuan, jumlah (rumus), catatan. */
function barisItem($sheet, int $b, string $uraian, string $satuan, $qty, $harga, string $catatan = ''): void
{
    $sheet->setCellValue("A{$b}", $uraian);
    $sheet->setCellValue("B{$b}", $satuan);
    $sheet->setCellValue("C{$b}", $qty);
    $sheet->setCellValue("D{$b}", $harga);
    $sheet->setCellValue("E{$b}", "=C{$b}*D{$b}");
    $sheet->setCellValue("F{$b}", $catatan);
    $sheet->getStyle("D{$b}:E{$b}")->getNumberFormat()->setFormatCode(RP);
    $sheet->getStyle("C{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("C{$b}:D{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(KUNING);
    $sheet->getStyle("F{$b}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
    $sheet->getStyle("F{$b}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle("A{$b}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
}

/** Baris keterangan tanpa angka — dipakai untuk rincian pekerjaan. */
function barisRincian($sheet, int $b, string $uraian, string $catatan = ''): void
{
    $sheet->setCellValue("A{$b}", '     • ' . $uraian);
    $sheet->setCellValue("F{$b}", $catatan);
    $sheet->mergeCells("B{$b}:E{$b}");
    $sheet->getStyle("A{$b}:F{$b}")->getFont()->setSize(9.5);
    $sheet->getStyle("F{$b}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
    $sheet->getStyle("A{$b}:F{$b}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
}

function kepalaTabel($sheet, int $b, array $kolom = []): void
{
    $bawaan = ['A' => 'Uraian', 'B' => 'Satuan', 'C' => 'Vol', 'D' => 'Harga Satuan', 'E' => 'Jumlah', 'F' => 'Catatan'];
    foreach (array_merge($bawaan, $kolom) as $k => $v) {
        $sheet->setCellValue("{$k}{$b}", $v);
    }
    $s = $sheet->getStyle("A{$b}:F{$b}");
    $s->getFont()->setBold(true)->setSize(10);
    $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE5E7EB');
    $s->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
    $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
}

function barisTotal($sheet, int $b, string $label, string $rumus, string $warna = ABU): void
{
    $sheet->setCellValue("A{$b}", $label);
    $sheet->mergeCells("A{$b}:D{$b}");
    $sheet->setCellValue("E{$b}", $rumus);
    $sheet->getStyle("A{$b}:F{$b}")->getFont()->setBold(true);
    $sheet->getStyle("A{$b}:F{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($warna);
    $sheet->getStyle("E{$b}")->getNumberFormat()->setFormatCode(RP);
    $sheet->getStyle("A{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle("A{$b}:F{$b}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
}

function catatan($sheet, int $b, string $teks, string $warna = BIRUMD, float $tinggi = 0): void
{
    $sheet->setCellValue("A{$b}", $teks);
    $sheet->mergeCells("A{$b}:F{$b}");
    $sheet->getStyle("A{$b}:F{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($warna);
    $sheet->getStyle("A{$b}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    $sheet->getStyle("A{$b}")->getFont()->setSize(9.5);
    if ($tinggi > 0) {
        $sheet->getRowDimension($b)->setRowHeight($tinggi);
    }
}

function aturKolom($sheet): void
{
    $sheet->getColumnDimension('A')->setWidth(50);
    $sheet->getColumnDimension('B')->setWidth(12);
    $sheet->getColumnDimension('C')->setWidth(7);
    $sheet->getColumnDimension('D')->setWidth(16);
    $sheet->getColumnDimension('E')->setWidth(17);
    $sheet->getColumnDimension('F')->setWidth(48);
    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0);
    $sheet->freezePane('A6');
}

/** Kepala halaman: judul, penjelasan singkat, tanggal. */
function kepalaHalaman($sheet, string $judul, string $anak, string $penjelasan, string $warna): void
{
    $sheet->setCellValue('A1', $judul);
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB($warna);

    $sheet->setCellValue('A2', $anak);
    $sheet->mergeCells('A2:F2');
    $sheet->getStyle('A2')->getFont()->setSize(11)->getColor()->setARGB('FF6B7280');

    $sheet->setCellValue('A3', $penjelasan);
    $sheet->mergeCells('A3:F3');
    $sheet->getStyle('A3')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension(3)->setRowHeight(32);

    $sheet->setCellValue('A4', 'Disusun ' . date('d/m/Y')
        . '  ·  Berlaku 30 hari  ·  Belum termasuk PPN  ·  Sel kuning = asumsi yang bisa diubah');
    $sheet->mergeCells('A4:F4');
    $sheet->getStyle('A4')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF6B7280');
}

/**
 * Tabel penambahan fitur/modul — dipakai di kedua tab.
 * Harganya kisaran, karena yang menentukan bukan jenis usahanya melainkan
 * tingkat kesulitan pekerjaannya.
 *
 * @return int baris terakhir yang terpakai
 */
function tabelPenambahanFitur($sheet, int $b, string $warna): int
{
    judulBagian($sheet, $b++, 'PENAMBAHAN FITUR / MODUL BARU (di luar lingkup awal)', $warna);
    kepalaTabel($sheet, $b++, ['B' => 'Perkiraan Waktu', 'C' => '', 'D' => 'Kisaran Bawah', 'E' => 'Kisaran Atas']);

    $tingkat = [
        ['Ringan', '1–3 hari',
            750000, 2000000,
            'Laporan baru, tambah kolom/filter, ubah tampilan atau isi nota, penyesuaian hak akses.'],
        ['Sedang', '1–2 minggu',
            3000000, 7500000,
            'Modul baru yang berdiri sendiri: data induk + transaksi + cetak + laporannya. Mis. pencatatan pakan, absensi kandang, kas kecil.'],
        ['Berat', '3–6 minggu',
            9000000, 20000000,
            'Kategori usaha baru dengan alur berbeda, integrasi ke sistem pihak lain (akuntansi, marketplace, payment gateway), atau perubahan cara stok dihitung.'],
    ];

    foreach ($tingkat as [$nama, $waktu, $bawah, $atas, $ket]) {
        $sheet->setCellValue("A{$b}", $nama);
        $sheet->setCellValue("B{$b}", $waktu);
        $sheet->setCellValue("D{$b}", $bawah);
        $sheet->setCellValue("E{$b}", $atas);
        $sheet->setCellValue("F{$b}", $ket);
        $sheet->mergeCells("B{$b}:C{$b}");
        $sheet->getStyle("A{$b}")->getFont()->setBold(true);
        $sheet->getStyle("D{$b}:E{$b}")->getNumberFormat()->setFormatCode(RP);
        $sheet->getStyle("D{$b}:E{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(KUNING);
        $sheet->getStyle("B{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$b}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
        $sheet->getStyle("A{$b}:F{$b}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($b)->setRowHeight(34);
        $b++;
    }

    barisItem($sheet, $b++, 'Perubahan kecil di luar tiga tingkat di atas', 'jam', 1, 250000,
        'Untuk permintaan sekecil ganti kata pada nota atau tambah satu kolom. Minimal 1 jam.');

    catatan($sheet, $b++,
        'Cara kerjanya: setiap permintaan ditaksir dulu (tingkat kesulitan + perkiraan waktu), disetujui klien, '
        . 'baru dikerjakan. Tidak ada pekerjaan tambahan yang ditagih tanpa persetujuan lebih dulu.', BIRUMD, 28);

    return $b;
}

/* =======================================================================
   TAB 1 — LANGGANAN DI SERVER MOODA
   ======================================================================= */
$s1 = $wb->getActiveSheet();
$s1->setTitle('1. Langganan (Server Mooda)');
aturKolom($s1);
kepalaHalaman(
    $s1,
    'RENCANA ANGGARAN BIAYA — SKEMA LANGGANAN',
    'Mooda Stok — Aplikasi Inventori & Perdagangan Ternak / Perkebunan',
    'Aplikasi berjalan di server Mooda. Klien MEMAKAI aplikasi, bukan memilikinya. Alamat memakai subdomain Mooda '
    . '(mis. ayamjaya.mooda.id); domain sendiri tidak termasuk skema ini. Hosting, pencadangan, pembaruan, dan '
    . 'dukungan sudah menyatu di dalam biaya langganan — klien tidak mengurus server sama sekali.',
    HIJAU
);

$b = 6;

/* ---- A. Sekali bayar ---- */
judulBagian($s1, $b++, 'A. BIAYA SEKALI BAYAR — IMPLEMENTASI');
kepalaTabel($s1, $b++);
$awalA = $b;
barisItem($s1, $b++, 'Penyiapan tenant, subdomain & sertifikat HTTPS', 'paket', 1, 750000,
    'Pembuatan ruang kerja klien di server Mooda, basis data terpisah, SSL otomatis.');
barisItem($s1, $b++, 'Pengaturan data induk awal (supplier, agen, item, satuan)', 'paket', 1, 1000000,
    'Diinput bersama klien agar aplikasi langsung sesuai kebiasaan usaha mereka.');
$akhirA = $b - 1;
barisTotal($s1, $b, 'SUBTOTAL A — sekali bayar', "=SUM(E{$awalA}:E{$akhirA})");
$subA = $b;
$b += 2;

/* ---- B. Paket 1 tahun (tabel terpisah) ---- */
judulBagian($s1, $b++, 'B. PAKET LANGGANAN 1 TAHUN', BIRU);
kepalaTabel($s1, $b++);
$awalB = $b;
barisItem($s1, $b++, 'Pemakaian aplikasi — seluruh modul', 'bulan', 12, 350000,
    'Barang masuk/keluar FIFO, deposit supplier, realisasi, piutang agen, produksi telur, penyesuaian stok, buka/tutup gudang, laporan & HPP per supplier.');
barisItem($s1, $b++, 'Hosting, pencadangan harian & pemantauan', 'bulan', 12, 100000,
    'Server, basis data terpisah, cadangan otomatis, pemantauan layanan. Tidak ada tagihan hosting terpisah.');
barisItem($s1, $b++, 'Pembaruan fitur, perbaikan & dukungan WhatsApp', 'bulan', 12, 0,
    'TERMASUK, tanpa biaya tambahan selama berlangganan. Jam kerja Sen–Sab 08.00–17.00.');
$akhirB = $b - 1;
$brutoB = $b;
barisTotal($s1, $b++, 'Jumlah sebelum diskon', "=SUM(E{$awalB}:E{$akhirB})");

$s1->setCellValue("A{$b}", 'Diskon bayar di muka 1 tahun');
$s1->setCellValue("B{$b}", 'persen');
$s1->setCellValue("C{$b}", 0.10);
$s1->setCellValue("E{$b}", "=-E{$brutoB}*C{$b}");
$s1->getStyle("C{$b}")->getNumberFormat()->setFormatCode('0%');
$s1->getStyle("C{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$s1->getStyle("C{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(KUNING);
$s1->getStyle("E{$b}")->getNumberFormat()->setFormatCode(RP);
$s1->setCellValue("F{$b}", 'Ganti angka diskon di sel kuning bila perlu.');
$s1->getStyle("F{$b}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
$diskonB = $b++;

barisTotal($s1, $b, 'TOTAL LANGGANAN 1 TAHUN', "=E{$brutoB}+E{$diskonB}", HIJAUMD);
$totalB = $b++;
$b++;
catatan($s1, $b++, 'Pilih SALAH SATU: paket 1 tahun (tabel B) atau paket 2 tahun (tabel C). '
    . 'Tabel yang tidak dipakai boleh dihapus utuh — kesimpulan di bagian E memuat keduanya secara terpisah.', BIRUMD, 28);
$b++;

/* ---- C. Paket 2 tahun (tabel terpisah) ---- */
judulBagian($s1, $b++, 'C. PAKET LANGGANAN 2 TAHUN', BIRU);
kepalaTabel($s1, $b++);
$awalC = $b;
barisItem($s1, $b++, 'Pemakaian aplikasi — seluruh modul', 'bulan', 24, 350000,
    'Lingkupnya sama dengan paket 1 tahun.');
barisItem($s1, $b++, 'Hosting, pencadangan harian & pemantauan', 'bulan', 24, 100000,
    'Harga terkunci 2 tahun — tidak ikut naik walau biaya server naik.');
barisItem($s1, $b++, 'Pembaruan fitur, perbaikan & dukungan WhatsApp', 'bulan', 24, 0,
    'TERMASUK, tanpa biaya tambahan selama berlangganan.');
$akhirC = $b - 1;
$brutoC = $b;
barisTotal($s1, $b++, 'Jumlah sebelum diskon', "=SUM(E{$awalC}:E{$akhirC})");

$s1->setCellValue("A{$b}", 'Diskon bayar di muka 2 tahun');
$s1->setCellValue("B{$b}", 'persen');
$s1->setCellValue("C{$b}", 0.20);
$s1->setCellValue("E{$b}", "=-E{$brutoC}*C{$b}");
$s1->getStyle("C{$b}")->getNumberFormat()->setFormatCode('0%');
$s1->getStyle("C{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$s1->getStyle("C{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(KUNING);
$s1->getStyle("E{$b}")->getNumberFormat()->setFormatCode(RP);
$diskonC = $b++;

barisTotal($s1, $b, 'TOTAL LANGGANAN 2 TAHUN', "=E{$brutoC}+E{$diskonC}", HIJAUMD);
$totalC = $b++;

$s1->setCellValue("A{$b}", 'Setara per tahun');
$s1->mergeCells("A{$b}:D{$b}");
$s1->setCellValue("E{$b}", "=E{$totalC}/2");
$s1->getStyle("E{$b}")->getNumberFormat()->setFormatCode(RP);
$s1->getStyle("A{$b}:F{$b}")->getFont()->setItalic(true)->setSize(9.5);
$s1->getStyle("A{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$setaraC = $b;
$b += 2;

/* ---- D. Penambahan fitur ---- */
$b = tabelPenambahanFitur($s1, $b, COKLAT);
$b++;

/* ---- E. Kesimpulan ---- */
judulBagian($s1, $b++, 'E. KESIMPULAN BIAYA — SKEMA LANGGANAN', HIJAU);
kepalaTabel($s1, $b++, ['B' => 'Masa', 'C' => '', 'D' => 'Dibayar di Muka', 'E' => 'Jumlah']);

$ringkas = [
    ['Pilihan 1 — Langganan 1 tahun', '12 bulan', "=E{$subA}", "=E{$subA}+E{$totalB}",
        'Biaya implementasi + langganan 1 tahun, dibayar di awal.'],
    ['Pilihan 2 — Langganan 2 tahun', '24 bulan', "=E{$subA}", "=E{$subA}+E{$totalC}",
        'Lebih murah per tahunnya dan harga terkunci 2 tahun.'],
];
foreach ($ringkas as [$nama, $masa, $muka, $rumus, $ket]) {
    $s1->setCellValue("A{$b}", $nama);
    $s1->setCellValue("B{$b}", $masa);
    $s1->setCellValue("D{$b}", $muka);
    $s1->setCellValue("E{$b}", $rumus);
    $s1->setCellValue("F{$b}", $ket);
    $s1->mergeCells("B{$b}:C{$b}");
    $s1->getStyle("A{$b}")->getFont()->setBold(true);
    $s1->getStyle("D{$b}:E{$b}")->getNumberFormat()->setFormatCode(RP);
    $s1->getStyle("E{$b}")->getFont()->setBold(true)->setSize(11);
    $s1->getStyle("B{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $s1->getStyle("F{$b}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
    $s1->getStyle("A{$b}:F{$b}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
    $s1->getStyle("A{$b}:F{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(HIJAUMD);
    $s1->getStyle("A{$b}:F{$b}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
    $s1->getRowDimension($b)->setRowHeight(30);
    $b++;
}

$s1->setCellValue("A{$b}", 'Biaya rutin tahun berikutnya (setelah masa langganan habis)');
$s1->mergeCells("A{$b}:D{$b}");
$s1->setCellValue("E{$b}", "=E{$totalB}");
$s1->getStyle("E{$b}")->getNumberFormat()->setFormatCode(RP);
$s1->getStyle("A{$b}:F{$b}")->getFont()->setBold(true);
$s1->getStyle("A{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$s1->setCellValue("F{$b}", 'Diperbarui per tahun; tidak ada lagi biaya implementasi.');
$s1->getStyle("F{$b}")->getFont()->setBold(false)->setSize(9)->getColor()->setARGB('FF6B7280');
$b += 2;

catatan($s1, $b, "Yang perlu disiapkan klien: perangkat (HP/tablet/laptop) dan koneksi internet. "
    . "Printer nota thermal opsional. Domain sendiri, VPS sendiri, dan payment gateway tidak termasuk skema ini — "
    . "bila memang diperlukan, lihat Tab 2 atau tabel penambahan fitur.", BIRUMD, 30);

/* =======================================================================
   TAB 2 — JUAL PUTUS
   ======================================================================= */
$s2 = $wb->createSheet();
$s2->setTitle('2. Jual Putus');
aturKolom($s2);
kepalaHalaman(
    $s2,
    'RENCANA ANGGARAN BIAYA — SKEMA JUAL PUTUS',
    'Mooda Stok — aplikasi diserahkan ke klien, dipasang di hosting milik klien',
    'Aplikasi dipasang di VPS milik klien dan memakai domain klien. Setelah diserahkan, hosting dan pemeliharaan '
    . 'menjadi tanggung jawab klien — kami hanya membantu dari sisi konfigurasi (tidak ada server fisik yang kami '
    . 'kelola). Harga di bawah disusun untuk usaha yang baru berjalan: seluruh angka sudah ditekan ke tingkat yang '
    . 'masuk akal untuk skala kecil.',
    BIRU
);

$b = 6;

/* ---- A. Harga aplikasi ---- */
judulBagian($s2, $b++, 'A. HARGA APLIKASI — PILIH SALAH SATU', BIRU);
kepalaTabel($s2, $b++);
$awalA2 = $b;
$rowOpsi1 = $b;
barisItem($s2, $b++, 'Opsi 1 — Lisensi pakai (tanpa kode sumber)', 'paket', 1, 16500000,
    'Aplikasi dipasang & berjalan penuh di VPS klien, satu badan usaha, tanpa batas jumlah pengguna. Kode sumber tetap milik Mooda.');
$rowOpsi2 = $b;
barisItem($s2, $b++, 'Opsi 2 — Lisensi + kode sumber', 'paket', 0, 29500000,
    'Klien menerima kode sumber dan boleh memodifikasi/memindahkan sendiri. Dipilih bila klien punya (atau akan punya) programer sendiri.');
$akhirA2 = $b - 1;
catatan($s2, $b++, 'Volume disetel 1 pada opsi yang dipilih dan 0 pada yang tidak — bawaannya Opsi 1. '
    . 'Kesimpulan di bagian F tetap menampilkan total kedua opsi, jadi keduanya bisa dibandingkan tanpa mengubah apa pun.', KUNING, 28);
barisTotal($s2, $b, 'SUBTOTAL A — harga aplikasi (opsi terpilih)', "=SUM(E{$awalA2}:E{$akhirA2})");
$subA2 = $b;
$b += 2;

/* ---- B. Pemasangan ---- */
judulBagian($s2, $b++, 'B. PEMASANGAN AWAL DI HOSTING KLIEN (sekali bayar)', BIRU);
kepalaTabel($s2, $b++);
$awalB2 = $b;
barisItem($s2, $b++, 'Penyiapan VPS & pemasangan aplikasi', 'paket', 1, 1500000,
    'PostgreSQL, Redis, PHP-FPM, Octane, antrean/worker, cron, Nginx, sertifikat SSL, jadwal cadangan otomatis, pengarahan domain.');
barisItem($s2, $b++, 'Pengaturan data induk awal (supplier, agen, item, satuan)', 'paket', 1, 1000000,
    'Sama seperti skema langganan.');
$akhirB2 = $b - 1;
barisTotal($s2, $b, 'SUBTOTAL B — pemasangan', "=SUM(E{$awalB2}:E{$akhirB2})");
$subB2 = $b;
$b += 2;

/* ---- C. Hosting (harga Hostinger) ---- */
judulBagian($s2, $b++, 'C. HOSTING — HARGA HOSTINGER (per 4 Agustus 2026)', BIRU);
kepalaTabel($s2, $b++, ['B' => 'Spesifikasi', 'C' => '', 'D' => 'Promo/Bulan', 'E' => 'Perpanjangan/Bulan']);

$hosting = [
    ['Web Hosting Premium (shared)', '3 situs · 20 GB', 24900, 84900, MERAHMD,
        'TIDAK BISA dipakai aplikasi ini: shared hosting hanya menyediakan MySQL, tanpa PostgreSQL, tanpa Redis, dan tanpa izin menjalankan proses latar. Hanya cocok untuk website profil usaha.'],
    ['VPS KVM 1', '1 vCPU · 4 GB · 50 GB NVMe', 116900, 193900, ABU,
        'Batas minimum. Cukup untuk 1 gudang dengan 2–3 pengguna bersamaan.'],
    ['VPS KVM 2 — DISARANKAN', '2 vCPU · 8 GB · 100 GB NVMe', 151900, 232900, HIJAUMD,
        'Pilihan yang dipakai pada perhitungan di bawah. Lega untuk PostgreSQL + Redis + Octane + laporan, dan masih sisa untuk pertumbuhan data beberapa tahun.'],
];
foreach ($hosting as [$nama, $spek, $promo, $lanjut, $warna, $ket]) {
    $s2->setCellValue("A{$b}", $nama);
    $s2->setCellValue("B{$b}", $spek);
    $s2->setCellValue("D{$b}", $promo);
    $s2->setCellValue("E{$b}", $lanjut);
    $s2->setCellValue("F{$b}", $ket);
    $s2->mergeCells("B{$b}:C{$b}");
    $s2->getStyle("A{$b}")->getFont()->setBold(true);
    $s2->getStyle("D{$b}:E{$b}")->getNumberFormat()->setFormatCode(RP);
    $s2->getStyle("B{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $s2->getStyle("F{$b}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
    $s2->getStyle("A{$b}:F{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($warna);
    $s2->getStyle("A{$b}:F{$b}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
    $s2->getRowDimension($b)->setRowHeight(40);
    $b++;
}
$barisKvm2 = $b - 1;

$s2->setCellValue("A{$b}", 'Biaya hosting per tahun (VPS KVM 2, harga perpanjangan × 12)');
$s2->mergeCells("A{$b}:D{$b}");
$s2->setCellValue("E{$b}", "=E{$barisKvm2}*12");
$s2->getStyle("E{$b}")->getNumberFormat()->setFormatCode(RP);
$s2->getStyle("A{$b}:F{$b}")->getFont()->setBold(true);
$s2->getStyle("A{$b}:F{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(ABU);
$s2->getStyle("A{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$s2->setCellValue("F{$b}", 'Dipakai harga perpanjangan, bukan promo — supaya anggaran tahun kedua dan seterusnya tidak kaget.');
$s2->getStyle("F{$b}")->getFont()->setBold(false)->setSize(9)->getColor()->setARGB('FF6B7280');
$hostingTahun = $b++;

catatan($s2, $b++, 'Tagihan VPS dan domain dibayar klien langsung ke Hostinger atas nama klien sendiri, '
    . 'bukan lewat kami. Harga promo hanya berlaku pada kontrak pertama (24 bulan bayar di muka); '
    . 'setelah itu memakai harga perpanjangan. Domain .id sekitar Rp150.000–250.000/tahun, terpisah.', BIRUMD, 30);
$b++;

/* ---- D. Maintenance ---- */
judulBagian($s2, $b++, 'D. MAINTENANCE — 2 BULAN PERTAMA GRATIS, LALU 2× SETAHUN', COKLAT);
catatan($s2, $b++, 'Kami hanya menangani sisi konfigurasi hosting — tidak ada server fisik yang dikelola, '
    . 'jadi biayanya ditekan seminimal mungkin. Selama 2 bulan pertama setelah aplikasi diserahkan, '
    . 'seluruh perbaikan dan penyetelan GRATIS. Sesudah itu maintenance dijadwalkan 2 kali setahun.', HIJAUMD, 30);

kepalaTabel($s2, $b++);
$awalD2 = $b;
barisItem($s2, $b++, 'Maintenance berjadwal', 'sesi', 2, 750000,
    '2 sesi per tahun (mis. bulan ke-6 dan ke-12). Isi setiap sesi dirinci di bawah.');
$akhirD2 = $b - 1;

barisRincian($s2, $b++, 'Pencadangan basis data + berkas unggahan, lalu diuji pulihkan',
    'Cadangan yang belum pernah diuji pulih sama saja dengan tidak ada cadangan.');
barisRincian($s2, $b++, 'Konfigurasi hosting: PHP-FPM, Nginx, antrean/worker, cron, sertifikat SSL, firewall',
    'Termasuk memastikan layanan otomatis hidup kembali setelah VPS restart.');
barisRincian($s2, $b++, 'Pembaruan modul aplikasi, framework Laravel, dan library (Composer & npm)',
    'Naik ke versi perbaikan/keamanan terbaru, lalu diuji agar fungsi lama tidak rusak.');
barisRincian($s2, $b++, 'Pemeriksaan kesehatan: log error, ruang disk, kecepatan halaman, ukuran basis data',
    'Masalah ditemukan sebelum menghambat pekerjaan.');
barisRincian($s2, $b++, 'Laporan singkat hasil pekerjaan',
    'Apa yang diperbarui, apa yang perlu diwaspadai berikutnya.');

barisTotal($s2, $b, 'BIAYA MAINTENANCE PER TAHUN', "=SUM(E{$awalD2}:E{$akhirD2})");
$maintTahun = $b++;

barisItem($s2, $b++, 'Panggilan di luar jadwal (aplikasi bermasalah)', 'jam', 1, 350000,
    'Hanya bila dibutuhkan, minimal 1 jam. Gratis bila penyebabnya dari sisi kami.');
$b++;

/* ---- E. Penambahan fitur ---- */
$b = tabelPenambahanFitur($s2, $b, COKLAT);
$b++;

/* ---- F. Kesimpulan ---- */
judulBagian($s2, $b++, 'F. KESIMPULAN BIAYA — SKEMA JUAL PUTUS', BIRU);
kepalaTabel($s2, $b++, ['B' => 'Kapan Dibayar', 'C' => '', 'D' => '', 'E' => 'Jumlah']);

// Biaya pendukung dulu (sama untuk kedua opsi), baru totalnya per opsi. Dengan
// begini kedua opsi selalu terlihat berdampingan tanpa perlu mengubah volume.
$rinci = [
    ['Pemasangan awal di hosting klien', 'di awal', "=E{$subB2}",
        'Penyiapan VPS + pemasangan aplikasi + data induk awal.'],
    ['Hosting VPS KVM 2 — tahun pertama', 'ke Hostinger, atas nama klien', "=E{$hostingTahun}",
        'Bukan dibayar ke kami. Bisa lebih murah bila mengambil promo kontrak 24 bulan.'],
    ['Maintenance tahun pertama — 1 sesi', 'setelah 2 bulan gratis', "=E{$maintTahun}/2",
        '2 bulan pertama gratis, jadi hanya satu jadwal maintenance yang jatuh di tahun pertama.'],
];
$awalF = $b;
foreach ($rinci as [$nama, $kapan, $rumus, $ket]) {
    $s2->setCellValue("A{$b}", $nama);
    $s2->setCellValue("B{$b}", $kapan);
    $s2->setCellValue("E{$b}", $rumus);
    $s2->setCellValue("F{$b}", $ket);
    $s2->mergeCells("B{$b}:D{$b}");
    $s2->getStyle("E{$b}")->getNumberFormat()->setFormatCode(RP);
    $s2->getStyle("B{$b}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
    $s2->getStyle("F{$b}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
    $s2->getStyle("A{$b}:F{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(ABU);
    $s2->getStyle("A{$b}:F{$b}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
    $s2->getRowDimension($b)->setRowHeight(26);
    $b++;
}
$akhirF = $b - 1;
barisTotal($s2, $b, 'Subtotal biaya pendukung tahun pertama', "=SUM(E{$awalF}:E{$akhirF})");
$subPendukung = $b++;

// Total per opsi memakai HARGA satuan (kolom D), bukan jumlah (kolom E), supaya
// angkanya tetap benar berapa pun volume yang sedang disetel di tabel A.
foreach ([
    ['TOTAL TAHUN PERTAMA — Opsi 1 (lisensi pakai)', $rowOpsi1,
        'Aplikasi terpasang & jalan penuh, kode sumber tetap milik Mooda.'],
    ['TOTAL TAHUN PERTAMA — Opsi 2 (+ kode sumber)', $rowOpsi2,
        'Termasuk kode sumber; klien bebas memodifikasi sendiri.'],
] as $i => [$label, $rowOpsi, $ket]) {
    $s2->setCellValue("A{$b}", $label);
    $s2->mergeCells("A{$b}:D{$b}");
    $s2->setCellValue("E{$b}", "=D{$rowOpsi}+E{$subPendukung}");
    $s2->setCellValue("F{$b}", $ket);
    $s2->getStyle("A{$b}:F{$b}")->getFont()->setBold(true);
    $s2->getStyle("E{$b}")->getNumberFormat()->setFormatCode(RP);
    $s2->getStyle("E{$b}")->getFont()->setBold(true)->setSize(12);
    $s2->getStyle("A{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $s2->getStyle("F{$b}")->getFont()->setBold(false)->setSize(9)->getColor()->setARGB('FF6B7280');
    $s2->getStyle("A{$b}:F{$b}")->getFill()->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB($i === 0 ? HIJAUMD : BIRUMD);
    $s2->getStyle("A{$b}:F{$b}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
    $s2->getStyle("F{$b}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
    $b++;
}

$s2->setCellValue("A{$b}", 'Biaya rutin tahun berikutnya — hosting + maintenance 2 sesi');
$s2->mergeCells("A{$b}:D{$b}");
$s2->setCellValue("E{$b}", "=E{$hostingTahun}+E{$maintTahun}");
$s2->getStyle("E{$b}")->getNumberFormat()->setFormatCode(RP);
$s2->getStyle("A{$b}:F{$b}")->getFont()->setBold(true);
$s2->getStyle("A{$b}:F{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(ABU);
$s2->getStyle("A{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$s2->setCellValue("F{$b}", 'Tidak ada lagi harga aplikasi maupun pemasangan.');
$s2->getStyle("F{$b}")->getFont()->setBold(false)->setSize(9)->getColor()->setARGB('FF6B7280');
$b += 2;

catatan($s2, $b, "Yang menjadi tanggung jawab klien setelah penyerahan: tagihan VPS & domain, kata sandi akses, "
    . "dan keputusan kapan pembaruan dijalankan. Maintenance boleh tidak diambil — aplikasi tetap jalan — "
    . "hanya saja pembaruan keamanan dan cadangan tidak lagi ada yang memastikan.", BIRUMD, 30);

/* ---- Simpan ---- */
$wb->setActiveSheetIndex(0);
$berkas = __DIR__ . '/RAB-Mooda-Stok.xlsx';
(new Xlsx($wb))->save($berkas);

echo "Tersimpan: {$berkas}\n";
echo 'Ukuran: ' . number_format(filesize($berkas) / 1024, 1) . " KB\n";
