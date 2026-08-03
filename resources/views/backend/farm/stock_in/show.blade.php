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
          $netto   = $row->netTotal();
          $sisa    = $row->remainingToPay();
          $nilaiRl = (float) $row->realizations->sum('value');
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
                  @if ($nilaiRl > 0)
                    − realisasi {{ $rp($nilaiRl) }} = <b>{{ $rp($netto) }}</b>
                  @endif
                  @if ((float) $row->credit_applied > 0)
                    · ditutup piutang {{ $rp($row->credit_applied) }}
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

        {{-- ============ PIUTANG SUPPLIER ============ --}}
        @if ($row->supplier && ($piutang > 0.01 || (float) $row->credit_applied > 0))
          <div class="card border-0 mt-4 {{ $piutang > 0.01 ? 'bg-light-danger' : 'bg-light-success' }}">
            <div class="card-body p-5">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                  <div class="fw-bold fs-6 text-gray-800">
                    <i class="ki-outline ki-information-5 fs-3 me-1"></i>
                    Piutang Supplier — {{ $row->supplier->name }}
                  </div>
                  <div class="fs-8 text-muted mt-1">
                    @if ($piutang > 0.01)
                      Supplier ini masih berutang <b class="text-danger">{{ $rp($piutang) }}</b>
                      dari realisasi terdahulu (barang kurang).
                    @else
                      Tidak ada piutang tersisa dari supplier ini.
                    @endif
                    @if ((float) $row->credit_applied > 0)
                      <br>Nota ini sudah memakai <b>{{ $rp($row->credit_applied) }}</b> piutang supplier.
                    @endif
                  </div>
                </div>
                <div class="d-flex gap-2">
                  @if ((float) $row->credit_applied > 0)
                    <form method="POST" action="{{ route('farm.stock-in.revoke-credit', $row->id) }}" class="m-0"
                          onsubmit="return confirm('Batalkan penutupan piutang oleh nota ini?')">
                      @csrf
                      <button class="btn btn-sm btn-light fw-bold">Batalkan Penutupan</button>
                    </form>
                  @endif
                  @if ($piutang > 0.01 && $sisa > 0.01)
                    <form method="POST" action="{{ route('farm.stock-in.apply-credit', $row->id) }}" class="m-0">
                      @csrf
                      <button class="btn btn-sm btn-danger fw-bold">
                        <i class="ki-outline ki-arrows-circle fs-5"></i> Tutupi dengan Piutang</button>
                    </form>
                  @endif
                </div>
              </div>

              @if ($row->settlements->count())
                <div class="mt-3 fs-8">
                  <div class="fw-bold text-gray-700 mb-1">Piutang yang ditutup nota ini:</div>
                  @foreach ($row->settlements as $st)
                    <div class="d-flex justify-content-between border-bottom py-1">
                      <span>Realisasi #{{ $st->realization_id }} · {{ $st->date->format('d/m/Y') }}</span>
                      <span class="fw-bold">{{ $rp($st->amount) }}</span>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>
        @endif

        {{-- ============ REALISASI ============ --}}
        <div class="card bg-light border-0 mt-4">
          <div class="card-body p-5">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <div>
                <div class="fw-bold fs-6 text-gray-800">Realisasi Barang</div>
                <div class="fs-8 text-muted">Jika hasil timbang ternyata <b>kurang</b> dari surat jalan supplier.
                  Stok ikut dikoreksi dan kekurangannya menjadi <b>piutang supplier</b>.<br>
                  Berbeda dengan <a href="{{ route('farm.adjustments.index') }}" class="fw-bold">Penyesuaian Stok</a>
                  yang mencatat kerugian sendiri di gudang, tanpa melibatkan supplier.</div>
              </div>
              <button class="btn btn-sm btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#m-real">
                <i class="ki-outline ki-plus fs-4"></i> Catat Realisasi</button>
            </div>

            @if ($row->realizations->count())
              <div class="table-responsive">
                <table class="table table-row-bordered align-middle gy-2 mb-0 farm-list-table">
                  <thead><tr class="fw-bold text-muted bg-white fs-8">
                    <th class="ps-3">Tanggal</th><th>Barang</th><th>Alasan</th>
                    <th class="text-end">Kurang</th><th class="text-end">Nilai</th>
                    <th class="text-end">Sudah Ditutup</th><th class="text-end pe-3">Aksi</th>
                  </tr></thead>
                  <tbody>
                  @foreach ($row->realizations as $r)
                    <tr>
                      <td class="ps-3">{{ $r->date->format('d/m/Y') }}</td>
                      <td class="fw-bold text-gray-800">{{ $r->line?->item?->name ?? '-' }}</td>
                      <td><span class="badge badge-light-danger fs-9">{{ $r->reasonLabel() }}</span></td>
                      <td class="text-end">{{ $num($r->qty_ekor_short) }} ekor
                        <div class="fs-9 text-muted">{{ $num($r->weight_kg_short, 2) }} kg</div></td>
                      <td class="text-end fw-bold text-danger">{{ $rp($r->value) }}</td>
                      <td class="text-end">
                        {{ $rp($r->settled_amount) }}
                        <div class="fs-9 {{ $r->isSettled() ? 'text-success' : 'text-muted' }}">
                          {{ $r->isSettled() ? 'lunas' : 'sisa ' . $rp($r->remaining()) }}</div>
                      </td>
                      <td class="text-end pe-3">
                        <form method="POST" action="{{ route('farm.stock-in.realization.delete', [$row->id, $r->id]) }}"
                              onsubmit="return confirm('Batalkan realisasi ini? Stok akan dikembalikan.')" class="m-0">
                          @csrf @method('DELETE')
                          <button class="btn btn-sm btn-light-danger py-1 px-3 fs-8">Batalkan</button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                  </tbody>
                  <tfoot><tr class="fw-bold">
                    <td colspan="4" class="text-end">TOTAL KEKURANGAN</td>
                    <td class="text-end text-danger">{{ $rp($nilaiRl) }}</td>
                    <td colspan="2"></td>
                  </tr></tfoot>
                </table>
              </div>
            @else
              <div class="text-center text-muted py-5 fs-8 border border-dashed rounded bg-white">
                Belum ada realisasi. Barang diterima sesuai surat jalan supplier.
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

