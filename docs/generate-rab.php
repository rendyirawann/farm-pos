<?php
/**
 * Pembuat berkas RAB (Rencana Anggaran Biaya) Mooda Stok — 2 tab:
 *   1. RAB Langganan (numpang server Mooda, subdomain, aplikasi tidak diserahkan)
 *   2. RAB Jual Putus (lisensi / sumber kode, hosting & maintenance sendiri)
 *
 * Angka pada berkas ini adalah USULAN dan ditandai jelas agar mudah disesuaikan.
 * Sel bertanda kuning = asumsi yang boleh diubah; sel abu = hasil rumus.
 *
 * Jalankan: php docs/generate-rab.php
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$RP   = '"Rp"#,##0';
$PCT  = '0"%"';
$HIJAU = 'FF15803D';
$KUNING = 'FFFFF3CD';
$ABU   = 'FFF3F4F6';
$MERAH = 'FFFEE2E2';
$BIRU  = 'FFDBEAFE';

$wb = new Spreadsheet();
$wb->getProperties()
    ->setCreator('Mooda.ID')
    ->setTitle('RAB Mooda Stok — Aplikasi Inventori Ternak & Perkebunan')
    ->setSubject('Rencana Anggaran Biaya')
    ->setDescription('Dua skema: langganan di server Mooda, atau jual putus.');

/** Tulis judul bagian. */
function judulBagian($sheet, int $baris, string $teks, string $warna = 'FF15803D'): void
{
    $sheet->setCellValue("A{$baris}", $teks);
    $sheet->mergeCells("A{$baris}:F{$baris}");
    $s = $sheet->getStyle("A{$baris}:F{$baris}");
    $s->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
    $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($warna);
    $s->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension($baris)->setRowHeight(22);
}

