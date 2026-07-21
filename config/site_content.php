<?php

/**
 * Registry field CMS "Kelola Situs" (per-situs: mooda.id / blog / affiliate).
 * Dihasilkan dari konten asli landing; default = tampilan semula.
 * Nilai kustom disimpan di SiteOption "{site}.{key}" & dikelola via /admin/kelola-situs.
 */

return array (
  'sites' => 
  array (
    'landing' => 
    array (
      'label' => 'Situs Utama — mooda.id',
      'url' => 'https://mooda.id',
      'groups' => 
      array (
        0 => 
        array (
          'key' => 'nav',
          'label' => 'Navigasi & Logo',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'logo_putih',
              'label' => 'Logo Mooda versi putih (untuk latar gelap)',
              'type' => 'image',
              'default' => 'assets/media/logos/mooda-logo-white.png',
            ),
            1 => 
            array (
              'key' => 'logo_gelap',
              'label' => 'Logo Mooda versi gelap (untuk latar terang)',
              'type' => 'image',
              'default' => 'assets/media/logos/mooda-logo.png',
            ),
            2 => 
            array (
              'key' => 'nav_fitur',
              'label' => 'Menu navigasi: Fitur',
              'type' => 'text',
              'default' => 'Fitur',
            ),
            3 => 
            array (
              'key' => 'nav_harga',
              'label' => 'Menu navigasi: Harga',
              'type' => 'text',
              'default' => 'Harga',
            ),
            4 => 
            array (
              'key' => 'nav_partner',
              'label' => 'Menu navigasi: Partner',
              'type' => 'text',
              'default' => 'Partner',
            ),
            5 => 
            array (
              'key' => 'nav_masuk',
              'label' => 'Tombol Masuk',
              'type' => 'text',
              'default' => 'Masuk',
            ),
            6 => 
            array (
              'key' => 'nav_daftar',
              'label' => 'Tombol Daftar',
              'type' => 'text',
              'default' => 'Daftar',
            ),
          ),
        ),
        1 => 
        array (
          'key' => 'hero',
          'label' => 'Hero (Bagian Atas)',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'hero_bg',
              'label' => 'Gambar latar hero',
              'type' => 'image',
              'default' => 'assets/media/landing/hero.jpg',
            ),
            1 => 
            array (
              'key' => 'hero_badge',
              'label' => 'Badge kecil di atas judul',
              'type' => 'text',
              'default' => 'Sistem Kasir Restoran All-in-One',
            ),
            2 => 
            array (
              'key' => 'hero_judul',
              'label' => 'Judul utama hero (boleh HTML: <span>, &)',
              'type' => 'textarea',
              'default' => 'Kelola restoran lebih <span class="lp-gradient-text">cepat, rapi &amp; cuan</span>',
            ),
            3 => 
            array (
              'key' => 'hero_subjudul',
              'label' => 'Paragraf penjelasan hero',
              'type' => 'textarea',
              'default' => 'Satukan kasir, dapur (kitchen display), nomor antrian, dan laporan penjualan dalam satu sistem. Untuk restoran, cafe & warung — bisa multi-outlet.',
            ),
            4 => 
            array (
              'key' => 'hero_cta_daftar',
              'label' => 'Tombol utama hero (daftar)',
              'type' => 'text',
              'default' => 'Coba Gratis Sekarang',
            ),
            5 => 
            array (
              'key' => 'hero_cta_demo',
              'label' => 'Tombol sekunder hero (demo)',
              'type' => 'text',
              'default' => 'Lihat Demo Via WA',
            ),
            6 => 
            array (
              'key' => 'hero_scroll_hint',
              'label' => 'Petunjuk scroll',
              'type' => 'text',
              'default' => 'Scroll untuk menjelajah',
            ),
          ),
        ),
        2 => 
        array (
          'key' => 'fitur',
          'label' => 'Section Fitur (judul)',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'fitur_eyebrow',
              'label' => 'Label kecil di atas judul',
              'type' => 'text',
              'default' => 'Fitur',
            ),
            1 => 
            array (
              'key' => 'fitur_judul',
              'label' => 'Judul section fitur',
              'type' => 'text',
              'default' => 'Semua yang Anda Butuhkan Untuk Mengelola Bisnis',
            ),
            2 => 
            array (
              'key' => 'fitur_subjudul',
              'label' => 'Subjudul section fitur',
              'type' => 'textarea',
              'default' => 'Dari pelanggan datang sampai laporan akhir bulan, semua tercatat otomatis.',
            ),
          ),
        ),
        3 => 
        array (
          'key' => 'dashboard',
          'label' => 'Section Dashboard',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'dashboard_eyebrow',
              'label' => 'Label kecil di atas judul',
              'type' => 'text',
              'default' => 'Dashboard',
            ),
            1 => 
            array (
              'key' => 'dashboard_judul',
              'label' => 'Judul section dashboard',
              'type' => 'text',
              'default' => 'Pantau Bisnis Anda dari Satu Dashboard',
            ),
            2 => 
            array (
              'key' => 'dashboard_subjudul',
              'label' => 'Subjudul section dashboard',
              'type' => 'textarea',
              'default' => 'Semua angka penting — omzet, transaksi, produk terlaris, hingga target harian — dalam satu layar yang mudah dibaca.',
            ),
            3 => 
            array (
              'key' => 'dashboard_img',
              'label' => 'Gambar dashboard (laptop & ponsel)',
              'type' => 'image',
              'default' => 'assets/media/landing/section2.webp',
            ),
          ),
        ),
        4 => 
        array (
          'key' => 'kenapa',
          'label' => 'Section Kenapa Mooda',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'kenapa_eyebrow',
              'label' => 'Label kecil di atas judul',
              'type' => 'text',
              'default' => 'Kenapa Mooda',
            ),
            1 => 
            array (
              'key' => 'kenapa_judul',
              'label' => 'Judul section',
              'type' => 'text',
              'default' => 'Kenapa Memilih Mooda?',
            ),
            2 => 
            array (
              'key' => 'kenapa_subjudul',
              'label' => 'Subjudul section',
              'type' => 'textarea',
              'default' => 'Alasan ratusan bisnis kuliner mempercayakan operasional hariannya ke Mooda.',
            ),
          ),
        ),
        6 => 
        array (
          'key' => 'showcase',
          'label' => 'Section Lihat Aplikasinya',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'showcase_eyebrow',
              'label' => 'Label kecil di atas judul',
              'type' => 'text',
              'default' => 'Lihat Aplikasinya',
            ),
            1 => 
            array (
              'key' => 'showcase_judul',
              'label' => 'Judul section showcase',
              'type' => 'text',
              'default' => 'Kasir cepat, tampilan bersih',
            ),
            2 => 
            array (
              'key' => 'showcase_subjudul',
              'label' => 'Subjudul section showcase',
              'type' => 'textarea',
              'default' => 'Antarmuka POS satu layar — pilih menu, tambah keranjang, bayar. Dibuat untuk jam sibuk.',
            ),
            3 => 
            array (
              'key' => 'showcase_img_1',
              'label' => 'Gambar showcase 1 (kasir di tablet)',
              'type' => 'image',
              'default' => 'assets/media/landing/section3.webp',
            ),
            4 => 
            array (
              'key' => 'showcase_caption_1',
              'label' => 'Keterangan gambar 1 (boleh HTML: <span>, &)',
              'type' => 'textarea',
              'default' => 'Kasir / POS di Tablet <span>Pilih menu &amp; bayar dalam satu layar.</span>',
            ),
            5 => 
            array (
              'key' => 'showcase_img_2',
              'label' => 'Gambar showcase 2 (perangkat mobile)',
              'type' => 'image',
              'default' => 'assets/media/landing/section3_1.webp',
            ),
            6 => 
            array (
              'key' => 'showcase_caption_2',
              'label' => 'Keterangan gambar 2 (boleh HTML: <span>, &)',
              'type' => 'textarea',
              'default' => 'Jalan di HP &amp; Tablet <span>Buka lewat aplikasi atau browser, di mana saja.</span>',
            ),
          ),
        ),
        7 => 
        array (
          'key' => 'harga',
          'label' => 'Section Harga',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'harga_eyebrow',
              'label' => 'Label kecil di atas judul',
              'type' => 'text',
              'default' => 'Harga',
            ),
            1 => 
            array (
              'key' => 'harga_judul',
              'label' => 'Judul section harga',
              'type' => 'text',
              'default' => 'Paket sederhana & transparan',
            ),
            2 => 
            array (
              'key' => 'harga_subjudul',
              'label' => 'Subjudul section harga',
              'type' => 'textarea',
              'default' => 'Pilih sesuai skala bisnis — dari deposit bayar-sesuai-pakai hingga enterprise. Tanpa biaya tersembunyi.',
            ),
            3 => 
            array (
              'key' => 'harga_starter_nama',
              'label' => 'Paket Starter: nama',
              'type' => 'text',
              'default' => 'Starter',
            ),
            4 => 
            array (
              'key' => 'harga_starter_tagline',
              'label' => 'Paket Starter: tagline',
              'type' => 'textarea',
              'default' => 'Bayar sesuai pemakaian (deposit saldo) — cocok untuk baru mulai / musiman.',
            ),
            5 => 
            array (
              'key' => 'harga_starter_harga',
              'label' => 'Paket Starter: label harga',
              'type' => 'text',
              'default' => 'Deposit',
            ),
            6 => 
            array (
              'key' => 'harga_starter_satuan',
              'label' => 'Paket Starter: satuan harga',
              'type' => 'text',
              'default' => '/isi saldo',
            ),
            7 => 
            array (
              'key' => 'harga_starter_note',
              'label' => 'Paket Starter: catatan top-up',
              'type' => 'text',
              'default' => 'Top-up mulai Rp 25.000 · potong Rp 169 / transaksi',
            ),
            8 => 
            array (
              'key' => 'harga_starter_cta',
              'label' => 'Paket Starter: tombol',
              'type' => 'text',
              'default' => 'Mulai Deposit',
            ),
            9 => 
            array (
              'key' => 'harga_badge_populer',
              'label' => 'Badge paket populer',
              'type' => 'text',
              'default' => 'Populer',
            ),
            10 => 
            array (
              'key' => 'harga_satuan_bulan',
              'label' => 'Satuan harga bulanan',
              'type' => 'text',
              'default' => '/bulan',
            ),
            11 => 
            array (
              'key' => 'harga_custom_badge',
              'label' => 'Paket Customize: badge',
              'type' => 'text',
              'default' => 'Fleksibel',
            ),
            12 => 
            array (
              'key' => 'harga_custom_nama',
              'label' => 'Paket Customize: nama',
              'type' => 'text',
              'default' => 'Customize',
            ),
            13 => 
            array (
              'key' => 'harga_custom_tagline',
              'label' => 'Paket Customize: tagline',
              'type' => 'textarea',
              'default' => 'Rakit paketmu sendiri — kontrak 2 tahun, fitur menyesuaikan bisnis.',
            ),
            14 => 
            array (
              'key' => 'harga_custom_harga',
              'label' => 'Paket Customize: label harga',
              'type' => 'text',
              'default' => 'Custom',
            ),
            15 => 
            array (
              'key' => 'harga_custom_satuan',
              'label' => 'Paket Customize: satuan harga',
              'type' => 'text',
              'default' => '/per 2 tahun',
            ),
            16 => 
            array (
              'key' => 'harga_custom_cta',
              'label' => 'Paket Customize: tombol',
              'type' => 'text',
              'default' => 'Konsultasi via WhatsApp',
            ),
          ),
        ),
        8 => 
        array (
          'key' => 'partner',
          'label' => 'Section Partner',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'partner_eyebrow',
              'label' => 'Label kecil di atas judul',
              'type' => 'text',
              'default' => 'Partner Kami',
            ),
            1 => 
            array (
              'key' => 'partner_judul',
              'label' => 'Judul section partner',
              'type' => 'text',
              'default' => 'Sudah berlangganan bersama Mooda',
            ),
            2 => 
            array (
              'key' => 'partner_subjudul',
              'label' => 'Subjudul section partner',
              'type' => 'textarea',
              'default' => 'Bisnis kuliner yang telah mempercayakan operasional hariannya ke Mooda.',
            ),
          ),
        ),
        9 => 
        array (
          'key' => 'cta',
          'label' => 'CTA Penutup',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'cta_judul',
              'label' => 'Judul kartu CTA',
              'type' => 'text',
              'default' => 'Siap Membuat Bisnis Anda Lebih Mudah?',
            ),
            1 => 
            array (
              'key' => 'cta_subjudul',
              'label' => 'Deskripsi kartu CTA',
              'type' => 'textarea',
              'default' => 'Bergabung dengan ratusan bisnis lainnya dan rasakan kemudahan menggunakan Mooda.',
            ),
            2 => 
            array (
              'key' => 'cta_tombol_daftar',
              'label' => 'Tombol daftar CTA',
              'type' => 'text',
              'default' => 'Mulai Gratis Sekarang →',
            ),
            3 => 
            array (
              'key' => 'cta_tombol_kontak',
              'label' => 'Tombol kontak CTA',
              'type' => 'text',
              'default' => 'Hubungi Kami',
            ),
          ),
        ),
        10 => 
        array (
          'key' => 'footer',
          'label' => 'Footer',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'footer_logo',
              'label' => 'Logo footer (putih)',
              'type' => 'image',
              'default' => 'assets/media/logos/mooda-logo-white.png',
            ),
            1 => 
            array (
              'key' => 'footer_tagline',
              'label' => 'Tagline footer',
              'type' => 'textarea',
              'default' => 'POS modern untuk Cafe, Restoran, Coffee Shop, Bakery dan UMKM.',
            ),
            2 => 
            array (
              'key' => 'footer_kontak_judul',
              'label' => 'Judul kolom Kontak',
              'type' => 'text',
              'default' => 'Kontak (CP)',
            ),
            3 => 
            array (
              'key' => 'footer_kontak_nomor',
              'label' => 'Nomor kontak (teks tampil)',
              'type' => 'text',
              'default' => '0823-6221-1676',
            ),
            4 => 
            array (
              'key' => 'footer_email_judul',
              'label' => 'Judul kolom Email',
              'type' => 'text',
              'default' => 'Email',
            ),
            5 => 
            array (
              'key' => 'footer_email',
              'label' => 'Alamat email (teks tampil)',
              'type' => 'text',
              'default' => 'admin.moodaid@gmail.com',
            ),
            6 => 
            array (
              'key' => 'footer_website_judul',
              'label' => 'Judul kolom Website',
              'type' => 'text',
              'default' => 'Website',
            ),
            7 => 
            array (
              'key' => 'footer_website',
              'label' => 'Alamat website (teks tampil)',
              'type' => 'text',
              'default' => 'mooda.id',
            ),
            8 => 
            array (
              'key' => 'footer_copyright',
              'label' => 'Teks hak cipta (setelah tahun)',
              'type' => 'text',
              'default' => 'Mooda. Seluruh hak cipta dilindungi.',
            ),
            9 => 
            array (
              'key' => 'footer_bottom_tagline',
              'label' => 'Tagline bawah footer (boleh HTML: &)',
              'type' => 'textarea',
              'default' => 'POS modern untuk Cafe, Resto, Coffee Shop, Bakery &amp; UMKM',
            ),
          ),
        ),
        11 => 
        array (
          'key' => 'fab',
          'label' => 'Tombol Melayang (Mobile)',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'fab_pesan',
              'label' => 'Tombol melayang: Pesan',
              'type' => 'text',
              'default' => 'Pesan Sekarang',
            ),
            1 => 
            array (
              'key' => 'fab_kontak',
              'label' => 'Tombol melayang: Kontak',
              'type' => 'text',
              'default' => 'Contact Us',
            ),
          ),
        ),
      ),
    ),
    'blog' => 
    array (
      'label' => 'Blog — blog.mooda.id',
      'url' => 'https://blog.mooda.id',
      'groups' => 
      array (
        0 => 
        array (
          'key' => 'nav',
          'label' => 'Navbar (Header)',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'nav_logo_huruf',
              'label' => 'Huruf logo (kotak gradient)',
              'type' => 'text',
              'default' => 'M',
            ),
            1 => 
            array (
              'key' => 'nav_brand',
              'label' => 'Nama brand di navbar (boleh HTML)',
              'type' => 'text',
              'default' => 'Mooda <span class="text-indigo-600">Blog</span>',
            ),
            2 => 
            array (
              'key' => 'nav_beranda',
              'label' => 'Menu: Beranda',
              'type' => 'text',
              'default' => 'Beranda',
            ),
            3 => 
            array (
              'key' => 'nav_langganan',
              'label' => 'Tombol: Langganan',
              'type' => 'text',
              'default' => 'Langganan',
            ),
          ),
        ),
        1 => 
        array (
          'key' => 'hero',
          'label' => 'Hero (Beranda)',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'hero_badge',
              'label' => 'Badge kecil di atas judul',
              'type' => 'text',
              'default' => 'Blog Resmi Mooda',
            ),
            1 => 
            array (
              'key' => 'hero_judul',
              'label' => 'Judul utama hero (boleh HTML: <br>, <span>)',
              'type' => 'textarea',
              'default' => 'Insight &amp; Inspirasi<br>untuk <span class="text-indigo-600">Bisnis Modern</span>',
            ),
            2 => 
            array (
              'key' => 'hero_deskripsi',
              'label' => 'Deskripsi hero',
              'type' => 'textarea',
              'default' => 'Dapatkan informasi terbaru, tips praktis, dan berita menarik seputar dunia bisnis, POS, dan manajemen usaha.',
            ),
            3 => 
            array (
              'key' => 'hero_tombol_utama',
              'label' => 'Tombol utama',
              'type' => 'text',
              'default' => 'Jelajahi Artikel',
            ),
            4 => 
            array (
              'key' => 'hero_tombol_kedua',
              'label' => 'Tombol kedua',
              'type' => 'text',
              'default' => 'Tentang Mooda',
            ),
            5 => 
            array (
              'key' => 'hero_preview_url',
              'label' => 'URL di preview browser',
              'type' => 'text',
              'default' => 'blog.mooda.id',
            ),
            6 => 
            array (
              'key' => 'hero_preview_judul',
              'label' => 'Judul di dalam preview browser',
              'type' => 'text',
              'default' => 'Artikel Terbaru',
            ),
          ),
        ),
        2 => 
        array (
          'key' => 'trending',
          'label' => 'Section Trending / Artikel Pilihan',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'trending_eyebrow',
              'label' => 'Label kecil (eyebrow)',
              'type' => 'text',
              'default' => 'Trending Now',
            ),
            1 => 
            array (
              'key' => 'trending_judul',
              'label' => 'Judul section',
              'type' => 'text',
              'default' => 'Artikel Pilihan Minggu Ini',
            ),
            2 => 
            array (
              'key' => 'trending_link',
              'label' => 'Link \'Lihat Semua\'',
              'type' => 'text',
              'default' => 'Lihat Semua Artikel →',
            ),
          ),
        ),
        3 => 
        array (
          'key' => 'kategori',
          'label' => 'Section Jelajahi Kategori',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'kategori_judul',
              'label' => 'Judul section',
              'type' => 'text',
              'default' => 'Jelajahi Berdasarkan Kategori',
            ),
            1 => 
            array (
              'key' => 'kategori_satuan_artikel',
              'label' => 'Satuan setelah angka jumlah artikel',
              'type' => 'text',
              'default' => 'Artikel',
            ),
          ),
        ),
        4 => 
        array (
          'key' => 'populer',
          'label' => 'Section Artikel Populer & Spotlight',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'populer_eyebrow',
              'label' => 'Label kecil (eyebrow)',
              'type' => 'text',
              'default' => 'Artikel Populer',
            ),
            1 => 
            array (
              'key' => 'populer_judul',
              'label' => 'Judul section',
              'type' => 'text',
              'default' => 'Paling Banyak Dibaca',
            ),
            2 => 
            array (
              'key' => 'populer_baca_selengkapnya',
              'label' => 'Tombol pada kartu spotlight',
              'type' => 'text',
              'default' => 'Baca Selengkapnya →',
            ),
          ),
        ),
        5 => 
        array (
          'key' => 'semua_artikel',
          'label' => 'Section Semua Artikel',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'semua_judul',
              'label' => 'Judul section',
              'type' => 'text',
              'default' => 'Semua Artikel',
            ),
          ),
        ),
        6 => 
        array (
          'key' => 'newsletter',
          'label' => 'Section Newsletter',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'newsletter_judul',
              'label' => 'Judul (boleh HTML: &amp;)',
              'type' => 'text',
              'default' => 'Dapatkan Artikel &amp; Tips Terbaru',
            ),
            1 => 
            array (
              'key' => 'newsletter_deskripsi',
              'label' => 'Deskripsi',
              'type' => 'textarea',
              'default' => 'Berlangganan newsletter kami dan dapatkan insight terbaru langsung ke email Anda setiap minggunya.',
            ),
            2 => 
            array (
              'key' => 'newsletter_placeholder',
              'label' => 'Placeholder input email',
              'type' => 'text',
              'default' => 'Masukkan email Anda',
            ),
            3 => 
            array (
              'key' => 'newsletter_tombol',
              'label' => 'Tombol berlangganan',
              'type' => 'text',
              'default' => 'Langganan',
            ),
            4 => 
            array (
              'key' => 'newsletter_sukses',
              'label' => 'Pesan setelah submit',
              'type' => 'text',
              'default' => 'Terima kasih!',
            ),
          ),
        ),
        7 => 
        array (
          'key' => 'kategori_page',
          'label' => 'Halaman Kategori (list per kategori)',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'kat_link_semua',
              'label' => 'Link kembali ke semua artikel',
              'type' => 'text',
              'default' => '← Semua artikel',
            ),
            1 => 
            array (
              'key' => 'kat_badge',
              'label' => 'Badge di atas judul kategori',
              'type' => 'text',
              'default' => 'Kategori',
            ),
            2 => 
            array (
              'key' => 'kat_subjudul',
              'label' => 'Sub-judul di bawah nama kategori',
              'type' => 'text',
              'default' => 'Kumpulan artikel di kategori ini.',
            ),
            3 => 
            array (
              'key' => 'kat_kosong',
              'label' => 'Teks saat kategori kosong',
              'type' => 'text',
              'default' => 'Belum ada artikel di kategori ini.',
            ),
          ),
        ),
        8 => 
        array (
          'key' => 'umum',
          'label' => 'Label Umum & Kondisi Kosong',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'label_menit_baca',
              'label' => 'Label estimasi waktu baca (dipakai di kartu)',
              'type' => 'text',
              'default' => 'menit baca',
            ),
            1 => 
            array (
              'key' => 'kosong_judul',
              'label' => 'Judul saat belum ada artikel',
              'type' => 'text',
              'default' => 'Belum ada artikel',
            ),
            2 => 
            array (
              'key' => 'kosong_deskripsi',
              'label' => 'Deskripsi saat belum ada artikel',
              'type' => 'text',
              'default' => 'Nantikan tulisan-tulisan bermanfaat dari tim Mooda.',
            ),
          ),
        ),
        9 => 
        array (
          'key' => 'footer',
          'label' => 'Footer',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'footer_brand',
              'label' => 'Nama brand di footer (boleh HTML)',
              'type' => 'text',
              'default' => 'Mooda <span class="text-indigo-400">Blog</span>',
            ),
            1 => 
            array (
              'key' => 'footer_deskripsi',
              'label' => 'Deskripsi footer',
              'type' => 'textarea',
              'default' => 'Wawasan & tips praktis mengembangkan bisnis Anda di era digital — dari operasional sampai memilih produk digital yang tepat.',
            ),
            2 => 
            array (
              'key' => 'footer_kol_kategori',
              'label' => 'Judul kolom: Kategori',
              'type' => 'text',
              'default' => 'Kategori',
            ),
            3 => 
            array (
              'key' => 'footer_kol_navigasi',
              'label' => 'Judul kolom: Navigasi',
              'type' => 'text',
              'default' => 'Navigasi',
            ),
            4 => 
            array (
              'key' => 'footer_nav_semua_artikel',
              'label' => 'Link navigasi: Semua Artikel',
              'type' => 'text',
              'default' => 'Semua Artikel',
            ),
            5 => 
            array (
              'key' => 'footer_nav_tentang',
              'label' => 'Link navigasi: Tentang Mooda',
              'type' => 'text',
              'default' => 'Tentang Mooda',
            ),
            6 => 
            array (
              'key' => 'footer_nav_produk',
              'label' => 'Link navigasi: Produk & Harga',
              'type' => 'text',
              'default' => 'Produk & Harga',
            ),
            7 => 
            array (
              'key' => 'footer_kol_kontak',
              'label' => 'Judul kolom: Kontak',
              'type' => 'text',
              'default' => 'Kontak',
            ),
            8 => 
            array (
              'key' => 'footer_telepon',
              'label' => 'Nomor telepon (teks tampil)',
              'type' => 'text',
              'default' => '0823-6221-1676',
            ),
            9 => 
            array (
              'key' => 'footer_email',
              'label' => 'Alamat email (teks tampil)',
              'type' => 'text',
              'default' => 'hello@mooda.id',
            ),
            10 => 
            array (
              'key' => 'footer_website',
              'label' => 'Website (teks tampil)',
              'type' => 'text',
              'default' => 'blog.mooda.id',
            ),
            11 => 
            array (
              'key' => 'footer_copyright',
              'label' => 'Teks copyright (setelah tahun)',
              'type' => 'text',
              'default' => 'Mooda — Produk Digital untuk Bisnis Anda',
            ),
            12 => 
            array (
              'key' => 'footer_bottom_link',
              'label' => 'Link kanan bawah',
              'type' => 'text',
              'default' => 'mooda.id',
            ),
          ),
        ),
      ),
    ),
    'affiliate' => 
    array (
      'label' => 'Affiliate — affiliate.mooda.id',
      'url' => 'https://affiliate.mooda.id',
      'groups' => 
      array (
        0 => 
        array (
          'key' => 'hero',
          'label' => 'Hero (Bagian Atas)',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'hero_badge',
              'label' => 'Badge Kecil di Atas Judul',
              'type' => 'text',
              'default' => '⭐ PROGRAM AFFILIATE',
            ),
            1 => 
            array (
              'key' => 'hero_judul',
              'label' => 'Judul Utama Hero (boleh pakai HTML)',
              'type' => 'textarea',
              'default' => 'Bagikan Mooda,<br>Dapatkan <span class="text-indigo-600">Komisi</span><br>Setiap Bulan',
            ),
            2 => 
            array (
              'key' => 'hero_deskripsi',
              'label' => 'Deskripsi Hero (sebelum nilai komisi)',
              'type' => 'textarea',
              'default' => 'Promosikan Mooda POS ke audiens Anda dan dapatkan komisi',
            ),
            3 => 
            array (
              'key' => 'hero_tombol_utama',
              'label' => 'Teks Tombol Utama',
              'type' => 'text',
              'default' => 'Daftar Gratis',
            ),
            4 => 
            array (
              'key' => 'hero_tombol_kedua',
              'label' => 'Teks Tombol Kedua',
              'type' => 'text',
              'default' => 'Lihat Cara Kerja',
            ),
            5 => 
            array (
              'key' => 'hero_join_prefix',
              'label' => 'Teks Sebelum Jumlah Affiliate',
              'type' => 'text',
              'default' => 'Bergabung dengan',
            ),
            6 => 
            array (
              'key' => 'hero_join_jumlah',
              'label' => 'Angka Jumlah Affiliate (statistik)',
              'type' => 'text',
              'default' => '1.200+',
            ),
            7 => 
            array (
              'key' => 'hero_join_suffix',
              'label' => 'Teks Sesudah Jumlah Affiliate',
              'type' => 'text',
              'default' => 'affiliate lainnya',
            ),
            8 => 
            array (
              'key' => 'hero_mockup_judul',
              'label' => 'Judul Mockup Dashboard',
              'type' => 'text',
              'default' => 'Affiliate Dashboard',
            ),
            9 => 
            array (
              'key' => 'hero_mockup_stat1_label',
              'label' => 'Mockup - Label Statistik 1',
              'type' => 'text',
              'default' => 'Total Komisi',
            ),
            10 => 
            array (
              'key' => 'hero_mockup_stat1_nilai',
              'label' => 'Mockup - Nilai Statistik 1',
              'type' => 'text',
              'default' => 'Rp 12.450.000',
            ),
            11 => 
            array (
              'key' => 'hero_mockup_stat2_label',
              'label' => 'Mockup - Label Statistik 2',
              'type' => 'text',
              'default' => 'Klik',
            ),
            12 => 
            array (
              'key' => 'hero_mockup_stat2_nilai',
              'label' => 'Mockup - Nilai Statistik 2',
              'type' => 'text',
              'default' => '18.230',
            ),
            13 => 
            array (
              'key' => 'hero_mockup_stat3_label',
              'label' => 'Mockup - Label Statistik 3',
              'type' => 'text',
              'default' => 'Konversi',
            ),
            14 => 
            array (
              'key' => 'hero_mockup_stat3_nilai',
              'label' => 'Mockup - Nilai Statistik 3',
              'type' => 'text',
              'default' => '320',
            ),
            15 => 
            array (
              'key' => 'hero_mockup_performa',
              'label' => 'Mockup - Label Grafik Performa',
              'type' => 'text',
              'default' => 'Performa',
            ),
          ),
        ),
        1 => 
        array (
          'key' => 'fitur',
          'label' => '3 Fitur Unggulan',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'fitur1_judul_prefix',
              'label' => 'Fitur 1 - Awalan Judul (diikuti nilai komisi)',
              'type' => 'text',
              'default' => 'Komisi',
            ),
            1 => 
            array (
              'key' => 'fitur1_deskripsi',
              'label' => 'Fitur 1 - Deskripsi',
              'type' => 'textarea',
              'default' => 'Dapatkan komisi menarik setiap bulan.',
            ),
            2 => 
            array (
              'key' => 'fitur2_judul',
              'label' => 'Fitur 2 - Judul',
              'type' => 'text',
              'default' => 'Tracking Real-time',
            ),
            3 => 
            array (
              'key' => 'fitur2_deskripsi',
              'label' => 'Fitur 2 - Deskripsi',
              'type' => 'textarea',
              'default' => 'Pantau klik, konversi, dan komisi secara real-time.',
            ),
            4 => 
            array (
              'key' => 'fitur3_judul',
              'label' => 'Fitur 3 - Judul',
              'type' => 'text',
              'default' => 'Pembayaran Tepat Waktu',
            ),
            5 => 
            array (
              'key' => 'fitur3_deskripsi',
              'label' => 'Fitur 3 - Deskripsi',
              'type' => 'textarea',
              'default' => 'Komisi dibayarkan otomatis setiap bulan.',
            ),
          ),
        ),
        2 => 
        array (
          'key' => 'cara_kerja',
          'label' => 'Cara Kerja (4 Langkah)',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'cara_kerja_judul',
              'label' => 'Judul Section',
              'type' => 'text',
              'default' => 'Cara Kerja',
            ),
            1 => 
            array (
              'key' => 'langkah1_judul',
              'label' => 'Langkah 1 - Judul',
              'type' => 'text',
              'default' => 'Daftar',
            ),
            2 => 
            array (
              'key' => 'langkah1_deskripsi',
              'label' => 'Langkah 1 - Deskripsi',
              'type' => 'textarea',
              'default' => 'Daftar gratis sebagai affiliate Mooda.',
            ),
            3 => 
            array (
              'key' => 'langkah2_judul',
              'label' => 'Langkah 2 - Judul',
              'type' => 'text',
              'default' => 'Bagikan Link',
            ),
            4 => 
            array (
              'key' => 'langkah2_deskripsi',
              'label' => 'Langkah 2 - Deskripsi',
              'type' => 'textarea',
              'default' => 'Bagikan link affiliate Anda ke audiens.',
            ),
            5 => 
            array (
              'key' => 'langkah3_judul',
              'label' => 'Langkah 3 - Judul',
              'type' => 'text',
              'default' => 'Dapatkan Konversi',
            ),
            6 => 
            array (
              'key' => 'langkah3_deskripsi',
              'label' => 'Langkah 3 - Deskripsi',
              'type' => 'textarea',
              'default' => 'Setiap kali ada yang berlangganan melalui link Anda.',
            ),
            7 => 
            array (
              'key' => 'langkah4_judul',
              'label' => 'Langkah 4 - Judul',
              'type' => 'text',
              'default' => 'Terima Komisi',
            ),
            8 => 
            array (
              'key' => 'langkah4_deskripsi',
              'label' => 'Langkah 4 - Deskripsi',
              'type' => 'textarea',
              'default' => 'Dapatkan komisi otomatis setiap bulan.',
            ),
          ),
        ),
        3 => 
        array (
          'key' => 'potensi',
          'label' => 'Potensi Penghasilan',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'potensi_judul',
              'label' => 'Judul Section',
              'type' => 'text',
              'default' => 'Potensi Penghasilan Anda',
            ),
            1 => 
            array (
              'key' => 'potensi1_atas',
              'label' => 'Kartu 1 - Teks Atas',
              'type' => 'text',
              'default' => 'Komisi Hingga',
            ),
            2 => 
            array (
              'key' => 'potensi1_sub',
              'label' => 'Kartu 1 - Teks Bawah',
              'type' => 'text',
              'default' => 'Setiap Langganan',
            ),
            3 => 
            array (
              'key' => 'potensi2_angka',
              'label' => 'Kartu 2 - Angka Besar (statistik)',
              'type' => 'text',
              'default' => '1.200+',
            ),
            4 => 
            array (
              'key' => 'potensi2_sub',
              'label' => 'Kartu 2 - Teks Bawah',
              'type' => 'text',
              'default' => 'Affiliate Aktif',
            ),
            5 => 
            array (
              'key' => 'potensi3_angka',
              'label' => 'Kartu 3 - Angka Besar (statistik)',
              'type' => 'text',
              'default' => 'Rp 2 Miliar+',
            ),
            6 => 
            array (
              'key' => 'potensi3_sub',
              'label' => 'Kartu 3 - Teks Bawah',
              'type' => 'text',
              'default' => 'Total Komisi Dibayarkan',
            ),
          ),
        ),
        4 => 
        array (
          'key' => 'cta',
          'label' => 'Banner Ajakan (CTA)',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'cta_judul',
              'label' => 'Judul CTA (boleh pakai HTML)',
              'type' => 'textarea',
              'default' => 'Mulai Hasilkan<br class="hidden sm:block"> Bersama Mooda!',
            ),
            1 => 
            array (
              'key' => 'cta_deskripsi',
              'label' => 'Deskripsi CTA',
              'type' => 'textarea',
              'default' => 'Gabung sekarang dan mulai dapatkan komisi dari setiap langganan.',
            ),
            2 => 
            array (
              'key' => 'cta_tombol',
              'label' => 'Teks Tombol CTA',
              'type' => 'text',
              'default' => 'Daftar Affiliate Gratis',
            ),
          ),
        ),
        5 => 
        array (
          'key' => 'nav',
          'label' => 'Navigasi (Header)',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'nav_brand',
              'label' => 'Nama Brand di Header (boleh pakai HTML)',
              'type' => 'text',
              'default' => 'Mooda <span class="text-indigo-600">Affiliate</span>',
            ),
            1 => 
            array (
              'key' => 'nav_beranda',
              'label' => 'Menu - Beranda',
              'type' => 'text',
              'default' => 'Beranda',
            ),
            2 => 
            array (
              'key' => 'nav_fitur',
              'label' => 'Menu - Fitur',
              'type' => 'text',
              'default' => 'Fitur',
            ),
            3 => 
            array (
              'key' => 'nav_affiliate',
              'label' => 'Menu - Affiliate',
              'type' => 'text',
              'default' => 'Affiliate',
            ),
            4 => 
            array (
              'key' => 'nav_blog',
              'label' => 'Menu - Blog',
              'type' => 'text',
              'default' => 'Blog',
            ),
            5 => 
            array (
              'key' => 'nav_faq',
              'label' => 'Menu - FAQ',
              'type' => 'text',
              'default' => 'FAQ',
            ),
            6 => 
            array (
              'key' => 'nav_dashboard',
              'label' => 'Tombol - Dashboard',
              'type' => 'text',
              'default' => 'Dashboard',
            ),
            7 => 
            array (
              'key' => 'nav_keluar',
              'label' => 'Tombol - Keluar',
              'type' => 'text',
              'default' => 'Keluar',
            ),
            8 => 
            array (
              'key' => 'nav_masuk',
              'label' => 'Tombol - Masuk',
              'type' => 'text',
              'default' => 'Masuk',
            ),
            9 => 
            array (
              'key' => 'nav_daftar',
              'label' => 'Tombol - Daftar Affiliate',
              'type' => 'text',
              'default' => 'Daftar Affiliate',
            ),
          ),
        ),
        6 => 
        array (
          'key' => 'footer',
          'label' => 'Footer',
          'fields' => 
          array (
            0 => 
            array (
              'key' => 'footer_brand',
              'label' => 'Nama Brand Footer',
              'type' => 'text',
              'default' => 'mooda',
            ),
            1 => 
            array (
              'key' => 'footer_deskripsi',
              'label' => 'Deskripsi Footer',
              'type' => 'textarea',
              'default' => 'Solusi POS modern untuk Cafe, Resto, Bakery, dan berbagai jenis usaha.',
            ),
            2 => 
            array (
              'key' => 'footer_kolom_produk',
              'label' => 'Judul Kolom - Produk',
              'type' => 'text',
              'default' => 'Produk',
            ),
            3 => 
            array (
              'key' => 'footer_produk_fitur',
              'label' => 'Produk - Fitur POS',
              'type' => 'text',
              'default' => 'Fitur POS',
            ),
            4 => 
            array (
              'key' => 'footer_produk_harga',
              'label' => 'Produk - Harga',
              'type' => 'text',
              'default' => 'Harga',
            ),
            5 => 
            array (
              'key' => 'footer_produk_integrasi',
              'label' => 'Produk - Integrasi',
              'type' => 'text',
              'default' => 'Integrasi',
            ),
            6 => 
            array (
              'key' => 'footer_produk_update',
              'label' => 'Produk - Update',
              'type' => 'text',
              'default' => 'Update',
            ),
            7 => 
            array (
              'key' => 'footer_kolom_perusahaan',
              'label' => 'Judul Kolom - Perusahaan',
              'type' => 'text',
              'default' => 'Perusahaan',
            ),
            8 => 
            array (
              'key' => 'footer_perusahaan_tentang',
              'label' => 'Perusahaan - Tentang Kami',
              'type' => 'text',
              'default' => 'Tentang Kami',
            ),
            9 => 
            array (
              'key' => 'footer_perusahaan_blog',
              'label' => 'Perusahaan - Blog',
              'type' => 'text',
              'default' => 'Blog',
            ),
            10 => 
            array (
              'key' => 'footer_perusahaan_kontak',
              'label' => 'Perusahaan - Kontak',
              'type' => 'text',
              'default' => 'Kontak',
            ),
            11 => 
            array (
              'key' => 'footer_kolom_kontak',
              'label' => 'Judul Kolom - Kontak',
              'type' => 'text',
              'default' => 'Kontak',
            ),
            12 => 
            array (
              'key' => 'footer_kontak_telepon',
              'label' => 'Kontak - Nomor Telepon (teks)',
              'type' => 'text',
              'default' => '0823-6221-1676',
            ),
            13 => 
            array (
              'key' => 'footer_kontak_email',
              'label' => 'Kontak - Email (teks)',
              'type' => 'text',
              'default' => 'hello@mooda.id',
            ),
            14 => 
            array (
              'key' => 'footer_kontak_situs',
              'label' => 'Kontak - Alamat Situs (teks)',
              'type' => 'text',
              'default' => 'affiliate.mooda.id',
            ),
            15 => 
            array (
              'key' => 'footer_copyright',
              'label' => 'Teks Copyright (setelah tahun)',
              'type' => 'text',
              'default' => 'Mooda. All rights reserved.',
            ),
          ),
        ),
      ),
    ),
  ),
);
