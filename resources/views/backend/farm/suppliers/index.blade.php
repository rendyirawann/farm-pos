@extends('backend.layout.app')
@section('title', 'Supplier')
@section('content')
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <div class="card card-flush">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Supplier</h3>
          <span class="text-muted fs-8">Pemasok ayam. Supplier yang sudah dipakai pembelian tidak bisa dihapus, hanya dinonaktifkan.</span>
        </div>
        <div class="card-toolbar">
          <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#m-supplier"
                  onclick="isiSupplier(null)"><i class="ki-outline ki-plus fs-3"></i> Tambah Supplier</button>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Nama</th><th>Telepon</th><th>Alamat</th><th>Status</th><th class="text-end pe-4">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse ($suppliers as $s)
              <tr>
                <td class="ps-4 fw-bold text-gray-800">{{ $s->name }}
                  @if ($s->notes)<div class="fs-8 text-muted">{{ $s->notes }}</div>@endif
                </td>
                <td>{{ $s->phone ?: '-' }}</td>
                <td class="text-muted fs-8">{{ $s->address ?: '-' }}</td>
                <td>
                  <span class="badge badge-light-{{ $s->is_active ? 'success' : 'secondary' }}">
                    {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </td>
                <td class="text-end pe-4">
                  <button class="btn btn-sm btn-light-primary py-1 px-3 fs-8" data-bs-toggle="modal" data-bs-target="#m-supplier"
                          onclick='isiSupplier(@json($s))'>Ubah</button>
                  <form action="{{ route('farm.suppliers.toggle', $s->id) }}" method="POST" class="d-inline">@csrf
                    <button class="btn btn-sm btn-light py-1 px-3 fs-8">{{ $s->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                  </form>
                  <form action="{{ route('farm.suppliers.destroy', $s->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Hapus supplier ini?')">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-light-danger py-1 px-3 fs-8">Hapus</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted py-10">Belum ada supplier.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="m-supplier" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="f-supplier" action="{{ route('farm.suppliers.store') }}">
        @csrf <input type="hidden" name="_method" id="sp-method" value="POST">
        <div class="modal-header py-4"><h3 class="fw-bold mb-0" id="sp-judul">Tambah Supplier</h3>
          <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label fw-semibold fs-7 required">Nama</label>
            <input name="name" id="sp-name" class="form-control form-control-solid" required maxlength="100"></div>
          <div class="mb-3"><label class="form-label fw-semibold fs-7">Telepon</label>
            <input name="phone" id="sp-phone" class="form-control form-control-solid" maxlength="30"></div>
          <div class="mb-3"><label class="form-label fw-semibold fs-7">Alamat</label>
            <input name="address" id="sp-address" class="form-control form-control-solid" maxlength="255"></div>
          <div class="mb-1"><label class="form-label fw-semibold fs-7">Catatan</label>
            <input name="notes" id="sp-notes" class="form-control form-control-solid" maxlength="255"></div>
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
  function isiSupplier(s) {
    var f = document.getElementById('f-supplier');
    document.getElementById('sp-judul').textContent = s ? 'Ubah Supplier' : 'Tambah Supplier';
    document.getElementById('sp-method').value = s ? 'PUT' : 'POST';
    f.action = s ? '{{ url('admin/farm/suppliers') }}/' + s.id : '{{ route('farm.suppliers.store') }}';
    ['name','phone','address','notes'].forEach(function (k) {
      document.getElementById('sp-' + k).value = s ? (s[k] || '') : '';
    });
  }
</script>
@endpush