/** Tulis baris tabel: uraian, satuan, qty, harga, jumlah(rumus), catatan. */
function barisItem($sheet, int $b, string $uraian, string $satuan, $qty, $harga, string $catatan = '', string $rp = '"Rp"#,##0'): void
{
    $sheet->setCellValue("A{$b}", $uraian);
    $sheet->setCellValue("B{$b}", $satuan);
    $sheet->setCellValue("C{$b}", $qty);
    $sheet->setCellValue("D{$b}", $harga);
    $sheet->setCellValue("E{$b}", "=C{$b}*D{$b}");
    $sheet->setCellValue("F{$b}", $catatan);
    $sheet->getStyle("D{$b}:E{$b}")->getNumberFormat()->setFormatCode($rp);
    $sheet->getStyle("C{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    // Sel asumsi (qty & harga) diberi latar kuning agar jelas boleh diubah.
    $sheet->getStyle("C{$b}:D{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF3CD');
    $sheet->getStyle("F{$b}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
    $sheet->getStyle("F{$b}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
}

function kepalaTabel($sheet, int $b): void
{
    foreach (['A' => 'Uraian', 'B' => 'Satuan', 'C' => 'Vol', 'D' => 'Harga Satuan', 'E' => 'Jumlah', 'F' => 'Catatan'] as $k => $v) {
        $sheet->setCellValue("{$k}{$b}", $v);
    }
    $s = $sheet->getStyle("A{$b}:F{$b}");
    $s->getFont()->setBold(true)->setSize(10);
    $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE5E7EB');
    $s->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
    $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

function barisTotal($sheet, int $b, string $label, string $rumus, string $warna = 'FFF3F4F6'): void
{
    $sheet->setCellValue("A{$b}", $label);
    $sheet->mergeCells("A{$b}:D{$b}");
    $sheet->setCellValue("E{$b}", $rumus);
    $sheet->getStyle("A{$b}:F{$b}")->getFont()->setBold(true);
    $sheet->getStyle("A{$b}:F{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($warna);
    $sheet->getStyle("E{$b}")->getNumberFormat()->setFormatCode('"Rp"#,##0');
    $sheet->getStyle("A{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle("A{$b}:F{$b}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
}

function catatan($sheet, int $b, string $teks, string $warna = 'FFDBEAFE'): void
{
    $sheet->setCellValue("A{$b}", $teks);
    $sheet->mergeCells("A{$b}:F{$b}");
    $sheet->getStyle("A{$b}:F{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($warna);
    $sheet->getStyle("A{$b}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    $sheet->getStyle("A{$b}")->getFont()->setSize(9.5);
}

function aturKolom($sheet): void
{
    $sheet->getColumnDimension('A')->setWidth(52);
    $sheet->getColumnDimension('B')->setWidth(12);
    $sheet->getColumnDimension('C')->setWidth(7);
    $sheet->getColumnDimension('D')->setWidth(16);
    $sheet->getColumnDimension('E')->setWidth(17);
    $sheet->getColumnDimension('F')->setWidth(46);
    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0);
    $sheet->freezePane('A6');
}

/* =======================================================================
   TAB 1 — LANGGANAN DI SERVER MOODA
   ======================================================================= */
$s1 = $wb->getActiveSheet();
$s1->setTitle('1. Langganan (Server Mooda)');
aturKolom($s1);

$s1->setCellValue('A1', 'RENCANA ANGGARAN BIAYA — SKEMA LANGGANAN');
$s1->mergeCells('A1:F1');
$s1->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB($HIJAU);

$s1->setCellValue('A2', 'Mooda Stok — Aplikasi Inventori & Perdagangan Ternak / Perkebunan');
$s1->mergeCells('A2:F2');
$s1->getStyle('A2')->getFont()->setSize(11)->getColor()->setARGB('FF6B7280');

$s1->setCellValue('A3', 'Aplikasi berjalan di server Mooda. Klien memakai aplikasi (bukan memiliki). Alamat memakai subdomain Mooda, mis. ayam.mooda.id — domain sendiri tidak termasuk skema ini.');
$s1->mergeCells('A3:F3');
$s1->getStyle('A3')->getAlignment()->setWrapText(true);
$s1->getRowDimension(3)->setRowHeight(30);

$s1->setCellValue('A4', 'Tanggal disusun: ' . date('d/m/Y') . '  ·  Berlaku 30 hari  ·  Semua angka belum termasuk PPN');
$s1->mergeCells('A4:F4');
$s1->getStyle('A4')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF6B7280');

$b = 6;
judulBagian($s1, $b++, 'A. BIAYA SEKALI BAYAR — IMPLEMENTASI');
kepalaTabel($s1, $b++);
$awalA = $b;
barisItem($s1, $b++, 'Penyiapan tenant, subdomain & sertifikat HTTPS', 'paket', 1, 750000, 'Pembuatan ruang kerja klien di server Mooda + SSL otomatis.');
barisItem($s1, $b++, 'Pengaturan data induk awal (supplier, agen, item, satuan)', 'paket', 1, 1000000, 'Diinput bersama klien agar langsung sesuai kebiasaan usaha mereka.');
barisItem($s1, $b++, 'Pelatihan pengguna — 2 sesi', 'sesi', 2, 625000, 'Sesi 1: owner & admin (harga, laporan, deposit). Sesi 2: supervisor & gudang (barang masuk/keluar, realisasi).');
barisItem($s1, $b++, 'Pendampingan 2 minggu pertama', 'paket', 1, 1000000, 'Jam kerja, lewat WhatsApp. Masa paling rawan salah input.');
$akhirA = $b - 1;
barisTotal($s1, $b, 'SUBTOTAL A (sekali bayar)', "=SUM(E{$awalA}:E{$akhirA})");
$rowSubA = $b;
$b += 2;

judulBagian($s1, $b++, 'B. LANGGANAN BULANAN — PAKET PETERNAKAN');
kepalaTabel($s1, $b++);
$awalB = $b;
barisItem($s1, $b++, 'Pemakaian aplikasi (seluruh modul)', 'bulan', 1, 350000, 'Barang masuk/keluar FIFO, deposit supplier, realisasi, piutang agen, produksi telur, penyesuaian stok, gudang, laporan.');
barisItem($s1, $b++, 'Hosting, pencadangan harian & pemantauan', 'bulan', 1, 100000, 'Server, basis data terpisah, cadangan otomatis, pemantauan layanan.');
barisItem($s1, $b++, 'Pembaruan fitur & perbaikan', 'bulan', 1, 0, 'TERMASUK — tanpa biaya tambahan selama berlangganan.');
barisItem($s1, $b++, 'Dukungan WhatsApp jam kerja', 'bulan', 1, 0, 'TERMASUK — Sen–Sab 08.00–17.00.');
$akhirB = $b - 1;
barisTotal($s1, $b, 'LANGGANAN PER BULAN', "=SUM(E{$awalB}:E{$akhirB})");
$rowBulan = $b;
$b += 2;

judulBagian($s1, $b++, 'C. PILIHAN MASA LANGGANAN');
kepalaTabel($s1, $b++);
$hdr = $b - 1;
$s1->setCellValue("B{$hdr}", 'Bulan');
$s1->setCellValue("C{$hdr}", 'Diskon');
$s1->setCellValue("D{$hdr}", 'Efektif/Bulan');
$s1->setCellValue("E{$hdr}", 'Total Dibayar');

$opsi = [
    ['Bulanan (tanpa kontrak)', 1, 0, 'Paling lentur. Bisa berhenti kapan saja dengan pemberitahuan 30 hari.'],
    ['1 tahun dibayar di muka', 12, 10, 'Diskon 10%. Cocok untuk klien yang baru mencoba tapi sudah yakin.'],
    ['2 tahun dibayar di muka (paket Customize)', 24, 20, 'Diskon 20%. Skema baku Mooda untuk paket Customize — harga terkunci 2 tahun.'],
];
$awalC = $b;
foreach ($opsi as [$nama, $bulan, $disk, $ket]) {
    $s1->setCellValue("A{$b}", $nama);
    $s1->setCellValue("B{$b}", $bulan);
    $s1->setCellValue("C{$b}", $disk);
    $s1->setCellValue("D{$b}", "=ROUND(\$E\${$rowBulan}*(1-C{$b}/100),0)");
    $s1->setCellValue("E{$b}", "=D{$b}*B{$b}");
    $s1->setCellValue("F{$b}", $ket);
    $s1->getStyle("B{$b}:C{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $s1->getStyle("C{$b}")->getNumberFormat()->setFormatCode($PCT);
    $s1->getStyle("D{$b}:E{$b}")->getNumberFormat()->setFormatCode($RP);
    $s1->getStyle("B{$b}:C{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($KUNING);
    $s1->getStyle("F{$b}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
    $s1->getStyle("F{$b}")->getAlignment()->setWrapText(true);
    $b++;
}
$s1->getStyle("A" . ($awalC + 2) . ":F" . ($awalC + 2))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDCFCE7');
$s1->getStyle("A" . ($awalC + 2) . ":F" . ($awalC + 2))->getFont()->setBold(true);
$b++;

catatan($s1, $b++, 'DIREKOMENDASIKAN: paket 2 tahun. Harga terkunci, dan cocok dengan rencana menambah kategori usaha lain (mis. sawit) dalam aplikasi yang sama.', 'FFDCFCE7');
$b++;

judulBagian($s1, $b++, 'D. TAMBAHAN KATEGORI USAHA (mis. SAWIT) DI APLIKASI YANG SAMA');
kepalaTabel($s1, $b++);
$awalD = $b;
barisItem($s1, $b++, 'Penyiapan kategori usaha baru', 'kategori', 1, 1500000, 'Sekali bayar per kategori: penyesuaian istilah (satuan, jenis barang), kategori item, dan laporan.');
barisItem($s1, $b++, 'Tambahan langganan per kategori', 'bulan', 1, 150000, 'Ditambahkan ke langganan bulanan. Data antar kategori terpisah, laporan bisa digabung.');
$akhirD = $b - 1;
$b++;
catatan($s1, $b++, 'Kategori tambahan memakai basis data & akun yang sama, jadi tidak perlu langganan penuh kedua. Contoh: Ternak + Sawit = Rp' . number_format(450000 + 150000, 0, ',', '.') . '/bulan, bukan Rp' . number_format(900000, 0, ',', '.') . '/bulan.');
$b++;

judulBagian($s1, $b++, 'E. BATAS PAKET & YANG TIDAK TERMASUK', 'FFB45309');
kepalaTabel($s1, $b++);
barisItem($s1, $b++, 'Jumlah pengguna', 'akun', 0, 0, 'TANPA BATAS — owner, admin, supervisor, gudang, sebanyak yang dibutuhkan.');
barisItem($s1, $b++, 'Penyimpanan berkas (foto bon, bukti transfer)', 'GB', 5, 0, 'Termasuk 5 GB. Tambahan Rp50.000/bulan per 5 GB berikutnya.');
$b++;
catatan($s1, $b++, "TIDAK TERMASUK dalam skema langganan:\n"
    . "• Domain sendiri (mis. ayamjaya.com) — skema ini memakai subdomain Mooda.\n"
    . "• VPS / server terpisah milik klien.\n"
    . "• Payment gateway (pembayaran online) — lihat Tab 2 bila diperlukan.\n"
    . "• Perangkat keras: printer thermal, timbangan digital, tablet/HP.\n"
    . "• Integrasi ke sistem pihak ketiga (akuntansi, e-Faktur, marketplace).\n"
    . "• Penambahan modul baru di luar lingkup — dihitung terpisah per permintaan.", $MERAH);
$s1->getRowDimension($b - 1)->setRowHeight(96);
$b++;

judulBagian($s1, $b++, 'F. RINGKASAN BIAYA TAHUN PERTAMA');
kepalaTabel($s1, $b++);
$s1->setCellValue("A{$b}", 'Implementasi (sekali bayar)');
$s1->setCellValue("E{$b}", "=E{$rowSubA}");
$s1->getStyle("E{$b}")->getNumberFormat()->setFormatCode($RP);
$r1 = $b; $b++;
$s1->setCellValue("A{$b}", 'Langganan 1 tahun (diskon 10%)');
$s1->setCellValue("E{$b}", '=E' . ($awalC + 1));
$s1->getStyle("E{$b}")->getNumberFormat()->setFormatCode($RP);
$r2 = $b; $b++;
barisTotal($s1, $b, 'TOTAL TAHUN PERTAMA (opsi 1 tahun)', "=E{$r1}+E{$r2}", 'FFDCFCE7');
$b++;
$s1->setCellValue("A{$b}", 'Alternatif: Implementasi + Langganan 2 tahun (diskon 20%)');
$s1->mergeCells("A{$b}:D{$b}");
$s1->setCellValue("E{$b}", "=E{$rowSubA}+E" . ($awalC + 2));
$s1->getStyle("A{$b}:F{$b}")->getFont()->setBold(true);
$s1->getStyle("E{$b}")->getNumberFormat()->setFormatCode($RP);
$s1->getStyle("A{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$s1->getStyle("A{$b}:F{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($BIRU);
$b += 2;
catatan($s1, $b++, 'Tahun kedua dan seterusnya (opsi 1 tahun): hanya biaya langganan, tanpa implementasi lagi.');

/* =======================================================================
   TAB 2 — JUAL PUTUS
   ======================================================================= */
$s2 = $wb->createSheet();
$s2->setTitle('2. Jual Putus');
aturKolom($s2);

$s2->setCellValue('A1', 'RENCANA ANGGARAN BIAYA — SKEMA JUAL PUTUS');
$s2->mergeCells('A1:F1');
$s2->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF1D4ED8');

$s2->setCellValue('A2', 'Mooda Stok — Aplikasi Inventori & Perdagangan Ternak / Perkebunan');
$s2->mergeCells('A2:F2');
$s2->getStyle('A2')->getFont()->setSize(11)->getColor()->setARGB('FF6B7280');

$s2->setCellValue('A3', 'Aplikasi dipasang di server milik klien. Klien menanggung hosting, cadangan, dan keamanan. Mooda tidak lagi memegang data klien.');
$s2->mergeCells('A3:F3');
$s2->getStyle('A3')->getAlignment()->setWrapText(true);
$s2->getRowDimension(3)->setRowHeight(30);

$s2->setCellValue('A4', 'Tanggal disusun: ' . date('d/m/Y') . '  ·  Berlaku 30 hari  ·  Semua angka belum termasuk PPN');
$s2->mergeCells('A4:F4');
$s2->getStyle('A4')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF6B7280');

$b = 6;
judulBagian($s2, $b++, 'A. HARGA APLIKASI — PILIH SALAH SATU', 'FF1D4ED8');
kepalaTabel($s2, $b++);
$awalA2 = $b;
barisItem($s2, $b++, 'Opsi 1 — LISENSI PEMAKAIAN (tanpa sumber kode)', 'lisensi', 1, 48000000,
    'Aplikasi dipasang & dijalankan di server klien. Sumber kode tetap milik Mooda. Klien tidak bisa memodifikasi sendiri; perubahan lewat Mooda.');
barisItem($s2, $b++, 'Opsi 2 — SUMBER KODE + HAK MODIFIKASI', 'lisensi', 1, 95000000,
    'Sumber kode diserahkan. Klien boleh memodifikasi & memakai untuk badan usaha sendiri. TIDAK termasuk hak menjual ulang / menyewakan ke pihak lain.');
$akhirA2 = $b - 1;
$b++;
catatan($s2, $b++, "Kenapa jauh lebih mahal dari langganan? Pada jual putus, seluruh biaya pengembangan ditanggung sekali oleh satu klien, dan Mooda kehilangan pendapatan berulang. Nilai ini setara ± 9 tahun langganan (Rp450.000 x 24 bln x ...) — silakan bandingkan di Tab 1 bagian Ringkasan.", 'FFFEF3C7');
$s2->getRowDimension($b - 1)->setRowHeight(42);
$b++;

judulBagian($s2, $b++, 'B. HAK LISENSI — RINCIAN', 'FF1D4ED8');
kepalaTabel($s2, $b++);
barisItem($s2, $b++, 'Jumlah badan usaha yang boleh memakai', 'badan usaha', 1, 0, 'Lisensi berlaku untuk SATU badan usaha. Cabang/gudang tambahan milik badan usaha yang sama: tidak dikenai biaya.');
barisItem($s2, $b++, 'Tambahan badan usaha (grup usaha berbeda)', 'lisensi', 1, 18000000, 'Bila lisensi ingin dipakai PT/CV lain dalam satu grup.');
barisItem($s2, $b++, 'Hak menjual ulang / menyewakan ke pihak ketiga', 'paket', 0, 0, 'TIDAK TERMASUK pada kedua opsi. Bila diinginkan, dirundingkan terpisah sebagai kerja sama.');
$b++;

judulBagian($s2, $b++, 'C. PEMELIHARAAN TAHUNAN (mulai tahun ke-2, opsional)', 'FF1D4ED8');
kepalaTabel($s2, $b++);
$hdr2 = $b - 1;
$s2->setCellValue("C{$hdr2}", '% Lisensi');
$s2->setCellValue("D{$hdr2}", 'Dasar (Opsi 1)');

$s2->setCellValue("A{$b}", 'Paket Dasar — perbaikan bug + pembaruan keamanan');
$s2->setCellValue("B{$b}", 'tahun');
$s2->setCellValue("C{$b}", 15);
$s2->setCellValue("D{$b}", "=E{$awalA2}");
$s2->setCellValue("E{$b}", "=ROUND(D{$b}*C{$b}/100,0)");
$s2->setCellValue("F{$b}", 'Tanpa penambahan fitur. Tanggap dalam 2 hari kerja.');
$b++;
$s2->setCellValue("A{$b}", 'Paket Penuh — Dasar + perubahan kecil 8 jam/bulan');
$s2->setCellValue("B{$b}", 'tahun');
$s2->setCellValue("C{$b}", 22);
$s2->setCellValue("D{$b}", "=E{$awalA2}");
$s2->setCellValue("E{$b}", "=ROUND(D{$b}*C{$b}/100,0)");
$s2->setCellValue("F{$b}", 'Termasuk jam kerja pengembang untuk penyesuaian kecil. Tanggap dalam 1 hari kerja.');
$b++;
$s2->setCellValue("A{$b}", 'Tanpa pemeliharaan');
$s2->setCellValue("B{$b}", 'tahun');
$s2->setCellValue("C{$b}", 0);
$s2->setCellValue("D{$b}", 0);
$s2->setCellValue("E{$b}", 0);
$s2->setCellValue("F{$b}", 'Diperbolehkan. Perbaikan di luar kontrak dihitung Rp350.000/jam, minimum 4 jam.');
$b++;
foreach (range($b - 3, $b - 1) as $r) {
    $s2->getStyle("C{$r}")->getNumberFormat()->setFormatCode($PCT);
    $s2->getStyle("D{$r}:E{$r}")->getNumberFormat()->setFormatCode($RP);
    $s2->getStyle("C{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $s2->getStyle("C{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($KUNING);
    $s2->getStyle("F{$r}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
    $s2->getStyle("F{$r}")->getAlignment()->setWrapText(true);
}
$b++;

judulBagian($s2, $b++, 'D. HOSTING — PERINGATAN TEKNIS PENTING', 'FFB91C1C');
catatan($s2, $b++, "APLIKASI INI TIDAK BISA BERJALAN DI SHARED HOSTING BIASA.\n"
    . "Yang dibutuhkan: PostgreSQL, Redis, dan proses latar yang hidup terus (Octane/RoadRunner + Reverb).\n"
    . "Shared hosting umumnya hanya menyediakan MySQL, tanpa Redis, dan mematikan proses latar.\n\n"
    . "Ada dua jalan: (D1) VPS — cara yang benar dan disarankan; atau (D2) versi hemat yang\n"
    . "dipaksa jalan di shared hosting dengan konsekuensi nyata di bawah.", $MERAH);
$s2->getRowDimension($b - 1)->setRowHeight(84);
$b++;

kepalaTabel($s2, $b++);
$awalD2 = $b;
barisItem($s2, $b++, 'D1. VPS 4 vCPU / 8 GB RAM / 100 GB SSD (disarankan)', 'bulan', 12, 450000, 'Sanggup menjalankan seluruh aplikasi seperti apa adanya. Harga pasar Hostinger/Biznet/IdCloudHost kelas ini.');
barisItem($s2, $b++, 'Penyiapan VPS: pemasangan, pengamanan, HTTPS, cadangan otomatis', 'paket', 1, 3500000, 'Sekali bayar. Termasuk firewall, fail2ban, sertifikat otomatis, cadangan harian ke penyimpanan terpisah.');
$akhirD2 = $b - 1;
barisTotal($s2, $b, 'SUBTOTAL D1 (VPS, tahun pertama)', "=SUM(E{$awalD2}:E{$akhirD2})");
$rowD1 = $b;
$b += 2;

kepalaTabel($s2, $b++);
$awalD3 = $b;
barisItem($s2, $b++, 'D2. Shared hosting kelas bisnis (PHP 8.3, PostgreSQL bila tersedia)', 'tahun', 1, 1500000, 'Hanya bila penyedia menyanggupi PostgreSQL. Bila hanya MySQL, perlu penyesuaian kode (lihat baris berikut).');
barisItem($s2, $b++, 'Penyesuaian agar jalan tanpa Redis & Octane', 'paket', 1, 7500000, 'Sesi & cache dipindah ke basis data, antrean jadi sinkron, WebSocket dimatikan. Aplikasi JADI LEBIH LAMBAT dan fitur waktu-nyata hilang.');
barisItem($s2, $b++, 'Pemindahan PostgreSQL → MySQL (bila penyedia tak punya PostgreSQL)', 'paket', 1, 6000000, 'Perlu bila shared hosting hanya menyediakan MySQL. Termasuk penyesuaian kueri & pengujian ulang.');
$akhirD3 = $b - 1;
barisTotal($s2, $b, 'SUBTOTAL D2 (shared hosting, tahun pertama)', "=SUM(E{$awalD3}:E{$akhirD3})", 'FFFEE2E2');
$rowD2 = $b;
$b++;
catatan($s2, $b++, 'Perhatikan: D2 terlihat murah pada biaya hosting, tetapi biaya penyesuaiannya justru LEBIH BESAR daripada tiga tahun VPS — dan hasilnya aplikasi yang lebih lambat serta kehilangan pembaruan waktu-nyata. Kami menyarankan D1.', 'FFFEF3C7');
$s2->getRowDimension($b - 1)->setRowHeight(42);
$b += 2;

judulBagian($s2, $b++, 'E. PAYMENT GATEWAY (hanya bila ingin terima pembayaran online)', 'FF1D4ED8');
kepalaTabel($s2, $b++);
$awalE = $b;
barisItem($s2, $b++, 'Integrasi payment gateway (Tripay / Midtrans)', 'paket', 1, 6500000, 'Sekali bayar: pendaftaran merchant, sambungan API, halaman bayar, penanganan callback, uji transaksi.');
barisItem($s2, $b++, 'Sertifikat & domain khusus callback (bila diperlukan)', 'tahun', 1, 350000, 'Beberapa penyedia mensyaratkan domain terverifikasi untuk callback.');
$akhirE = $b - 1;
barisTotal($s2, $b, 'SUBTOTAL E (sekali bayar + tahunan)', "=SUM(E{$awalE}:E{$akhirE})");
$rowE = $b;
$b++;
catatan($s2, $b++, "Biaya per transaksi dibayar langsung ke penyedia, BUKAN ke Mooda — besarnya mengikuti tarif penyedia (umumnya QRIS ± 0,7%, Virtual Account ± Rp4.000/transaksi). Payment gateway hanya masuk akal bila agen membayar secara online; bila pembayaran agen tunai/transfer manual, bagian E ini tidak diperlukan.");
$s2->getRowDimension($b - 1)->setRowHeight(42);
$b += 2;

judulBagian($s2, $b++, 'F. RINGKASAN — CONTOH SKENARIO JUAL PUTUS');
kepalaTabel($s2, $b++);
$s2->setCellValue("A{$b}", 'Lisensi pemakaian (Opsi 1)');
$s2->setCellValue("E{$b}", "=E{$awalA2}");
$x1 = $b; $b++;
$s2->setCellValue("A{$b}", 'Hosting VPS + penyiapan (D1, tahun pertama)');
$s2->setCellValue("E{$b}", "=E{$rowD1}");
$x2 = $b; $b++;
$s2->setCellValue("A{$b}", 'Pemeliharaan tahun ke-2 (Paket Dasar 15%)');
$s2->setCellValue("E{$b}", "=ROUND(E{$awalA2}*0.15,0)");
$x3 = $b; $b++;
foreach ([$x1, $x2, $x3] as $r) {
    $s2->getStyle("E{$r}")->getNumberFormat()->setFormatCode($RP);
}
barisTotal($s2, $b, 'TOTAL TAHUN PERTAMA (tanpa payment gateway)', "=E{$x1}+E{$x2}", 'FFDBEAFE');
$b++;
$s2->setCellValue("A{$b}", 'Bila memakai payment gateway, tambahkan');
$s2->mergeCells("A{$b}:D{$b}");
$s2->setCellValue("E{$b}", "=E{$rowE}");
$s2->getStyle("E{$b}")->getNumberFormat()->setFormatCode($RP);
$s2->getStyle("A{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$s2->getStyle("A{$b}:F{$b}")->getFont()->setBold(true);
$b += 2;

judulBagian($s2, $b++, 'G. PERBANDINGAN JANGKA PANJANG — LANGGANAN vs JUAL PUTUS', 'FF6D28D9');
kepalaTabel($s2, $b++);
$hdr3 = $b - 1;
$s2->setCellValue("B{$hdr3}", 'Tahun');
$s2->setCellValue("C{$hdr3}", 'Langganan');
$s2->setCellValue("D{$hdr3}", 'Jual Putus + Maintenance');
$s2->setCellValue("E{$hdr3}", 'Jual Putus tanpa Maintenance');
$s2->setCellValue("F{$hdr3}", 'Catatan');

$langgananTahun = 450000 * 12 * 0.8;   // paket 2 tahun, efektif Rp360.000/bulan
$implementasi   = 4000000;
$lisensi        = 48000000;
$vpsTahun       = 450000 * 12;
$setupVps       = 3500000;
$maintenance    = $lisensi * 0.15;

$kumL = 0; $kumJ1 = 0; $kumJ2 = 0;
$titikBalik = null;
for ($t = 1; $t <= 10; $t++) {
    $kumL  += $langgananTahun + ($t === 1 ? $implementasi : 0);
    $kumJ1 += $vpsTahun + ($t === 1 ? $lisensi + $setupVps : $maintenance);
    $kumJ2 += $vpsTahun + ($t === 1 ? $lisensi + $setupVps : 0);
    if ($titikBalik === null && $kumJ2 <= $kumL) {
        $titikBalik = $t;
    }

    $s2->setCellValue("A{$b}", "Kumulatif sampai tahun ke-{$t}");
    $s2->setCellValue("B{$b}", $t);
    $s2->setCellValue("C{$b}", round($kumL));
    $s2->setCellValue("D{$b}", round($kumJ1));
    $s2->setCellValue("E{$b}", round($kumJ2));
    $s2->setCellValue("F{$b}", $t === 1 ? 'Tahun pertama: langganan jauh lebih ringan.' : '');
    $s2->getStyle("B{$b}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $s2->getStyle("C{$b}:E{$b}")->getNumberFormat()->setFormatCode('"Rp"#,##0');
    $s2->getStyle("F{$b}")->getFont()->setSize(9)->getColor()->setARGB('FF6B7280');
    if ($kumJ2 <= $kumL) {
        $s2->getStyle("A{$b}:F{$b}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDCFCE7');
        $s2->setCellValue("F{$b}", 'Sejak titik ini jual putus (tanpa maintenance) mulai lebih hemat.');
        $s2->getStyle("F{$b}")->getAlignment()->setWrapText(true);
    }
    $b++;
}
$b++;

$pesan = "TEMUAN YANG HARUS DISAMPAIKAN APA ADANYA:\n\n"
    . "1) Dengan angka pada berkas ini, JUAL PUTUS + MAINTENANCE TIDAK PERNAH menjadi lebih murah. "
    . "Sebabnya biaya berulangnya (VPS Rp" . number_format($vpsTahun, 0, ',', '.') . " + maintenance Rp"
    . number_format($maintenance, 0, ',', '.') . " = Rp" . number_format($vpsTahun + $maintenance, 0, ',', '.')
    . "/tahun) lebih besar daripada langganan itu sendiri (Rp" . number_format($langgananTahun, 0, ',', '.') . "/tahun). "
    . "Selisihnya justru makin lebar setiap tahun.\n\n"
    . "2) Tanpa maintenance, jual putus baru lebih hemat "
    . ($titikBalik ? "pada tahun ke-{$titikBalik}" : 'setelah lebih dari 10 tahun')
    . " — dengan syarat klien sanggup mengurus server, cadangan, dan pembaruan keamanan sendiri.\n\n"
    . "3) Karena itu jual putus sebaiknya TIDAK dijual sebagai cara berhemat, melainkan karena alasan lain: "
    . "data sepenuhnya di tangan klien, tidak bergantung pada Mooda, dan aplikasi menjadi aset perusahaan.";
catatan($s2, $b++, $pesan, 'FFFEF3C7');
$s2->getRowDimension($b - 1)->setRowHeight(150);
$b++;

catatan($s2, $b++, "PERTIMBANGAN HARGA (untuk internal Mooda, boleh dihapus sebelum dikirim ke klien):\n"
    . "Rasio lisensi terhadap langganan saat ini = Rp" . number_format($lisensi, 0, ',', '.') . " / Rp"
    . number_format($langgananTahun, 0, ',', '.') . "/tahun = " . round($lisensi / $langgananTahun, 1) . " tahun langganan. "
    . "Lazimnya jual putus dihargai 3-5 tahun langganan. Artinya ada dua pilihan: turunkan lisensi ke kisaran "
    . "Rp" . number_format($langgananTahun * 4, 0, ',', '.') . " (4 tahun), atau naikkan langganan bila memang "
    . "nilai aplikasinya dinilai setara Rp" . number_format($lisensi, 0, ',', '.') . " — mis. langganan Rp"
    . number_format(round($lisensi / 4 / 12 / 1000) * 1000, 0, ',', '.') . "/bulan. "
    . "Keputusan ini milik Mooda; berkas ini hanya menunjukkan konsekuensi angkanya.", 'FFE0E7FF');
$s2->getRowDimension($b - 1)->setRowHeight(96);

$wb->setActiveSheetIndex(0);
$keluaran = __DIR__ . '/RAB-Mooda-Stok.xlsx';
(new Xlsx($wb))->save($keluaran);

echo "  Berkas dibuat: {$keluaran}\n";
echo '  Ukuran: ' . number_format(filesize($keluaran) / 1024, 1) . " KB\n";
echo "  Tab: " . implode(' | ', $wb->getSheetNames()) . "\n";
