<?php

namespace App\Console\Commands;

use App\Services\Doku\DokuSnap;
use Illuminate\Console\Command;

/**
 * Verifikasi DOKU SNAP di SANDBOX secara terisolasi (tanpa menyentuh UI publik).
 *
 *   php artisan doku:sandbox-test                 # token + create VA percobaan
 *   php artisan doku:sandbox-test --amount=50000  # nominal berbeda
 *   php artisan doku:sandbox-test --token-only     # hanya uji access token B2B
 *
 * TIDAK memakai driver billing / tenant / DB. Murni memanggil API sandbox DOKU.
 */
class DokuSandboxTest extends Command
{
    protected $signature = 'doku:sandbox-test
                            {--amount=50000 : Nominal VA (rupiah, integer)}
                            {--channel= : Override channel VA (mis. VIRTUAL_ACCOUNT_BCA)}
                            {--customer= : Nomor customer (digit, tanpa prefix; prefix ditambah otomatis)}
                            {--partner= : Override partnerServiceId (digit)}
                            {--prefix= : Override prefix customer (kosongkan dengan --prefix=)}
                            {--token-only : Hanya uji access token B2B}';

    protected $description = 'Uji DOKU SNAP (sandbox): access token B2B + create Virtual Account. Terisolasi dari jalur publik.';

    public function handle(): int
    {
        $cfg = config('services.doku');
        // Override untuk eksperimen sandbox (tidak mengubah .env).
        if ($this->option('partner') !== null) $cfg['partner_service_id'] = $this->option('partner');
        if ($this->option('prefix') !== null)  $cfg['customer_prefix'] = $this->option('prefix');
        if ($this->option('channel'))          $cfg['channel'] = $this->option('channel');
        $doku = new DokuSnap($cfg);

        $this->line('<info>DOKU SNAP Sandbox Test</info>');
        $this->line('Base URL   : ' . $doku->baseUrl());
        $this->line('Production : ' . (($cfg['is_production'] ?? false) ? 'YA (!)' : 'tidak (sandbox)'));

        if (($cfg['is_production'] ?? false)) {
            $this->error('DOKU_IS_PRODUCTION=true — perintah ini khusus SANDBOX. Batalkan.');
            return self::FAILURE;
        }

        // Cek konfigurasi minimum.
        $missing = [];
        if (empty($cfg['client_id']))   $missing[] = 'DOKU_CLIENT_ID';
        if (empty($cfg['private_key'])) $missing[] = 'DOKU_PRIVATE_KEY';
        // Secret key hanya diperlukan untuk create VA (tanda tangan simetris), bukan token B2B.
        if (! $this->option('token-only') && empty($cfg['secret_key'])) $missing[] = 'DOKU_SECRET_KEY';
        if ($missing) {
            $this->error('Konfigurasi belum lengkap di .env: ' . implode(', ', $missing));
            return self::FAILURE;
        }

        // 1) Access token B2B (asimetris).
        $this->newLine();
        $this->line('<comment>[1/2] Meminta access token B2B ...</comment>');
        try {
            $token = $doku->getAccessToken(true);
            $this->info('✓ Access token diperoleh: ' . substr($token, 0, 24) . '...');
        } catch (\Throwable $e) {
            $this->error('✗ Gagal access token: ' . $e->getMessage());
            return self::FAILURE;
        }

        if ($this->option('token-only')) {
            $this->info('Selesai (token-only).');
            return self::SUCCESS;
        }

        // 2) Create VA (simetris).
        $this->newLine();
        $this->line('<comment>[2/2] Membuat Virtual Account percobaan ...</comment>');
        if (empty($cfg['partner_service_id'])) {
            $this->error('✗ DOKU_PARTNER_SERVICE_ID (prefix VA) belum diisi — wajib untuk create VA.');
            return self::FAILURE;
        }

        $amount = (int) $this->option('amount');
        $trxId  = 'SANDBOX-' . strtoupper(bin2hex(random_bytes(4)));
        // customerNo (tanpa prefix; DokuSnap menambah prefix customer otomatis). Default 8 digit.
        $customerNo = $this->option('customer') ?: str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

        try {
            $res = $doku->createVa([
                'trx_id'         => $trxId,
                'customer_no'    => $customerNo,
                'amount'         => $amount,
                'name'           => 'Sandbox Tester',
                'email'          => 'sandbox@mooda.id',
                'phone'          => '628123456789',
                'channel'        => $this->option('channel') ?: ($cfg['channel'] ?? null),
                'expiry_minutes' => 60,
            ]);
        } catch (\Throwable $e) {
            $this->error('✗ Exception create VA: ' . $e->getMessage());
            return self::FAILURE;
        }

        $rc  = $res['responseCode'] ?? '(none)';
        $msg = $res['responseMessage'] ?? '';
        $this->line('HTTP status : ' . ($res['_http_status'] ?? '?'));
        $this->line('responseCode: ' . $rc . '  ' . $msg);

        if ($rc === '2002700') {
            $va = $res['virtualAccountData'] ?? [];
            $this->info('✓ VA berhasil dibuat!');
            $this->table(['Field', 'Value'], [
                ['virtualAccountNo', $va['virtualAccountNo'] ?? '-'],
                ['trxId', $va['trxId'] ?? '-'],
                ['amount', ($va['totalAmount']['value'] ?? '-') . ' ' . ($va['totalAmount']['currency'] ?? '')],
                ['channel', $va['additionalInfo']['channel'] ?? '-'],
                ['expiredDate', $va['expiredDate'] ?? '-'],
                ['howToPayPage', $va['additionalInfo']['howToPayPage'] ?? '-'],
            ]);
            $this->newLine();
            $this->info('SANDBOX OK — token + create VA sukses. Jalur publik TIDAK tersentuh.');
            return self::SUCCESS;
        }

        $this->error('✗ create VA gagal. Respons lengkap:');
        $this->line(json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return self::FAILURE;
    }
}
