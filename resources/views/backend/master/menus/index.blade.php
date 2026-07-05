@extends('backend.layout.app')
@section('title', 'Master Menu')
@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-0">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Menu
                    Makanan & Minuman</h1>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="card card-flush">
                <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                            <input type="text" id="search" class="form-control w-250px ps-12"
                                placeholder="Cari Menu..." />
                        </div>
                    </div>
                    <div class="card-toolbar d-flex flex-wrap gap-2">
                        @can('menu.create')
                            <button type="button" class="btn btn-sm btn-light-success" data-bs-toggle="modal"
                                data-bs-target="#Modal_Import_Menu">
                                <i class="ki-outline ki-file-up fs-3"></i> Import CSV
                            </button>
                        @endcan
                        <button type="button" class="btn btn-sm btn-primary" id="btn_tambah_data">
                            <i class="ki-outline ki-plus fs-2"></i> Tambah Menu
                        </button>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-menus">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">No</th>
                                <th class="min-w-50px">Foto</th>
                                <th class="min-w-200px">Nama Menu & Kategori</th>
                                <th class="min-w-100px">Harga</th>
                                <th class="min-w-100px">Status</th>
                                <th class="text-end min-w-100px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Tambah_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Tambah Menu Baru</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body mx-5 mx-xl-15 my-7">
                    <form id="FormTambahModalID" class="form" enctype="multipart/form-data">
                        @csrf
                        <div class="row mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Nama Menu</label>
                                <input type="text" class="form-control" name="name"
                                    placeholder="Misal: Nasi Goreng" />
                                <span class="text-danger error-text name_error_add mt-2"></span>
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Kategori</label>
                                <select name="category_id" class="form-select form-select-solid" data-control="select2"
                                    data-dropdown-parent="#Modal_Tambah_Data" data-placeholder="Pilih Kategori">
                                    <option></option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <span class="text-danger error-text category_id_error_add mt-2"></span>
                            </div>
                        </div>

                        <div class="row mb-7">
                            <div class="col-md-4 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">Harga (Rp)</label>
                                <input type="number" class="form-control" name="price" placeholder="Misal: 25000"
                                    min="0" required />
                                <span class="text-danger error-text price_error_add mt-2"></span>
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="fs-6 fw-semibold mb-2">Diskon (%)</label>
                                <input type="number" class="form-control" name="discount_percent" value="0"
                                    min="0" max="100" />
                                <span class="text-danger error-text discount_percent_error_add mt-2"></span>
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="fs-6 fw-semibold mb-2 d-block">Ketersediaan</label>
                                <div class="form-check form-switch form-check-custom form-check-solid mt-3">
                                    <input class="form-check-input w-40px h-20px" type="checkbox" name="is_available"
                                        value="1" checked id="status_add" />
                                    <label class="form-check-label text-gray-700 fw-bold" for="status_add">Tersedia</label>
                                </div>
                            </div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">Deskripsi (Opsional)</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Penjelasan singkat menu..."></textarea>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2 d-block">Foto Menu (Opsional)</label>
                            <input type="file" class="form-control" name="image" accept=".png, .jpg, .jpeg" />
                            <div class="form-text">File yang diizinkan: png, jpg, jpeg. Maks: 2MB.</div>
                            <span class="text-danger error-text image_error_add mt-2"></span>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2 d-block">Add-Ons / Tambahan (Opsional)</label>
                            <div class="form-text mb-2">Mis. "Extra Keju", "Level Pedas" beserta harganya.</div>
                            <div id="addons-wrapper-add"></div>
                            <button type="button" class="btn btn-sm btn-light-primary mt-2"
                                data-addon-add="#addons-wrapper-add">
                                <i class="ki-outline ki-plus fs-4"></i> Tambah Add-On
                            </button>
                        </div>

                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btn-add-data">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Edit_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Menu</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body mx-5 mx-xl-15 my-7">
                    <form id="FormEditModalID" class="form" enctype="multipart/form-data">
                        @csrf
                        <div id="EditRowModalBody"></div>
                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary" id="btn-edit-data">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Modal_Detail_Data" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h2 class="fw-bold">Detail Menu</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body pb-10" id="DetailRowModalBody">
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Modal Import Menu via CSV ===== --}}
    <div class="modal fade" id="Modal_Import_Menu" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-600px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Import Menu dari CSV</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i
                            class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body mx-5 my-7">
                    <div class="alert alert-primary d-flex align-items-start">
                        <i class="ki-outline ki-information-5 fs-2x text-primary me-3 mt-1"></i>
                        <div class="fs-7 text-gray-700">
                            <div class="fw-bold mb-1">Cara pakai:</div>
                            1) Unduh template &rarr; 2) Isi di Excel/Google Sheets &rarr; 3) Simpan sebagai <b>.csv</b>
                            &rarr; 4) Upload di sini.
                            <div class="mt-2">Kolom: <b>name</b>, <b>price</b> (wajib), lalu <b>category</b>,
                                <b>description</b>, <b>available</b> (opsional). Kategori kosong akan
                                <b>dideteksi otomatis</b> (minuman/makanan) dari nama. Menu dengan nama yang sudah ada
                                akan dilewati.
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('menus.template') }}" class="btn btn-light-primary mb-6">
                        <i class="ki-outline ki-cloud-download fs-3"></i> Unduh Template CSV
                    </a>
                    <form action="{{ route('menus.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <label class="required fw-semibold fs-6 mb-2">File CSV</label>
                        <input type="file" name="file" accept=".csv,text/csv,text/plain" class="form-control mb-2"
                            required>
                        <div class="text-muted fs-8 mb-6">Maksimal 4 MB. Pemisah koma atau titik-koma didukung.</div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary"><i class="ki-outline ki-file-up fs-3"></i>
                                Upload &amp; Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Notifikasi hasil import --}}
    @push('scripts')
        <script>
            (function () {
                if (typeof Swal === 'undefined') return;
                @if (session('import_summary'))
                    var _errs = @json(session('import_errors', []));
                    Swal.fire({
                        icon: {!! empty(session('import_errors')) ? "'success'" : "'warning'" !!},
                        title: 'Import selesai',
                        html: '<div class="fw-semibold">{{ session('import_summary') }}</div>' +
                            (_errs.length ? '<div class="text-start text-muted fs-8 mt-3" style="max-height:180px;overflow:auto">• ' +
                                _errs.map(function (e) { return String(e).replace(/&/g,'&amp;').replace(/</g, '&lt;'); }).join('<br>• ') + '</div>' : ''),
                        confirmButtonText: 'OK'
                    });
                @endif
                @if (session('import_error'))
                    Swal.fire({ icon: 'error', title: 'Import gagal', text: @json(session('import_error')) });
                @endif
            })();
        </script>
    @endpush

    @push('stylesheets')
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <link rel="stylesheet" href="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.css') }}" />
    @endpush

    @push('scripts')
        <script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
        <script>
            $(document).ready(function() {
                var table = $('#table-menus').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('get-datamenus') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'image_view',
                            name: 'image',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'menu_info',
                            name: 'name'
                        },
                        {
                            data: 'price_format',
                            name: 'price'
                        },
                        {
                            data: 'status_badge',
                            name: 'is_available',
                            searchable: false
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false,
                            className: 'text-end'
                        }
                    ]
                });

                $('#search').on('keyup', function() {
                    table.search(this.value).draw();
                });

                // Buka Modal Tambah
                $('#btn_tambah_data').click(function() {
                    $('#FormTambahModalID')[0].reset();
                    $('#FormTambahModalID select').val(null).trigger('change');
                    $('#addons-wrapper-add').html('');
                    $('.error-text').text('');
                    $('#Modal_Tambah_Data').modal('show');
                });

                // ===== Add-On repeater (dipakai form Tambah & Edit) =====
                window.addonRowHtml = function(name = '', price = 0) {
                    return `<div class="d-flex gap-2 mb-2 addon-row">
                        <input type="text" name="addon_name[]" class="form-control form-control-sm" placeholder="Nama add-on" value="${$('<div>').text(name).html()}">
                        <input type="number" name="addon_price[]" class="form-control form-control-sm w-125px" placeholder="Harga" min="0" value="${price}">
                        <button type="button" class="btn btn-sm btn-icon btn-light-danger addon-remove"><i class="ki-outline ki-trash fs-5"></i></button>
                    </div>`;
                };
                $('body').on('click', '[data-addon-add]', function() {
                    let target = $(this).data('addon-add');
                    $(target).append(window.addonRowHtml());
                });
                $('body').on('click', '.addon-remove', function() {
                    $(this).closest('.addon-row').remove();
                });

                // Proses Tambah (AJAX dengan FormData untuk file upload)
                $('#FormTambahModalID').on('submit', function(e) {
                    e.preventDefault();
                    $('.error-text').text('');
                    $('#btn-add-data').prop('disabled', true).text('Menyimpan...');

                    $.ajax({
                        url: "{{ route('menus.store') }}",
                        method: 'POST',
                        data: new FormData(this),
                        processData: false, // Penting untuk file upload
                        contentType: false, // Penting untuk file upload
                        success: function(res) {
                            $('#Modal_Tambah_Data').modal('hide');
                            table.ajax.reload();
                            Swal.fire("Berhasil!", res.success, "success");
                            $('#btn-add-data').prop('disabled', false).text('Simpan');
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                let errors = xhr.responseJSON.errors;
                                $.each(errors, function(key, val) {
                                    $('span.' + key + '_error_add').text(val[0]);
                                });
                            } else {
                                Swal.fire("Error", "Gagal menyimpan data", "error");
                            }
                            $('#btn-add-data').prop('disabled', false).text('Simpan');
                        }
                    });
                });

                // Detail Data
                $('body').on('click', '.btn-detail', function() {
                    let id = $(this).data('id');
                    $('#DetailRowModalBody').html(
                        '<div class="text-center"><span class="spinner-border text-primary"></span></div>');
                    $('#Modal_Detail_Data').modal('show');
                    $.get("{{ url('admin') }}/menus/" + id, function(res) {
                        $('#DetailRowModalBody').html(res.html);
                    });
                });

                // Buka Edit Data
                $('body').on('click', '.btn-edit', function() {
                    let id = $(this).data('id');
                    $.get("{{ url('admin') }}/menus/" + id + "/edit", function(res) {
                        $('#EditRowModalBody').html(res.html);
                        // Re-initialize Select2 setelah konten di load
                        $('#EditRowModalBody select').select2({
                            dropdownParent: $('#Modal_Edit_Data')
                        });
                        $('#Modal_Edit_Data').modal('show');
                    });
                });

                // Proses Edit (AJAX dengan FormData untuk file upload)
                $('#FormEditModalID').on('submit', function(e) {
                    e.preventDefault();
                    $('.error-text').text('');
                    let id = $('#edit_menu_id').val();
                    let formData = new FormData(this);
                    formData.append('_method', 'PUT'); // Trick Laravel untuk file upload pakai PUT

                    $('#btn-edit-data').prop('disabled', true).text('Menyimpan...');

                    $.ajax({
                        url: "{{ url('admin') }}/menus/" + id,
                        method: 'POST', // Tetap POST karena FormData, tapi bawa '_method: PUT'
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(res) {
                            $('#Modal_Edit_Data').modal('hide');
                            table.ajax.reload();
                            Swal.fire("Berhasil!", res.success, "success");
                            $('#btn-edit-data').prop('disabled', false).text('Simpan Perubahan');
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                let errors = xhr.responseJSON.errors;
                                $.each(errors, function(key, val) {
                                    $('span.' + key + '_error_edit').text(val[0]);
                                });
                            } else {
                                Swal.fire("Error", "Gagal mengupdate data", "error");
                            }
                            $('#btn-edit-data').prop('disabled', false).text('Simpan Perubahan');
                        }
                    });
                });

                // Hapus Data
                $('body').on('click', '.btn-delete', function() {
                    let id = $(this).data('id');
                    let name = $(this).data('name');

                    Swal.fire({
                        title: "Hapus Menu?",
                        text: "Menu '" + name + "' akan dihapus permanen.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Ya, Hapus!",
                        cancelButtonText: "Batal",
                        customClass: {
                            confirmButton: "btn btn-danger",
                            cancelButton: "btn btn-secondary"
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ url('admin') }}/menus/" + id,
                                type: "DELETE",
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(res) {
                                    table.ajax.reload();
                                    Swal.fire("Terhapus!", res.success, "success");
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
