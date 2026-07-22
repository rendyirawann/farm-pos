@extends('backend.layout.app')
@section('title', 'Channel Tripay')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="alert alert-primary d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div class="fw-bold fs-5 mb-1">Channel Pembayaran Tripay</div>
                <form method="POST" action="{{ route('tripay-channels.sync') }}" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').innerHTML='Menyinkron…';">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="ki-outline ki-arrows-circle fs-5"></i> Sinkron dari Tripay
                    </button>
                </form>
            </div>
            <div class="fs-7">
                Mode: <b>{{ $production ? 'PRODUCTION' : 'SANDBOX' }}</b> · Driver aktif: <b>{{ strtoupper($driver) }}</b>.
                Isi <b>Kode channel</b> sesuai dashboard Tripay (<b>Channel Pembayaran</b>), mis. <code>QRIS</code>, <code>BRIVA</code>, <code>OVO</code>.
                Customer akan memilih dari channel yang <b>Aktif</b> saat checkout langganan / top-up deposit.
                <div class="mt-1"><b>Sinkron dari Tripay</b>: otomatis mengambil channel yang aktif di merchant Tripay Anda &amp; menyamakannya (aktifkan yang cocok, nonaktifkan sisanya) — cara termudah &amp; anti-salah.</div>
                @if ($driver !== 'tripay')
                    <div class="text-danger mt-1">Catatan: driver aktif masih <b>{{ strtoupper($driver) }}</b> — channel ini baru dipakai pelanggan saat driver = <b>tripay</b> (atur di Payment → Payment Gateway).</div>
                @endif
            </div>
        </div>

        {{-- FORM TAMBAH --}}
        <div class="card card-flush mb-6">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Tambah Channel</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('tripay-channels.store') }}" class="row g-4 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label required">Nama Tampil</label>
                        <input type="text" name="name" class="form-control form-control-solid" placeholder="QRIS" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Kode Channel Tripay</label>
                        <input type="text" name="code" class="form-control form-control-solid text-uppercase" list="tripayCodes" placeholder="QRIS" required>
                        <datalist id="tripayCodes">
                            @foreach ($suggested as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Grup</label>
                        <input type="text" name="group" class="form-control form-control-solid" placeholder="Virtual Account / E-Wallet / QRIS">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="sort_order" class="form-control form-control-solid" value="0" min="0">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">Tambah</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABEL --}}
        <div class="card card-flush">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Daftar Channel</h3></div>
            <div class="card-body">
                <table class="table table-row-dashed align-middle gy-3">
                    <thead>
                        <tr class="fw-bold text-muted">
                            <th>Nama</th><th>Kode</th><th>Grup</th><th>Urutan</th><th>Status</th><th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($channels as $c)
                            <tr>
                                <td class="fw-bold">{{ $c->name }}</td>
                                <td><span class="badge badge-light-info">{{ $c->code }}</span></td>
                                <td>{{ $c->group ?? '-' }}</td>
                                <td>{{ $c->sort_order }}</td>
                                <td>
                                    <form method="POST" action="{{ route('tripay-channels.toggle', $c) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-light-{{ $c->is_active ? 'success' : 'secondary' }}">{{ $c->is_active ? 'Aktif' : 'Nonaktif' }}</button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light-primary btn-edit"
                                        data-id="{{ $c->id }}" data-name="{{ $c->name }}" data-code="{{ $c->code }}"
                                        data-group="{{ $c->group }}" data-sort="{{ $c->sort_order }}">Edit</button>
                                    <form method="POST" action="{{ route('tripay-channels.destroy', $c) }}" class="d-inline"
                                        onsubmit="return confirm('Hapus channel {{ $c->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-6">Belum ada channel. Tambahkan di atas (kode dari dashboard Tripay).</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="editTripayChannelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="editTripayForm">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h3 class="modal-title">Edit Channel</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label required">Nama Tampil</label>
                        <input type="text" name="name" id="edit-name" class="form-control form-control-solid" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label required">Kode Channel Tripay</label>
                        <input type="text" name="code" id="edit-code" class="form-control form-control-solid text-uppercase" list="tripayCodes" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Grup</label>
                        <input type="text" name="group" id="edit-group" class="form-control form-control-solid">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="sort_order" id="edit-sort" class="form-control form-control-solid" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('editTripayForm').action = "{{ url('admin/tripay-channels') }}/" + btn.dataset.id;
            document.getElementById('edit-name').value = btn.dataset.name || '';
            document.getElementById('edit-code').value = btn.dataset.code || '';
            document.getElementById('edit-group').value = btn.dataset.group || '';
            document.getElementById('edit-sort').value = btn.dataset.sort || 0;
            new bootstrap.Modal(document.getElementById('editTripayChannelModal')).show();
        });
    });
</script>
@endpush
@endsection
