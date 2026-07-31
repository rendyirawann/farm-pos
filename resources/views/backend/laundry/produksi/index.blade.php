@extends('backend.layout.app')
@section('title', 'Produksi Laundry')
@section('content')

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
    <div id="kt_app_content_container" class="app-container container-xxl">

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="alert alert-primary fs-7 d-flex flex-wrap gap-2 align-items-center">
            <span class="fw-bold me-2">Alur:</span>
            @foreach ($pipeline as $st)
                <span class="badge badge-light-info">{{ $stageLabels[$st] }}</span>@if (!$loop->last)<span class="text-muted">→</span>@endif
            @endforeach
            <span class="text-muted">→</span><span class="badge badge-light-dark">Diambil</span>
        </div>

        <div class="card card-flush mb-6">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Dalam Proses ({{ $active->count() }})</h3></div>
            <div class="card-body">
                <div class="row g-4">
                    @forelse ($active as $o)
                        <div class="col-md-6 col-lg-4">
                            <div class="border border-2 rounded p-4 h-100 border-gray-300">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bolder text-gray-900">{{ $o->invoice_no }}</div>
                                        <div class="fs-8 text-muted">{{ $o->customer_name }} · {{ $o->items->count() }} item</div>
                                    </div>
                                    <span class="badge badge-primary">{{ $stageLabels[$o->order_status] ?? $o->order_status }}</span>
                                </div>
                                <div class="fs-8 text-muted mt-2">Masuk {{ $o->created_at->format('d/m H:i') }} · estimasi {{ $o->estimated_completed_at ? $o->estimated_completed_at->format('d/m H:i') : '-' }}</div>
                                <div class="mt-3">
                                    @php $next = $o->nextStatus(); @endphp
                                    @if ($next)
                                        <form method="POST" action="{{ route('laundry.produksi.advance', $o) }}">@csrf
                                            <button class="btn btn-sm btn-primary w-100">Maju → {{ $stageLabels[$next] }}</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-8">Tidak ada cucian dalam proses.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card card-flush">
            <div class="card-header pt-5"><h3 class="card-title fw-bold">Selesai — Siap Diambil ({{ $ready->count() }})</h3></div>
            <div class="card-body">
                <div class="row g-4">
                    @forelse ($ready as $o)
                        <div class="col-md-6 col-lg-4">
                            <div class="border border-2 border-success bg-light-success rounded p-4">
                                <div class="fw-bolder text-gray-900">{{ $o->invoice_no }}</div>
                                <div class="fs-8 text-muted">{{ $o->customer_name }} · {{ $o->customer_phone }}</div>
                                <div class="fs-8 mt-2">Selesai {{ $o->actual_completed_at ? $o->actual_completed_at->format('d/m H:i') : '-' }} ·
                                    <span class="badge badge-light-{{ $o->payment_status === 'paid' ? 'success' : 'danger' }}">{{ $o->payment_status === 'paid' ? 'Lunas' : 'Belum bayar' }}</span></div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-6">Belum ada yang selesai (3 hari terakhir).</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
