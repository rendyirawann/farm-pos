{{--
  Modal SETOR DEPOSIT — dipakai dua tempat:
    - halaman detail supplier  -> $supplier terisi, tujuan form tetap
    - daftar supplier          -> $supplier null, tujuan form diisi JS saat tombol diklik

  Disatukan supaya petugas melihat form yang sama persis di kedua tempat; kalau
  ada dua salinan, satu di antaranya pasti ketinggalan saat diubah.
--}}
@php $supplier = $supplier ?? null; @endphp

<div class="modal fade" id="m-topup" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" id="f-topup" enctype="multipart/form-data"
          action="{{ $supplier ? route('farm.deposits.topup', $supplier->id) : '' }}">
      @csrf
      <div class="modal-header py-4">
        <div>
          <h4 class="modal-title fs-5 mb-0">Tambah Deposit</h4>
          <span class="text-muted fs-8" id="topup-nama">{{ $supplier->name ?? '' }}</span>
        </div>
        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
          <i class="ki-outline ki-cross fs-2"></i></button>
      </div>
      <div class="modal-body">
        {{-- Saldo sekarang dipajang di dalam form: setoran hampir selalu diputuskan
             dari angka ini, jadi tidak boleh perlu menutup modal untuk melihatnya. --}}
        <div class="alert alert-light-primary border border-primary py-3 mb-4 fs-8" id="topup-saldo-kotak">
          Saldo sekarang: <span class="fs-6 fw-bold" id="topup-saldo">{{ isset($saldo)
              ? ($saldo < -0.01 ? '−' : '') . 'Rp ' . number_format(abs((float) $saldo), 0, ',', '.')
              : '—' }}</span><span id="topup-saldo-ket">{{ isset($saldo) && $saldo < -0.01
              ? ' — kita belum bayar sebanyak itu.' : '' }}</span>
        </div>

        <div class="mb-4">
          <label class="form-label fw-bold fs-7 required">Jumlah setoran (Rp)</label>
          <input type="text" name="amount" id="dep-amount" class="form-control form-control-lg js-money" required
                 inputmode="numeric" autocomplete="off" placeholder="0">
          <div class="fs-8 text-muted mt-1" id="dep-huruf"></div>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold fs-7 required">Tanggal transfer</label>
          <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold fs-7">Bukti transfer</label>
          {{-- capture="environment" membuat HP langsung membuka kamera; di laptop
               tetap menjadi pemilih berkas biasa (termasuk PDF dari m-banking). --}}
          <input type="file" name="proof" class="form-control" accept="image/*,application/pdf" capture="environment">
          <div class="fs-8 text-muted mt-1">Foto struk transfer atau berkas PDF dari m-banking. Maksimal 8 MB.</div>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold fs-7">Catatan</label>
          <input type="text" name="notes" class="form-control" maxlength="255" placeholder="mis. transfer BCA an. Budi">
        </div>
        <label class="form-check form-check-sm">
          <input class="form-check-input" type="checkbox" name="confirm_duplicate" value="1">
          <span class="form-check-label fs-8">Ya, ini setoran berbeda (lewati pemeriksaan setoran kembar)</span>
        </label>
      </div>
      <div class="modal-footer py-4">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-success fw-bold" id="btn-topup">Simpan Deposit</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  (function () {
    const form = document.getElementById('f-topup');
    if (!form) return;

    const SATUAN = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
                    'sepuluh', 'sebelas'];

    function huruf(n) {
      n = Math.floor(n);
      if (n < 12) return SATUAN[n];
      if (n < 20) return SATUAN[n - 10] + ' belas';
      if (n < 100) return SATUAN[Math.floor(n / 10)] + ' puluh ' + huruf(n % 10);
      if (n < 200) return 'seratus ' + huruf(n - 100);
      if (n < 1000) return SATUAN[Math.floor(n / 100)] + ' ratus ' + huruf(n % 100);
      if (n < 2000) return 'seribu ' + huruf(n - 1000);
      if (n < 1e6) return huruf(Math.floor(n / 1000)) + ' ribu ' + huruf(n % 1000);
      if (n < 1e9) return huruf(Math.floor(n / 1e6)) + ' juta ' + huruf(n % 1e6);
      return huruf(Math.floor(n / 1e9)) + ' miliar ' + huruf(n % 1e9);
    }

    // Jumlah ditulis ulang dalam huruf: satu digit nol yang kelebihan adalah selisih
    // sepuluh kali lipat, dan itu paling cepat tertangkap dengan membacanya.
    const el = document.getElementById('dep-amount');
    const out = document.getElementById('dep-huruf');
    el?.addEventListener('input', function () {
      const n = parseInt(String(el.value).replace(/[^\d]/g, ''), 10) || 0;
      out.textContent = n > 0 ? huruf(n).replace(/\s+/g, ' ').trim() + ' rupiah' : '';
    });

    // Tombol "Setor" pada daftar supplier: satu modal dipakai untuk semua baris,
    // tujuan form dan nama supplier diisi dari tombol yang membuka modal.
    //
    // Dipasang pada 'show.bs.modal', bukan pada klik tombol: peristiwa itu dikirim
    // Bootstrap tepat sebelum modal tampil, jadi urutannya pasti benar dan juga
    // bekerja bila modal dibuka lewat papan tuts, bukan klik.
    document.getElementById('m-topup')?.addEventListener('show.bs.modal', function (e) {
      const b = e.relatedTarget;
      // Di halaman detail, tombol pembuka tidak membawa data-aksi -> tujuan form
      // yang sudah dicetak server jangan disentuh.
      if (!b || !b.dataset || !b.dataset.aksi) return;

      form.setAttribute('action', b.dataset.aksi);
      document.getElementById('topup-nama').textContent = b.dataset.nama || '';

      // Hanya isi & warna yang diganti — elemennya jangan ditimpa innerHTML,
      // supaya masih ditemukan saat modal dibuka untuk supplier berikutnya.
      const kotak = document.getElementById('topup-saldo-kotak');
      const sel = document.getElementById('topup-saldo');
      const ket = document.getElementById('topup-saldo-ket');
      if (b.dataset.saldo !== undefined && sel) {
        const n = parseFloat(b.dataset.saldo) || 0;
        const minus = n < -0.01;
        sel.textContent = (minus ? '−Rp ' : 'Rp ') + Math.round(Math.abs(n)).toLocaleString('id-ID');
        sel.className = 'fs-6 fw-bold ' + (minus ? 'text-danger' : 'text-gray-900');
        if (ket) ket.textContent = minus ? ' — kita belum bayar sebanyak itu.' : '';
        kotak.className = 'alert border py-3 mb-4 fs-8 '
          + (minus ? 'alert-light-danger border-danger' : 'alert-light-primary border-primary');
      }

      // Kolom dikosongkan setiap kali dibuka supaya angka supplier sebelumnya
      // tidak ikut terkirim ke supplier lain.
      if (el) { el.value = ''; el.dataset.raw = ''; }
      if (out) out.textContent = '';
      form.querySelector('[name="proof"]').value = '';
      form.querySelector('[name="notes"]').value = '';
      form.querySelector('[name="confirm_duplicate"]').checked = false;
    });

    // Sekali-kirim & spinner ditangani penjaga global (partials/_submit_guard).
  })();
</script>
@endpush
