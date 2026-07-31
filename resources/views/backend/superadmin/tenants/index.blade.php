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
                    <div class="card-toolbar gap-2">
                        {{-- Filter VERTICAL (F&B / Laundry / Retail) --}}
                        <select id="filter-vertical" class="form-select form-select-solid w-175px" data-control="select2" data-hide-search="true">
                            <option value="">Semua Vertical ({{ array_sum($verticalCounts ?? []) }})</option>
                            @foreach (($verticals ?? []) as $vKey => $vMeta)
                                <option value="{{ $vKey }}">{{ $vMeta['label'] ?? $vKey }} ({{ $verticalCounts[$vKey] ?? 0 }})</option>
                            @endforeach
                        </select>
                        <input type="text" id="search" class="form-control form-control-solid w-250px" placeholder="Cari tenant..." />
                        <a href="{{ route('tenants.create') }}" class="btn btn-primary fw-bold"><i class="ki-outline ki-plus fs-3"></i> Buat Tenant</a>
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
                    ajax: {
                        url: "{{ route('tenants.data') }}",
                        // Kirim filter vertical yang dipilih ke server.
                        data: function (d) { d.vertical = $('#filter-vertical').val() || ''; }
                    },
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

                // Ganti filter vertical -> muat ulang tabel.
                $('#filter-vertical').on('change', function () { table.ajax.reload(); });

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

                // RESET DATA tenant (Superadmin) — konfirmasi ketik nama tenant.
                $(document).on('click', '.btn-reset-tenant', function () {
                    const id = $(this).data('id');
                    const name = $(this).data('name');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Reset data tenant?',
                        html: 'Semua <b>data operasional</b> tenant <b>' + $('<div>').text(name).html() + '</b> — pesanan, menu, kategori, promo, shift, pengeluaran, target, meja — akan <b>dihapus permanen</b> agar bersih.<br><span class="text-muted fs-8">Akun user, langganan, & setelan toko TETAP dipertahankan.</span><br><br>Ketik nama tenant untuk konfirmasi:',
                        input: 'text',
                        inputPlaceholder: name,
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Reset Data',
                        cancelButtonText: 'Batal',
                        buttonsStyling: false,
                        customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-light' },
                        preConfirm: (val) => {
                            if ((val || '').trim() !== name) { Swal.showValidationMessage('Nama tenant tidak cocok.'); return false; }
                            return val;
                        }
                    }).then((r) => {
                        if (!r.isConfirmed) return;
                        $.ajax({
                            url: "{{ url('admin/tenants') }}/" + id + "/reset-data",
                            method: 'POST',
                            data: { confirm: name, _token: '{{ csrf_token() }}' },
                            success: function (res) {
                                table.ajax.reload(null, false);
                                Swal.fire({ icon: 'success', title: 'Direset', text: (res && res.success) || 'Data tenant berhasil direset.', timer: 3000, showConfirmButton: false });
                            },
                            error: function (xhr) {
                                const msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'Gagal reset data.';
                                Swal.fire('Gagal', msg, 'error');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

@endsection
