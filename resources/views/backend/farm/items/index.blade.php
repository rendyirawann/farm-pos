@extends('backend.layout.app')
@section('title', 'Item')
@section('content')
@php
  $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
  $num = fn($n,$d=0) => number_format((float)$n, $d, ',', '.');
@endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <div class="alert alert-primary d-flex align-items-start py-3 fs-8">
      <i class="ki-outline ki-information-5 fs-2 me-2"></i>
      <div>Item kategori <b>Telur</b> otomatis ditandai sebagai hasil produksi — tidak muncul di form pembelian,
        dan harga pokoknya dihitung otomatis dari biaya operasional.</div>
    </div>

    <div class="card card-flush">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Item</h3>
          <span class="text-muted fs-8">Objek yang diperdagangkan beserta stoknya saat ini.</span>
        </div>
        <div class="card-toolbar">
          <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#m-item"
                  onclick="isiItem(null)"><i class="ki-outline ki-plus fs-3"></i> Tambah Item</button>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Nama</th><th>Kategori</th><th>Satuan</th>
              <th class="text-end">Stok</th><th class="text-end">Nilai</th>
              <th>Status</th><th class="text-end pe-4">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse ($items as $i)
              @php $s = $i->stock(); @endphp
              <tr>
                <td class="ps-4 fw-bold text-gray-800">{{ $i->name }}</td>
                <td><span class="badge badge-light-{{ $i->category === 'telur' ? 'warning' : 'success' }}">{{ $i->categoryLabel() }}</span></td>
                <td class="text-muted fs-8">{{ strtoupper($i->primary_unit) }}</td>
                <td class="text-end">
                  <span class="fw-bold">{{ $num($s['ekor']) }}</span>
                  <span class="fs-9 text-muted">{{ $i->category === 'telur' ? 'butir' : 'ekor' }}</span>
                  @if ($i->category !== 'telur')<div class="fs-8 text-muted">{{ $num($s['kg'], 2) }} kg</div>@endif
                </td>
                <td class="text-end">{{ $rp($i->stockValue()) }}</td>
                <td><span class="badge badge-light-{{ $i->is_active ? 'success' : 'secondary' }}">{{ $i->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td class="text-end pe-4">
                  <button class="btn btn-sm btn-light-primary py-1 px-3 fs-8" data-bs-toggle="modal" data-bs-target="#m-item"
                          onclick='isiItem(@json($i))'>Ubah</button>
                  <form action="{{ route('farm.items.toggle', $i->id) }}" method="POST" class="d-inline">@csrf
                    <button class="btn btn-sm btn-light py-1 px-3 fs-8">{{ $i->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-10">Belum ada item.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="m-item" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="f-item" action="{{ route('farm.items.store') }}">
        @csrf <input type="hidden" name="_method" id="it-method" value="POST">
        <div class="modal-header py-4"><h3 class="fw-bold mb-0" id="it-judul">Tambah Item</h3>
          <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
        </div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label fw-semibold fs-7 required">Nama</label>
            <input name="name" id="it-name" class="form-control form-control-solid" required maxlength="100"></div>
          <div class="mb-3"><label class="form-label fw-semibold fs-7 required">Kategori</label>
            <select name="category" id="it-category" class="form-select form-select-solid" required>
              @foreach ($categories as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
            </select></div>
          <div class="row g-3">
            <div class="col-6"><label class="form-label fw-semibold fs-7 required">Satuan Utama</label>
              <select name="primary_unit" id="it-primary_unit" class="form-select form-select-solid" required>
                <option value="kg">KG</option><option value="ekor">Ekor</option><option value="butir">Butir</option>
              </select></div>
            <div class="col-6"><label class="form-label fw-semibold fs-7">Stok Minimum (kg)</label>
              <input type="number" name="min_stock_kg" id="it-min_stock_kg" class="form-control form-control-solid" min="0" step="0.01" value="0"></div>
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
  function isiItem(i) {
    var f = document.getElementById('f-item');
    document.getElementById('it-judul').textContent = i ? 'Ubah Item' : 'Tambah Item';
    document.getElementById('it-method').value = i ? 'PUT' : 'POST';
    f.action = i ? '{{ url('admin/farm/items') }}/' + i.id : '{{ route('farm.items.store') }}';
    document.getElementById('it-name').value = i ? i.name : '';
    document.getElementById('it-category').value = i ? i.category : 'ayam_potong';
    document.getElementById('it-primary_unit').value = i ? i.primary_unit : 'kg';
    document.getElementById('it-min_stock_kg').value = i ? (i.min_stock_kg || 0) : 0;
  }
</script>
@endpush
