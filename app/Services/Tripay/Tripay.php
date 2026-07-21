<?php

namespace App\Services\Tripay;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Klien Tripay (payment gateway aggregator) — Closed Payment.
 *
 * Kripto:
 *   - Create transaksi : signature = HMAC-SHA256(merchantCode + merchantRef + amount, privateKey).
 *   - Callback masuk   : header X-Callback-Signature = HMAC-SHA256(rawBody, privateKey).
 *
 * Endpoint:
 *   sandbox    : https://tripay.co.id/api-sandbox
 *   production : https://tripay.co.id/api
 *   channels   : GET  /merchant/payment-channel
 *   create     : POST /transaction/create
 *   detail     : GET  /transaction/detail?reference=
 *
 * Auth: header Authorization: Bearer {api_key}.
 */
class Tripay
{
    public const CHANNEL_PATH = '/merchant/payment-channel';
    public const CREATE_PATH  = '/transaction/create';
    public const DETAIL_PATH  = '/transaction/detail';

    private string $merchantCode;
    private string $apiKey;
    private string $privateKey;
    private bool $isProduction;
    private string $baseUrl;
    private int $expiryHours;

    public function __construct(?array $config = null)
    {
        $config = $config ?? config('services.tripay');

        $this->merchantCode = (string) ($config['merchant_code'] ?? '');
        $this->apiKey       = (string) ($config['api_key'] ?? '');
        $this->privateKey   = (string) ($config['private_key'] ?? '');
        $this->isProduction = (bool) ($config['is_production'] ?? false);
        $this->baseUrl      = rtrim($this->isProduction
            ? ($config['production_base'] ?? 'https://tripay.co.id/api')
            : ($config['sandbox_base'] ?? 'https://tripay.co.id/api-sandbox'), '/');
        $this->expiryHours  = (int) ($config['expiry_hours'] ?? 24);
    }

    public function isConfigured(): bool
    {
        return $this->merchantCode !== '' && $this->apiKey !== '' && $this->privateKey !== '';
    }

    public function isProduction(): bool
    {
        return $this->isProduction;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /* ===================== Signature ===================== */

    /** Signature create transaksi: HMAC-SHA256(merchantCode + merchantRef + amount, privateKey). */
    public function transactionSignature(string $merchantRef, int $amount): string
    {
        return hash_hmac('sha256', $this->merchantCode . $merchantRef . $amount, $this->privateKey);
    }

    /** Verifikasi callback: header X-Callback-Signature == HMAC-SHA256(rawBody, privateKey). */
    public function verifyCallbackSignature(string $rawBody, ?string $signatureHeader): bool
    {
        if (empty($signatureHeader) || $this->privateKey === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $rawBody, $this->privateKey);
        return hash_equals($expected, (string) $signatureHeader);
    }

    /* ===================== Channels ===================== */

    /**
     * Daftar channel pembayaran aktif milik merchant (di-cache 10 menit).
     * @return array<int, array{code:string,name:string,group:string,icon_url:string,fee_flat:int}>
     */
    public function paymentChannels(bool $forceRefresh = false): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $cacheKey = 'tripay_channels_' . md5($this->apiKey . '|' . $this->baseUrl);
        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $res  = $this->httpGet(self::CHANNEL_PATH);
        $data = $res['body']['data'] ?? [];
        if (! is_array($data)) {
            $data = [];
        }

        $channels = [];
        foreach ($data as $c) {
            if (array_key_exists('active', $c) && ! $c['active']) {
                continue;
            }
            $channels[] = [
                'code'     => (string) ($c['code'] ?? ''),
                'name'     => (string) ($c['name'] ?? ''),
                'group'    => (string) ($c['group'] ?? ''),
                'icon_url' => (string) ($c['icon_url'] ?? ''),
                'fee_flat' => (int) (data_get($c, 'total_fee.flat') ?? data_get($c, 'fee_customer.flat') ?? 0),
            ];
        }

        // Cache: hasil sukses 10 menit; hasil KOSONG (error/outage) 45 detik (negative cache)
        // supaya render halaman billing/deposit tidak memukul jaringan berulang saat Tripay bermasalah.
        Cache::put($cacheKey, $channels, empty($channels) ? 45 : 600);
        return $channels;
    }

    /** Apakah kode channel valid & aktif untuk merchant ini. */
    public function channelActive(string $code): bool
    {
        foreach ($this->paymentChannels() as $c) {
            if ($c['code'] === $code) {
                return true;
            }
        }
        return false;
    }

    /* ===================== Transaksi ===================== */

    /**
     * Buat transaksi Closed Payment.
     *
     * @param array{method:string, merchant_ref:string, amount:int, customer_name:string,
     *   customer_email?:string, customer_phone?:string, order_items:array,
     *   callback_url?:string, return_url?:string} $p
     * @return array Respons Tripay terdekode + _http_status.
     */
    public function createClosedTransaction(array $p): array
    {
        $merchantRef = (string) $p['merchant_ref'];
        $amount      = (int) $p['amount'];

        $payload = [
            'method'         => (string) $p['method'],
            'merchant_ref'   => $merchantRef,
            'amount'         => $amount,
            'customer_name'  => (string) ($p['customer_name'] ?? 'Pelanggan'),
            'customer_email' => (string) ($p['customer_email'] ?? ''),
            'customer_phone' => (string) ($p['customer_phone'] ?? ''),
            'order_items'    => $p['order_items'] ?? [],
            'callback_url'   => (string) ($p['callback_url'] ?? ''),
            'return_url'     => (string) ($p['return_url'] ?? ''),
            'expired_time'   => time() + ($this->expiryHours * 3600),
            'signature'      => $this->transactionSignature($merchantRef, $amount),
        ];

        $res = $this->httpPostForm(self::CREATE_PATH, $payload);

        if (! ($res['body']['success'] ?? false)) {
            Log::warning('Tripay createClosedTransaction non-success', [
                'status' => $res['status'],
                'body'   => $res['body'],
            ]);
        }
        return ($res['body'] ?: []) + ['_http_status' => $res['status']];
    }

    /* ===================== HTTP ===================== */

    private function httpGet(string $path): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_CONNECTTIMEOUT => 5,   // gagal cepat bila Tripay tak terjangkau (lindungi worker Octane)
            CURLOPT_TIMEOUT        => 10,
        ]);
        return $this->exec($ch);
    }

    private function httpPostForm(string $path, array $data): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 15,   // create transaksi (user menunggu di checkout)
        ]);
        return $this->exec($ch);
    }

    private function exec($ch): array
    {
        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            Log::error('Tripay cURL error: ' . $err);
            return ['status' => 0, 'body' => [], 'raw' => ''];
        }
        $json = json_decode($raw, true);
        return ['status' => $code, 'body' => is_array($json) ? $json : [], 'raw' => $raw];
    }
}
