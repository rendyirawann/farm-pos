@extends('backend.layout.app')
@section('title', 'Layanan Laundry')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

        <div class="alert alert-primary fs-7">
            Kelola <b>layanan cuci</b>. Satuan <b>kg</b> = kiloan; <b>pcs/meter/pasang</b> = satuan. <b>Express</b> dibuat sebagai layanan tersendiri (harga lebih tinggi, durasi lebih pendek).
        </div>

        {{-- FORM TAMBAH --}}
        <div class="card card-flush mb-6">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Tambah Layanan</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('laundry.services.store') }}" class="row g-4 align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="form-label required">Nama</label>
                        <input type="text" name="name" class="form-control form-control-solid" placeholder="Cuci + Setrika" required></div>
                    <div class="col-md-2"><label class="form-label">Kategori</label>
                        <input type="text" name="category" class="form-control form-control-solid" placeholder="Reguler / Express"></div>
                    <div class="col-md-2"><label class="form-label required">Satuan</label>
                        <select name="unit" class="form-select form-select-solid" required>
                            @foreach ($units as $val => $label)<option value="{{ $val }}">{{ $label }}</option>@endforeach
                        </select></div>
                    <div class="col-md-2"><label class="form-label required">Harga / satuan</label>
                        <input type="number" name="price_per_unit" class="form-control form-control-solid" min="0" step="100" placeholder="7000" required></div>
                    <div class="col-md-2"><label class="form-label required">Estimasi (jam)</label>
                        <input type="number" name="estimated_duration_hours" class="form-control form-control-solid" min="1" value="48" required></div>
                    <div class="col-md-1"><button type="submit" class="btn btn-primary w-100">Tambah</button></div>
                </form>
            </div>
        </div>

        {{-- TABEL --}}
        <div class="card card-flush">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Daftar Layanan</h3></div>
            <div class="card-body">
                <table class="table table-row-dashed align-middle gy-3">
                    <thead><tr class="fw-bold text-muted">
                        <th>Nama</th><th>Kategori</th><th>Satuan</th><th>Harga</th><th>Estimasi</th><th>Status</th><th class="text-end">Aksi</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($services as $s)
                            <tr>
                                <td class="fw-bold">{{ $s->name }}</td>
                                <td>{{ $s->category ?? '-' }}</td>
                                <td><span class="badge badge-light-info">{{ $units[$s->unit] ?? $s->unit }}</span></td>
                                <td>Rp {{ number_format($s->price_per_unit, 0, ',', '.') }}</td>
                                <td>{{ $s->estimated_duration_hours }} jam</td>
                                <td>
                                    <form method="POST" action="{{ route('laundry.services.toggle', $s) }}" class="d-inline">@csrf
                                        <button class="btn btn-sm btn-light-{{ $s->is_active ? 'success' : 'secondary' }}">{{ $s->is_active ? 'Aktif' : 'Nonaktif' }}</button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light-primary btn-edit"
                                        data-id="{{ $s->id }}" data-name="{{ $s->name }}" data-category="{{ $s->category }}"
                                        data-unit="{{ $s->unit }}" data-price="{{ $s->price_per_unit }}" data-hours="{{ $s->estimated_duration_hours }}"
                                        data-sort="{{ $s->sort_order }}">Edit</button>
                                    <form method="POST" action="{{ route('laundry.services.destroy', $s) }}" class="d-inline" onsubmit="return confirm('Hapus {{ $s->name }}?')">
                                        @csrf @method('DELETE')<button class="btn btn-sm btn-light-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-6">Belum ada layanan. Tambahkan di atas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="editSvcModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="POST" id="editSvcForm">@csrf @method('PUT')
            <div class="modal-header"><h3 class="modal-title">Edit Layanan</h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label required">Nama</label><input type="text" name="name" id="e-name" class="form-control form-control-solid" required></div>
                <div class="mb-3"><label class="form-label">Kategori</label><input type="text" name="category" id="e-category" class="form-control form-control-solid"></div>
                <div class="mb-3"><label class="form-label required">Satuan</label>
                    <select name="unit" id="e-unit" class="form-select form-select-solid" required>
                        @foreach ($units as $val => $label)<option value="{{ $val }}">{{ $label }}</option>@endforeach
                    </select></div>
                <div class="mb-3"><label class="form-label required">Harga / satuan</label><input type="number" name="price_per_unit" id="e-price" class="form-control form-control-solid" min="0" step="100" required></div>
                <div class="mb-3"><label class="form-label required">Estimasi (jam)</label><input type="number" name="estimated_duration_hours" id="e-hours" class="form-control form-control-solid" min="1" required></div>
                <div class="mb-2"><label class="form-label">Urutan</label><input type="number" name="sort_order" id="e-sort" class="form-control form-control-solid" min="0"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div></div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.btn-edit').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('editSvcForm').action = "{{ url('admin/laundry/services') }}/" + b.dataset.id;
            document.getElementById('e-name').value = b.dataset.name || '';
            document.getElementById('e-category').value = b.dataset.category || '';
            document.getElementById('e-unit').value = b.dataset.unit || 'kg';
            document.getElementById('e-price').value = b.dataset.price || 0;
            document.getElementById('e-hours').value = b.dataset.hours || 48;
            document.getElementById('e-sort').value = b.dataset.sort || 0;
            new bootstrap.Modal(document.getElementById('editSvcModal')).show();
        });
    });
</script>
@endpush
@endsection
