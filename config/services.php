<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'midtrans' => [
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        // Override URL notifikasi (opsional). Kosongkan untuk memakai setelan dashboard Midtrans.
        'notify_url' => env('MIDTRANS_NOTIFY_URL'),
    ],

    /*
    | DOKU SNAP (BI Standard). Dipakai oleh App\Services\Doku\DokuSnap.
    | Kredensial DARI DASHBOARD DOKU (Settings -> API Keys):
    |   - client_id   : Client ID (BRN-....)
    |   - secret_key  : Active Secret Key (SK-...) -> tanda tangan simetris HMAC-SHA512
    |   - private_key : RSA private key MERCHANT (public key-nya diupload ke DOKU) -> tanda tangan asimetris token B2B
    |   - doku_public_key : DOKU Public Key (dari dashboard) -> verifikasi tanda tangan DOKU saat DOKU memanggil endpoint token kita
    | is_production=false -> sandbox (https://api-sandbox.doku.com).
    */
    'doku' => [
        'client_id'       => env('DOKU_CLIENT_ID'),
        'secret_key'      => env('DOKU_SECRET_KEY'),
        'private_key'     => env('DOKU_PRIVATE_KEY'),      // isi PEM (boleh multi-baris via "\n") atau path file
        'doku_public_key' => env('DOKU_PUBLIC_KEY'),       // PEM public key milik DOKU
        'is_production'   => env('DOKU_IS_PRODUCTION', false),
        'sandbox_base'    => env('DOKU_SANDBOX_BASE', 'https://api-sandbox.doku.com'),
        'production_base' => env('DOKU_PRODUCTION_BASE', 'https://api.doku.com'),
        // partnerServiceId = "Partner Service ID" dari config channel VA (digit; kode akan pad ke 8 char).
        'partner_service_id' => env('DOKU_PARTNER_SERVICE_ID'),
        // "Prefix Customer No" dari config channel VA — customerNo WAJIB diawali ini.
        'customer_prefix' => env('DOKU_VA_CUSTOMER_PREFIX'),
        // Channel VA default (Close Amount / SNAP). Salah satu dari VaChannels DOKU.
        'channel'         => env('DOKU_VA_CHANNEL', 'VIRTUAL_ACCOUNT_BCA'),
        // Public key MERCHANT kita (yang diupload ke DOKU) -> verifikasi JWT token yang kita terbitkan ke DOKU.
        'own_public_key'  => env('DOKU_OWN_PUBLIC_KEY'),
    ],

    /*
    | Tripay (payment gateway aggregator). Dipakai oleh App\Services\Tripay\Tripay.
    | Kredensial DARI DASHBOARD TRIPAY (Pengaturan -> Merchant / Sandbox):
    |   - merchant_code : Kode Merchant (mis. T51781 utk sandbox)
    |   - api_key       : API Key (sandbox diawali "DEV-")
    |   - private_key   : Private Key -> tanda tangan HMAC-SHA256
    | is_production=false -> sandbox (https://tripay.co.id/api-sandbox).
    | JANGAN commit nilai asli — isi hanya di .env server.
    */
    'tripay' => [
        'merchant_code'   => env('TRIPAY_MERCHANT_CODE'),
        'api_key'         => env('TRIPAY_API_KEY'),
        'private_key'     => env('TRIPAY_PRIVATE_KEY'),
        'is_production'   => env('TRIPAY_IS_PRODUCTION', false),
        'sandbox_base'    => env('TRIPAY_SANDBOX_BASE', 'https://tripay.co.id/api-sandbox'),
        'production_base' => env('TRIPAY_PRODUCTION_BASE', 'https://tripay.co.id/api'),
        // Masa berlaku transaksi (jam) untuk Closed Payment.
        'expiry_hours'    => (int) env('TRIPAY_EXPIRY_HOURS', 24),
    ],

    'reverb' => [
        'app_id' => env('REVERB_APP_ID'),
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'host' => env('REVERB_HOST'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'https'),
    ],

    // Kode verifikasi Google Search Console (isi via .env: GOOGLE_SITE_VERIFICATION=...).
    // Bila diisi, tag <meta name="google-site-verification"> otomatis muncul di landing.
    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),

    // ID Aplikasi Facebook (opsional). Bila diisi, tag <meta property="fb:app_id"> muncul di
    // landing untuk menghilangkan warning di Facebook Sharing Debugger. Preview link tetap
    // berfungsi tanpa ini — jadi boleh dibiarkan kosong.
    'facebook' => [
        'app_id' => env('FACEBOOK_APP_ID'),
    ],

];
