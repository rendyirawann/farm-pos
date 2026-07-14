@extends('backend.layout.app')
@section('title', 'Affiliate')
@section('content')

    <div class="app-container container-fluid">
        <div class="d-flex flex-wrap flex-stack mb-6">
            <h1 class="fw-bold my-1 fs-2">Program Affiliate
                <span class="fs-6 text-gray-500 fw-semibold ms-1">Kelola afiliator, referral, & komisi</span>
            </h1>
            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#Modal_Tambah_Aff">
                <i class="ki-outline ki-plus fs-3"></i> Tambah Afiliator
            </button>
        </div>

        {{-- Stats --}}
        <div class="row g-4 mb-6">
            @php($cards = [
                ['Afiliator', $stats['total'], 'primary', 'ki-people'],
                ['Menunggu', $stats['pending'], 'warning', 'ki-time'],
                ['Aktif', $stats['active'], 'success', 'ki-check-circle'],
                ['Referral', $stats['referrals'], 'info', 'ki-share'],
            ])
            @foreach ($cards as [$label, $val, $c, $icon])
                <div class="col-6 col-xl-3">
                    <div class="card card-flush border border-{{ $c }} border-dashed">
                        <div class="card-body d-flex align-items-center gap-3 py-4">
                            <i class="ki-outline {{ $icon }} fs-2x text-{{ $c }}"></i>
                            <div><div class="fs-2x fw-bold text-gray-900">{{ $val }}</div>
                                <div class="fs-7 text-muted">{{ $label }}</div></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="row g-4 mb-6">
            <div class="col-6"><div class="card card-flush bg-light-danger"><div class="card-body py-4">
                <div class="fs-7 text-muted">Komisi belum dibayar</div>
                <div class="fs-2x fw-bold text-danger">Rp {{ number_format($stats['unpaid'], 0, ',', '.') }}</div>
            </div></div></div>
            <div class="col-6"><div class="card card-flush bg-light-success"><div class="card-body py-4">
                <div class="fs-7 text-muted">Komisi sudah dibayar</div>
                <div class="fs-2x fw-bold text-success">Rp {{ number_format($stats['paid'], 0, ',', '.') }}</div>
            </div></div></div>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" id="search" class="form-control form-control-solid w-250px ps-12" placeholder="Cari afiliator..." />
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-affiliates">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-40px">No</th>
                            <th>Afiliator</th>
                            <th>Kode</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th>Referral</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div class="modal fade" id="Modal_Tambah_Aff" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h2 class="fw-bold">Tambah Afiliator</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></button></div>
                <form id="FormTambahAff">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label required">Nama</label>
                                <input type="text" name="name" class="form-control form-control-solid" placeholder="Nama afiliator">
                                <span class="text-danger error-text fs-8 name_error_add"></span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control form-control-solid">
                                <span class="text-danger error-text fs-8 email_error_add"></span>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. WA</label>
                                <input type="text" name="phone" class="form-control form-control-solid">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Tipe</label>
                                <select name="type" id="aff-type" class="form-select form-select-solid">
                                    <option value="external">Eksternal (bukan pengguna POS)</option>
                                    <option value="tenant">Tenant (pelanggan POS)</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-none" id="aff-tenant-wrap">
                                <label class="form-label required">Tenant</label>
                                <select name="tenant_id" class="form-select form-select-solid">
                                    <option value="">— pilih tenant —</option>
                                    @foreach ($tenants as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                                <span class="text-danger error-text fs-8 tenant_id_error_add"></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">Simpan</span>
                            <span class="indicator-progress" style="display:none;">Harap tunggu... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Referral --}}
    <div class="modal fade" id="Modal_Referrals" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header"><h2 class="fw-bold" id="ref-title">Referral</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></button></div>
                <div class="modal-body">
                    <div class="alert bg-light-primary d-flex align-items-center gap-3 mb-4">
                        <i class="ki-outline ki-link fs-2x text-primary"></i>
                        <div class="fs-7"><span class="text-muted d-block">Link referral</span><b id="ref-url" class="text-primary"></b></div>
                    </div>
                    <div id="ref-body"></div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('stylesheets')
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="stylesheet" href="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.css') }}" />
@endpush

