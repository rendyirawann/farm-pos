@extends('backend.layout.app')
@section('title', 'Barang Masuk')
@section('content')
@php
    // @json() tidak bisa mengurai closure multi-baris -> siapkan array di sini.
    $itemsJson = $items->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->primary_unit])->values();
@endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <form method="POST" action="{{ route('farm.stock-in.store') }}" id="f-in">
      @csrf
      <div class="card card-flush mb-4">
        <div class="card-header pt-5">
          <div>
            <h3 class="card-title fw-bold fs-4 mb-0"><i class="ki-outline ki-entrance-left fs-2 text-success me-2"></i>Barang Masuk</h3>
            <span class="text-muted fs-8">Pembelian ayam dari supplier. Tiap baris menjadi satu lot — dasar perhitungan FIFO saat dijual.</span>
          </div>
        </div>
        <div class="card-body pt-4">
          <div class="row g-4 mb-4">
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold fs-7 required">Tanggal</label>
              <input type="date" name="date" class="form-control form-control-solid form-control-lg"
                     value="{{ old('date', now()->format('Y-m-d')) }}" required>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold fs-7">Supplier</label>
              <select name="supplier_id" id="in-supplier" class="form-select form-select-solid form-select-lg">
                <option value="">— tanpa supplier —</option>
                @foreach ($suppliers as $s)
                  <option value="{{ $s->id }}" data-saldo="{{ (float) $s->saldo }}" data-nama="{{ $s->name }}"
                          {{ old('supplier_id') == $s->id ? 'selected' : '' }}>
                    {{ $s->name }} — saldo Rp {{ number_format($s->saldo, 0, ',', '.') }}
                  </option>
                @endforeach
              </select>
              @if ($suppliers->isEmpty())
                <div class="fs-8 text-danger mt-1">Belum ada supplier — <a href="{{ route('farm.suppliers.index') }}" class="fw-bold">tambah dulu</a>.</div>
              @endif
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold fs-7">Catatan</label>
              <input name="notes" class="form-control form-control-solid form-control-lg" maxlength="255" value="{{ old('notes') }}">
            </div>
          </div>

          {{-- SALDO DEPOSIT dipajang sebelum baris nota diisi: nota ini akan memotong
               saldo itu, jadi angkanya harus terlihat lebih dulu — bukan setelah simpan. --}}
          <div class="alert d-none mb-4" id="panel-saldo">
            <div class="d-flex align-items-start">
              <i class="ki-outline ki-wallet fs-2x me-3"></i>
              <div class="flex-grow-1">
                <div class="fw-bold fs-6 text-gray-800">
                  Saldo deposit <span id="saldo-nama"></span>: <span id="saldo-nilai" class="fs-4"></span>
                </div>
                <div class="fs-8 text-muted mt-1" id="saldo-ket">
                  Nilai nota ini akan memotong saldo tersebut.
                </div>
                <a href="{{ route('farm.deposits.index') }}" target="_blank"
                   class="btn btn-sm btn-light-primary fw-bold mt-3 py-1 px-3 fs-8" id="saldo-tombol">
                  Buka Menu Deposit</a>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-2 mb-0 farm-form-table" id="t-lines">
              <thead><tr class="fw-bold text-muted bg-light fs-8">
                <th style="min-width:200px">Item</th>
                <th class="text-center" style="min-width:110px">Jumlah (ekor)</th>
                <th class="text-center" style="min-width:120px">Berat (kg)</th>
                <th class="text-center" style="min-width:110px">Dasar Harga</th>
                <th class="text-center" style="min-width:140px">Harga Satuan</th>
                <th class="text-end" style="min-width:130px">Subtotal</th>
                <th style="width:44px"></th>
              </tr></thead>
              <tbody></tbody>
              <tfoot><tr class="fw-bold fs-4">
                <td colspan="5" class="text-end">TOTAL</td>
                <td class="text-end text-success" id="grand" data-label="Total">Rp 0</td><td></td>
              </tr></tfoot>
            </table>
          </div>

          <button type="button" class="btn btn-light-success fw-bold mt-3" id="btn-add">
            <i class="ki-outline ki-plus fs-3"></i> Tambah Baris
          </button>

          <div class="alert alert-primary d-flex align-items-start py-3 fs-8 mt-4 mb-0">
            <i class="ki-outline ki-information-5 fs-2 me-2"></i>
            <div>Isi <b>ekor dan kg sekaligus</b>. Menyimpan dua-duanya membuat susut bobot terlihat nanti —
              kalau hanya satu, selisih berat tidak akan pernah ketahuan.</div>
          </div>
        </div>
        <div class="card-footer py-4 d-flex justify-content-end gap-2 farm-actions">
          <a href="{{ route('farm.stock-in.index') }}" class="btn btn-light">Batal</a>
          <button class="btn btn-success fw-bold btn-lg"><i class="ki-outline ki-check fs-3"></i> Simpan &amp; Cetak Nota</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const ITEMS = @json($itemsJson);
  let idx = 0;

  const rupiah = n => 'Rp ' + Number(n || 0).toLocaleString('id-ID');

  /**
   * Membaca angka dari input dengan BENAR.
   *
   * Pemformat ribuan global (partials/_number_format) mengubah input angka jadi
   * teks berformat Indonesia: 38000 -> "38.000". Membacanya dengan +value akan
   * menghasilkan 38 karena JavaScript menganggap titik sebagai koma desimal —
   * inilah sebab subtotal sempat 1000x lebih kecil. Nilai aslinya disimpan
   * pemformat di dataset.raw, jadi itu yang dipakai bila tersedia.
   */
  function angka(el) {
      if (!el) return 0;
      if (el.dataset && el.dataset.raw !== undefined && el.dataset.raw !== '') {
          return Number(el.dataset.raw) || 0;
      }
      return parseFloat(el.value) || 0;
  }


  function barisBaru() {
    const opsi = ITEMS.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
    // data-label dipakai gaya responsif: di layar <768px tiap sel berubah jadi
    // baris berlabel sehingga tabel 8 kolom tak perlu digeser ke samping.
    const html = `<tr data-row="${idx}">
      <td data-label="Item"><select name="lines[${idx}][item_id]" class="form-select form-select-solid" required>${opsi}</select></td>
      <td data-label="Jumlah (ekor)"><input type="number" name="lines[${idx}][qty_ekor]" class="form-control form-control-solid text-center js-hit" min="0" value="0"></td>
      <td data-label="Berat (kg)"><input type="number" name="lines[${idx}][weight_kg]" class="form-control form-control-solid text-center js-hit js-no-format" min="0" step="0.01" value="0"></td>
      <td data-label="Dasar Harga"><select name="lines[${idx}][price_basis]" class="form-select form-select-solid js-hit">
            <option value="kg">per kg</option><option value="ekor">per ekor</option></select></td>
      <td data-label="Harga Satuan"><input type="number" name="lines[${idx}][unit_price]" class="form-control form-control-solid text-center js-hit" min="0" step="100" value="0" required></td>
      <td data-label="Subtotal" class="text-end fw-bold js-sub">Rp 0</td>
      <td class="text-center farm-cell-action"><button type="button" class="btn btn-sm btn-icon btn-light-danger js-del"><i class="ki-outline ki-cross fs-4"></i></button></td>
    </tr>`;
    document.querySelector('#t-lines tbody').insertAdjacentHTML('beforeend', html);
    idx++;
  }

  function hitung() {
    let total = 0;
    document.querySelectorAll('#t-lines tbody tr').forEach(tr => {
      const ekor = angka(tr.querySelector('[name*="[qty_ekor]"]'));
      const kg   = angka(tr.querySelector('[name*="[weight_kg]"]'));
      const basis = tr.querySelector('[name*="[price_basis]"]').value;
      const harga = angka(tr.querySelector('[name*="[unit_price]"]'));
      const sub = basis === 'ekor' ? harga * ekor : harga * kg;
      tr.querySelector('.js-sub').textContent = rupiah(sub);
      total += sub;
    });
    document.getElementById('grand').textContent = rupiah(total);
  }

  document.getElementById('btn-add').addEventListener('click', () => { barisBaru(); hitung(); });
  document.addEventListener('input', e => { if (e.target.classList.contains('js-hit')) hitung(); });
  document.addEventListener('change', e => { if (e.target.classList.contains('js-hit')) hitung(); });
  document.addEventListener('click', e => {
    const b = e.target.closest('.js-del');
    if (b) { b.closest('tr').remove(); hitung(); }
  });


  /**
   * Panel SALDO DEPOSIT supplier.
   *
   * Ditampilkan sejak supplier dipilih dan ikut berubah mengikuti total nota yang
   * sedang diketik: petugas melihat sisa saldo setelah nota ini SEBELUM menyimpan,
   * bukan mengetahuinya belakangan saat saldo sudah minus.
   */
  function perbaruiPanelSaldo() {
      var sel = document.getElementById('in-supplier');
      var panel = document.getElementById('panel-saldo');
      if (!sel || !panel) return;

      var opt = sel.selectedOptions[0];
      if (!opt || !opt.value) {          // tanpa supplier -> tidak menyentuh deposit
          panel.classList.add('d-none');
          return;
      }

      var saldo = parseFloat(opt.dataset.saldo || 0) || 0;
      panel.classList.remove('d-none');
      document.getElementById('saldo-nama').textContent = opt.dataset.nama || '';
      document.getElementById('saldo-nilai').textContent = rupiah(saldo);

      // Total nota saat ini dipakai untuk memperkirakan sisa saldo.
      var total = 0;
      document.querySelectorAll('#t-lines tbody tr').forEach(function (tr) {
          var ekor = angka(tr.querySelector('[name*="[qty_ekor]"]'));
          var kg = angka(tr.querySelector('[name*="[weight_kg]"]'));
          var basis = tr.querySelector('[name*="[price_basis]"]').value;
          var harga = angka(tr.querySelector('[name*="[unit_price]"]'));
          total += basis === 'ekor' ? harga * ekor : harga * kg;
      });

      var sisa = saldo - total;
      var ket = document.getElementById('saldo-ket');

      panel.classList.remove('alert-success', 'alert-warning', 'alert-danger');

      if (total <= 0) {
          panel.classList.add(saldo < 0 ? 'alert-danger' : 'alert-success');
          ket.innerHTML = saldo < 0
              ? 'Saldo sudah minus — <b>kita belum bayar ' + rupiah(-saldo) + '</b> ke supplier ini.'
              : 'Nilai nota ini akan memotong saldo tersebut.';
      } else if (sisa >= 0) {
          panel.classList.add('alert-success');
          ket.innerHTML = 'Nota ' + rupiah(total) + ' <b>tertutup saldo</b>. Sisa saldo setelah nota ini: <b>'
              + rupiah(sisa) + '</b>.';
      } else {
          panel.classList.add('alert-danger');
          ket.innerHTML = 'Saldo <b>kurang ' + rupiah(-sisa) + '</b> untuk nota ' + rupiah(total) + '. '
              + 'Nota tetap bisa disimpan, tapi saldo jadi minus — artinya <b>kita belum bayar</b> '
              + 'sebesar itu. Setor deposit dulu bila memang sudah ditransfer.';
      }
  }

  document.getElementById('in-supplier')?.addEventListener('change', perbaruiPanelSaldo);
  document.addEventListener('input', function (e) {
      if (e.target.classList.contains('js-hit')) perbaruiPanelSaldo();
  });

  barisBaru(); hitung(); perbaruiPanelSaldo();
</script>
@endpush
