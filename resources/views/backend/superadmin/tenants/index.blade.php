@extends('backend.layout.app')
@section('title', 'Manajemen Tenant')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- STAT CARDS --}}
            <div class="row g-5 mb-8">
                @php
                    $cards = [
                        ['Total Tenant', $stats['total'], 'ki-shop', 'primary'],
                        ['Langganan Aktif', $stats['active'], 'ki-check-circle', 'success'],
                        ['Belum Aktif / Habis', $stats['inactive'], 'ki-time', 'warning'],
                        ['Total User Tenant', $stats['users'], 'ki-people', 'info'],
                    ];
                @endphp
                @foreach ($cards as [$label, $value, $icon, $color])
                    <div class="col-md-3">
                        <div class="card card-flush">
                            <div class="card-body d-flex align-items-center">
                                <i class="ki-outline {{ $icon }} fs-3x text-{{ $color }} me-4"></i>
                                <div>
                                    <div class="fs-2hx fw-bolder text-gray-800">{{ number_format($value, 0, ',', '.') }}</div>
                                    <div class="fs-7 text-muted">{{ $label }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card card-flush">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-2">Daftar Tenant</h3>
                    <div class="card-toolbar">
                        <input type="text" id="search" class="form-control form-control-solid w-250px" placeholder="Cari tenant..." />
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-row-dashed align-middle gs-0 gy-3 table-tenants">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th>#</th>
                                <th>Bisnis</th>
                                <th>Paket</th>
                                <th>Status</th>
                                <th>Aktif s/d</th>
                                <th>User</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    @push('stylesheets')
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <link rel="stylesheet" href="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.css') }}" />
    @endpush

    @push('scripts')
        <script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
        <script>
            $(document).ready(function () {
                var table = $('.table-tenants').DataTable({
                    processing: true,
                    serverSide: true,
                    order: false,
                    ajax: "{{ route('tenants.data') }}",
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'business', name: 'name' },
                        { data: 'plan', name: 'plan', orderable: false, searchable: false },
                        { data: 'status', name: 'subscription_status', orderable: false, searchable: false },
                        { data: 'ends_at', name: 'subscription_ends_at', orderable: false, searchable: false },
                        { data: 'users_count', name: 'users_count', orderable: false, searchable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' },
                    ]
                });

                let timeout;
                $('#search').on('keyup', function () {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => table.search($(this).val()).draw(), 400);
                });

                $(document).on('click', '.btn-toggle-active', function () {
                    const id = $(this).data('id');
                    $.ajax({
                        url: "{{ url('admin/tenants') }}/" + id + "/toggle-active",
                        method: 'POST',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function () { table.ajax.reload(null, false); },
                        error: function () { alert('Gagal mengubah status tenant.'); }
                    });
                });

                // Hapus tenant (tombol hanya muncul untuk tenant yang di-suspend)
                $(document).on('click', '.btn-delete-tenant', function () {
                    const id = $(this).data('id');
                    const name = $(this).data('name');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Hapus tenant?',
                        html: 'Tenant <b>' + $('<div>').text(name).html() + '</b> beserta seluruh akun & datanya akan <b>dihapus permanen</b>. Tindakan ini tidak bisa dibatalkan.',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus permanen',
                        cancelButtonText: 'Batal',
                        buttonsStyling: false,
                        customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-light' },
                    }).then((r) => {
                        if (!r.isConfirmed) return;
                        $.ajax({
                            url: "{{ url('admin/tenants') }}/" + id,
                            method: 'POST',
                            data: { _method: 'DELETE', _token: '{{ csrf_token() }}' },
                            success: function (res) {
                                table.ajax.reload(null, false);
                                Swal.fire({ icon: 'success', title: 'Terhapus', text: (res && res.message) || 'Tenant berhasil dihapus.', timer: 2500, showConfirmButton: false });
                            },
                            error: function (xhr) {
                                const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus tenant.';
                                Swal.fire('Gagal', msg, 'error');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

@endsection
