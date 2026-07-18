@extends('backend.layout.app')
@section('title', 'Channel VA DOKU')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <div class="alert alert-primary d-flex flex-column">
            <div class="fw-bold fs-5 mb-1">Channel Virtual Account DOKU (SNAP)</div>
            <div class="fs-7">
                Environment aktif: <b>{{ strtoupper($currentEnv) }}</b> · Driver pembayaran: <b>{{ strtoupper($driver) }}</b>.
                Ambil nilai dari dashboard DOKU → <b>Payment Virtual Account → Configure</b> bank (pakai baris <b>SNAP</b>).
                <b>Partner Service ID / Merchant BIN</b> = isi kolom di bawah dengan <b>Merchant BIN</b> (angka penuh, mis. 139250).
                Jangan lupa isi <b>Payment Notification URL</b> di DOKU per bank: <code>https://mooda.id/doku/snap/notify</code>.
                @if ($driver !== 'doku')
                    <div class="text-danger mt-1">Catatan: driver publik masih <b>{{ strtoupper($driver) }}</b> — channel ini baru dipakai pelanggan saat driver = <b>doku</b>.</div>
                @endif
            </div>
        </div>

        {{-- FORM TAMBAH --}}
        <div class="card card-flush mb-6">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Tambah Channel</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('doku-channels.store') }}" class="row g-4">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label required">Nama Tampil</label>
                        <input type="text" name="name" class="form-control form-control-solid" placeholder="Bank BRI" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Channel DOKU</label>
                        <select name="channel" class="form-select form-select-solid" required>
                            <option value="">— pilih bank —</option>
                            @foreach ($options as $val => $label)
                                <option value="{{ $val }}">{{ $label }} ({{ $val }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label required">Merchant BIN</label>
                        <input type="text" name="partner_service_id" class="form-control form-control-solid" placeholder="139250" required>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Prefix</label>
                        <input type="text" name="prefix_customer" class="form-control form-control-solid" placeholder="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label required">Environment</label>
                        <select name="environment" class="form-select form-select-solid" required>
                            <option value="production" @selected($currentEnv === 'production')>Production</option>
                            <option value="sandbox" @selected($currentEnv === 'sandbox')>Sandbox</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
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
                            <th>Nama</th><th>Channel</th><th>Merchant BIN</th><th>Prefix</th><th>Env</th><th>Status</th><th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($channels as $c)
                            <tr>
                                <td class="fw-bold">{{ $c->name }}</td>
                                <td><span class="badge badge-light-info">{{ $c->channel }}</span></td>
                                <td>{{ $c->partner_service_id }}</td>
                                <td>{{ $c->prefix_customer ?? '-' }}</td>
                                <td><span class="badge badge-light-{{ $c->environment === 'production' ? 'danger' : 'warning' }}">{{ $c->environment }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('doku-channels.toggle', $c) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-light-{{ $c->is_active ? 'success' : 'secondary' }}">{{ $c->is_active ? 'Aktif' : 'Nonaktif' }}</button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light-primary btn-edit"
                                        data-id="{{ $c->id }}" data-name="{{ $c->name }}" data-channel="{{ $c->channel }}"
                                        data-psid="{{ $c->partner_service_id }}" data-prefix="{{ $c->prefix_customer }}"
                                        data-env="{{ $c->environment }}">Edit</button>
                                    <form method="POST" action="{{ route('doku-channels.destroy', $c) }}" class="d-inline"
                                        onsubmit="return confirm('Hapus channel {{ $c->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-6">Belum ada channel. Tambahkan di atas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

{{-- MODAL EDIT --}}
<div class="modal fade" id="editChannelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="editChannelForm">
                @csrf @method('PUT')
                <div class="modal-header"><h3 class="modal-title">Edit Channel</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label required">Nama Tampil</label><input type="text" name="name" id="edit-name" class="form-control form-control-solid" required></div>
                    <div class="mb-3"><label class="form-label required">Channel DOKU</label>
                        <select name="channel" id="edit-channel" class="form-select form-select-solid" required>
                            @foreach ($options as $val => $label)<option value="{{ $val }}">{{ $label }} ({{ $val }})</option>@endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3"><label class="form-label required">Merchant BIN</label><input type="text" name="partner_service_id" id="edit-psid" class="form-control form-control-solid" required></div>
                        <div class="col-6 mb-3"><label class="form-label">Prefix</label><input type="text" name="prefix_customer" id="edit-prefix" class="form-control form-control-solid"></div>
                    </div>
                    <div class="mb-3"><label class="form-label required">Environment</label>
                        <select name="environment" id="edit-env" class="form-select form-select-solid" required>
                            <option value="production">Production</option><option value="sandbox">Sandbox</option>
                        </select>
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
    document.querySelectorAll('.btn-edit').forEach(function (b) {
        b.addEventListener('click', function () {
            const d = b.dataset;
            document.getElementById('editChannelForm').action = "{{ url('admin/doku-channels') }}/" + d.id;
            document.getElementById('edit-name').value = d.name;
            document.getElementById('edit-channel').value = d.channel;
            document.getElementById('edit-psid').value = d.psid;
            document.getElementById('edit-prefix').value = d.prefix || '';
            document.getElementById('edit-env').value = d.env;
            new bootstrap.Modal(document.getElementById('editChannelModal')).show();
        });
    });
</script>
@endpush
@endsection
