<?php

/**
 * Definisi vertical (industri) Mooda. Satu aplikasi, satu DB, dibedakan kolom `tenants.vertical`
 * + subdomain. Branding SERAGAM Mooda; yang beda hanya modul & objek jualan.
 */
return [
    'default' => 'fnb',

    'list' => [
        'fnb' => [
            'label'   => 'F&B',
            'name'    => 'Mooda',
            'host'    => env('APP_HOST_FNB', 'mooda.id'),
            'icon'    => 'ki-coffee',
            'modules' => ['menu', 'kitchen', 'tables'],
        ],
        'laundry' => [
            'label'   => 'Laundry',
            'name'    => 'Mooda Laundry',
            'host'    => env('APP_HOST_LAUNDRY', 'laundry.mooda.id'),
            'icon'    => 'ki-abstract-26',
            'modules' => ['laundry_service', 'laundry_produksi'],
        ],
        // FARM (farm.mooda.id) — bukan POS: inventori & perdagangan ternak.
        // Objek jualan = ayam (potong/petelur) & telur; alur = stock in dari supplier,
        // stock out ke agen dengan harga pokok FIFO. Tidak ada kasir/meja/dapur.
        'farm' => [
            'label'   => 'Peternakan',
            'name'    => 'Mooda Farm',
            'host'    => env('APP_HOST_FARM', 'farm.mooda.id'),
            'icon'    => 'ki-abstract-44',
            'modules' => ['inventory', 'stock_in', 'stock_out', 'supplier', 'agent'],
        ],

        // Retail menyusul (fokus Laundry dulu).
        'retail' => [
            'label'   => 'Retail',
            'name'    => 'Mooda Retail',
            'host'    => env('APP_HOST_RETAIL', 'retail.mooda.id'),
            'icon'    => 'ki-basket',
            'modules' => ['product', 'stock'],
            'enabled' => false,
        ],
    ],
];
