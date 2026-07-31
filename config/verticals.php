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
