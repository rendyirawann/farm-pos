<?php

/**
 * Definisi paket langganan SaaS.
 * - starter  : paket tetap, checkout via Midtrans.
 * - customize : paket fleksibel (fitur menyesuaikan kebutuhan), via konsultasi WhatsApp
 *               (tidak checkout Midtrans; diaktifkan manual oleh Superadmin).
 *
 * "modules" = daftar fitur yang boleh diakses paket tsb.
 * Modul yang dikenal: kasir, kitchen, report_sales, promo, report_items, data_master, resources
 */
return [

    'currency' => 'Rp',
    'trial_days' => 14,

    'plans' => [
        'starter' => [
            'name'  => 'Starter',
            'price' => 199000,
            'tagline' => 'Semua yang dibutuhkan untuk mulai jualan dengan rapi & cepat.',
            'limits' => [
                'outlets' => 1,
                'staff'   => 8,
            ],
            'modules' => [
                'kasir', 'kitchen', 'report_sales', 'data_master', 'resources',
            ],
            'features' => [
                'Kasir / POS satu layar (Tunai & QRIS)',
                'Kitchen Display (layar dapur)',
                'Add-on menu & nomor antrian di struk',
                'Laporan penjualan',
                'Data master menu & kategori',
                'Maks 8 staf',
                'Support via email',
            ],
        ],

        'customize' => [
            'name'  => 'Customize',
            'price' => 0,          // harga fleksibel — via konsultasi
            'contact' => true,     // tandai paket konsultasi (WhatsApp), bukan checkout Midtrans
            'wa' => '6282362211676',
            'tagline' => 'Rakit paketmu sendiri — fitur menyesuaikan kebutuhan bisnis.',
            'limits' => [
                'outlets' => null, // fleksibel
                'staff'   => null, // fleksibel
            ],
            'modules' => [
                'kasir', 'kitchen', 'report_sales', 'data_master',
                'promo', 'report_items', 'resources',
            ],
            'features' => [
                'Semua fitur paket Starter',
                'Promo & diskon otomatis',
                'Laporan per-item / produk terlaris',
                'Manajemen user & role (staf)',
                'Pilih & atur modul sesuai kebutuhan',
                'Jumlah staf & outlet fleksibel',
                'Konsultasi & support prioritas',
            ],
        ],
    ],
];
