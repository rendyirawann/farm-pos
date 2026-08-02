@extends('backend.layout.app')
@section('title', 'Piutang Agen')
@section('content')
@include('backend.farm._style')
@php $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.'); @endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <div class="row g-4 mb-4">
      <div class="col-6"><div class="card bg-light-danger border-0"><div class="card-body p-5">
        <div class="fs-8 text-muted fw-bold text-uppercase">Total Piutang</div>
        <div class="fs-2hx fw-bold text-gray-800">{{ $rp($total) }}</div></div></div></div>
      <div class="col-6"><div class="card bg-light-warning border-0"><div class="card-body p-5">
        <div class="fs-8 text-muted fw-bold text-uppercase">Sudah Jatuh Tempo</div>
        <div class="fs-2hx fw-bold {{ $jatuhTempo > 0 ? 'text-danger' : 'text-gray-800' }}">{{ $rp($jatuhTempo) }}</div></div></div></div>
    </div>

    <div class="card card-flush">
      <div class="card-header pt-5">
        <h3 class="card-title fw-bold fs-4 mb-0">Nota Belum Lunas</h3>
        <div class="card-toolbar">
          <form method="GET" class="d-flex gap-2">
            <select name="agent_id" class="form-select form-select-sm form-select-solid" style="width:180px">
              <option value="">Semua agen</option>
              @foreach ($agents as $a)
                <option value="{{ $a->id }}" {{ (string) $agentId === (string) $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
              @endforeach
            </select>
            <select name="filter" class="form-select form-select-sm form-select-solid" style="width:180px">
              <option value="">Semua</option>
              <option value="overdue" {{ $filter === 'overdue' ? 'selected' : '' }}>Jatuh tempo saja</option>
            </select>
            <button class="btn btn-sm btn-light-primary fw-bold">Filter</button>
          </form>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0 farm-list-table">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Nota</th><th>Tanggal</th><th>Agen</th><th>Jatuh Tempo</th>
              <th class="text-end">Total</th><th class="text-end">Dibayar</th><th class="text-end pe-4">Sisa</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $r)
              <tr class="{{ $r->isOverdue() ? 'bg-light-danger' : '' }}">
                <td class="ps-4"><a href="{{ route('farm.stock-out.show', $r->id) }}" class="fw-bold">{{ $r->invoice_no }}</a></td>
                <td class="text-muted fs-8">{{ $r->date->format('d/m/Y') }}</td>
                <td>{{ $r->agent?->name ?? 'Umum' }}</td>
                <td>
                  @if ($r->due_date)
                    <span class="{{ $r->isOverdue() ? 'text-danger fw-bold' : '' }}">{{ $r->due_date->format('d/m/Y') }}</span>
                    @if ($r->isOverdue())
                      <div class="fs-9 text-danger">telat {{ $r->due_date->diffInDays(now()) }} hari</div>
                    @endif
                  @else — @endif
                </td>
                <td class="text-end">{{ $rp($r->total_sale) }}</td>
                <td class="text-end text-muted">{{ $rp($r->paid_amount) }}</td>
                <td class="text-end pe-4 fw-bold text-danger">{{ $rp($r->remaining()) }}</td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-10">Tidak ada piutang. 🎉</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-4">{{ $rows->links() }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
