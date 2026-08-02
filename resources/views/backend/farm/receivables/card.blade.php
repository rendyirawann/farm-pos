@extends('backend.layout.app')
@section('title', 'Kartu Piutang')
@section('content')
@include('backend.farm._style')
@php $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.'); @endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <div class="card card-flush">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Kartu Piutang — {{ $agent->name }}</h3>
          <span class="text-muted fs-8">{{ $agent->phone ?: 'tanpa nomor telepon' }} ·
            tempo {{ $agent->term_days ? $agent->term_days . ' hari' : 'tunai' }}</span>
        </div>
        <div class="card-toolbar">
          <a href="{{ route('farm.receivables.index') }}" class="btn btn-sm btn-light">Kembali</a>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="alert alert-{{ $sisa > 0 ? 'danger' : 'success' }} d-flex align-items-center py-4">
          <i class="ki-outline ki-dollar fs-2x me-3"></i>
          <div><div class="fs-8 text-uppercase fw-bold">Sisa Piutang</div>
            <div class="fs-2hx fw-bold">{{ $rp($sisa) }}</div></div>
        </div>

        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0 farm-list-table">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Nota</th><th>Tanggal</th><th>Jatuh Tempo</th>
              <th class="text-end">Total</th><th class="text-end">Dibayar</th>
              <th class="text-end">Sisa</th><th class="text-end pe-4">Status</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $r)
              <tr>
                <td class="ps-4"><a href="{{ route('farm.stock-out.show', $r->id) }}" class="fw-bold">{{ $r->invoice_no }}</a>
                  @if ($r->payments->count())
                    <div class="fs-9 text-muted">
                      @foreach ($r->payments as $p){{ $p->date->format('d/m') }}: {{ $rp($p->amount) }}@if(!$loop->last) · @endif @endforeach
                    </div>
                  @endif
                </td>
                <td class="text-muted fs-8">{{ $r->date->format('d/m/Y') }}</td>
                <td class="fs-8">{{ $r->due_date?->format('d/m/Y') ?? '—' }}</td>
                <td class="text-end">{{ $rp($r->total_sale) }}</td>
                <td class="text-end text-muted">{{ $rp($r->paid_amount) }}</td>
                <td class="text-end fw-bold">{{ $rp($r->remaining()) }}</td>
                <td class="text-end pe-4">
                  @if ($r->isPaid())<span class="badge badge-light-success">Lunas</span>
                  @elseif ($r->isOverdue())<span class="badge badge-light-danger">Jatuh Tempo</span>
                  @else<span class="badge badge-light-warning">Belum Lunas</span>@endif
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-10">Belum ada transaksi.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
