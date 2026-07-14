@extends('backend.layout.app')
@section('title', 'Kategori Blog')
@section('content')

    <div class="app-container container-fluid">
        <div class="d-flex flex-wrap flex-stack mb-6">
            <h1 class="fw-bold my-1 fs-2">Kategori Blog</h1>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('blog.admin.posts.index') }}" class="btn btn-light fw-bold">
                    <i class="ki-outline ki-arrow-left fs-3"></i> Artikel
                </a>
                <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#Modal_Tambah_Cat">
                    <i class="ki-outline ki-plus fs-3"></i> Tambah Kategori
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" id="search" class="form-control form-control-solid w-250px ps-12" placeholder="Cari kategori..." />
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-cats">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-40px">No</th>
                            <th>Nama</th>
                            <th>Slug</th>
                            <th>Artikel</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tambah --}}
    <div class="modal fade" id="Modal_Tambah_Cat" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h2 class="fw-bold">Tambah Kategori</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></button></div>
                <form id="FormTambahCat">
                    @csrf
                    <div class="modal-body">
                        <label class="form-label required">Nama Kategori</label>
                        <input type="text" name="name" class="form-control form-control-solid" placeholder="mis. Tips Usaha">
                        <span class="text-danger error-text fs-8 name_error_add"></span>
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

    {{-- Edit --}}
    <div class="modal fade" id="Modal_Edit_Cat" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h2 class="fw-bold">Ubah Kategori</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></button></div>
                <form id="FormEditCat">
                    @csrf @method('PUT')
                    <input type="hidden" id="edit_cat_id">
                    <div class="modal-body">
                        <label class="form-label required">Nama Kategori</label>
                        <input type="text" name="name" id="edit_cat_name" class="form-control form-control-solid">
                        <span class="text-danger error-text fs-8 name_error_edit"></span>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">Simpan Perubahan</span>
                            <span class="indicator-progress" style="display:none;">Harap tunggu... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
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
            const BASE = "{{ url('admin/blog-categories') }}";

            let table = $('#table-cats').DataTable({
                processing: true, serverSide: true, order: false,
                ajax: "{{ route('blog.admin.categories.data') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'slug', name: 'slug' },
                    { data: 'posts_count', name: 'posts_count', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' },
                ],
            });
            let t;
            $('#search').on('keyup', function () { clearTimeout(t); const v = this.value; t = setTimeout(() => table.search(v).draw(), 500); });

            function btn(f, on) { f.find('[type=submit]').prop('disabled', on); f.find('.indicator-label').toggle(!on); f.find('.indicator-progress').toggle(on); }

            $('#FormTambahCat').on('submit', function (e) {
                e.preventDefault(); const f = $(this); f.find('.error-text').text(''); btn(f, true);
                $.ajax({
                    url: "{{ route('blog.admin.categories.store') }}", method: 'POST', data: new FormData(this), processData: false, contentType: false,
                    success: res => { btn(f, false);
                        if (res.errors) { $.each(res.errors, (k, v) => f.find('.' + k + '_error_add').text(v[0])); return; }
                        $('#Modal_Tambah_Cat').modal('hide'); this.reset(); table.ajax.reload();
                        if (typeof Swal !== 'undefined') Swal.fire(res.judul || 'Berhasil', res.success, 'success'); },
                    error: xhr => { btn(f, false); Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.error) || 'Terjadi kesalahan.', 'error'); },
                });
            });

            $('body').on('click', '.btn-edit', function () {
                $('#edit_cat_id').val($(this).data('id'));
                $('#edit_cat_name').val($(this).data('name'));
                $('#FormEditCat .error-text').text('');
                $('#Modal_Edit_Cat').modal('show');
            });

            $('#FormEditCat').on('submit', function (e) {
                e.preventDefault(); const f = $(this); f.find('.error-text').text(''); btn(f, true);
                const id = $('#edit_cat_id').val(); const fd = new FormData(this); fd.append('_method', 'PUT');
                $.ajax({
                    url: BASE + '/' + id, method: 'POST', data: fd, processData: false, contentType: false,
                    success: res => { btn(f, false);
                        if (res.errors) { $.each(res.errors, (k, v) => f.find('.' + k + '_error_edit').text(v[0])); return; }
                        $('#Modal_Edit_Cat').modal('hide'); table.ajax.reload();
                        if (typeof Swal !== 'undefined') Swal.fire(res.judul || 'Berhasil', res.success, 'success'); },
                    error: xhr => { btn(f, false); Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.error) || 'Terjadi kesalahan.', 'error'); },
                });
            });

            $('body').on('click', '.btn-delete', function () {
                const id = $(this).data('id'); const name = $(this).data('name');
                Swal.fire({ title: 'Hapus kategori?', html: 'Kategori <b>' + $('<div>').text(name).html() + '</b> akan dihapus. Artikel terkait tidak terhapus (kategorinya dikosongkan).',
                    icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal',
                    customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-light' }, buttonsStyling: false,
                }).then(r => { if (!r.isConfirmed) return;
                    $.ajax({ url: BASE + '/' + id, method: 'DELETE', data: { _token: CSRF },
                        success: res => { table.ajax.reload(); Swal.fire(res.judul || 'Berhasil', res.success, 'success'); },
                        error: xhr => Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.error) || 'Gagal menghapus.', 'error') });
                });
            });
        });
    </script>
@endpush
