<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'Apa itu Mooda?',
                'Mooda adalah aplikasi kasir (POS) berbasis web untuk restoran, cafe, bakery, dan warung. Mooda menyatukan kasir, layar dapur (kitchen display), nomor antrian, menu & add-on, hingga laporan penjualan dalam satu sistem yang bisa dipakai di web maupun tablet.',
            ],
            [
                'Apakah Mooda bisa dicoba gratis?',
                'Bisa. Anda dapat mendaftar akun gratis dan langsung mencoba. Setelah aktivasi akun, Anda memperoleh saldo Starter gratis untuk mencoba transaksi. Tersedia juga akun demo untuk melihat semua fitur tanpa mendaftar.',
            ],
            [
                'Bagaimana model pembayaran / biaya Mooda?',
                'Ada dua pilihan: (1) Deposit/Saldo — bayar sesuai pemakaian, saldo dipotong per transaksi yang diselesaikan; cocok untuk usaha baru atau musiman. (2) Langganan Bulanan — biaya tetap per bulan. Anda bebas memilih sesuai skala bisnis.',
            ],
            [
                'Metode pembayaran apa saja untuk isi saldo / langganan?',
                'Top-up saldo dan langganan dapat dibayar via Virtual Account (BRI, BNI, Mandiri, BCA, BSI), QRIS, serta gerai retail (Alfamart/Indomaret/Alfamidi). Pembayaran diverifikasi otomatis dan saldo/langganan langsung aktif.',
            ],
            [
                'Apakah Mooda bisa untuk banyak outlet dan perangkat?',
                'Ya. Mooda mendukung multi-outlet dengan data tiap bisnis terisolasi penuh, hak akses staf (owner, admin, kasir, dapur), dan bisa dibuka di browser web maupun aplikasi tablet.',
            ],
            [
                'Apakah data penjualan saya aman?',
                'Aman. Semua data tersimpan di cloud dengan backup otomatis dan terisolasi per bisnis. Hanya akun dengan hak akses yang sesuai yang dapat melihat data Anda.',
            ],
            [
                'Bagaimana jika saya butuh bantuan?',
                'Tim Mooda siap membantu melalui WhatsApp. Anda juga dapat mengakses panduan penggunaan di dalam aplikasi. Hubungi kami kapan saja jika ada kendala saat setup atau operasional.',
            ],
        ];

        foreach ($faqs as $i => [$q, $a]) {
            Faq::firstOrCreate(
                ['question' => $q],
                ['answer' => $a, 'sort_order' => $i + 1, 'is_active' => true]
            );
        }
    }
}
