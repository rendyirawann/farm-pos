@extends('backend.layout.app')
@section('title', 'Penyesuaian Stok')
@section('content')
@include('backend.farm._style')
@php
  $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
  $num = fn($n,$d=0) => number_format((float)$n, $d, ',', '.');
@endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <div class="alert alert-danger d-flex align-items-start py-3 fs-8">
      <i class="ki-outline ki-information-5 fs-2 me-2"></i>
      <div>Catat ayam <b>mati, susut bobot, rusak</b>, atau koreksi hasil opname di sini.
        Tanpa jalur ini stok sistem tidak akan pernah cocok dengan fisik, dan perhitungan FIFO ikut melenceng.</div>
    </div>

    <div class="card card-flush">
      <div class="card-header pt-5">
        <h3 class="card-title fw-bold fs-4 mb-0">Penyesuaian Stok</h3>
        <div class="card-toolbar">
          <button class="btn btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#m-adj">
            <i class="ki-outline ki-plus fs-3"></i> Buat Penyesuaian</button>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0 farm-list-table">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Ref</th><th>Tanggal</th><th>Item</th><th>Alasan</th>
              <th class="text-end">Jumlah</th><th class="text-end">Dampak Nilai</th>
              <th>Persetujuan</th><th class="text-end pe-4">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $r)
              <tr>
                <td class="ps-4 fw-bold fs-8">{{ $r->ref_no }}</td>
                <td class="text-muted fs-8">{{ $r->date->format('d/m/Y') }}</td>
                <td class="fw-bold text-gray-800">{{ $r->item?->name }}</td>
                <td><span class="badge badge-light-{{ $r->isAddition() ? 'success' : 'danger' }}">{{ $r->reasonLabel() }}</span></td>
                <td class="text-end">{{ $num($r->qty_ekor) }} ekor<div class="fs-8 text-muted">{{ $num($r->weight_kg, 2) }} kg</div></td>
                <td class="text-end fw-bold {{ $r->isAddition() ? 'text-success' : 'text-danger' }}">
                  {{ $r->isAddition() ? '+' : '−' }} {{ $rp($r->cost_impact) }}</td>
                <td>
                  @if ($r->isApproved())
                    <span class="badge badge-light-success">Disetujui</span>
                    <div class="fs-9 text-muted">{{ $r->approver?->name }}</div>
                  @else
                    <span class="badge badge-light-warning">Menunggu</span>
                  @endif
                </td>
                <td class="text-end pe-4">
                  @if (! $r->isApproved())
                    <form action="{{ route('farm.adjustments.approve', $r->id) }}" method="POST" class="d-inline">@csrf
                      <button class="btn btn-sm btn-light-success py-1 px-3 fs-8">Setujui</button></form>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted py-10">Belum ada penyesuaian.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-4">{{ $rows->links() }}</div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="m-adj" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('farm.adjustments.store') }}">
        @csrf
        <div class="modal-header py-4"><h3 class="fw-bold mb-0">Penyesuaian Stok</h3>
          <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-6"><label class="form-label fw-semibold fs-7 required">Tanggal</label>
              <input type="date" name="date" class="form-control form-control-solid" value="{{ now()->format('Y-m-d') }}" required></div>
            <div class="col-6"><label class="form-label fw-semibold fs-7 required">Alasan</label>
              <select name="reason" class="form-select form-select-solid" required>
                @foreach ($reasons as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
              </select></div>
            <div class="col-12"><label class="form-label fw-semibold fs-7 required">Item</label>
              <select name="item_id" id="adj-item" class="form-select form-select-solid" required>
                @foreach ($items as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach
              </select></div>
            <div class="col-12"><label class="form-label fw-semibold fs-7">Lot Tertentu <span class="text-muted">(opsional)</span></label>
              <select name="lot_id" id="adj-lot" class="form-select form-select-solid">
                <option value="">— ikut urutan FIFO —</option>
              </select>
              <div class="fs-9 text-muted mt-1">Kosongkan agar dikurangi dari pembelian terlama.</div></div>
            <div class="col-6"><label class="form-label fw-semibold fs-7">Jumlah (ekor/butir)</label>
              <input type="number" name="qty_ekor" class="form-control form-control-solid" min="0" value="0"></div>
            <div class="col-6"><label class="form-label fw-semibold fs-7">Berat (kg)</label>
              <input type="number" name="weight_kg" class="form-control form-control-solid" min="0" step="0.01" value="0"></div>
            <div class="col-12"><label class="form-label fw-semibold fs-7">Catatan</label>
              <input name="notes" class="form-control form-control-solid" maxlength="255"></div>
          </div>
        </div>
        <div class="modal-footer py-3">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-danger fw-bold">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Daftar lot mengikuti item yang dipilih.
  function muatLot() {
    var id = document.getElementById('adj-item').value;
    var sel = document.getElementById('adj-lot');
    sel.innerHTML = '<option value="">— ikut urutan FIFO —</option>';
    if (!id) return;
    fetch('{{ url('admin/farm/items') }}/' + id + '/lots', { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (rows) {
        rows.forEach(function (l) {
          var o = document.createElement('option');
          o.value = l.id; o.textContent = l.label;
          sel.appendChild(o);
        });
      })
      .catch(function () {});
  }
  document.getElementById('adj-item').addEventListener('change', muatLot);
  muatLot();
</script>
@endpush
