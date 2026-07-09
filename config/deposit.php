<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Plan Deposit / Poin — nilai DEFAULT
    |--------------------------------------------------------------------------
    | Ini hanya nilai awal. Nilai yang dipakai sistem dibaca dari DB
    | (tabel deposit_settings & deposit_tiers) lewat App\Tenancy\DepositConfig,
    | dan bisa diubah Superadmin kapan saja. Jika baris DB belum ada,
    | DepositConfig memakai nilai default di bawah ini.
    |
    | Poin bernilai Rupiah (1 poin = Rp1). Tiap transaksi (pesanan yang
    | diselesaikan) memotong `fee_per_transaction` poin.
    */

    // Fitur plan deposit aktif/tidak (menyembunyikan pilihan deposit bila false).
    'enabled' => env('DEPOSIT_ENABLED', true),

    // Batas maksimum SALDO poin. Top-up yang membuat saldo melewati ini ditolak.
    // 70.000 agar tier terbesar (bayar 50.000 => 62.500 poin) tetap muat dari saldo nol.
    // Bila null/0 (dikosongkan di Superadmin) => tanpa batas (unlimited).
    'max_points' => 70000,

    // Potongan poin per transaksi (pesanan diselesaikan). Bisa diubah Superadmin.
    'fee_per_transaction' => 150,

    // Poin hangus bila tidak ada aktivitas (top-up/pemakaian) selama sekian hari.
    'expiry_days' => 60,

    // Minimal nominal top-up LANJUTAN (setelah aktivasi). Nominal bebas di atas ini.
    'min_deposit' => 5000,

    // Nominal top-up WAJIB pertama kali untuk mengaktifkan plan deposit (akun baru).
    // Dapat 62.500 poin (dari tier 50.000). Akun demo yang sudah punya saldo dikecualikan.
    'initial_topup' => 50000,

    // Info top-up manual (bila pembayaran otomatis bermasalah): transfer bank + chat WA,
    // lalu Superadmin kreditkan poin. Bisa diubah Superadmin.
    'manual_wa'   => env('DEPOSIT_MANUAL_WA', '6282362211676'),
    'manual_bank' => env('DEPOSIT_MANUAL_BANK', ''),

    // Tier top-up default: nominal (Rupiah dibayar) => poin diterima (sudah termasuk bonus).
    'tiers' => [
        ['amount' => 5000,  'points' => 5500],   // +10%
        ['amount' => 10000, 'points' => 11500],  // +15%
        ['amount' => 25000, 'points' => 30000],  // +20%
        ['amount' => 50000, 'points' => 62500],  // +25%
    ],
];
