@extends('backend.layout.app')
@section('title', 'Kelola Blog')
@section('content')

    <div class="app-container container-fluid">
        <div class="d-flex flex-wrap flex-stack mb-6">
            <h1 class="fw-bold my-1 fs-2">Kelola Blog
                <span class="fs-6 text-gray-500 fw-semibold ms-1">Artikel marketing (blog.mooda.id)</span>
            </h1>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('blog.admin.categories.index') }}" class="btn btn-light-primary fw-bold">
                    <i class="ki-outline ki-category fs-3"></i> Kategori
                </a>
                <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#Modal_Tambah_Post">
                    <i class="ki-outline ki-plus fs-3"></i> Tulis Artikel
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" id="search" class="form-control form-control-solid w-250px ps-12" placeholder="Cari artikel..." />
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-posts">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-40px">No</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Terbit</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== Modal Tambah ===== --}}
    <div class="modal fade" id="Modal_Tambah_Post" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Tulis Artikel</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></button>
                </div>
                <form id="FormTambahPost" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('backend.blog.posts._fields', ['mode' => 'add', 'categories' => $categories])
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

    {{-- ===== Modal Edit ===== --}}
    <div class="modal fade" id="Modal_Edit_Post" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Ubah Artikel</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></button>
                </div>
                <form id="FormEditPost" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="modal-body"><div id="EditPostBody"></div></div>
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

    {{-- ===== Modal Detail ===== --}}
    <div class="modal fade" id="Modal_Detail_Post" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Detail Artikel</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></button>
                </div>
                <div class="modal-body"><div id="DetailPostBody"></div></div>
            </div>
        </div>
    </div>

@endsection

@push('stylesheets')
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="stylesheet" href="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.css') }}" />
    <style>
        .ck-editor__editable_inline { min-height: 260px; }
        /* Pastikan dropdown/dialog CKEditor tampil di atas modal Bootstrap */
        .ck-body-wrapper { z-index: 100050 !important; }
    </style>
@endpush

@push('scripts')
    <script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/ckeditor/ckeditor-classic.bundle.js') }}"></script>
    <script>
        // CKEditor di dalam Bootstrap modal: cegah modal "mencuri" fokus dari dialog CKEditor (link/gambar).
        document.addEventListener('focusin', function (e) {
            if (e.target.closest('.ck-body-wrapper')) e.stopImmediatePropagation();
        }, true);

        $(document).ready(function () {
            const CSRF = $('meta[name="csrf-token"]').attr('content');
            const BASE = "{{ url('admin/blog') }}";

            let table = $('#table-posts').DataTable({
                processing: true, serverSide: true, order: false,
                ajax: "{{ route('blog.admin.posts.data') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'title', name: 'title' },
                    { data: 'category', name: 'category', orderable: false },
                    { data: 'status', name: 'status', orderable: false },
                    { data: 'published_at', name: 'published_at', orderable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' },
                ],
            });
            let t;
            $('#search').on('keyup', function () { clearTimeout(t); const v = this.value; t = setTimeout(() => table.search(v).draw(), 500); });

            // Editor untuk form Tambah (textarea sudah ada di modal statis).
            ClassicEditor.create(document.querySelector('#add_body')).then(ed => window.addEditor = ed).catch(console.error);

            function btn(f, on) {
                f.find('[type=submit]').prop('disabled', on);
                f.find('.indicator-label').toggle(!on);
                f.find('.indicator-progress').toggle(on);
            }
            function clearErr(f) { f.find('.error-text').text(''); }

            // ===== Tambah =====
            $('#FormTambahPost').on('submit', function (e) {
                e.preventDefault();
                const f = $(this); clearErr(f); btn(f, true);
                if (window.addEditor) window.addEditor.updateSourceElement();
                $.ajax({
                    url: "{{ route('blog.admin.posts.store') }}", method: 'POST',
                    data: new FormData(this), processData: false, contentType: false,
                    success: res => {
                        btn(f, false);
                        if (res.errors) { $.each(res.errors, (k, v) => f.find('.' + k + '_error_add').text(v[0])); return; }
                        $('#Modal_Tambah_Post').modal('hide'); this.reset();
                        if (window.addEditor) window.addEditor.setData('');
                        table.ajax.reload();
                        if (typeof Swal !== 'undefined') Swal.fire(res.judul || 'Berhasil', res.success, 'success');
                    },
                    error: xhr => { btn(f, false); Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.error) || 'Terjadi kesalahan.', 'error'); },
                });
            });

            // ===== Buka Edit (render partial + init editor) =====
            $('body').on('click', '.btn-edit', function () {
                const id = $(this).data('id');
                $.get(BASE + '/' + id + '/edit', async res => {
                    if (window.editEditor) { try { await window.editEditor.destroy(); } catch (e) {} window.editEditor = null; }
                    $('#EditPostBody').html(res.html);
                    $('#Modal_Edit_Post').modal('show');
                    ClassicEditor.create(document.querySelector('#edit_body')).then(ed => window.editEditor = ed).catch(console.error);
                }).fail(() => Swal.fire('Gagal', 'Tidak bisa memuat data artikel.', 'error'));
            });

            // ===== Simpan Edit =====
            $('#FormEditPost').on('submit', function (e) {
                e.preventDefault();
                const f = $(this); clearErr(f); btn(f, true);
                if (window.editEditor) window.editEditor.updateSourceElement();
                const id = $('#edit_post_id').val();
                const fd = new FormData(this); fd.append('_method', 'PUT');
                $.ajax({
                    url: BASE + '/' + id, method: 'POST', data: fd, processData: false, contentType: false,
                    success: res => {
                        btn(f, false);
                        if (res.errors) { $.each(res.errors, (k, v) => f.find('.' + k + '_error_edit').text(v[0])); return; }
                        $('#Modal_Edit_Post').modal('hide'); table.ajax.reload();
                        if (typeof Swal !== 'undefined') Swal.fire(res.judul || 'Berhasil', res.success, 'success');
                    },
                    error: xhr => { btn(f, false); Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.error) || 'Terjadi kesalahan.', 'error'); },
                });
            });

            // ===== Detail =====
            $('body').on('click', '.btn-view-detail', function () {
                const id = $(this).data('id');
                $.get(BASE + '/' + id, res => { $('#DetailPostBody').html(res.html); $('#Modal_Detail_Post').modal('show'); });
            });

            // ===== Hapus =====
            $('body').on('click', '.btn-delete', function () {
                const id = $(this).data('id'); const name = $(this).data('name');
                Swal.fire({
                    title: 'Hapus artikel?', html: 'Artikel <b>' + $('<div>').text(name).html() + '</b> akan dihapus permanen.',
                    icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal',
                    customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-light' }, buttonsStyling: false,
                }).then(r => {
                    if (!r.isConfirmed) return;
                    $.ajax({
                        url: BASE + '/' + id, method: 'DELETE', data: { _token: CSRF },
                        success: res => { table.ajax.reload(); Swal.fire(res.judul || 'Berhasil', res.success, 'success'); },
                        error: xhr => Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.error) || 'Gagal menghapus.', 'error'),
                    });
                });
            });
        });
    </script>
@endpush
