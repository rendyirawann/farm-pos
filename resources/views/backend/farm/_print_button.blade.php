{{-- Tombol koneksi printer thermal — dipakai halaman yang punya "Cetak Nota".
     Hanya muncul bila metode cetak memang butuh koneksi (Bluetooth / QZ Tray /
     native app); pada perangkat yang mencetak lewat dialog sistem, tombol ini
     tidak berguna sehingga sengaja disembunyikan. --}}
<button id="btn-printer" type="button" class="btn btn-sm btn-light-primary fw-bold d-none">
    <i class="ki-outline ki-printer fs-4 me-1"></i><span id="printer-label">Hubungkan Printer</span>
</button>
