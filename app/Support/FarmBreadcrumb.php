<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * JEJAK HALAMAN (breadcrumb) untuk modul peternakan.
 *
 * Dibangun dari NAMA ROUTE, bukan dari potongan alamat URL. Alasannya: alamat
 * seperti /admin/farm/warehouse/stock/12 tidak memberi tahu apa pun tentang
 * namanya, sedangkan nama route sudah menyatakan maksud halaman dengan tepat.
 *
 * Dengan cara ini seluruh halaman mendapat jejak & tautan ke menu induknya tanpa
 * perlu menyunting satu per satu berkas tampilan — dan halaman baru cukup
 * didaftarkan satu baris di sini.
 */
class FarmBreadcrumb
{
    /**
     * Peta halaman: nama route => [judul, grup menu, route induk].
     *
     * Grup menu mengikuti susunan menu navbar supaya jejaknya terasa sama dengan
     * cara orang menemukan halaman itu.
     */
    private const PETA = [
        // Dashboard
        'farm.dashboard'        => ['Dashboard', null, null],
        'dashboard'             => ['Dashboard', null, null],

        // Inventori
        'farm.stock-in.index'   => ['Barang Masuk', 'Inventori', null],
        'farm.stock-in.create'  => ['Catat Barang Masuk', 'Inventori', 'farm.stock-in.index'],
        'farm.stock-in.show'    => ['Detail Nota', 'Inventori', 'farm.stock-in.index'],
        'farm.stock-out.index'  => ['Barang Keluar', 'Inventori', null],
        'farm.stock-out.create' => ['Catat Barang Keluar', 'Inventori', 'farm.stock-out.index'],
        'farm.stock-out.show'   => ['Detail Nota', 'Inventori', 'farm.stock-out.index'],

        // Data Master
        'farm.suppliers.index'  => ['Supplier', 'Data Master', null],
        'farm.deposits.index'   => ['Deposit Supplier', 'Data Master', null],
        'farm.deposits.show'    => ['Kartu Deposit', 'Data Master', 'farm.deposits.index'],
        'farm.agents.index'     => ['Agen', 'Data Master', null],
        'farm.items.index'      => ['Item', 'Data Master', null],

        // Laporan
        'farm.reports.index'    => ['Laporan', null, null],

        // Operasional
        'farm.warehouse.index'  => ['Gudang', 'Operasional', null],
        'farm.warehouse.stock'  => ['Stok per Supplier', 'Operasional', 'farm.warehouse.index'],
        'farm.warehouse.stock.detail' => ['Rincian HPP', 'Operasional', 'farm.warehouse.stock'],
        'farm.eggs.index'       => ['Produksi Telur', 'Operasional', null],
        'farm.adjustments.index' => ['Penyesuaian Stok', 'Operasional', null],
        'farm.receivables.index' => ['Piutang Agen', 'Operasional', null],
        'farm.receivables.card' => ['Kartu Piutang', 'Operasional', 'farm.receivables.index'],
        'expenses.index'        => ['Pengeluaran', 'Operasional', null],

        // Resources & Pengaturan Sistem
        'users.index'           => ['User Management', 'Resources', null],
        'roles.index'           => ['Role Management', 'Resources', null],
        'settings.index'        => ['Setelan', 'Pengaturan Sistem', null],
        'billing.index'         => ['Langganan', 'Pengaturan Sistem', null],
        'download-app'          => ['Aplikasi', 'Pengaturan Sistem', null],
        'log-activity.index'    => ['Log Activity', 'Pengaturan Sistem', null],
        'account.index'         => ['Profil Saya', null, null],
    ];

    /**
     * Susun jejak halaman saat ini.
     *
     * @return array<int, array{judul: string, url: ?string}> kosong bila halaman
     *         tidak terdaftar — lebih baik tidak menampilkan apa pun daripada
     *         menampilkan jejak yang salah.
     */
    public static function jejak(): array
    {
        $nama = Route::currentRouteName();
        if (! $nama || ! isset(self::PETA[$nama])) {
            return [];
        }

        $rantai = [];
        $kunci = $nama;
        $pengaman = 0;   // jaga-jaga bila peta salah tulis dan saling menunjuk

        // Ditelusuri dari halaman sekarang ke atas, lalu dibalik.
        while ($kunci && isset(self::PETA[$kunci]) && $pengaman++ < 6) {
            [$judul, $grup, $induk] = self::PETA[$kunci];

            $rantai[] = [
                'judul' => $judul,
                // Halaman yang sedang dibuka tidak perlu tautan ke dirinya sendiri.
                'url'   => $kunci === $nama ? null : self::url($kunci),
            ];

            if (! $induk && $grup) {
                // Grup menu bukan halaman, jadi tidak punya tautan.
                $rantai[] = ['judul' => $grup, 'url' => null];
            }

            $kunci = $induk;
        }

        $rantai = array_reverse($rantai);

        // Beranda selalu jadi pijakan pertama.
        array_unshift($rantai, [
            'judul' => 'Dashboard',
            'url'   => $nama === 'farm.dashboard' ? null : self::url('farm.dashboard'),
        ]);

        // Halaman dashboard sendiri: cukup satu butir tanpa pengulangan.
        if ($nama === 'farm.dashboard' || $nama === 'dashboard') {
            return [['judul' => 'Dashboard', 'url' => null]];
        }

        return $rantai;
    }

    /** Route yang butuh parameter dilewati saja daripada melempar galat. */
    private static function url(string $nama): ?string
    {
        try {
            return route($nama);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
