<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Plan Deposit / Saldo — nilai DEFAULT
    |--------------------------------------------------------------------------
    | Ini hanya nilai awal. Nilai yang dipakai sistem dibaca dari DB
    | (tabel deposit_settings & deposit_tiers) lewat App\Tenancy\DepositConfig,
    | dan bisa diubah Superadmin kapan saja. Jika baris DB belum ada,
    | DepositConfig memakai nilai default di bawah ini.
    |
    | Saldo bernilai Rupiah (Rp1 = 1 saldo). Tiap transaksi (pesanan yang
    | diselesaikan) memotong `fee_per_transaction` dari saldo.
    */

    // Fitur plan deposit aktif/tidak (menyembunyikan pilihan deposit bila false).
    'enabled' => env('DEPOSIT_ENABLED', true),

    // Batas maksimum SALDO. Top-up yang membuat saldo melewati ini ditolak.
    // Bila null/0 (dikosongkan di Superadmin) => tanpa batas (unlimited).
    'max_points' => 70000,

    // Potongan saldo per transaksi (pesanan diselesaikan). Bisa diubah Superadmin.
    'fee_per_transaction' => 169,

    // Saldo hangus bila tidak ada AKTIVITAS (top-up/pemakaian) selama sekian hari
    // berturut-turut. Setiap pemakaian me-reset hitungan hari ini.
    'expiry_days' => 10,

    // Batas peringatan: bila saldo <= nilai ini, tampilkan peringatan merah "segera top up".
    'warning_threshold' => 10000,

    // Minimal nominal top-up (paket terkecil).
    'min_deposit' => 25000,

    // Nominal top-up WAJIB pertama kali untuk mengaktifkan plan deposit (akun baru).
    'initial_topup' => 50000,

    // Info top-up manual (bila pembayaran otomatis bermasalah): transfer bank + chat WA,
    // lalu Superadmin kreditkan saldo. Bisa diubah Superadmin.
    'manual_wa'   => env('DEPOSIT_MANUAL_WA', '6282362211676'),
    'manual_bank' => env('DEPOSIT_MANUAL_BANK', ''),

    // Paket top-up default: nominal (Rupiah dibayar) => saldo diterima (sudah termasuk bonus).
    'tiers' => [
        ['amount' => 25000, 'points' => 30000],  // +20%
        ['amount' => 50000, 'points' => 62500],  // +25%
    ],
];
