@extends('backend.layout.app')
@section('title', 'Pelanggan Laundry')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

        <div class="card card-flush mb-6">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Tambah Pelanggan</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('laundry.customers.store') }}" class="row g-4 align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="form-label required">Nama</label><input type="text" name="name" class="form-control form-control-solid" required></div>
                    <div class="col-md-2"><label class="form-label">No. WA/HP</label><input type="text" name="phone" class="form-control form-control-solid" placeholder="0812..."></div>
                    <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control form-control-solid"></div>
                    <div class="col-md-2"><label class="form-label required">Member</label>
                        <select name="member_status" class="form-select form-select-solid"><option value="regular">Regular</option><option value="vip">VIP (diskon 10%)</option></select></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Tambah</button></div>
                    <div class="col-12"><label class="form-label">Alamat</label><input type="text" name="address" class="form-control form-control-solid"></div>
                </form>
            </div>
        </div>

        <div class="card card-flush">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Daftar Pelanggan</h3></div>
            <div class="card-body">
                <table class="table table-row-dashed align-middle gy-3">
                    <thead><tr class="fw-bold text-muted"><th>Nama</th><th>HP</th><th>Member</th><th>Poin</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($customers as $c)
                            <tr>
                                <td class="fw-bold">{{ $c->name }}<div class="fs-8 text-muted">{{ $c->email }}</div></td>
                                <td>{{ $c->phone ?? '-' }}</td>
                                <td><span class="badge badge-light-{{ $c->member_status === 'vip' ? 'warning' : 'secondary' }}">{{ strtoupper($c->member_status) }}</span></td>
                                <td>{{ number_format($c->loyalty_points, 0, ',', '.') }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light-primary btn-edit" data-id="{{ $c->id }}" data-name="{{ $c->name }}"
                                        data-phone="{{ $c->phone }}" data-email="{{ $c->email }}" data-address="{{ $c->address }}" data-member="{{ $c->member_status }}">Edit</button>
                                    <form method="POST" action="{{ route('laundry.customers.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Hapus {{ $c->name }}?')">
                                        @csrf @method('DELETE')<button class="btn btn-sm btn-light-danger">Hapus</button></form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-6">Belum ada pelanggan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $customers->links() }}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editCustModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="POST" id="editCustForm">@csrf @method('PUT')
            <div class="modal-header"><h3 class="modal-title">Edit Pelanggan</h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label required">Nama</label><input type="text" name="name" id="c-name" class="form-control form-control-solid" required></div>
                <div class="mb-3"><label class="form-label">No. WA/HP</label><input type="text" name="phone" id="c-phone" class="form-control form-control-solid"></div>
                <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" id="c-email" class="form-control form-control-solid"></div>
                <div class="mb-3"><label class="form-label">Alamat</label><input type="text" name="address" id="c-address" class="form-control form-control-solid"></div>
                <div class="mb-2"><label class="form-label required">Member</label>
                    <select name="member_status" id="c-member" class="form-select form-select-solid"><option value="regular">Regular</option><option value="vip">VIP (diskon 10%)</option></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div></div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.btn-edit').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('editCustForm').action = "{{ url('admin/laundry/customers') }}/" + b.dataset.id;
            document.getElementById('c-name').value = b.dataset.name || '';
            document.getElementById('c-phone').value = b.dataset.phone || '';
            document.getElementById('c-email').value = b.dataset.email || '';
            document.getElementById('c-address').value = b.dataset.address || '';
            document.getElementById('c-member').value = b.dataset.member || 'regular';
            new bootstrap.Modal(document.getElementById('editCustModal')).show();
        });
    });
</script>
@endpush
@endsection
