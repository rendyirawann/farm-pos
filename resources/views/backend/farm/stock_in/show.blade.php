@extends('backend.layout.app')
@section('title', 'Nota Pembelian')
@section('content')
@php
  $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
  $num = fn($n,$d=0) => number_format((float)$n, $d, ',', '.');
@endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <div class="card card-flush mw-800px mx-auto">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Nota Pembelian</h3>
          <span class="text-muted fs-8">{{ $row->invoice_no }}</span>
        </div>
        <div class="card-toolbar gap-2">
          <a href="{{ route('farm.stock-in.index') }}" class="btn btn-sm btn-light">Kembali</a>
          <a href="{{ route('farm.stock-in.pdf', $row->id) }}" class="btn btn-sm btn-light-danger fw-bold">
            <i class="ki-outline ki-file-down fs-4"></i> Simpan PDF</a>
          @include('backend.farm._print_button')
          <button class="btn btn-sm btn-primary fw-bold" id="btn-cetak">
            <i class="ki-outline ki-printer fs-4"></i> Cetak Nota</button>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="row mb-5 fs-7">
          <div class="col-6">
            <div class="text-muted">Tanggal</div>
            <div class="fw-bold">{{ $row->date->locale('id')->translatedFormat('d F Y') }}</div>
          </div>
          <div class="col-6 text-end">
            <div class="text-muted">Supplier</div>
            <div class="fw-bold">{{ $row->supplier?->name ?? '—' }}</div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-3">Item</th><th class="text-center">Ekor</th><th class="text-center">Berat</th>
              <th class="text-end">Harga</th><th class="text-end pe-3">Subtotal</th>
            </tr></thead>
            <tbody>
            @foreach ($row->lines as $l)
              <tr>
                <td class="ps-3 fw-bold text-gray-800">{{ $l->item?->name }}</td>
                <td class="text-center">{{ $num($l->qty_ekor) }}</td>
                <td class="text-center">{{ $num($l->weight_kg, 2) }} kg</td>
                <td class="text-end">{{ $rp($l->unit_price) }} <span class="fs-9 text-muted">/{{ $l->price_basis }}</span></td>
                <td class="text-end pe-3 fw-bold">{{ $rp($l->subtotal) }}</td>
              </tr>
            @endforeach
            </tbody>
            <tfoot><tr class="fw-bold fs-4">
              <td colspan="4" class="text-end">TOTAL</td>
              <td class="text-end pe-3 text-success">{{ $rp($row->total) }}</td>
            </tr></tfoot>
          </table>
        </div>

        @if ($row->notes)
          <div class="alert alert-light-primary py-3 fs-8 mt-3"><b>Catatan:</b> {{ $row->notes }}</div>
        @endif
        {{-- ============ FOTO BON DARI SUPPLIER ============ --}}
        <div class="card bg-light border-0 mt-5">
          <div class="card-body p-5">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <div>
                <div class="fw-bold fs-6 text-gray-800">Bon Supplier</div>
                <div class="fs-8 text-muted">Bukti harga beli. Bon kertas mudah hilang — simpan di sini agar
                  harga pokok bisa dicek ulang kapan saja.<br>
                  <span class="d-none d-md-inline">Di laptop: unggah berkas hasil scan atau PDF dari supplier.</span>
                  <span class="d-md-none">Di HP: tombol ini langsung membuka kamera.</span></div>
              </div>
              <form method="POST" action="{{ route('farm.stock-in.photo', $row->id) }}"
                    enctype="multipart/form-data" id="f-bon" class="m-0">
                @csrf
                {{-- accept memuat PDF agar laptop bisa mengunggah scan/PDF dari supplier.
                     Atribut capture hanya berlaku di perangkat berkamera; di laptop diabaikan
                     dan yang terbuka adalah pemilih berkas biasa. --}}
                <input type="file" name="photos[]" id="in-bon"
                       accept="image/*,application/pdf" capture="environment"
                       multiple class="d-none">
                <button type="button" class="btn btn-sm btn-success fw-bold" id="btn-bon">
                  <i class="ki-outline ki-camera fs-4" id="ikon-bon"></i>
                  <span id="label-bon">Ambil Foto / Unggah Berkas</span>
                </button>
                <span class="fs-8 text-muted ms-2 d-none" id="bon-status"></span>
              </form>
            </div>

            @if ($row->hasPhotos())
              <div class="row g-3">
                @foreach ($row->photoList() as $foto)
                  <div class="col-6 col-md-4 col-lg-3">
                    <div class="position-relative border rounded overflow-hidden bg-white">
                      @if (\App\Models\Farm\StockIn::isImagePath($foto))
                        <a href="{{ asset('storage/' . $foto) }}" target="_blank" rel="noopener">
                          <img src="{{ asset('storage/' . $foto) }}" alt="Bon supplier"
                               style="width:100%;height:150px;object-fit:cover;display:block">
                        </a>
                      @else
                        {{-- PDF/scan: tampilkan kartu berkas agar tidak jadi gambar rusak --}}
                        <a href="{{ asset('storage/' . $foto) }}" target="_blank" rel="noopener"
                           class="d-flex flex-column align-items-center justify-content-center text-decoration-none"
                           style="height:150px;background:#fff5f5">
                          <i class="ki-outline ki-file fs-3x text-danger mb-2"></i>
                          <span class="fw-bold fs-8 text-gray-700">PDF</span>
                          <span class="fs-9 text-muted">{{ \Illuminate\Support\Str::limit(basename($foto), 18) }}</span>
                        </a>
                      @endif
                      <form method="POST" action="{{ route('farm.stock-in.photo.delete', $row->id) }}"
                            class="position-absolute" style="top:6px;right:6px"
                            onsubmit="return confirm('Hapus foto bon ini?')">
                        @csrf @method('DELETE')
                        <input type="hidden" name="path" value="{{ $foto }}">
                        <button class="btn btn-sm btn-icon btn-danger" style="width:28px;height:28px">
                          <i class="ki-outline ki-cross fs-5"></i></button>
                      </form>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="text-center text-muted py-6 fs-8 border border-dashed rounded">
                Belum ada foto bon. Tekan <b>Ambil / Pilih Foto</b> — di HP akan langsung membuka kamera.
              </div>
            @endif
          </div>
        </div>

        <div class="fs-8 text-muted mt-3">Dicatat oleh {{ $row->user?->name ?? '-' }} · {{ $row->created_at->format('d/m/Y H:i') }}</div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@include('backend.farm._print_script')

