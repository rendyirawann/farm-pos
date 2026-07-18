<?php

namespace App\Services\Doku;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Klien DOKU SNAP (BI Standard) — DIRAKIT SENDIRI tanpa SDK.
 *
 * Kripto (persis mengikuti spesifikasi & SDK resmi DOKU):
 *   - Access token B2B  : tanda tangan ASIMETRIS SHA256withRSA atas "clientId|timestamp"
 *                         dengan PRIVATE KEY merchant. -> POST /authorization/v1/access-token/b2b
 *   - Request layanan   : tanda tangan SIMETRIS HMAC-SHA512 atas
 *                         "METHOD:path:token:sha256hex(minify(body)):timestamp" dengan SECRET KEY.
 *   - Notifikasi masuk  : token B2B yang KITA terbitkan ke DOKU berupa JWT RS256 (private key kita),
 *                         diverifikasi dengan PUBLIC KEY kita; DOKU menandatangani panggilan token-nya
 *                         secara asimetris -> diverifikasi dengan DOKU Public Key.
 *
 * Endpoint (dari Config resmi DOKU):
 *   sandbox     : https://api-sandbox.doku.com
 *   production  : https://api.doku.com
 *   token B2B   : /authorization/v1/access-token/b2b
 *   create VA   : /virtual-accounts/bi-snap-va/v1.1/transfer-va/create-va
 *   status VA   : /orders/v1.0/transfer-va/status
 */
class DokuSnap
{
    public const ACCESS_TOKEN_PATH = '/authorization/v1/access-token/b2b';
    public const CREATE_VA_PATH     = '/virtual-accounts/bi-snap-va/v1.1/transfer-va/create-va';
    public const CHECK_VA_PATH      = '/orders/v1.0/transfer-va/status';

    private string $clientId;
    private string $secretKey;
    private string $privateKey;     // PEM
    private ?string $dokuPublicKey; // PEM (punya DOKU)
    private bool $isProduction;
    private string $baseUrl;
    private string $partnerServiceId;
    private string $customerPrefix;
    private string $defaultChannel;

    public function __construct(?array $config = null)
    {
        $config = $config ?? config('services.doku');

        $this->clientId       = (string) ($config['client_id'] ?? '');
        $this->secretKey      = (string) ($config['secret_key'] ?? '');
        $this->privateKey     = $this->normalizeKey((string) ($config['private_key'] ?? ''));
        $this->dokuPublicKey  = ! empty($config['doku_public_key'])
            ? $this->normalizeKey((string) $config['doku_public_key'])
            : null;
        $this->isProduction   = (bool) ($config['is_production'] ?? false);
        $this->baseUrl        = rtrim($this->isProduction
            ? ($config['production_base'] ?? 'https://api.doku.com')
            : ($config['sandbox_base'] ?? 'https://api-sandbox.doku.com'), '/');
        $this->partnerServiceId = (string) ($config['partner_service_id'] ?? '');
        $this->customerPrefix   = preg_replace('/\D/', '', (string) ($config['customer_prefix'] ?? ''));
        $this->defaultChannel   = (string) ($config['channel'] ?? 'VIRTUAL_ACCOUNT_BCA');
    }

    /* ============================ Util umum ============================ */

    /** Timestamp ISO-8601 WIB (+07:00), format('c'), sesuai Helper DOKU. */
    public function timestamp(int $bufferSeconds = 0): string
    {
        $ts = new \DateTime('now');
        if ($bufferSeconds !== 0) {
            $ts->modify(($bufferSeconds >= 0 ? '+' : '') . $bufferSeconds . ' seconds');
        }
        $ts->setTimezone(new \DateTimeZone('+07:00'));
        return $ts->format('c');
    }

