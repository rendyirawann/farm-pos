@extends('backend.layout.app')
@section('title', 'Pengeluaran')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- Ringkasan pengeluaran hari ini (diambil dari uang laci) --}}
            <div class="row g-5 mb-6">
                <div class="col-md-6">
                    <div class="card bg-light-danger border-0 shadow-sm h-100">
                        <div class="card-body p-6">
                            <div class="fs-6 fw-semibold text-danger mb-1">Total Pengeluaran Hari Ini</div>
                            <div class="fs-2x fw-bold text-gray-800">Rp {{ number_format($spent, 0, ',', '.') }}</div>
                            <div class="fs-8 text-muted">Total pengeluaran tanggal ini (gabungan semua shift). Kolom <b>Shift</b> di tabel menandai dari laci/shift mana tiap pengeluaran.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabel pengeluaran --}}
            <div class="card card-flush shadow-sm">
                <div class="card-header pt-6">
                    <h3 class="card-title fw-bold text-gray-800 fs-3">Catatan Pengeluaran</h3>
                    <div class="card-toolbar">
                        <button class="btn btn-danger" id="btn-open-add-expense">
                            <i class="ki-outline ki-plus fs-3"></i> Catat Pengeluaran
                        </button>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-expenses">
                            <thead>
                                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="w-40px">No</th>
                                    <th>Tanggal</th>
                                    <th>Kategori / Keterangan</th>
                                    <th>Nominal</th>
                                    <th>Dicatat Oleh</th>
                                    <th>Shift</th>
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

    {{-- ===== Modal Tambah/Edit Pengeluaran ===== --}}
    <div class="modal fade" id="modal-expense" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-600px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold text-danger" id="modal-expense-title">Catat Pengeluaran</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body mx-5 my-5">
                    <form id="form-expense">
                        @csrf
                        <input type="hidden" name="expense_id" id="expense_id">
                        <div class="mb-5">
                            <label class="required fs-6 fw-semibold mb-2">Tanggal</label>
                            <input type="date" class="form-control" name="date" id="ex-date" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" required>
                        </div>
                        <div class="mb-5">
                            <label class="required fs-6 fw-semibold mb-2">Kategori</label>
                            <input type="text" class="form-control" name="category" id="ex-category" placeholder="Contoh: Bahan Baku, Listrik, Gaji" list="ex-cat-list" required>
                            <datalist id="ex-cat-list">
                                <option value="Bahan Baku"></option>
                                <option value="Gaji Karyawan"></option>
                                <option value="Listrik & Air"></option>
                                <option value="Sewa Tempat"></option>
                                <option value="Perlengkapan"></option>
                                <option value="Lain-lain"></option>
                            </datalist>
                        </div>
                        <div class="mb-5">
                            <label class="required fs-6 fw-semibold mb-2">Nominal Uang Keluar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light-danger text-danger fw-bold">Rp</span>
                                <input type="number" class="form-control fw-bold" name="amount" id="ex-amount" value="0" min="0" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="fs-6 fw-semibold mb-2">Keterangan (opsional)</label>
                            <textarea class="form-control" name="notes" id="ex-notes" rows="2" placeholder="Catatan tambahan"></textarea>
                        </div>
                        <div class="text-center pt-8">
                            <button type="submit" class="btn btn-danger w-100" id="btn-save-expense">Simpan Pengeluaran</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('stylesheets')
    <link rel="stylesheet" href="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.css') }}" />
@endpush

@push('scripts')
    <script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <script>
        const EX_ROUTES = {
            data:   "{{ route('expenses.data') }}",
            store:  "{{ route('expenses.store') }}",
            update: "{{ url('admin/expenses') }}",
            destroy:"{{ url('admin/expenses') }}",
        };
        const CSRF = "{{ csrf_token() }}";

        const dt = $('#table-expenses').DataTable({
            processing: true,
            serverSide: true,
            ajax: EX_ROUTES.data,
            order: [],
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'date', name: 'date' },
                { data: 'title', name: 'category' },
                { data: 'amount', name: 'amount' },
                { data: 'user', name: 'user.name', orderable: false, searchable: false },
                { data: 'shift', name: 'shift', orderable: false, searchable: false },
                { data: 'action', orderable: false, searchable: false, className: 'text-end' },
            ],
            language: { emptyTable: 'Belum ada catatan pengeluaran.', processing: 'Memuat...' },
        });

        function openExpenseModal(mode, row) {
            $('#form-expense')[0].reset();
            $('#expense_id').val('');
            if (mode === 'edit' && row) {
                $('#modal-expense-title').text('Ubah Pengeluaran');
                $('#expense_id').val(row.id);
                $('#ex-date').val(row.date);
                $('#ex-category').val(row.category);
                $('#ex-amount').val(row.amount);
                $('#ex-notes').val(row.notes || '');
            } else {
                $('#modal-expense-title').text('Catat Pengeluaran');
                $('#ex-date').val("{{ \Carbon\Carbon::today()->format('Y-m-d') }}");
            }
            $('#modal-expense').modal('show');
        }

        $('#btn-open-add-expense').on('click', () => openExpenseModal('add'));

        $('#table-expenses').on('click', '.btn-edit-expense', function () {
            openExpenseModal('edit', $(this).data('row'));
        });

        $('#form-expense').on('submit', function (e) {
            e.preventDefault();
            const id = $('#expense_id').val();
            const url = id ? (EX_ROUTES.update + '/' + id) : EX_ROUTES.store;
            const payload = $(this).serialize() + (id ? '&_method=PUT' : '');
            $('#btn-save-expense').prop('disabled', true).text('Menyimpan...');
            $.ajax({
                url: url, method: 'POST', data: payload,
                success: function (res) {
                    $('#modal-expense').modal('hide');
                    dt.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 2500 })
                        .then(() => { if (!id) location.reload(); });
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && (xhr.responseJSON.message || 'Periksa kembali isian form.')) || 'Gagal menyimpan.';
                    Swal.fire('Gagal', msg, 'error');
                },
                complete: function () { $('#btn-save-expense').prop('disabled', false).text('Simpan Pengeluaran'); },
            });
        });

        $('#table-expenses').on('click', '.btn-del-expense', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Hapus pengeluaran ini?', icon: 'warning', showCancelButton: true,
                confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal',
                customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-light' }, buttonsStyling: false,
            }).then((r) => {
                if (!r.isConfirmed) return;
                $.ajax({
                    url: EX_ROUTES.destroy + '/' + id, method: 'POST', data: { _method: 'DELETE', _token: CSRF },
                    success: function (res) { dt.ajax.reload(null, false); Swal.fire({ icon: 'success', title: 'Terhapus', text: res.message, timer: 2000 }).then(() => location.reload()); },
                    error: function () { Swal.fire('Gagal', 'Gagal menghapus data.', 'error'); },
                });
            });
        });
    </script>
@endpush
