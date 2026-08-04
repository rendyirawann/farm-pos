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
          {{-- Simpan PDF & Cetak Nota disembunyikan sementara untuk BARANG MASUK:
               bukti pembelian yang dipakai adalah bon asli dari supplier (diunggah di
               bawah), bukan nota cetakan sendiri. Route-nya tetap ada bila nanti dipakai. --}}
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

        {{-- ============ STATUS PEMBAYARAN KE SUPPLIER ============ --}}
        @php
          $real    = $row->realization;
          $netto   = $row->netTotal();
          $sisa    = $row->remainingToPay();
          $nilaiRl = (float) ($real->value ?? 0);
        @endphp
        <div class="card border-0 mt-5 {{ $row->isPaid() ? 'bg-light-success' : 'bg-light-warning' }}">
          <div class="card-body p-5">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <div class="fw-bold fs-6 text-gray-800">
                  Pembayaran ke Supplier
                  <span class="badge badge-{{ $row->isPaid() ? 'success' : 'warning' }} ms-2">
                    {{ $row->isPaid() ? 'Lunas' : 'Belum Lunas' }}</span>
                </div>
                <div class="fs-8 text-muted mt-1">
                  Nota {{ $rp($row->total) }}
                  @if (abs($nilaiRl) > 0.01)
                    {{ $nilaiRl > 0 ? '−' : '+' }} realisasi {{ $rp(abs($nilaiRl)) }} = <b>{{ $rp($netto) }}</b>
                  @endif
                  @if ((float) $row->paid_amount > 0)
                    · dibayar {{ $rp($row->paid_amount) }}
                  @endif
                  @if ($sisa > 0)
                    · <span class="text-danger fw-bold">sisa {{ $rp($sisa) }}</span>
                  @endif
                </div>
              </div>
              <form method="POST" action="{{ route('farm.stock-in.payment', $row->id) }}" class="d-flex gap-2 align-items-center m-0">
                @csrf
                <select name="payment_status" class="form-select form-select-sm form-select-solid" style="max-width:170px">
                  <option value="unpaid" {{ ! $row->isPaid() ? 'selected' : '' }}>Belum Lunas</option>
                  <option value="paid" {{ $row->isPaid() ? 'selected' : '' }}>Lunas</option>
                </select>
                <button class="btn btn-sm btn-primary fw-bold">Ubah Status</button>
              </form>
            </div>
          </div>
        </div>

        {{-- ============ SALDO DEPOSIT SUPPLIER ============ --}}
        @if ($row->supplier)
          <div class="card border-0 mt-4 {{ $saldo < -0.01 ? 'bg-light-danger' : 'bg-light-primary' }}">
            <div class="card-body p-5">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                  <div class="fw-bold fs-6 text-gray-800">
                    <i class="ki-outline ki-wallet fs-3 me-1"></i>
                    Saldo Deposit — {{ $row->supplier->name }}
                  </div>
                  <div class="fs-8 text-muted mt-1">
                    Nota ini memotong saldo <b>{{ $rp($row->total) }}</b>.
                    Saldo sekarang <b class="{{ $saldo < -0.01 ? 'text-danger' : 'text-gray-800' }}">{{ $rp($saldo) }}</b>.
                    @if ($saldo < -0.01)
                      <br><span class="text-danger fw-bold">Saldo minus — kita belum bayar {{ $rp(abs($saldo)) }} ke supplier ini.</span>
                    @endif
                  </div>
                </div>
                <a href="{{ route('farm.deposits.show', $row->supplier->id) }}" class="btn btn-sm btn-primary fw-bold">
                  Buka Kartu Deposit</a>
              </div>
            </div>
          </div>
        @endif

        {{-- ============ REALISASI (satu nota satu realisasi) ============ --}}
        <div class="card bg-light border-0 mt-4">
          <div class="card-body p-5">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <div>
                <div class="fw-bold fs-6 text-gray-800">Realisasi Barang</div>
                <div class="fs-8 text-muted">Hasil timbang ulang: barang yang <b>benar-benar diterima</b>.
                  Stok dikoreksi ke angka nyata dan selisihnya menyesuaikan <b>saldo deposit supplier</b> —
                  kurang berarti saldo naik, lebih berarti saldo turun.<br>
                  Berbeda dengan <a href="{{ route('farm.adjustments.index') }}" class="fw-bold">Penyesuaian Stok</a>
                  yang mencatat kerugian sendiri di gudang (ayam mati/susut), tanpa melibatkan supplier.<br>
                  <b>Satu nota hanya bisa punya satu realisasi</b> — bila salah, batalkan lalu catat ulang.</div>
              </div>
              @if (! $real)
                <button class="btn btn-sm btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#m-real">
                  <i class="ki-outline ki-plus fs-4"></i> Catat Realisasi</button>
              @endif
            </div>

            @if ($real)
              <div class="alert {{ $real->isShort() ? 'alert-success' : ($real->isOver() ? 'alert-warning' : 'alert-light') }} py-3 fs-7 fw-bold">
                {{ $real->effectLabel() }}
                <span class="fw-normal fs-8 text-muted d-block mt-1">
                  Dicatat {{ $real->date->format('d/m/Y') }} · {{ $real->reasonLabel() }}
                  @if ($real->user) · oleh {{ $real->user->name }} @endif
                </span>
              </div>

              <div class="table-responsive">
                <table class="table table-row-bordered align-middle gy-2 mb-0 farm-list-table">
                  <thead><tr class="fw-bold text-muted bg-white fs-8">
                    <th class="ps-3">Barang</th>
                    <th class="text-end">Nota</th>
                    <th class="text-end">Nyata Diterima</th>
                    <th class="text-end">Selisih</th>
                    <th class="text-end pe-3">Nilai</th>
                  </tr></thead>
                  <tbody>
                  @foreach ($real->lines as $rl)
                    <tr>
                      <td class="ps-3 fw-bold text-gray-800" data-label="Barang">{{ $rl->line?->item?->name ?? '-' }}
                        <div class="fs-9 text-muted fw-normal">{{ $rp($rl->unit_price) }}/{{ $rl->price_basis }}</div>
                      </td>
                      <td class="text-end" data-label="Nota">{{ $num($rl->nota_qty_ekor) }} ekor
                        <div class="fs-9 text-muted">{{ $num($rl->nota_weight_kg, 2) }} kg</div></td>
                      <td class="text-end fw-bold" data-label="Nyata Diterima">{{ $num($rl->received_qty_ekor) }} ekor
                        <div class="fs-9 text-muted fw-normal">{{ $num($rl->received_weight_kg, 2) }} kg</div></td>
                      <td class="text-end" data-label="Selisih">
                        @if ($rl->isSesuai())
                          <span class="badge badge-light-secondary fs-9">Sesuai nota</span>
                        @else
                          <span class="badge badge-light-{{ (float) $rl->value > 0 ? 'success' : 'warning' }} fs-9">
                            {{ $rl->deltaLabel() }}</span>
                        @endif
                      </td>
                      <td class="text-end pe-3 fw-bold {{ (float) $rl->value > 0 ? 'text-success' : ((float) $rl->value < 0 ? 'text-danger' : 'text-muted') }}"
                          data-label="Nilai">
                        {{ (float) $rl->value == 0 ? '—' : ((float) $rl->value > 0 ? '+' : '−') . ' ' . $rp(abs((float) $rl->value)) }}
                      </td>
                    </tr>
                  @endforeach
                  </tbody>
                  <tfoot><tr class="fw-bold">
                    <td colspan="4" class="text-end">KORESPONDENSI KE SALDO SUPPLIER</td>
                    <td class="text-end pe-3 {{ $real->isShort() ? 'text-success' : 'text-danger' }}">
                      {{ $nilaiRl > 0 ? '+' : '−' }} {{ $rp(abs($nilaiRl)) }}</td>
                  </tr></tfoot>
                </table>
              </div>

              @if ($real->notes)
                <div class="fs-8 text-muted mt-3"><b>Catatan:</b> {{ $real->notes }}</div>
              @endif

              <form method="POST" action="{{ route('farm.stock-in.realization.delete', $row->id) }}"
                    onsubmit="return confirm('Batalkan realisasi ini? Stok kembali ke angka nota dan saldo supplier dibalik.')"
                    class="mt-4 m-0">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-light-danger fw-bold">Batalkan Realisasi</button>
              </form>
            @else
              <div class="text-center text-muted py-5 fs-8 border border-dashed rounded bg-white">
                Belum ada realisasi. Barang dianggap diterima sesuai surat jalan supplier.
              </div>
            @endif
          </div>
        </div>

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
                      @php $adaBerkas = \Illuminate\Support\Facades\Storage::disk('public')->exists($foto); @endphp
                      @if (! $adaBerkas)
                        {{-- Berkas hilang dari disk (mis. terhapus manual). Tampilkan apa adanya
                             supaya tidak muncul gambar rusak tanpa penjelasan. --}}
                        <div class="d-flex flex-column align-items-center justify-content-center text-center px-2"
                             style="height:150px;background:#fff5f5">
                          <i class="ki-outline ki-information-5 fs-3x text-danger mb-2"></i>
                          <span class="fs-9 text-danger fw-bold">Berkas tidak ditemukan</span>
                          <span class="fs-9 text-muted">hapus entri ini</span>
                        </div>
                      @elseif (\App\Models\Farm\StockIn::isImagePath($foto))
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

