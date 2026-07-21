<?php

/**
 * Seksi REPEATER (daftar item dinamis) untuk CMS "Kelola Situs".
 * Beda dari field tetap: item bisa TAMBAH / UBAH / HAPUS. Tiap item punya ikon
 * (pilih dari set / emoji / upload gambar), judul, deskripsi, warna.
 * Disimpan di SiteOption "{site}.{key}" sebagai JSON array item.
 */

$COLORS = [
    'indigo'  => ['label' => 'Indigo',      'grad' => 'from-indigo-500 to-blue-500',  'hex' => '#4f46e5'],
    'rose'    => ['label' => 'Merah/Oranye', 'grad' => 'from-rose-500 to-orange-500',  'hex' => '#e11d48'],
    'violet'  => ['label' => 'Ungu',         'grad' => 'from-violet-500 to-purple-500', 'hex' => '#7c3aed'],
    'emerald' => ['label' => 'Hijau',        'grad' => 'from-emerald-500 to-teal-500',  'hex' => '#059669'],
    'amber'   => ['label' => 'Kuning',       'grad' => 'from-amber-500 to-yellow-500',  'hex' => '#f59e0b'],
    'slate'   => ['label' => 'Gelap',        'grad' => 'from-slate-700 to-slate-900',   'hex' => '#334155'],
    'sky'     => ['label' => 'Biru',         'grad' => 'from-sky-500 to-cyan-500',      'hex' => '#0ea5e9'],
    'pink'    => ['label' => 'Pink',         'grad' => 'from-pink-500 to-rose-500',     'hex' => '#db2777'],
];

return [
    'colors' => $COLORS,

    'sites' => [
        'landing' => [
            'features' => [
                'label'      => 'Kartu Fitur ("Yang bikin Mooda beda")',
                'item_label' => 'Fitur',
                'default'    => [
                    ['icon' => 'pos',     'image' => null, 'color' => 'indigo',  'title' => 'Kasir / POS Satu Layar', 'desc' => 'Input nama, pilih menu + add-on, dan bayar dalam satu halaman. Cepat & anti ribet.'],
                    ['icon' => 'kitchen', 'image' => null, 'color' => 'rose',    'title' => 'Kitchen Display',        'desc' => 'Pesanan tampil di layar dapur. Status masak per item terpantau, tidak ada yang terlewat.'],
                    ['icon' => 'menu',    'image' => null, 'color' => 'violet',  'title' => 'Menu & Add-on',          'desc' => 'Kelola kategori, menu, foto, promo, dan add-on (tambahan) beserta harganya dengan mudah.'],
                    ['icon' => 'payment', 'image' => null, 'color' => 'emerald', 'title' => 'Tunai & QRIS',           'desc' => 'Bayar di depan (struk lunas) atau di belakang. Tunai hitung kembalian otomatis, atau QRIS.'],
                    ['icon' => 'report',  'image' => null, 'color' => 'amber',   'title' => 'Laporan & Analitik',     'desc' => 'Laporan penjualan, produk terlaris, dan target penjualan harian—tercatat otomatis.'],
                    ['icon' => 'shield',  'image' => null, 'color' => 'slate',   'title' => 'Multi-Outlet & Tablet',  'desc' => 'Data tiap bisnis terisolasi penuh, hak akses staf, & bisa dipakai di web maupun aplikasi tablet.'],
                ],
            ],
            'whys' => [
                'label'      => 'Kartu "Kenapa Memilih Mooda?"',
                'item_label' => 'Alasan',
                'default'    => [
                    ['icon' => '⚡',  'image' => null, 'color' => 'indigo',  'title' => 'Cepat & Mudah',      'desc' => 'Antarmuka simpel, transaksi lebih cepat & efisien.'],
                    ['icon' => '🛡️', 'image' => null, 'color' => 'emerald', 'title' => 'Aman & Terpercaya',  'desc' => 'Data aman di cloud dengan backup otomatis.'],
                    ['icon' => '📈', 'image' => null, 'color' => 'amber',   'title' => 'Bertumbuh Bersama',  'desc' => 'Fitur lengkap untuk dukung bisnismu berkembang.'],
                    ['icon' => '🌐', 'image' => null, 'color' => 'violet',  'title' => 'Akses Dimana Saja',  'desc' => 'Kelola bisnis dari mana saja lewat semua perangkat.'],
                ],
            ],
        ],
    ],
];