@push('scripts')
    <script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <script>
        $(document).ready(function () {
            const CSRF = $('meta[name="csrf-token"]').attr('content');
            const BASE = "{{ url('admin/affiliates') }}";
            const rupiah = n => 'Rp ' + Number(n || 0).toLocaleString('id-ID');

            let table = $('#table-affiliates').DataTable({
                processing: true, serverSide: true, order: false,
                ajax: "{{ route('affiliates.data') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'affiliate', name: 'name' },
                    { data: 'code', name: 'code' },
                    { data: 'type', name: 'type', orderable: false },
                    { data: 'status', name: 'status', orderable: false },
                    { data: 'referrals', name: 'referrals', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' },
                ],
            });
            let t;
            $('#search').on('keyup', function () { clearTimeout(t); const v = this.value; t = setTimeout(() => table.search(v).draw(), 500); });

            $('#aff-type').on('change', function () { $('#aff-tenant-wrap').toggleClass('d-none', this.value !== 'tenant'); });

            function done(res) {
                table.ajax.reload();
                if (typeof Swal !== 'undefined') Swal.fire(res.judul || 'Berhasil', res.success, 'success');
            }
            function fail(xhr) { Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.error) || 'Terjadi kesalahan.', 'error'); }

            // Tambah
            $('#FormTambahAff').on('submit', function (e) {
                e.preventDefault(); const f = $(this); f.find('.error-text').text('');
                f.find('[type=submit]').prop('disabled', true); f.find('.indicator-label').hide(); f.find('.indicator-progress').show();
                $.ajax({ url: "{{ route('affiliates.store') }}", method: 'POST', data: new FormData(this), processData: false, contentType: false,
                    success: res => {
                        f.find('[type=submit]').prop('disabled', false); f.find('.indicator-label').show(); f.find('.indicator-progress').hide();
                        if (res.errors) { $.each(res.errors, (k, v) => f.find('.' + k + '_error_add').text(v[0])); return; }
                        $('#Modal_Tambah_Aff').modal('hide'); this.reset(); $('#aff-tenant-wrap').addClass('d-none'); done(res);
                    },
                    error: xhr => { f.find('[type=submit]').prop('disabled', false); f.find('.indicator-label').show(); f.find('.indicator-progress').hide(); fail(xhr); } });
            });

            // Status (aktif/suspend)
            $('body').on('click', '.btn-status', function () {
                const id = $(this).data('id'); const to = $(this).data('to');
                $.ajax({ url: BASE + '/' + id + '/status', method: 'POST', data: { _token: CSRF, to },
                    success: done, error: fail });
            });

            // Hapus
            $('body').on('click', '.btn-del-aff', function () {
                const id = $(this).data('id'); const name = $(this).data('name');
                Swal.fire({ title: 'Hapus afiliator?', html: '<b>' + $('<div>').text(name).html() + '</b> beserta data referralnya akan dihapus.',
                    icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal',
                    customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-light' }, buttonsStyling: false })
                    .then(r => { if (!r.isConfirmed) return;
                        $.ajax({ url: BASE + '/' + id, method: 'DELETE', data: { _token: CSRF }, success: done, error: fail }); });
            });

            // Lihat referral
            $('body').on('click', '.btn-referrals', function () {
                const id = $(this).data('id');
                $.get(BASE + '/' + id + '/referrals', function (res) {
                    $('#ref-title').text('Referral — ' + res.affiliate.name + ' (' + res.affiliate.code + ')');
                    $('#ref-url').text(res.affiliate.url);
                    if (!res.referrals.length) { $('#ref-body').html('<div class="text-center text-muted py-8">Belum ada tenant yang memakai kode ini.</div>'); }
                    else {
                        let h = '<table class="table table-row-dashed align-middle fs-7"><thead><tr class="fw-bold text-muted"><th>Tenant</th><th>Tanggal</th><th>Komisi</th><th class="text-end">Aksi</th></tr></thead><tbody>';
                        res.referrals.forEach(r => {
                            const paid = r.commission_status === 'paid';
                            const badge = paid ? '<span class="badge badge-light-success">Lunas</span>' : '<span class="badge badge-light-warning">Belum</span>';
                            h += '<tr><td class="fw-bold text-gray-800">' + $('<div>').text(r.tenant).html() + '</td><td>' + r.date + '</td>'
                               + '<td>' + rupiah(r.commission) + ' ' + badge + '</td>'
                               + '<td class="text-end">' + (paid ? '<span class="text-muted fs-8">—</span>' : '<button class="btn btn-sm btn-light-success btn-pay" data-id="' + r.id + '">Cairkan</button>') + '</td></tr>';
                        });
                        h += '</tbody></table>';
                        $('#ref-body').html(h);
                    }
                    $('#Modal_Referrals').modal('show');
                }).fail(() => Swal.fire('Gagal', 'Tidak bisa memuat referral.', 'error'));
            });

            // Cairkan komisi
            $('body').on('click', '.btn-pay', function () {
                const id = $(this).data('id'); const btn = $(this);
                Swal.fire({ title: 'Cairkan komisi?', text: 'Tandai komisi referral ini sebagai LUNAS.', icon: 'question',
                    showCancelButton: true, confirmButtonText: 'Ya, Cairkan', cancelButtonText: 'Batal',
                    customClass: { confirmButton: 'btn btn-success', cancelButton: 'btn btn-light' }, buttonsStyling: false })
                    .then(r => { if (!r.isConfirmed) return;
                        $.ajax({ url: "{{ url('admin/referrals') }}/" + id + '/pay', method: 'POST', data: { _token: CSRF },
                            success: res => { btn.closest('tr').find('td:eq(2)').append(' '); table.ajax.reload();
                                Swal.fire(res.judul || 'Berhasil', res.success, 'success'); $('#Modal_Referrals').modal('hide'); },
                            error: fail }); });
            });
        });
    </script>
@endpush
