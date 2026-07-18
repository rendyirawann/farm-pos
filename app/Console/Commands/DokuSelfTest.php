<?php

namespace App\Console\Commands;

use App\Services\Doku\DokuSnap;
use Illuminate\Console\Command;

/**
 * Uji kebenaran KRIPTO DOKU secara OFFLINE (tanpa jaringan, tanpa kredensial DOKU).
 * Membuktikan tanda tangan & JWT kita benar sebelum ada sandbox live.
 *
 *   php artisan doku:selftest
 */
class DokuSelfTest extends Command
{
    protected $signature = 'doku:selftest';
    protected $description = 'Self-test kripto DOKU SNAP (asimetris, simetris, JWT, verifikasi tanda tangan DOKU) — offline.';

    private int $pass = 0;
    private int $fail = 0;

    public function handle(): int
    {
        $this->line('<info>DOKU SNAP — Self-test kripto (offline)</info>');
        $this->newLine();

        // Buat pasangan kunci MERCHANT ephemeral + kunci DOKU ephemeral (simulasi).
        $merchant = $this->genKeyPair();
        $doku     = $this->genKeyPair();

        $cfg = [
            'client_id'       => 'BRN-TEST-CLIENT',
            'secret_key'      => 'SK-TEST-SECRET-KEY',
            'private_key'     => $merchant['private'],
            'own_public_key'  => $merchant['public'],
            'doku_public_key' => $doku['public'],   // seolah-olah ini public key milik DOKU
            'is_production'   => false,
            'partner_service_id' => '   12345',
            'channel'         => 'VIRTUAL_ACCOUNT_BANK_CIMB',
        ];
        $snap = new DokuSnap($cfg);
        $ts   = $snap->timestamp();

        /* 1. Asimetris (token B2B): sign lalu verifikasi dengan public key kita. */
        $sig = $snap->asymmetricSignature($ts);
        $verify = openssl_verify(
            'BRN-TEST-CLIENT|' . $ts,
            base64_decode($sig),
            openssl_pkey_get_public($merchant['public']),
            OPENSSL_ALGO_SHA256
        );
        $this->assert('Asimetris RSA-SHA256 (sign->verify)', $verify === 1);
        $this->assert('Asimetris tolak timestamp berbeda', openssl_verify(
            'BRN-TEST-CLIENT|' . $snap->timestamp(60),
            base64_decode($sig),
            openssl_pkey_get_public($merchant['public']),
            OPENSSL_ALGO_SHA256
        ) !== 1);

        /* 2. Simetris (HMAC-SHA512): deterministik + cocok perhitungan manual. */
        $body = json_encode(['a' => 1, 'b' => 'x/y']);
        $s1 = $snap->symmetricSignature('POST', '/p', 'TOKEN', $body, $ts);
        $s2 = $snap->symmetricSignature('POST', '/p', 'TOKEN', $body, $ts);
        $this->assert('Simetris deterministik (sama input -> sama output)', $s1 === $s2);

        $minified = json_encode(json_decode($body), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $manual = base64_encode(hash_hmac('sha512',
            'POST:/p:TOKEN:' . strtolower(hash('sha256', $minified)) . ':' . $ts,
            'SK-TEST-SECRET-KEY', true));
        $this->assert('Simetris cocok perhitungan manual (formula SNAP)', $s1 === $manual);
        $this->assert('Simetris berubah bila body berubah',
            $s1 !== $snap->symmetricSignature('POST', '/p', 'TOKEN', json_encode(['a' => 2]), $ts));

        /* 3. JWT RS256 (token yang kita terbitkan ke DOKU): issue -> verify. */
        $jwt = $snap->issueJwtToken(900, 'mooda');
        $this->assert('JWT RS256 issue->verify (public key kita)', $snap->verifyOwnJwt('Bearer ' . $jwt, $merchant['public']) === true);
        $this->assert('JWT ditolak bila diubah', $snap->verifyOwnJwt('Bearer ' . $jwt . 'x', $merchant['public']) === false);
        $this->assert('JWT ditolak dengan public key salah', $snap->verifyOwnJwt('Bearer ' . $jwt, $doku['public']) === false);

        /* 4. Verifikasi tanda tangan DOKU (saat DOKU panggil endpoint token kita). */
        // Simulasikan DOKU menandatangani "clientId|timestamp" dengan private key DOKU.
        $dokuSig = '';
        openssl_sign('BRN-TEST-CLIENT|' . $ts, $dokuSig, openssl_pkey_get_private($doku['private']), OPENSSL_ALGO_SHA256);
        $this->assert('Verifikasi tanda tangan DOKU sah (DOKU public key)', $snap->verifyDokuSignature(base64_encode($dokuSig), $ts) === true);
        $this->assert('Verifikasi tanda tangan DOKU tolak yang palsu', $snap->verifyDokuSignature(base64_encode('palsu'), $ts) === false);

        /* 5. Timestamp format ISO-8601 +07:00. */
        $this->assert('Timestamp format ISO-8601 +07:00', (bool) preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+07:00$/', $ts));

        $this->newLine();
        if ($this->fail === 0) {
            $this->info("SEMUA LULUS ✓  ({$this->pass} tes) — kripto DOKU benar & konsisten.");
            return self::SUCCESS;
        }
        $this->error("GAGAL: {$this->fail} tes gagal, {$this->pass} lulus.");
        return self::FAILURE;
    }

    private function assert(string $label, bool $ok): void
    {
        if ($ok) {
            $this->pass++;
            $this->line('  <fg=green>✓</> ' . $label);
        } else {
            $this->fail++;
            $this->line('  <fg=red>✗ ' . $label . '</>');
        }
    }

    private function genKeyPair(): array
    {
        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $private);
        $public = openssl_pkey_get_details($res)['key'];
        return ['private' => $private, 'public' => $public];
    }
}