{{-- ============ MODAL CATAT REALISASI ============
     Yang ditanyakan hanya ANGKA NYATA yang diterima. Tidak ada pilihan
     "kurang / lebih": arah uang disimpulkan sistem, karena meminta petugas
     menyimpulkannya sendiri adalah sumber salah tanda yang paling sering. --}}
@if (! $real)
<div class="modal fade" id="m-real" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <form method="POST" action="{{ route('farm.stock-in.realization', $row->id) }}" id="f-real">
        @csrf
        <div class="modal-header py-4">
          <div>
            <h3 class="fw-bold mb-0">Catat Realisasi</h3>
            <span class="text-muted fs-8">Isi berapa yang <b>benar-benar diterima</b> setelah ditimbang di gudang.</span>
          </div>
          <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
        </div>
        <div class="modal-body">
          <div class="alert alert-light-primary border border-primary d-flex align-items-start py-3 fs-8">
            <i class="ki-outline ki-information-5 fs-2 me-2"></i>
            <div>
              Angka di bawah sudah terisi <b>sesuai nota</b>. Ubah hanya baris yang berbeda.
              Stok akan disetel ke angka nyata dan selisihnya menyesuaikan saldo deposit supplier.
              <br><b>Catat sebelum barang dijual</b> — setelah stok terpakai, angkanya tidak bisa diubah lagi.
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold fs-7 required">Tanggal timbang</label>
              <input type="date" name="date" class="form-control form-control-solid" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold fs-7 required">Alasan selisih</label>
              <select name="reason" class="form-select form-select-solid" required>
                @foreach ($alasan as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold fs-7">Catatan</label>
              <input name="notes" class="form-control form-control-solid" maxlength="255"
                     placeholder="mis. timbangan supplier beda 3 kg">
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-2 mb-0 farm-form-table" id="t-real">
              <thead><tr class="fw-bold text-muted bg-light fs-8">
                <th style="min-width:170px">Barang</th>
                <th class="text-center" style="min-width:120px">Nota</th>
                <th class="text-center" style="min-width:120px">Diterima (ekor)</th>
                <th class="text-center" style="min-width:130px">Diterima (kg)</th>
                <th class="text-end" style="min-width:190px">Akibat ke saldo supplier</th>
              </tr></thead>
              <tbody>
              @foreach ($row->lines as $l)
                <tr data-basis="{{ $l->price_basis }}" data-harga="{{ (float) $l->unit_price }}"
                    data-ekor="{{ (int) $l->qty_ekor }}" data-kg="{{ (float) $l->weight_kg }}">
                  <td data-label="Barang" class="fw-bold text-gray-800">
                    {{ $l->item?->name }}
                    <div class="fs-9 text-muted fw-normal">{{ $rp($l->unit_price) }}/{{ $l->price_basis }}</div>
                  </td>
                  <td data-label="Nota" class="text-center fs-8 text-muted">
                    {{ $num($l->qty_ekor) }} ekor<br>{{ $num($l->weight_kg, 2) }} kg
                  </td>
                  <td data-label="Diterima (ekor)">
                    <input type="number" name="lines[{{ $l->id }}][qty_ekor]" min="0" step="1"
                           class="form-control form-control-solid text-center js-real js-no-format"
                           value="{{ (int) $l->qty_ekor }}">
                  </td>
                  <td data-label="Diterima (kg)">
                    <input type="number" name="lines[{{ $l->id }}][weight_kg]" min="0" step="0.01"
                           class="form-control form-control-solid text-center js-real js-no-format"
                           value="{{ number_format((float) $l->weight_kg, 2, '.', '') }}">
                  </td>
                  <td data-label="Akibat ke saldo supplier" class="text-end fs-8 js-akibat text-muted">Sesuai nota</td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>

          <div class="alert alert-light border mt-4 mb-0 py-3" id="real-ringkas">
            <div class="fw-bold fs-6 text-gray-800" id="real-judul">Belum ada selisih</div>
            <div class="fs-8 text-muted mt-1" id="real-ket">
              Ubah angka yang diterima bila hasil timbang berbeda dari nota.
            </div>
          </div>
        </div>
        <div class="modal-footer py-3">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-danger fw-bold" id="btn-real">Simpan Realisasi</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
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
  // Tombol cetak barang masuk sedang disembunyikan; pengaitnya dilewati bila
  // elemennya tidak ada agar tidak melempar error di konsol.
  var btnCetak = document.getElementById('btn-cetak');
  if (btnCetak) {
    btnCetak.addEventListener('click', function () {
      fetch('{{ route('farm.stock-in.receipt', $row->id) }}', { headers: { Accept: 'application/json' } })
        .then(r => r.json())
        .then(d => window.MoodaPrint && window.MoodaPrint.print(window.farmNota(d)))
        .catch(() => alert('Gagal menyiapkan nota.'));
    });
  }
</script>

@if (! $real)
<script>
  /**
   * Pratinjau realisasi.
   *
   * Petugas mengisi angka nyata; kalimat akibatnya ditulis lengkap ("saldo NAIK
   * Rp1.400.000") sebelum tombol Simpan ditekan. Tanpa kalimat itu, satu digit
   * salah ketik hanya terlihat setelah saldo sudah bergeser.
   */
  (function () {
      var tabel = document.getElementById('t-real');
      if (!tabel) return;

      function rupiah(n) {
          return 'Rp ' + Math.round(Math.abs(n)).toLocaleString('id-ID');
      }

      function hitung() {
          var totalNilai = 0, adaSelisih = false, rincian = [];

          tabel.querySelectorAll('tbody tr').forEach(function (tr) {
              var basis = tr.dataset.basis;
              var harga = parseFloat(tr.dataset.harga) || 0;
              var notaEkor = parseInt(tr.dataset.ekor, 10) || 0;
              var notaKg = parseFloat(tr.dataset.kg) || 0;

              var inputs = tr.querySelectorAll('.js-real');
              var nyataEkor = inputs[0].value === '' ? notaEkor : (parseInt(inputs[0].value, 10) || 0);
              var nyataKg = inputs[1].value === '' ? notaKg : (parseFloat(inputs[1].value) || 0);

              var dEkor = nyataEkor - notaEkor;
              var dKg = Math.round((nyataKg - notaKg) * 100) / 100;

              // Nilai memakai dasar harga nota, sama seperti perhitungan di server.
              var nilai = basis === 'ekor' ? -1 * dEkor * harga : -1 * dKg * harga;
              totalNilai += nilai;

              var sel = tr.querySelector('.js-akibat');
              if (dEkor === 0 && Math.abs(dKg) < 0.005) {
                  sel.className = 'text-end fs-8 js-akibat text-muted';
                  sel.textContent = 'Sesuai nota';
                  return;
              }

              adaSelisih = true;
              var bagian = [];
              if (dEkor !== 0) bagian.push(Math.abs(dEkor) + ' ekor');
              if (Math.abs(dKg) >= 0.005) bagian.push(Math.abs(dKg).toLocaleString('id-ID') + ' kg');

              var dasar = basis === 'ekor' ? dEkor : dKg;
              var arah = dasar < 0 ? 'Kurang' : 'Lebih';

              sel.className = 'text-end fs-8 js-akibat fw-bold ' + (nilai > 0 ? 'text-success' : 'text-danger');
              sel.innerHTML = arah + ' ' + bagian.join(' / ') + '<br>saldo '
                  + (nilai > 0 ? 'NAIK ' : 'TURUN ') + rupiah(nilai);

              rincian.push(arah.toLowerCase() + ' ' + bagian.join(' / '));
          });

          var kotak = document.getElementById('real-ringkas');
          var judul = document.getElementById('real-judul');
          var ket = document.getElementById('real-ket');
          var tombol = document.getElementById('btn-real');

          kotak.className = 'alert mt-4 mb-0 py-3 ' +
              (!adaSelisih ? 'alert-light border' : (totalNilai > 0 ? 'alert-success' : 'alert-warning'));

          if (!adaSelisih) {
              judul.textContent = 'Belum ada selisih';
              ket.textContent = 'Ubah angka yang diterima bila hasil timbang berbeda dari nota.';
              if (tombol) tombol.disabled = true;
              return;
          }

          if (tombol) tombol.disabled = false;

          if (Math.abs(totalNilai) < 0.01) {
              judul.textContent = 'Ada selisih barang, tapi nilainya saling menutup';
              ket.textContent = 'Stok tetap dikoreksi ke angka nyata; saldo supplier tidak berubah.';
          } else if (totalNilai > 0) {
              judul.textContent = 'Saldo supplier NAIK ' + rupiah(totalNilai);
              ket.textContent = 'Barang ' + rincian.join(', ') + ' — potongan saat nota dicatat ternyata kelebihan, '
                  + 'jadi selisihnya dikembalikan ke saldo.';
          } else {
              judul.textContent = 'Saldo supplier TURUN ' + rupiah(totalNilai);
              ket.textContent = 'Barang ' + rincian.join(', ') + ' — potongan saat nota dicatat ternyata kurang, '
                  + 'jadi saldo dipotong lagi sebesar selisihnya.';
          }
      }

      tabel.addEventListener('input', hitung);
      hitung();

      // Kunci tombol setelah diklik: di gudang bersinyal lemah, tombol Simpan
      // sering ditekan berulang karena spinner terlihat menggantung.
      var form = document.getElementById('f-real');
      if (form) {
          form.addEventListener('submit', function () {
              var b = document.getElementById('btn-real');
              if (b) { b.disabled = true; b.textContent = 'Menyimpan…'; }
          });
      }
  })();
</script>
@endif
@endpush
