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
    'purchase_enabled' => env('BILLING_PURCHASE_ENABLED', false),
];
