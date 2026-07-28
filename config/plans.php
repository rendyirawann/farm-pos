<?php

/**
 * Definisi paket langganan SaaS.
 * - basic      : paket entry, checkout via Midtrans.
 * - enterprise : paket lanjutan (manajemen meja, HPP, laporan keuangan), checkout via Midtrans.
 * - customize  : paket fleksibel (kontrak 2 tahun) via konsultasi WhatsApp (diaktifkan manual Superadmin).
 *
 * Catatan: "Starter" di landing = akun DEPOSIT (pay-as-you-go), bukan plan bulanan di sini.
 *
 * "modules" = daftar fitur yang boleh diakses paket tsb. Modul yang dikenal:
 * kasir, kitchen, report_sales, promo, report_items, data_master, resources, expense,
 * tables (manajemen meja), hpp (menu HPP), report_finance (laporan keuangan),
 * qr_selforder (QR self-order), payment_gateway (setelan payment gateway).
 */
return [

    'currency' => 'Rp',
    'trial_days' => 14,

    // Biaya tambah user di luar kuota paket.
    'extra_user_price' => 10000,

    'plans' => [
        'basic' => [
            'name'  => 'Basic',
            'price' => 199000,
            'periods' => [
                ['months' => 1,  'price_per_month' => 199000, 'label' => 'Bulanan'],
                ['months' => 6,  'price_per_month' => 149000, 'label' => 'Promo 6 Bulan'],
                ['months' => 12, 'price_per_month' => 129000, 'label' => 'Promo 12 Bulan'],
            ],
            'tagline' => 'Semua yang dibutuhkan untuk mulai jualan dengan rapi & cepat.',
            'limits' => ['outlets' => 1, 'staff' => 3, 'customers' => 12000],
            'modules' => ['kasir', 'kitchen', 'report_sales', 'data_master', 'resources', 'expense'],
            'features' => [
                'Kasir / POS satu layar (Tunai & QRIS)',
                'Kitchen Display (layar dapur)',
                'Add-on menu & nomor antrian di struk',
                'Laporan penjualan',
                'Data master menu & kategori',
                'Maks 3 User (tambah user Rp10.000/user)',
                'Penyimpanan Database Pelanggan (12.000 Data)',
            ],
        ],

        'enterprise' => [
            'name'  => 'Enterprise',
            'price' => 399000,
            'periods' => [
                ['months' => 1,  'price_per_month' => 399000, 'label' => 'Bulanan'],
                ['months' => 6,  'price_per_month' => 349000, 'label' => 'Promo 6 Bulan'],
                ['months' => 12, 'price_per_month' => 329000, 'label' => 'Promo 12 Bulan'],
            ],
            'tagline' => 'Untuk bisnis berkembang dengan manajemen yang lebih lengkap.',
            'limits' => ['outlets' => 1, 'staff' => 5, 'customers' => 50000],
            'modules' => [
                'kasir', 'kitchen', 'report_sales', 'data_master', 'resources', 'expense',
                'promo', 'report_items', 'tables', 'hpp', 'report_finance',
            ],
            'features' => [
                'Semua fitur paket Basic',
                'Manajemen Pengaturan Meja',
                'Menu HPP',
                'Laporan Keuangan',
                'Maks 5 User (tambah user Rp10.000/user)',
                'Penyimpanan Database Pelanggan (50.000 Data)',
            ],
        ],

        'customize' => [
            'name'  => 'Customize',
            'price' => 0,
            'contact' => true,     // konsultasi WhatsApp, bukan checkout Midtrans
            'wa' => '6285760366666',
            'tagline' => 'Rakit paketmu sendiri — kontrak 2 tahun, fitur menyesuaikan bisnis.',
            'limits' => ['outlets' => null, 'staff' => null, 'customers' => null],
            'modules' => [
                'kasir', 'kitchen', 'report_sales', 'data_master', 'resources', 'expense',
                'promo', 'report_items', 'tables', 'hpp', 'report_finance',
                'qr_selforder', 'payment_gateway',
            ],
            'features' => [
                'Semua fitur Enterprise & Basic',
                'Tanpa batasan jumlah user',
                'Penyimpanan Database Pelanggan (Tidak Terbatas)',
                'VPS & domain sendiri',
                'QR Menu & Self Order pelanggan',
                'Payment Gateway + Setting Payment',
                'Tambah fitur/menu khusus (maks 3; lebih kena charge)',
                'Konsultasi & support prioritas',
            ],
        ],
    ],
];
