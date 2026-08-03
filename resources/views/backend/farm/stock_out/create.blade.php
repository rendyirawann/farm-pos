@extends('backend.layout.app')
@section('title', 'Barang Keluar')
@section('content')
@php
    $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
    // Disiapkan di sini karena @json() tidak bisa mengurai closure multi-baris.
    $itemsJson = $items->map(fn($i) => [
        'id'        => $i->id,
        'name'      => $i->name,
        'unit'      => $i->primary_unit,
        'produced'  => (bool) $i->is_produced,
        'stok_ekor' => (int) $i->stok_ekor,
        'stok_kg'   => (float) $i->stok_kg,
    ])->values();
@endphp

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <form method="POST" action="{{ route('farm.stock-out.store') }}" id="f-out">
      @csrf
      <div class="card card-flush mb-4">
        <div class="card-header pt-5">
          <div>
            <h3 class="card-title fw-bold fs-4 mb-0"><i class="ki-outline ki-entrance-right fs-2 text-warning me-2"></i>Barang Keluar</h3>
            <span class="text-muted fs-8">Penjualan ke agen. Harga pokok diambil FIFO dari pembelian terlama — muncul otomatis sebagai pembanding.</span>
          </div>
        </div>
        <div class="card-body pt-4">
          <div class="row g-4 mb-4">
            <div class="col-12 col-md-3">
              <label class="form-label fw-semibold fs-7 required">Tanggal</label>
              <input type="date" name="date" id="in-date" class="form-control form-control-solid form-control-lg"
                     value="{{ old('date', now()->format('Y-m-d')) }}" required>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label fw-semibold fs-7">Agen</label>
              <select name="agent_id" id="in-agent" class="form-select form-select-solid form-select-lg">
                <option value="">— umum / tanpa agen —</option>
                @foreach ($agents as $a)
                  <option value="{{ $a->id }}" data-term="{{ (int) $a->term_days }}">{{ $a->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label fw-semibold fs-7 required">Status Bayar</label>
              <select name="payment_status" id="in-status" class="form-select form-select-solid form-select-lg">
                <option value="paid">Lunas</option>
                <option value="unpaid" selected>Belum Lunas</option>
              </select>
            </div>
            <div class="col-12 col-md-3" id="wrap-tempo">
              <label class="form-label fw-semibold fs-7">Jatuh Tempo</label>
              <input type="date" name="due_date" id="in-due" class="form-control form-control-solid form-control-lg"
                     value="{{ old('due_date') }}">
              <div class="fs-9 text-muted mt-1">terisi otomatis dari tempo agen</div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-2 mb-0 farm-form-table" id="t-lines">
              <thead><tr class="fw-bold text-muted bg-light fs-8">
                <th style="min-width:210px">Item</th>
                <th class="text-center" style="min-width:105px">Ekor/Butir</th>
                <th class="text-center" style="min-width:110px">Berat (kg)</th>
                <th class="text-center" style="min-width:105px">Dasar</th>
                <th class="text-center" style="min-width:130px">Harga Jual</th>
                <th class="text-end" style="min-width:120px">Subtotal</th>
                <th class="text-end" style="min-width:150px">Modal (FIFO)</th>
                <th class="text-end" style="min-width:120px">Laba</th>
                <th style="width:44px"></th>
              </tr></thead>
              <tbody></tbody>
              <tfoot>
                <tr class="fw-bold">
                  <td colspan="5" class="text-end">TOTAL</td>
                  <td class="text-end fs-4" id="g-jual" data-label="Total Jual">Rp 0</td>
                  <td class="text-end text-muted" id="g-modal" data-label="Total Modal">Rp 0</td>
                  <td class="text-end fs-4 text-success" id="g-laba" data-label="Total Laba">Rp 0</td>
                  <td></td>
                </tr>
                <tr><td colspan="9" class="text-end fs-8 text-muted">Margin: <b id="g-margin">0%</b></td></tr>
              </tfoot>
            </table>
          </div>

          <button type="button" class="btn btn-light-warning fw-bold mt-3" id="btn-add">
            <i class="ki-outline ki-plus fs-3"></i> Tambah Baris
          </button>

          <div class="mb-3 mt-4">
            <label class="form-label fw-semibold fs-7">Catatan</label>
            <input name="notes" class="form-control form-control-solid" maxlength="255" value="{{ old('notes') }}">
          </div>

          <div class="alert alert-warning d-flex align-items-start py-3 fs-8 mb-0">
            <i class="ki-outline ki-information-5 fs-2 me-2"></i>
            <div>Kolom <b>Modal (FIFO)</b> memperlihatkan harga beli lot yang akan terpakai — dipakai sebagai pembanding
              sebelum Anda menetapkan harga jual. Telur memakai harga pokok otomatis
              (<b>{{ $rp($hppTelur) }}/butir</b> bulan ini) dari biaya operasional.</div>
          </div>
        </div>
        <div class="card-footer py-4 d-flex justify-content-end gap-2 farm-actions">
          <a href="{{ route('farm.stock-out.index') }}" class="btn btn-light">Batal</a>
          <button class="btn btn-warning fw-bold btn-lg"><i class="ki-outline ki-check fs-3"></i> Simpan &amp; Cetak Nota</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const ITEMS = @json($itemsJson);
  const URL_PREVIEW = @json(route('farm.stock-out.preview'));
  const CSRF = @json(csrf_token());
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
    const opsi = ITEMS.map(i => {
      const sisa = i.produced ? `${i.stok_ekor} butir` : `${i.stok_ekor} ekor / ${i.stok_kg} kg`;
      return `<option value="${i.id}" data-produced="${i.produced ? 1 : 0}">${i.name} — sisa ${sisa}</option>`;
    }).join('');

    // data-label dipakai gaya responsif: di layar <768px tiap sel berubah jadi
    // baris berlabel; tabel 10 kolom (~1200px) tak perlu digeser ke samping.
    document.querySelector('#t-lines tbody').insertAdjacentHTML('beforeend', `<tr data-row="${idx}">
      <td data-label="Item"><select name="lines[${idx}][item_id]" class="form-select form-select-solid js-item" required>${opsi}</select></td>
      <td data-label="Ekor/Butir"><input type="number" name="lines[${idx}][qty_ekor]" class="form-control form-control-solid text-center js-hit" min="0" value="0"></td>
      <td data-label="Berat (kg)"><input type="number" name="lines[${idx}][weight_kg]" class="form-control form-control-solid text-center js-hit js-no-format" min="0" step="0.01" value="0"></td>
      <td data-label="Dasar"><select name="lines[${idx}][price_basis]" class="form-select form-select-solid js-hit">
            <option value="kg">per kg</option><option value="ekor">per ekor</option><option value="butir">per butir</option></select></td>
      <td data-label="Harga Jual"><input type="number" name="lines[${idx}][unit_price]" class="form-control form-control-solid text-center js-hit" min="0" step="100" value="0" required></td>
      <td data-label="Subtotal" class="text-end fw-bold js-sub">Rp 0</td>
      <td data-label="Modal (FIFO)" class="text-end js-modal text-muted fs-8">—</td>
      <td data-label="Laba" class="text-end fw-bold js-laba">Rp 0</td>
      <td class="text-center farm-cell-action"><button type="button" class="btn btn-sm btn-icon btn-light-danger js-del"><i class="ki-outline ki-cross fs-4"></i></button></td>
    </tr>`);
    idx++;
  }

  /** Ambil harga pokok FIFO dari server — tanpa menyentuh stok. */
  function ambilModal(tr) {
    const itemId = tr.querySelector('.js-item').value;
    const ekor = angka(tr.querySelector('[name*="[qty_ekor]"]'));
    const kg   = angka(tr.querySelector('[name*="[weight_kg]"]'));
    if (!itemId || (ekor <= 0 && kg <= 0)) { tr.dataset.modal = 0; tr.querySelector('.js-modal').textContent = '—'; hitung(); return; }

    fetch(URL_PREVIEW, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
      body: JSON.stringify({ item_id: +itemId, qty_ekor: ekor, weight_kg: kg }),
    })
      .then(r => r.json())
      .then(d => {
        tr.dataset.modal = d.cost || 0;
        let ket = rupiah(d.cost || 0);
        if (d.lots && d.lots.length) {
          ket += '<div class="fs-9 text-muted">' + d.lots.map(l =>
            `${l.tanggal}: ${l.weight_kg} kg @ ${rupiah(l.cost_per_kg)}`).join('<br>') + '</div>';
        }
        if (d.catatan) ket += `<div class="fs-9 text-muted">${d.catatan}</div>`;
        if (d.kurang_kg > 0 || d.kurang_ekor > 0) {
          ket += `<div class="fs-9 text-danger fw-bold">stok kurang ${d.kurang_kg} kg / ${d.kurang_ekor} ekor</div>`;
        }
        tr.querySelector('.js-modal').innerHTML = ket;
        hitung();
      })
      .catch(() => { tr.querySelector('.js-modal').textContent = 'gagal memuat'; });
  }

  function hitung() {
    let jual = 0, modal = 0;
    document.querySelectorAll('#t-lines tbody tr').forEach(tr => {
      const ekor = angka(tr.querySelector('[name*="[qty_ekor]"]'));
      const kg   = angka(tr.querySelector('[name*="[weight_kg]"]'));
      const basis = tr.querySelector('[name*="[price_basis]"]').value;
      const harga = angka(tr.querySelector('[name*="[unit_price]"]'));
      const sub = (basis === 'kg') ? harga * kg : harga * ekor;
      const m = +tr.dataset.modal || 0;

      tr.querySelector('.js-sub').textContent = rupiah(sub);
      const laba = sub - m;
      const el = tr.querySelector('.js-laba');
      el.textContent = rupiah(laba);
      el.className = 'text-end fw-bold js-laba ' + (laba >= 0 ? 'text-success' : 'text-danger');

      jual += sub; modal += m;
    });
    document.getElementById('g-jual').textContent = rupiah(jual);
    document.getElementById('g-modal').textContent = rupiah(modal);
    const laba = jual - modal;
    const gl = document.getElementById('g-laba');
    gl.textContent = rupiah(laba);
    gl.className = 'text-end fs-4 fw-bold ' + (laba >= 0 ? 'text-success' : 'text-danger');
    document.getElementById('g-margin').textContent = (jual > 0 ? (laba / jual * 100).toFixed(1) : 0) + '%';
  }

  // Jatuh tempo terisi otomatis dari tempo baku agen.
  function aturTempo() {
    const sel = document.getElementById('in-agent');
    const term = +(sel.selectedOptions[0]?.dataset.term || 0);
    const status = document.getElementById('in-status').value;
    document.getElementById('wrap-tempo').style.display = status === 'unpaid' ? '' : 'none';
    if (status === 'unpaid' && term > 0) {
      const d = new Date(document.getElementById('in-date').value || Date.now());
      d.setDate(d.getDate() + term);
      document.getElementById('in-due').value = d.toISOString().slice(0, 10);
    }
  }

  document.getElementById('btn-add').addEventListener('click', () => { barisBaru(); hitung(); });
  document.getElementById('in-agent').addEventListener('change', aturTempo);
  document.getElementById('in-status').addEventListener('change', aturTempo);
  document.getElementById('in-date').addEventListener('change', aturTempo);

  let timer = null;
  document.addEventListener('input', e => {
    if (!e.target.classList.contains('js-hit')) return;
    hitung();
    const tr = e.target.closest('tr');
    clearTimeout(timer);
    timer = setTimeout(() => ambilModal(tr), 350);   // tunggu selesai mengetik
  });
  document.addEventListener('change', e => {
    if (e.target.classList.contains('js-item') || e.target.classList.contains('js-hit')) {
      const tr = e.target.closest('tr');
      hitung(); ambilModal(tr);
    }
  });
  document.addEventListener('click', e => {
    const b = e.target.closest('.js-del');
    if (b) { b.closest('tr').remove(); hitung(); }
  });

  barisBaru(); hitung(); aturTempo();
</script>
@endpush