{{-- ============ MODAL CATAT REALISASI ============ --}}
<div class="modal fade" id="m-real" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST" action="{{ route('farm.stock-in.realization', $row->id) }}">
        @csrf
        <div class="modal-header py-4">
          <div>
            <h3 class="fw-bold mb-0">Catat Realisasi</h3>
            <span class="text-muted fs-8">Selisih antara surat jalan supplier dan hasil timbang di gudang.</span>
          </div>
          <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger d-flex align-items-start py-3 fs-8">
            <i class="ki-outline ki-information-5 fs-2 me-2"></i>
            <div>Kekurangan ini <b>mengurangi stok</b> dan menjadi <b>piutang supplier</b> —
              nilainya dihitung dari harga satuan pada nota ini.</div>
          </div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-semibold fs-7 required">Baris Barang</label>
              <select name="stock_in_line_id" class="form-select form-select-solid" required>
                @foreach ($row->lines as $l)
                  @php
                    $sudahEkor = (int) $row->realizations->where('stock_in_line_id', $l->id)->sum('qty_ekor_short');
                    $sudahKg   = (float) $row->realizations->where('stock_in_line_id', $l->id)->sum('weight_kg_short');
                  @endphp
                  <option value="{{ $l->id }}">
                    {{ $l->item?->name }} — tercatat {{ $num($l->qty_ekor) }} ekor / {{ $num($l->weight_kg, 2) }} kg
                    @ {{ $rp($l->unit_price) }}/{{ $l->price_basis }}
                    @if ($sudahEkor || $sudahKg)
                      (sudah direalisasi {{ $num($sudahEkor) }} ekor / {{ $num($sudahKg, 2) }} kg)
                    @endif
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label fw-semibold fs-7 required">Tanggal</label>
              <input type="date" name="date" class="form-control form-control-solid" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="col-6 col-md-8">
              <label class="form-label fw-semibold fs-7 required">Alasan</label>
              <select name="reason" class="form-select form-select-solid" required>
                @foreach ($alasan as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold fs-7">Kurang (ekor)</label>
              <input type="number" name="qty_ekor_short" class="form-control form-control-solid js-no-format" min="0" value="0">
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold fs-7">Kurang (kg)</label>
              <input type="number" name="weight_kg_short" class="form-control form-control-solid js-no-format" min="0" step="0.01" value="0">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold fs-7">Catatan</label>
              <input name="notes" class="form-control form-control-solid" maxlength="255" placeholder="mis. timbangan supplier beda 3 kg">
            </div>
          </div>
        </div>
        <div class="modal-footer py-3">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-danger fw-bold">Simpan Realisasi</button>
        </div>
      </form>
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
@endpush
