@extends('backend.layout.app')
@section('title', 'Manajemen Meja')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card card-flush shadow-sm">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-800 fs-2">Manajemen Meja</h3>
                    <div class="card-toolbar">
                        <button class="btn btn-primary" id="btn-open-add-table">
                            <i class="ki-outline ki-plus fs-3"></i> Tambah Meja
                        </button>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <p class="text-muted fs-7 mb-4">Meja di sini muncul sebagai pilihan di Kasir (menggantikan pilihan statis 1–25).</p>
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-tables">
                            <thead>
                                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="w-40px">No</th>
                                    <th>Nama Meja</th>
                                    <th>Area</th>
                                    <th>Kapasitas</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-table" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-550px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold" id="modal-table-title">Tambah Meja</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body mx-5 my-5">
                    <form id="form-table">
                        @csrf
                        <input type="hidden" name="table_id" id="table_id">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="required fs-6 fw-semibold mb-2">Nama / Nomor Meja</label>
                                <input type="text" class="form-control" name="name" id="t-name" placeholder="mis. 1, A1, VIP 2" required>
                            </div>
                            <div class="col-md-6">
                                <label class="fs-6 fw-semibold mb-2">Area (opsional)</label>
                                <input type="text" class="form-control" name="area" id="t-area" placeholder="mis. Lantai 1, Outdoor">
                            </div>
                            <div class="col-md-6">
                                <label class="fs-6 fw-semibold mb-2">Kapasitas (org)</label>
                                <input type="number" class="form-control" name="capacity" id="t-capacity" min="1" placeholder="mis. 4">
                            </div>
                            <div class="col-md-6">
                                <label class="fs-6 fw-semibold mb-2">Urutan</label>
                                <input type="number" class="form-control" name="sort_order" id="t-sort" min="0" value="0">
                            </div>
                            <div class="col-12">
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="t-active" value="1" checked>
                                    <span class="form-check-label fw-semibold">Aktif (tampil di kasir)</span>
                                </label>
                            </div>
                        </div>
                        <div class="text-center pt-8">
                            <button type="submit" class="btn btn-primary w-100" id="btn-save-table">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <script>
        const T_ROUTES = { data: "{{ route('tables.data') }}", store: "{{ route('tables.store') }}", base: "{{ url('admin/tables') }}" };
        const CSRF = "{{ csrf_token() }}";

        const dt = $('#table-tables').DataTable({
            processing: true, serverSide: true, ajax: T_ROUTES.data, order: [],
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'area', name: 'area' },
                { data: 'capacity', name: 'capacity', orderable: false },
                { data: 'status', name: 'is_active' },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' },
            ],
            language: { emptyTable: 'Belum ada meja. Tambahkan meja pertama Anda.', processing: 'Memuat...' },
        });

        function openTableModal(mode, row) {
            $('#form-table')[0].reset();
            $('#table_id').val('');
            $('#t-active').prop('checked', true);
            if (mode === 'edit' && row) {
                $('#modal-table-title').text('Ubah Meja');
                $('#table_id').val(row.id);
                $('#t-name').val(row.name);
                $('#t-area').val(row.area || '');
                $('#t-capacity').val(row.capacity || '');
                $('#t-sort').val(row.sort_order || 0);
                $('#t-active').prop('checked', !!row.is_active);
            } else {
                $('#modal-table-title').text('Tambah Meja');
            }
            $('#modal-table').modal('show');
        }

        $('#btn-open-add-table').on('click', () => openTableModal('add'));
        $('#table-tables').on('click', '.btn-edit-table', function () { openTableModal('edit', $(this).data('row')); });

        $('#form-table').on('submit', function (e) {
            e.preventDefault();
            const id = $('#table_id').val();
            const url = id ? (T_ROUTES.base + '/' + id) : T_ROUTES.store;
            let data = $(this).serialize();
            if (!$('#t-active').is(':checked')) data += '&is_active=0';
            if (id) data += '&_method=PUT';
            $('#btn-save-table').prop('disabled', true).text('Menyimpan...');
            $.ajax({
                url: url, method: 'POST', data: data,
                success: function (res) {
                    $('#modal-table').modal('hide'); dt.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 2000, showConfirmButton: false });
                },
                error: function (xhr) {
                    Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Periksa isian form.', 'error');
                },
                complete: function () { $('#btn-save-table').prop('disabled', false).text('Simpan'); },
            });
        });

        $('#table-tables').on('click', '.btn-del-table', function () {
            const id = $(this).data('id');
            Swal.fire({ title: 'Hapus meja ini?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal', buttonsStyling: false, customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-light' } })
                .then((r) => {
                    if (!r.isConfirmed) return;
                    $.ajax({ url: T_ROUTES.base + '/' + id, method: 'POST', data: { _method: 'DELETE', _token: CSRF },
                        success: function (res) { dt.ajax.reload(null, false); Swal.fire({ icon: 'success', title: 'Terhapus', text: res.message, timer: 1800, showConfirmButton: false }); },
                        error: function () { Swal.fire('Gagal', 'Gagal menghapus.', 'error'); } });
                });
        });
    </script>
@endpush
