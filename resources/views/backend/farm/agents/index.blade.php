@extends('backend.layout.app')
@section('title', 'Agen')
@section('content')
@php $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.'); @endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <div class="card card-flush">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Agen</h3>
          <span class="text-muted fs-8">Pembeli langganan. Tempo baku otomatis mengisi jatuh tempo saat penjualan.</span>
        </div>
        <div class="card-toolbar">
          <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#m-agent"
                  onclick="isiAgen(null)"><i class="ki-outline ki-plus fs-3"></i> Tambah Agen</button>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Nama</th><th>Telepon</th><th class="text-end">Tempo</th>
              <th class="text-end">Batas Piutang</th><th class="text-end">Sisa Piutang</th>
              <th>Status</th><th class="text-end pe-4">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse ($agents as $a)
              <tr>
                <td class="ps-4 fw-bold text-gray-800">{{ $a->name }}
                  @if ($a->address)<div class="fs-8 text-muted">{{ $a->address }}</div>@endif
                </td>
                <td>{{ $a->phone ?: '-' }}</td>
                <td class="text-end">{{ $a->term_days ? $a->term_days . ' hari' : 'tunai' }}</td>
                <td class="text-end">{{ (float) $a->credit_limit > 0 ? $rp($a->credit_limit) : '—' }}</td>
                <td class="text-end fw-bold {{ $a->sisa_piutang > 0 ? 'text-danger' : 'text-muted' }}">
                  {{ $a->sisa_piutang > 0 ? $rp($a->sisa_piutang) : '—' }}
                </td>
                <td><span class="badge badge-light-{{ $a->is_active ? 'success' : 'secondary' }}">{{ $a->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td class="text-end pe-4">
                  <a href="{{ route('farm.receivables.card', $a->id) }}" class="btn btn-sm btn-light-warning py-1 px-3 fs-8">Kartu Piutang</a>
                  <button class="btn btn-sm btn-light-primary py-1 px-3 fs-8" data-bs-toggle="modal" data-bs-target="#m-agent"
                          onclick='isiAgen(@json($a))'>Ubah</button>
                  <form action="{{ route('farm.agents.toggle', $a->id) }}" method="POST" class="d-inline">@csrf
                    <button class="btn btn-sm btn-light py-1 px-3 fs-8">{{ $a->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-10">Belum ada agen.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="m-agent" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="f-agent" action="{{ route('farm.agents.store') }}">
        @csrf <input type="hidden" name="_method" id="ag-method" value="POST">
        <div class="modal-header py-4"><h3 class="fw-bold mb-0" id="ag-judul">Tambah Agen</h3>
          <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label fw-semibold fs-7 required">Nama</label>
            <input name="name" id="ag-name" class="form-control form-control-solid" required maxlength="100"></div>
          <div class="mb-3"><label class="form-label fw-semibold fs-7">Telepon</label>
            <input name="phone" id="ag-phone" class="form-control form-control-solid" maxlength="30"></div>
          <div class="mb-3"><label class="form-label fw-semibold fs-7">Alamat</label>
            <input name="address" id="ag-address" class="form-control form-control-solid" maxlength="255"></div>
          <div class="row g-3">
            <div class="col-6"><label class="form-label fw-semibold fs-7">Tempo (hari)</label>
              <input type="number" name="term_days" id="ag-term_days" class="form-control form-control-solid" min="0" max="180" value="0">
              <div class="fs-9 text-muted mt-1">0 = tunai</div></div>
            <div class="col-6"><label class="form-label fw-semibold fs-7">Batas Piutang</label>
              <input type="number" name="credit_limit" id="ag-credit_limit" class="form-control form-control-solid" min="0" step="1000" value="0">
              <div class="fs-9 text-muted mt-1">0 = tanpa batas</div></div>
          </div>
        </div>
        <div class="modal-footer py-3">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-success fw-bold">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  function isiAgen(a) {
    var f = document.getElementById('f-agent');
    document.getElementById('ag-judul').textContent = a ? 'Ubah Agen' : 'Tambah Agen';
    document.getElementById('ag-method').value = a ? 'PUT' : 'POST';
    f.action = a ? '{{ url('admin/farm/agents') }}/' + a.id : '{{ route('farm.agents.store') }}';
    ['name','phone','address'].forEach(function (k) { document.getElementById('ag-' + k).value = a ? (a[k] || '') : ''; });
    document.getElementById('ag-term_days').value = a ? (a.term_days || 0) : 0;
    document.getElementById('ag-credit_limit').value = a ? Math.round(a.credit_limit || 0) : 0;
  }
</script>
@endpush