<script>
  /**
   * Kompresi foto bon DI PERANGKAT sebelum diunggah.
   *
   * Kamera HP menghasilkan 3-5 MB per foto. Petugas gudang sering memakai kuota
   * pribadi dan sinyal seadanya, jadi foto diperkecil ke lebar maks 1600px dan
   * dikompres ke JPEG 75% — cukup untuk membaca angka pada bon, tapi ukurannya
   * turun drastis (biasanya di bawah 400 KB).
   */
  (function () {
      var tombol = document.getElementById('btn-bon');
      var input  = document.getElementById('in-bon');
      var form   = document.getElementById('f-bon');
      var status = document.getElementById('bon-status');
      if (!tombol || !input || !form) return;

      var MAKS_SISI = 1600;
      var MUTU = 0.75;

      // Label menyesuaikan perangkat: berkamera -> menekankan "Ambil Foto",
      // laptop/desktop -> "Unggah Berkas" (bon dari supplier biasanya PDF/scan).
      var adaKamera = ('ontouchstart' in window) &&
          navigator.maxTouchPoints > 0 && /Android|iPhone|iPad/i.test(navigator.userAgent);
      var label = document.getElementById('label-bon');
      var ikon  = document.getElementById('ikon-bon');
      if (!adaKamera && label) {
          label.textContent = 'Unggah Berkas Bon';
          if (ikon) ikon.className = 'ki-outline ki-file-up fs-4';
          input.removeAttribute('capture');   // pemilih berkas biasa
      } else if (label) {
          label.textContent = 'Ambil Foto Bon';
      }

      tombol.addEventListener('click', function () { input.click(); });

      function kecilkan(file) {
          return new Promise(function (selesai) {
              if (!/^image\//.test(file.type)) { selesai(file); return; }
              var img = new Image();
              var url = URL.createObjectURL(file);
              img.onload = function () {
                  URL.revokeObjectURL(url);
                  var w = img.width, h = img.height;
                  if (Math.max(w, h) > MAKS_SISI) {
                      var r = MAKS_SISI / Math.max(w, h);
                      w = Math.round(w * r); h = Math.round(h * r);
                  }
                  var c = document.createElement('canvas');
                  c.width = w; c.height = h;
                  c.getContext('2d').drawImage(img, 0, 0, w, h);
                  c.toBlob(function (blob) {
                      // Kalau hasil kompresi malah lebih besar, pakai berkas aslinya.
                      selesai(blob && blob.size < file.size
                          ? new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' })
                          : file);
                  }, 'image/jpeg', MUTU);
              };
              img.onerror = function () { URL.revokeObjectURL(url); selesai(file); };
              img.src = url;
          });
      }

      input.addEventListener('change', async function () {
          if (!input.files || !input.files.length) return;
          status.classList.remove('d-none');
          status.textContent = 'Memproses foto…';
          tombol.disabled = true;

          var dt = new DataTransfer();
          var asal = 0, hasil = 0;
          for (var i = 0; i < input.files.length && i < 5; i++) {
              var f = input.files[i];
              asal += f.size;
              // PDF/scan diteruskan apa adanya — mengompresnya lewat canvas justru merusak.
              var kecil = /^image\//.test(f.type) ? await kecilkan(f) : f;
              hasil += kecil.size;
              dt.items.add(kecil);
          }
          input.files = dt.files;

          var kb = function (b) { return Math.round(b / 1024) + ' KB'; };
          status.textContent = 'Mengunggah ' + dt.files.length + ' foto (' + kb(asal) + ' → ' + kb(hasil) + ')…';
          form.submit();
      });
  })();
</script>

<script>
  function cetakNota() {
    fetch('{{ route('farm.stock-in.receipt', $row->id) }}', { headers: { Accept: 'application/json' } })
      .then(r => r.json())
      .then(d => window.MoodaPrint && window.MoodaPrint.print(window.farmNota(d)))
      .catch(() => alert('Gagal menyiapkan nota.'));
  }
  document.getElementById('btn-cetak').addEventListener('click', cetakNota);
  @if (session('autoprint')) setTimeout(cetakNota, 600); @endif
</script>
@endpush
