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
              <select name="supplier_id" class="form-select form-select-solid form-select-lg">
                <option value="">— tanpa supplier —</option>
                @foreach ($suppliers as $s)
                  <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
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

  function barisBaru() {
    const opsi = ITEMS.map(i => `<option value="${i.id}">${i.name}</option>`).join('');
    // data-label dipakai gaya responsif: di layar <768px tiap sel berubah jadi
    // baris berlabel sehingga tabel 8 kolom tak perlu digeser ke samping.
    const html = `<tr data-row="${idx}">
      <td data-label="Item"><select name="lines[${idx}][item_id]" class="form-select form-select-solid" required>${opsi}</select></td>
      <td data-label="Jumlah (ekor)"><input type="number" name="lines[${idx}][qty_ekor]" class="form-control form-control-solid text-center js-hit" min="0" value="0"></td>
      <td data-label="Berat (kg)"><input type="number" name="lines[${idx}][weight_kg]" class="form-control form-control-solid text-center js-hit" min="0" step="0.01" value="0"></td>
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
      const ekor = +tr.querySelector('[name*="[qty_ekor]"]').value || 0;
      const kg   = +tr.querySelector('[name*="[weight_kg]"]').value || 0;
      const basis = tr.querySelector('[name*="[price_basis]"]').value;
      const harga = +tr.querySelector('[name*="[unit_price]"]').value || 0;
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

  barisBaru(); hitung();
</script>
@endpush
