<?php

/**
 * Konfigurasi khusus Stakko POS.
 */
return [
    // URL unduhan APK eksternal (opsional). Jika kosong & file lokal ada di
    // public/downloads/stakko-pos.apk, sistem memakai file lokal tsb.
    'mobile_apk_url' => env('MOBILE_APK_URL'),

    // Versi aplikasi tablet yang ditampilkan di halaman unduhan.
    'mobile_version' => env('MOBILE_APK_VERSION', '1.0.0'),

    // Nomor WhatsApp support (dipakai di beberapa halaman).
    'support_wa' => env('SUPPORT_WA', '6282362211676'),
];
