@extends('backend.layout.app')
@section('title', 'Laporan Penjualan')
@section('content')

    <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <div class="card card-flush mb-8">
                <div class="card-header pt-5">
                    <h3 class="card-title fw-bold text-gray-800 fs-2">Filter Laporan Penjualan</h3>
                </div>
                <div class="card-body">
                    <form id="form-filter">
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="fs-6 fw-semibold mb-2">Rentang Waktu</label>
                                <input class="form-control form-control-solid" placeholder="Pilih Tanggal"
                                    id="kt_daterangepicker" />
                                <input type="hidden" name="start_date" id="start_date">
                                <input type="hidden" name="end_date" id="end_date">
                            </div>
                            <div class="col-md-3">
                                <label class="fs-6 fw-semibold mb-2">Metode Pembayaran</label>
                                <select name="payment_method" id="payment_method" class="form-select form-select-solid">
                                    <option value="all">Semua Metode</option>
                                    <option value="cash">Tunai (Cash)</option>
                                    <option value="qris">QRIS</option>
                                </select>
                            </div>
                            <div class="col-md-5 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1"><i
                                        class="ki-outline ki-magnifier fs-2"></i> Tampilkan Data</button>
                                <button type="button" id="btn-print-pdf" class="btn btn-danger flex-grow-1"><i
                                        class="ki-outline ki-printer fs-2"></i> Cetak Laporan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-5 mb-8">
                <div class="col-6 col-md-3">
                    <div class="bg-light-primary rounded p-6 border border-primary border-dashed h-100">
                        <span class="fs-6 fw-semibold text-primary d-block mb-1">Total Nota</span>
                        <span class="fs-2x fw-bolder text-gray-900" id="summary-orders">0 Nota</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-light-info rounded p-6 border border-info border-dashed h-100">
                        <span class="fs-6 fw-semibold text-info d-block mb-1">Total Pendapatan</span>
                        <span class="fs-2x fw-bolder text-gray-900" id="summary-revenue">Rp 0</span>
                    </div>
                </div>
                <div class="col-6 col-md-3" id="card-expense">
                    <div class="bg-light-danger rounded p-6 border border-danger border-dashed h-100">
                        <span class="fs-6 fw-semibold text-danger d-block mb-1">Total Pengeluaran</span>
                        <span class="fs-2x fw-bolder text-gray-900" id="summary-expense">Rp 0</span>
                        <span class="fs-8 text-muted d-block mt-1">total per tanggal (gabungan semua shift)</span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-light-success rounded p-6 border border-success border-dashed h-100">
                        <span class="fs-6 fw-semibold text-success d-block mb-1">Omzet Bersih</span>
                        <span class="fs-2x fw-bolder text-gray-900" id="summary-net">Rp 0</span>
                    </div>
                </div>
            </div>

            {{-- Penanda pesanan SALAH (voided): TIDAK dihitung ke omzet, tetap muncul di daftar. --}}
            <div class="alert alert-dismissible bg-light-danger border border-danger border-dashed d-flex align-items-center p-5 mb-8 d-none"
                 id="voided-banner">
                <i class="ki-outline ki-information-5 fs-2hx text-danger me-4"></i>
                <div class="d-flex flex-column">
                    <span class="fw-bold fs-5 text-gray-900"><span id="voided-count">0</span> pesanan ditandai <span class="badge badge-danger">SALAH</span></span>
                    <span class="fs-7 text-gray-700">Senilai <b id="voided-amount">Rp 0</b> — TIDAK dihitung ke omzet/pendapatan, tetapi tetap tampil di daftar sebagai riwayat.</span>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="table-sales">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>No</th>
                                    <th>Tanggal & Waktu</th>
                                    <th>Invoice</th>
                                    <th>Pelanggan / No. Antrian</th>
                                    <th>Kasir</th>
                                    <th>Metode</th>
                                    <th class="text-end">Potongan Diskon</th>
                                    <th class="text-end">Total Belanja</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Modal detail pesanan per invoice --}}
    <div class="modal fade" id="modal-order-detail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title fw-bold"><i class="ki-outline ki-handcart fs-2 text-primary me-2"></i>Detail Pesanan</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
                <div class="modal-body" id="od-body"></div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
        <script>
            $(document).ready(function() {
                // 1. Inisialisasi DateRangePicker Metronic
                var start = moment(); // Default hari ini
                var end = moment();

                function cb(start, end) {
                    $('#kt_daterangepicker').val(start.format('DD MMM YYYY') + ' - ' + end.format('DD MMM YYYY'));
                    $('#start_date').val(start.format('YYYY-MM-DD'));
                    $('#end_date').val(end.format('YYYY-MM-DD'));
                }

                $('#kt_daterangepicker').daterangepicker({
                    startDate: start,
                    endDate: end,
                    ranges: {
                        'Hari Ini': [moment(), moment()],
                        'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
                        '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
                        'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
                        'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                            'month').endOf('month')]
                    },
                    locale: {
                        customRangeLabel: "Pilih Rentang"
                    }
                }, cb);

                cb(start, end); // Panggil saat halaman pertama dimuat

                // 2. Inisialisasi DataTables
                let table = $('#table-sales').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('reports.sales.data') }}",
                        data: function(d) {
                            d.start_date = $('#start_date').val();
                            d.end_date = $('#end_date').val();
                            d.payment_method = $('#payment_method').val();
                        },
                        dataSrc: function(json) {
                            $('#summary-revenue').text(json.totalRevenue);
                            $('#summary-expense').text(json.totalExpense);
                            $('#summary-net').text(json.netRevenue);
                            $('#summary-orders').text(json.totalOrders + ' Nota');
                            // Pengeluaran (kas tunai) tak relevan untuk filter QRIS -> sembunyikan kartunya.
                            if (json.showExpense === false) {
                                $('#card-expense').hide();
                            } else {
                                $('#card-expense').show();
                            }
                            // Banner pesanan salah (voided) — tampil hanya bila ada.
                            var vCount = parseInt((json.voidedCount || '0').replace(/[^\d]/g, ''), 10) || 0;
                            if (vCount > 0) {
                                $('#voided-count').text(json.voidedCount);
                                $('#voided-amount').text(json.voidedAmount);
                                $('#voided-banner').removeClass('d-none');
                            } else {
                                $('#voided-banner').addClass('d-none');
                            }
                            return json.data;
                        }
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'date',
                            name: 'created_at'
                        },
                        {
                            data: 'invoice',
                            name: 'invoice_no'
                        },
                        {
                            data: 'customer',
                            name: 'customer_name'
                        },
                        {
                            data: 'kasir',
                            name: 'kasir',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'payment_method',
                            name: 'payment_method'
                        },
                        {
                            data: 'discount',
                            name: 'discount_amount',
                            className: 'text-end'
                        }, // Kolom Diskon
                        {
                            data: 'grand_total',
                            name: 'grand_total',
                            className: 'text-end'
                        },
                        {
                            data: 'status',
                            name: 'voided_at',
                            className: 'text-center',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'action',
                            name: 'action',
                            className: 'text-center',
                            orderable: false,
                            searchable: false
                        }
                    ]
                });

                // ===== Detail pesanan per invoice (ikon mata) =====
                const rupiah = (n) => 'Rp ' + (Number(n) || 0).toLocaleString('id-ID');
                const orderModalEl = document.getElementById('modal-order-detail');
                const orderModal = new bootstrap.Modal(orderModalEl);

                $('#table-sales').on('click', '.btn-view-order', function () {
                    const id = $(this).data('id');
                    $('#od-body').html('<div class="text-center py-10"><span class="spinner-border text-primary"></span></div>');
                    orderModal.show();

                    $.get("{{ url('admin/reports/sales/order') }}/" + id)
                        .done(function (o) {
                            let rows = (o.items || []).map(function (it) {
                                const addons = (it.addons || []).length
                                    ? '<div class="fs-8 text-muted">+ ' + it.addons.map(a => typeof a === 'string' ? a : (a.name || '')).join(', ') + '</div>' : '';
                                const notes = it.notes ? '<div class="fs-8 text-warning">Catatan: ' + $('<div>').text(it.notes).html() + '</div>' : '';
                                return `<tr>
                                    <td><span class="fw-semibold text-gray-800">${$('<div>').text(it.name).html()}</span>${addons}${notes}</td>
                                    <td class="text-center">${it.qty}</td>
                                    <td class="text-end">${rupiah(it.price)}</td>
                                    <td class="text-end fw-bold">${rupiah(it.subtotal)}</td>
                                </tr>`;
                            }).join('');

                            const voidBadge = o.voided ? '<span class="badge badge-light-danger ms-2">SALAH — tidak dihitung</span>' : '';
                            const disc = o.discount_amount > 0 ? `<div class="d-flex justify-content-between text-danger"><span>Diskon</span><span>- ${rupiah(o.discount_amount)}</span></div>` : '';
                            const tax = o.tax > 0 ? `<div class="d-flex justify-content-between text-muted"><span>Pajak</span><span>${rupiah(o.tax)}</span></div>` : '';

                            $('#od-body').html(`
                                <div class="d-flex flex-wrap justify-content-between mb-4 gap-2">
                                    <div>
                                        <div class="fw-bold fs-4 text-gray-900">${$('<div>').text(o.customer_name || 'Pelanggan').html()} ${voidBadge}</div>
                                        <div class="text-muted fs-7">${o.date} • No. Antrian ${o.queue_number ?? '-'}</div>
                                        <div class="text-muted fs-8 mt-1">${o.invoice_no}</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge badge-light-${o.payment_method === 'CASH' ? 'success' : 'info'} fs-7">${o.payment_method}</span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-row-bordered align-middle fs-7 mb-3">
                                        <thead><tr class="fw-bold text-gray-500 text-uppercase fs-8">
                                            <th>Item</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th>
                                        </tr></thead>
                                        <tbody>${rows || '<tr><td colspan="4" class="text-center text-muted py-4">Tidak ada item.</td></tr>'}</tbody>
                                    </table>
                                </div>
                                <div class="ms-auto" style="max-width:280px">
                                    <div class="d-flex justify-content-between text-muted"><span>Subtotal</span><span>${rupiah(o.subtotal)}</span></div>
                                    ${disc}${tax}
                                    <div class="separator my-2"></div>
                                    <div class="d-flex justify-content-between fw-bold fs-4 text-gray-900"><span>Total</span><span>${rupiah(o.grand_total)}</span></div>
                                </div>
                            `);
                        })
                        .fail(function () {
                            $('#od-body').html('<div class="alert alert-danger">Gagal memuat detail pesanan.</div>');
                        });
                });

                // 3. Tombol Filter Ditekan
                $('#form-filter').on('submit', function(e) {
                    e.preventDefault();
                    table.draw();
                });

                // 4. Tombol Cetak PDF Ditekan
                $('#btn-print-pdf').on('click', function() {
                    let start_date = $('#start_date').val();
                    let end_date = $('#end_date').val();
                    let payment_method = $('#payment_method').val();

                    let url =
                        `{{ route('reports.sales.print') }}?start_date=${start_date}&end_date=${end_date}&payment_method=${payment_method}`;
                    window.open(url, '_blank');
                });
            });
        </script>
    @endpush
@endsection
