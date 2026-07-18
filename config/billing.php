<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Purchase enabled
    |--------------------------------------------------------------------------
    | When false, the "Berlangganan / beli paket" flow is disabled: the button
    | shows "Segera Hadir" and the checkout endpoint returns 503. Flip this to
    | true ONLY after Midtrans production keys are configured and verified.
    |
    | Re-enable: set BILLING_PURCHASE_ENABLED=true in .env, then run
    | `php artisan optimize` and `systemctl restart octane-stakko-pos`.
    */
    'purchase_enabled' => env('BILLING_PURCHASE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Payment driver
    |--------------------------------------------------------------------------
    | Gateway pembayaran untuk checkout langganan & top-up deposit.
    | 'midtrans' (default) = jalur publik yang SEDANG LIVE (production) — TIDAK diubah.
    | 'doku'                = DOKU SNAP Virtual Account.
    |
    | PENTING: selama DOKU masih SANDBOX, biarkan driver = 'midtrans' agar
    | transaksi nyata di landing/deposit TIDAK pernah masuk sandbox. Pindah ke
    | 'doku' HANYA setelah akun DOKU terverifikasi production + DOKU_IS_PRODUCTION=true.
    */
    'driver' => env('BILLING_DRIVER', 'midtrans'),

    /*
    | Teks yang ditampilkan saat purchase_enabled = false (dipakai landing page
    | pada tombol "Pilih Starter" & "Daftar" yang dinonaktifkan).
    */
    'maintenance_text' => env('BILLING_MAINTENANCE_TEXT', 'Available soon — Maintenance Midtrans Server'),
];