    /** Terima PEM langsung (boleh "\n" literal) atau path file. */
    private function normalizeKey(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (str_contains($value, 'BEGIN')) {
            // PEM inline; ubah "\n" literal menjadi newline sungguhan.
            return str_replace(['\\n', "\r\n"], ["\n", "\n"], $value);
        }
        // Anggap path file.
        if (is_file($value) && is_readable($value)) {
            return (string) file_get_contents($value);
        }
        return $value;
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->secretKey !== '' && $this->privateKey !== '';
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function partnerServiceId(): string
    {
        return $this->partnerServiceId;
    }

    /* ===================== Tanda tangan / kripto ====================== */

    /** Tanda tangan ASIMETRIS untuk access token B2B: base64(RSA-SHA256(clientId|timestamp)). */
    public function asymmetricSignature(string $timestamp): string
    {
        $stringToSign = $this->clientId . '|' . $timestamp;
        $pkey = openssl_pkey_get_private($this->privateKey);
        if ($pkey === false) {
            throw new RuntimeException('DOKU: private key tidak valid / gagal dibaca.');
        }
        $signature = '';
        $ok = openssl_sign($stringToSign, $signature, $pkey, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('DOKU: gagal membuat tanda tangan asimetris.');
        }
        return base64_encode($signature);
    }

    /**
     * Tanda tangan SIMETRIS untuk request layanan.
     * stringToSign = METHOD:path:token:lower(hex(sha256(minify(body)))):timestamp
     */
    public function symmetricSignature(string $method, string $path, string $token, string $body, string $timestamp): string
    {
        $minified = json_encode(json_decode($body), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $bodyHash = strtolower(hash('sha256', $minified));
        $stringToSign = $method . ':' . $path . ':' . $token . ':' . $bodyHash . ':' . $timestamp;
        return base64_encode(hash_hmac('sha512', $stringToSign, $this->secretKey, true));
    }

    /** Verifikasi tanda tangan ASIMETRIS milik DOKU (dipakai gateway saat DOKU minta token). */
    public function verifyDokuSignature(string $requestSignature, string $timestamp): bool
    {
        if (empty($this->dokuPublicKey)) {
            return false;
        }
        $data = $this->clientId . '|' . $timestamp;
        $pub = openssl_pkey_get_public($this->dokuPublicKey);
        if ($pub === false) {
            return false;
        }
        $ok = openssl_verify($data, base64_decode($requestSignature), $pub, OPENSSL_ALGO_SHA256);
        return $ok === 1;
    }

    /* ------------------------------ JWT RS256 ------------------------------ */
    // Token B2B yang KITA terbitkan untuk DOKU = JWT RS256 (private key kita).
    // Diverifikasi memakai PUBLIC KEY kita (yang diupload ke DOKU sebagai Merchant Public Key).

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64urlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }

    /** Terbitkan JWT RS256 sebagai access token untuk DOKU. */
    public function issueJwtToken(int $expiresIn = 900, string $issuer = 'mooda'): string
    {
        $now = time();
        $header  = ['typ' => 'JWT', 'alg' => 'RS256'];
        $payload = ['iss' => $issuer, 'iat' => $now, 'exp' => $now + $expiresIn, 'clientId' => $this->clientId];

        $segments = self::b64url(json_encode($header)) . '.' . self::b64url(json_encode($payload));
        $pkey = openssl_pkey_get_private($this->privateKey);
        if ($pkey === false) {
            throw new RuntimeException('DOKU: private key tidak valid untuk JWT.');
        }
        $signature = '';
        if (! openssl_sign($segments, $signature, $pkey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('DOKU: gagal menandatangani JWT.');
        }
        return $segments . '.' . self::b64url($signature);
    }

    /** Verifikasi JWT RS256 (Bearer di notifikasi) memakai PUBLIC KEY kita. */
    public function verifyOwnJwt(string $bearer, ?string $ownPublicKey): bool
    {
        $bearer = trim(preg_replace('/^Bearer\s+/i', '', $bearer));
        $parts = explode('.', $bearer);
        if (count($parts) !== 3 || empty($ownPublicKey)) {
            return false;
        }
        [$h, $p, $s] = $parts;
        $pub = openssl_pkey_get_public($this->normalizeKey($ownPublicKey));
        if ($pub === false) {
            return false;
        }
        $ok = openssl_verify("$h.$p", self::b64urlDecode($s), $pub, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            return false;
        }
        $payload = json_decode(self::b64urlDecode($p), true);
        if (! is_array($payload) || (isset($payload['exp']) && $payload['exp'] < time())) {
            return false;
        }
        return true;
    }

    /* ======================= Panggilan HTTP DOKU ======================= */

    private function httpPost(string $url, array $headers, string $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('DOKU: cURL error: ' . $err);
        }
        $json = json_decode($raw, true);
        return ['status' => $code, 'body' => is_array($json) ? $json : [], 'raw' => $raw];
    }

    /** Access token B2B (di-cache di Redis sampai mendekati kedaluwarsa). */
    public function getAccessToken(bool $forceRefresh = false): string
    {
        $cacheKey = 'doku_b2b_token_' . md5($this->clientId . '|' . $this->baseUrl);

        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $timestamp = $this->timestamp();
        $signature = $this->asymmetricSignature($timestamp);
        $headers = [
            'Content-Type: application/json',
            'X-CLIENT-KEY: ' . $this->clientId,
            'X-TIMESTAMP: ' . $timestamp,
            'X-SIGNATURE: ' . $signature,
        ];
        $body = json_encode(['grantType' => 'client_credentials', 'additionalInfo' => []]);

        $res = $this->httpPost($this->baseUrl . self::ACCESS_TOKEN_PATH, $headers, $body);
        $token = $res['body']['accessToken'] ?? null;
        $rc    = $res['body']['responseCode'] ?? null;

        if (! $token || substr((string) $rc, 0, 3) !== '200') {
            Log::error('DOKU getAccessToken gagal', ['status' => $res['status'], 'body' => $res['body']]);
            throw new RuntimeException('DOKU: gagal mendapatkan access token. ' . ($res['body']['responseMessage'] ?? ('HTTP ' . $res['status'])));
        }

        $expiresIn = (int) ($res['body']['expiresIn'] ?? 900);
        Cache::put($cacheKey, $token, max(60, $expiresIn - 30));

        return $token;
    }

    /**
     * Buat Virtual Account Close Amount (SNAP).
     *
     * @param array{trx_id:string, customer_no:string, amount:int, name:string,
     *              email?:string, phone?:string, channel?:string, expiry_minutes?:int} $p
     * @return array Respons DOKU terdekode (responseCode, virtualAccountData, ...).
     */
    public function createVa(array $p): array
    {
        // partnerServiceId bisa di-override per channel (dari DokuVaChannel); fallback ke config .env.
        $psidSource = (string) ($p['partner_service_id'] ?? $this->partnerServiceId);
        if ($psidSource === '') {
            throw new RuntimeException('DOKU: partner_service_id (Merchant BIN) belum dikonfigurasi.');
        }

        // customerNo = prefix customer (opsional) + digit unik.
        $customerNo = $this->customerPrefix . preg_replace('/\D/', '', (string) $p['customer_no']);
        if ($customerNo === '' || strlen($customerNo) > 20) {
            throw new RuntimeException('DOKU: customer_no harus digit (<=20 termasuk prefix).');
        }

        // partnerServiceId WAJIB 8 karakter: strip spasi lalu left-pad dengan spasi.
        $psid    = str_pad(preg_replace('/\s/', '', $psidSource), 8, ' ', STR_PAD_LEFT);
        $vaNo    = $psid . $customerNo;                     // WAJIB = psid . customerNo
        $channel = $p['channel'] ?? $this->defaultChannel;
        $value   = number_format((int) $p['amount'], 2, '.', '');  // "50000.00"
        $expiry  = $this->timestamp(($p['expiry_minutes'] ?? 1440) * 60);

        $payload = [
            'partnerServiceId'   => $psid,
            'customerNo'         => $customerNo,
            'virtualAccountNo'   => $vaNo,
            'virtualAccountName' => $this->sanitizeName($p['name'] ?? 'Pelanggan'),
            'virtualAccountEmail'=> $p['email'] ?? '',
            'virtualAccountPhone'=> $p['phone'] ?? '',
            'trxId'              => (string) $p['trx_id'],
            'totalAmount'        => ['value' => $value, 'currency' => 'IDR'],
            'additionalInfo'     => [
                'channel'              => $channel,
                'virtualAccountConfig' => ['reusableStatus' => false],
            ],
            'virtualAccountTrxType' => 'C',    // Closed amount
            'expiredDate'           => $expiry,
        ];

        $body      = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $token     = $this->getAccessToken();
        $timestamp = $this->timestamp();
        $signature = $this->symmetricSignature('POST', self::CREATE_VA_PATH, $token, $body, $timestamp);

        $headers = [
            'Content-Type: application/json',
            'X-PARTNER-ID: ' . $this->clientId,
            'X-EXTERNAL-ID: ' . $this->externalId(),
            'X-TIMESTAMP: ' . $timestamp,
            'X-SIGNATURE: ' . $signature,
            'Authorization: Bearer ' . $token,
            'CHANNEL-ID: ' . $channel,
        ];

        $res = $this->httpPost($this->baseUrl . self::CREATE_VA_PATH, $headers, $body);

        if (($res['body']['responseCode'] ?? null) !== '2002700') {
            Log::warning('DOKU createVa non-success', ['status' => $res['status'], 'body' => $res['body']]);
        }
        return $res['body'] + ['_http_status' => $res['status']];
    }

    private function externalId(): string
    {
        return bin2hex(random_bytes(16)) . preg_replace('/\D/', '', $this->timestamp());
    }

    private function sanitizeName(string $name): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9.\-\/+,=_:\'@% ]/', ' ', $name);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        return $clean === '' ? 'Pelanggan' : mb_substr($clean, 0, 255);
    }
}
