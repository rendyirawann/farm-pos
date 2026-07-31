@extends('backend.layout.app')
@section('title', 'Kasir Laundry')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="d-flex flex-wrap gap-4 justify-content-between align-items-center mb-6">
            <div class="d-flex flex-wrap gap-4">
                <div class="card card-flush px-5 py-3"><div class="fs-8 text-muted">Aktif (proses)</div><div class="fs-2 fw-bolder text-primary">{{ $countActive }}</div></div>
                <div class="card card-flush px-5 py-3"><div class="fs-8 text-muted">Siap diambil</div><div class="fs-2 fw-bolder text-success">{{ $countReady }}</div></div>
                <div class="card card-flush px-5 py-3"><div class="fs-8 text-muted">Omzet hari ini</div><div class="fs-2 fw-bolder text-gray-900">Rp {{ number_format($revenueToday, 0, ',', '.') }}</div></div>
            </div>
            <a href="{{ route('laundry.kasir.create') }}" class="btn btn-primary"><i class="ki-outline ki-plus fs-2"></i> Nota Baru</a>
        </div>

        <div class="card card-flush">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Pesanan Aktif</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle gy-3">
                        <thead><tr class="fw-bold text-muted">
                            <th>Nota</th><th>Pelanggan</th><th>Item</th><th>Status</th><th>Bayar</th><th>Total</th><th>Estimasi</th><th class="text-end">Aksi</th>
                        </tr></thead>
                        <tbody>
                            @forelse ($orders as $o)
                                @php $badge = $o->order_status === 'selesai' ? 'success' : ($o->order_status === 'diterima' ? 'warning' : 'info'); @endphp
                                <tr>
                                    <td class="fw-bold">{{ $o->invoice_no }}<div class="fs-8 text-muted">{{ $o->created_at->format('d/m H:i') }}</div></td>
                                    <td>{{ $o->customer_name }}<div class="fs-8 text-muted">{{ $o->customer_phone }}</div></td>
                                    <td>{{ $o->items->count() }} item</td>
                                    <td><span class="badge badge-light-{{ $badge }}">{{ $stageLabels[$o->order_status] ?? $o->order_status }}</span></td>
                                    <td><span class="badge badge-light-{{ $o->payment_status === 'paid' ? 'success' : 'danger' }}">{{ $o->payment_status === 'paid' ? 'Lunas' : 'Belum' }}</span></td>
                                    <td class="fw-bold">Rp {{ number_format($o->grand_total, 0, ',', '.') }}</td>
                                    <td class="fs-8">{{ $o->estimated_completed_at ? $o->estimated_completed_at->format('d/m H:i') : '-' }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('laundry.kasir.print', $o) }}" target="_blank" class="btn btn-sm btn-icon btn-light-info" title="Struk"><i class="ki-outline ki-printer fs-4"></i></a>
                                        @if ($o->payment_status !== 'paid')
                                            <form method="POST" action="{{ route('laundry.kasir.pay', $o) }}" class="d-inline" onsubmit="return confirm('Tandai lunas?')">@csrf
                                                <button class="btn btn-sm btn-light-primary" title="Lunasi">Lunasi</button></form>
                                        @endif
                                        @if ($o->order_status === 'selesai' && $o->payment_status === 'paid')
                                            <form method="POST" action="{{ route('laundry.kasir.handover', $o) }}" class="d-inline" onsubmit="return confirm('Serahkan ke pelanggan?')">@csrf
                                                <button class="btn btn-sm btn-light-success" title="Serahkan">Serahkan</button></form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-8">Belum ada pesanan aktif. <a href="{{ route('laundry.kasir.create') }}">Buat nota</a>.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
