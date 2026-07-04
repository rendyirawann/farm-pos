@extends('backend.layout.app')
@section('title', 'Kitchen Display System')
@section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack flex-wrap gap-3">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                    <i class="ki-outline ki-fire fs-2 me-2 text-danger"></i> Dapur (Antrian Pesanan)
                </h1>
            </div>

            <ul class="nav nav-pills nav-pills-custom border-transparent flex-row gap-2">
                <li class="nav-item">
                    <a class="nav-link btn btn-sm btn-color-muted btn-active-light-danger fw-bold active"
                        data-bs-toggle="tab" href="#tab_aktif">
                        Sedang Dibuat <span class="badge badge-danger ms-2">{{ $activeOrders->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-sm btn-color-muted btn-active-light-success fw-bold" data-bs-toggle="tab"
                        href="#tab_selesai">
                        Sudah Selesai <span class="badge badge-success ms-2">{{ $completedOrders->count() }}</span>
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <button type="button" class="btn btn-sm btn-light-primary fw-bold" onclick="location.reload()">
                    <i class="ki-outline ki-arrows-circle fs-3"></i> Refresh Manual
                </button>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="tab-content">

                <div class="tab-pane fade show active" id="tab_aktif" role="tabpanel">
                    <div class="row g-4 g-lg-5">
                        @forelse ($activeOrders as $order)
                            <div class="col-6 col-md-4 col-xl-3">
                                <div
                                    class="card shadow-sm border-0 h-100 {{ $order->order_status == 'cooking' ? 'border border-primary border-2' : '' }}">
                                    <div
                                        class="card-header min-h-50px px-4 {{ $order->order_status == 'cooking' ? 'bg-light-primary' : 'bg-light-warning' }}">
                                        <div class="card-title d-flex flex-column align-items-start m-0 py-2">
                                            <span class="fw-bold fs-3 text-gray-800">
                                                No. {{ $order->queue_number ?? '-' }}
                                            </span>
                                            <span class="fs-8 text-muted fw-semibold">{{ $order->customer_name }} •
                                                #{{ $order->invoice_no }}</span>
                                        </div>
                                        <div class="card-toolbar m-0">
                                            <span
                                                class="badge {{ $order->order_status == 'cooking' ? 'badge-primary' : 'badge-warning' }} fs-8">
                                                {{ \Carbon\Carbon::parse($order->created_at)->diffForHumans(null, true, true) }}
                                                lalu
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="d-flex flex-column gap-3">
                                            @foreach ($order->details as $item)
                                                <div
                                                    class="d-flex align-items-start justify-content-between border-bottom pb-2 mb-1">
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="fw-bold text-gray-800 fs-6 {{ $item->status == 'done' ? 'text-decoration-line-through text-muted' : '' }}">
                                                            {{ $item->qty }}x {{ $item->menu->name ?? 'Menu Dihapus' }}
                                                        </span>
                                                        @if (!empty($item->addons))
                                                            <span class="fs-8 fw-semibold text-primary">
                                                                + {{ collect($item->addons)->pluck('name')->implode(', ') }}
                                                            </span>
                                                        @endif
                                                        @if ($item->notes)
                                                            <span class="fs-8 fw-bold text-danger fst-italic"><i
                                                                    class="ki-outline ki-message-text-2 fs-8"></i>
                                                                {{ $item->notes }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="ms-2 min-w-40px text-end">
                                                        @if ($item->status == 'pending')
                                                            <button
                                                                class="btn btn-sm btn-icon btn-light-warning h-30px w-30px"
                                                                onclick="updateStatus({{ $item->id }}, 'cooking')"
                                                                title="Mulai Masak">
                                                                <i class="ki-outline ki-fire fs-4"></i>
                                                            </button>
                                                        @elseif ($item->status == 'cooking')
                                                            <button
                                                                class="btn btn-sm btn-icon btn-light-primary h-30px w-30px"
                                                                onclick="updateStatus({{ $item->id }}, 'done')"
                                                                title="Selesai & Sajikan">
                                                                <i class="ki-outline ki-check fs-4"></i>
                                                            </button>
                                                        @else
                                                            <span class="badge badge-light-success fs-8"><i
                                                                    class="ki-outline ki-check-circle text-success fs-5"></i></span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="card-footer p-3 d-flex justify-content-between bg-transparent border-top">
                                        @php
                                            $hasPending = $order->details->where('status', 'pending')->count() > 0;
                                        @endphp

                                        @if ($hasPending)
                                            <button class="btn btn-sm btn-light-warning flex-fill fw-bold"
                                                onclick="updateOrderStatus({{ $order->id }}, 'cooking')">
                                                <i class="ki-outline ki-fire fs-5"></i> Masak Semua
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-light-primary flex-fill fw-bold"
                                                onclick="updateOrderStatus({{ $order->id }}, 'done')">
                                                <i class="ki-outline ki-check fs-5"></i> Selesai Semua
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-10">
                                <i class="ki-outline ki-coffee fs-5x text-muted mb-3"></i>
                                <h3 class="text-gray-500 fw-semibold">Dapur sedang santai. Belum ada antrian pesanan.</h3>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="tab-pane fade" id="tab_selesai" role="tabpanel">
                    <div class="row g-4 g-lg-5">
                        @forelse ($completedOrders as $order)
                            <div class="col-6 col-md-4 col-xl-3">
                                <div class="card shadow-sm border-0 h-100 border border-success">
                                    <div class="card-header min-h-50px px-4 bg-light-success">
                                        <div class="card-title d-flex flex-column align-items-start m-0 py-2">
                                            <span class="fw-bold fs-3 text-gray-800">No. {{ $order->queue_number ?? '-' }}</span>
                                            <span class="fs-8 text-muted fw-semibold">{{ $order->customer_name }} •
                                                #{{ $order->invoice_no }}</span>
                                        </div>
                                        <div class="card-toolbar m-0">
                                            <span class="badge badge-success fs-8"><i
                                                    class="ki-outline ki-check text-white fs-7 me-1"></i> Selesai</span>
                                        </div>
                                    </div>

                                    <div class="card-body p-4">
                                        <div class="d-flex flex-column gap-3">
                                            @foreach ($order->details as $item)
                                                <div
                                                    class="d-flex align-items-start justify-content-between border-bottom pb-2 mb-1">
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="fw-bold text-gray-500 fs-6 text-decoration-line-through">
                                                            {{ $item->qty }}x {{ $item->menu->name ?? 'Menu Dihapus' }}
                                                        </span>
                                                        @if (!empty($item->addons))
                                                            <span class="fs-8 fw-semibold text-muted">
                                                                + {{ collect($item->addons)->pluck('name')->implode(', ') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="ms-2 text-end">
                                                        <span class="badge badge-light-success fs-8"><i
                                                                class="ki-outline ki-check-circle text-success fs-5"></i></span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-10">
                                <i class="ki-outline ki-burger fs-5x text-muted mb-3"></i>
                                <h3 class="text-gray-500 fw-semibold">Belum ada pesanan yang disiapkan hari ini.</h3>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                let activeTab = localStorage.getItem('kds_active_tab');
                if (activeTab) {
                    $('.nav-link[href="' + activeTab + '"]').tab('show');
                }

                $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                    localStorage.setItem('kds_active_tab', $(e.target).attr('href'));
                });

                toastr.options = {
                    "closeButton": true,
                    "progressBar": true,
                    "positionClass": "toastr-top-right",
                    "timeOut": "3000"
                };
            });

            function orderLabel(res) {
                return 'No. ' + (res.queue_number ?? '-') + ' (' + (res.customer_name ?? 'Pelanggan') + ')';
            }

            // Update per Item
            function updateStatus(detailId, newStatus) {
                let swalLoader = Swal.fire({
                    toast: true, position: 'top-end', showConfirmButton: false, title: 'Memproses...', icon: 'info'
                });

                $.ajax({
                    url: "{{ route('kitchen.update-status') }}",
                    method: "POST",
                    data: { _token: '{{ csrf_token() }}', detail_id: detailId, status: newStatus },
                    success: function(res) {
                        if (res.success) {
                            if (res.is_finished) {
                                swalLoader.close();
                                toastr.success('Pesanan ' + orderLabel(res) + ' telah selesai!', "Pesanan Siap! 🎉");
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                location.reload();
                            }
                        }
                    },
                    error: function() { Swal.fire('Error 500!', 'Terjadi kesalahan sistem.', 'error'); }
                });
            }

            // Update Massal
            function updateOrderStatus(orderId, newStatus) {
                let actionText = newStatus === 'cooking' ? 'memasak semua' : 'menyelesaikan semua';

                Swal.fire({
                    title: 'Yakin?',
                    text: `Anda akan ${actionText} pesanan di invoice ini.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Lanjut',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: newStatus === 'cooking' ? 'btn btn-warning' : 'btn btn-primary',
                        cancelButton: 'btn btn-light'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        let swalLoader = Swal.fire({
                            toast: true, position: 'top-end', showConfirmButton: false, title: 'Memproses...', icon: 'info'
                        });

                        $.ajax({
                            url: "{{ route('kitchen.update-order-status') }}",
                            method: "POST",
                            data: { _token: '{{ csrf_token() }}', order_id: orderId, status: newStatus },
                            success: function(res) {
                                if (res.success) {
                                    if (res.is_finished) {
                                        swalLoader.close();
                                        toastr.success('Pesanan ' + orderLabel(res) + ' telah selesai!', "Pesanan Siap! 🎉");
                                        setTimeout(() => location.reload(), 1500);
                                    } else {
                                        location.reload();
                                    }
                                }
                            },
                            error: function() { Swal.fire('Error 500!', 'Gagal memproses aksi.', 'error'); }
                        });
                    }
                });
            }

            // Real-time via Reverb: reload hanya saat ada perubahan order
            // (menggantikan auto-reload buta tiap 15 detik). Fallback lambat 90s
            // bila koneksi WebSocket terputus.
            (function () {
                const tenantId = document.querySelector('meta[name="tenant-id"]')?.content;
                if (window.Echo && tenantId) {
                    let deb;
                    window.Echo.private('orders.' + tenantId)
                        .listen('.order.changed', function () {
                            clearTimeout(deb);
                            deb = setTimeout(function () { location.reload(); }, 600);
                        });
                    setInterval(function () { location.reload(); }, 90000);
                } else {
                    setInterval(function () { location.reload(); }, 15000); // fallback
                }
            })();
        </script>
    @endpush
@endsection
