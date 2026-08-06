{{-- Gaya responsif MOODA STOK. Disertakan di halaman farm lewat @include. --}}
@push('stylesheets')
<style>
  /* =========================================================================
     1. LAYAR BESAR (monitor lebar / TV)
     container-xxl bawaan berhenti di 1320px; di layar 1600px+ isinya jadi
     kolom sempit dengan margin kosong sangat lebar. Dilebarkan bertahap.
     ========================================================================= */
  @media (min-width: 1600px) {
      .app-container.container-xxl { max-width: 1520px; }
  }
  @media (min-width: 1920px) {
      .app-container.container-xxl { max-width: 1760px; }
      .farm-kpi .fs-2hx { font-size: 2.9rem !important; }
  }
  @media (min-width: 2400px) {  /* TV 4K: naikkan skala teks agar terbaca dari jauh */
      .app-container.container-xxl { max-width: 2200px; }
      .farm-kpi .fs-2hx { font-size: 3.6rem !important; }
      .farm-kpi .fs-7   { font-size: 1.05rem !important; }
  }

  /* =========================================================================
     2. TABEL INPUT TRANSAKSI DI LAYAR SEMPIT
     Form Barang Masuk/Keluar punya 8-10 kolom (~1200px). Di HP, menggeser
     tabel ke samping sambil mengetik angka hampir mustahil. Di bawah 768px
     tiap baris diubah jadi KARTU bertumpuk; label diambil dari data-label.
     ========================================================================= */
  @media (max-width: 767.98px) {
      .farm-form-table thead { display: none; }

      .farm-form-table tbody tr {
          display: block;
          border: 1px solid #e4e6ef;
          border-radius: 12px;
          padding: 12px 14px;
          margin-bottom: 14px;
          background: #fff;
      }
      .farm-form-table tbody td {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 12px;
          width: 100%;
          border: 0 !important;
          padding: 7px 0 !important;
      }
      .farm-form-table tbody td::before {
          content: attr(data-label);
          flex: 0 0 40%;
          font-size: 11px;
          font-weight: 700;
          letter-spacing: .04em;
          text-transform: uppercase;
          color: #7e8299;
      }
      /* Sel tanpa label (mis. tombol hapus) memakai lebar penuh */
      .farm-form-table tbody td:not([data-label])::before { content: none; }
      .farm-form-table tbody td > * { flex: 1 1 auto; max-width: 100%; }
      .farm-form-table tbody td.farm-cell-action { justify-content: flex-end; }
      .farm-form-table tbody td.farm-cell-action > * { flex: 0 0 auto; }

      .farm-form-table tfoot tr { display: block; padding: 4px 2px; }
      .farm-form-table tfoot td {
          display: flex; justify-content: space-between; align-items: center;
          width: 100%; border: 0 !important; padding: 6px 0 !important; text-align: right;
      }
      .farm-form-table tfoot td::before {
          content: attr(data-label);
          font-size: 11px; font-weight: 700; text-transform: uppercase; color: #7e8299;
      }
      .farm-form-table tfoot td[colspan] { display: none; }

      /* Sasaran sentuh: minimal 44px agar nyaman dipakai berdiri di gudang */
      .farm-form-table .form-control,
      .farm-form-table .form-select { min-height: 44px; font-size: 15px; }

      /* Tombol utama melebar penuh di HP */
      .farm-actions .btn { width: 100%; }
      .farm-actions { flex-direction: column; gap: .5rem !important; }
  }

  /* =========================================================================
     3. HERO DASHBOARD
     Filter periode + 2 tombol aksi berdesakan di layar sempit.
     ========================================================================= */
  @media (max-width: 991.98px) {
      .farm-hero .card-body { padding: 18px !important; }
      .farm-hero h2 { font-size: 1.35rem; }
      .farm-hero .farm-hero-actions { width: 100%; }
      .farm-hero .farm-hero-actions > form { width: 100%; }
      .farm-hero .farm-hero-actions .btn { flex: 1 1 auto; }
  }
  @media (max-width: 575.98px) {
      .farm-hero .farm-hero-actions .form-control,
      .farm-hero .farm-hero-actions .form-select { min-width: 0 !important; width: 100%; }
      .farm-hero .farm-hero-actions .btn-group { width: 100%; }
      .farm-hero .farm-hero-actions .btn-group .btn { flex: 1 1 0; }
  }

  /* =========================================================================
     4. TABEL DAFTAR (riwayat) — tetap menggeser samping, tapi kolom pertama
     dibekukan supaya nomor nota selalu terlihat saat menggeser.
     ========================================================================= */
  @media (max-width: 767.98px) {
      .farm-list-table { font-size: 12.5px; }
      .farm-list-table th, .farm-list-table td { white-space: nowrap; }
      .farm-list-table thead th:first-child,
      .farm-list-table tbody td:first-child {
          position: sticky; left: 0; z-index: 2;
          background: #fff; box-shadow: 2px 0 4px -2px rgba(0,0,0,.12);
      }
      .farm-list-table thead th:first-child { background: #f9f9f9; }
  }

  /* =========================================================================
     4b. TABEL DAFTAR YANG AKSINYA SERING DIPAKAI (mis. Deposit)
     Kalau daftarnya dibiarkan menggeser ke samping, tombol aksi di kolom
     terakhir baru terjangkau setelah menggeser — padahal justru itu yang paling
     sering ditekan. Di bawah 768px barisnya diubah jadi KARTU bertumpuk.
     ========================================================================= */
  @media (max-width: 767.98px) {
      .farm-card-table { font-size: 13px; }
      .farm-card-table thead { display: none; }
      .farm-card-table tbody tr {
          display: block;
          border: 1px solid #e4e6ef;
          border-radius: 12px;
          padding: 12px 14px;
          margin-bottom: 14px;
          background: #fff;
      }
      .farm-card-table tbody td {
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 10px;
          width: 100%;
          border: 0 !important;
          padding: 5px 0 !important;
          text-align: right !important;
          white-space: normal !important;
      }
      .farm-card-table tbody td::before {
          content: attr(data-label);
          flex: 0 0 42%;
          text-align: left;
          font-size: 10.5px;
          font-weight: 700;
          letter-spacing: .04em;
          text-transform: uppercase;
          color: #7e8299;
      }
      .farm-card-table tbody td:not([data-label])::before { content: none; }
      /* Sel kosong (mis. kolom yang tidak terpakai) tidak perlu memakan tempat */
      .farm-card-table tbody td:empty { display: none; }
      /* Baris aksi: tombolnya melebar penuh supaya mudah ditekan dengan jempol */
      .farm-card-table tbody td[data-label="Aksi"] { display: block; margin-top: 8px; }
      .farm-card-table tbody td[data-label="Aksi"]::before { display: none; }
      .farm-card-table tbody td[data-label="Aksi"] .btn,
      .farm-card-table tbody td[data-label="Aksi"] form { width: 100%; margin-bottom: 6px; }
      .farm-card-table tbody td[data-label="Aksi"] .btn { min-height: 42px; }
  }

  /* Kartu KPI: 2 kolom di HP sudah diatur grid; rapikan jarak & ukuran angka */
  @media (max-width: 575.98px) {
      .farm-kpi .card-body { padding: 1rem !important; }
      .farm-kpi .fs-2hx { font-size: 1.5rem !important; }
      .farm-kpi .fs-7 { font-size: .8rem !important; }
  }

  /* =========================================================================
     5. NAVBAR ATAS DI LAYAR MENENGAH (1024px - 1400px)
     Menu farm punya 9 item; pada lebar tablet-lanskap & laptop, baris menu
     mendorong lebar dokumen jadi 1539px sehingga SELURUH halaman bisa digeser
     ke samping. Diverifikasi lewat render Chrome pada 1024px & 1366px.
     Solusi: izinkan membungkus + rapatkan jarak, jangan memaksa satu baris.
     ========================================================================= */
  @media (min-width: 992px) and (max-width: 1399.98px) {
      #kt_app_header_menu { flex-wrap: wrap; }
      #kt_app_header_menu > .menu-item > .menu-link { padding-left: .6rem !important; padding-right: .6rem !important; }
      #kt_app_header_menu .menu-title { font-size: 13px; }
      .app-header-wrapper { min-width: 0; overflow: visible; }
  }

  /* =========================================================================
     6. BARIS FORM SEBARIS (inline) DI HP
     Form "Buka Gudang" dan filter Piutang memakai lebar tetap (280px/180px)
     yang melewati tepi layar 360-390px. Dibuat menumpuk & selebar layar.
     ========================================================================= */
  @media (max-width: 575.98px) {
      .farm-inline-form { display: flex !important; flex-direction: column; width: 100%; gap: .5rem; }
      .farm-inline-form > * { width: 100% !important; min-width: 0 !important; max-width: 100% !important; }

      .card-toolbar { width: 100%; }
      .card-toolbar form { flex-wrap: wrap; width: 100%; gap: .5rem !important; }
      .card-toolbar form > * { flex: 1 1 100%; width: 100% !important; min-width: 0 !important; }
      .card-header { flex-wrap: wrap; gap: .75rem; }
  }

  /* =========================================================================
     8. TAB GAYA MAP ARSIP (dipakai daftar Barang Keluar)
     Tab diletakkan menempel pada tepi atas kartu. Tab yang aktif dibuat
     menyatu dengan badan kartu — itulah yang membuat mata langsung tahu
     daftar mana yang sedang terbuka, bukan sekadar teks yang lebih tebal.
     ========================================================================= */
  .farm-tab-arsip { margin-bottom: 0; }
  .farm-tab-arsip .nav-link {
      border-top-left-radius: .75rem !important;
      border-top-right-radius: .75rem !important;
      margin-right: 4px;
      transition: background-color .15s ease, color .15s ease;
  }
  .farm-tab-arsip .nav-link:not(.active):hover {
      background: rgba(255, 255, 255, .6);
      color: var(--bs-primary) !important;
  }
  /* Tab aktif: sambungkan ke badan kartu dengan menutup garis batasnya. */
  .farm-tab-arsip .nav-link.active {
      position: relative;
      box-shadow: 0 -2px 0 0 var(--bs-primary) inset,
                  0 -1px 0 0 var(--bs-body-bg);
  }
  .farm-tab-arsip .nav-link.active::after {
      content: '';
      position: absolute; left: 0; right: 0; bottom: -1px; height: 2px;
      background: var(--bs-body-bg);
  }
  @media (max-width: 575.98px) {
      .farm-tab-arsip .nav-link { padding-left: .9rem !important; padding-right: .9rem !important; font-size: .95rem !important; }
  }
</style>
@endpush
