@extends('backend.layout.app')
@section('title', 'Riwayat Barang Keluar')
@section('content')
@include('backend.farm._style')
@php $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.'); @endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <div class="row g-4 mb-4">
      <div class="col-4"><div class="card bg-light-primary border-0"><div class="card-body p-5">
        <div class="fs-8 text-muted fw-bold text-uppercase">Penjualan</div>
        <div class="fs-3 fw-bold text-gray-800">{{ $rp($rekap->jual) }}</div></div></div></div>
      <div class="col-4"><div class="card bg-light-warning border-0"><div class="card-body p-5">
        <div class="fs-8 text-muted fw-bold text-uppercase">Modal (FIFO)</div>
        <div class="fs-3 fw-bold text-gray-800">{{ $rp($rekap->modal) }}</div></div></div></div>
      <div class="col-4"><div class="card bg-light-success border-0"><div class="card-body p-5">
        <div class="fs-8 text-muted fw-bold text-uppercase">Laba Kotor</div>
        <div class="fs-3 fw-bold text-success">{{ $rp($rekap->laba) }}</div></div></div></div>
    </div>

    <div class="card card-flush">
      <div class="card-header pt-5">
        <h3 class="card-title fw-bold fs-4 mb-0">Barang Keluar</h3>
        <div class="card-toolbar gap-2">
          <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm form-control-solid" style="width:150px">
            <span class="text-muted">s/d</span>
            <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm form-control-solid" style="width:150px">
            <select name="status" class="form-select form-select-sm form-select-solid" style="width:150px">
              <option value="">Semua status</option>
              <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
            </select>
            <button class="btn btn-sm btn-light-primary fw-bold">Filter</button>
          </form>
          <a href="{{ route('farm.stock-out.create') }}" class="btn btn-warning fw-bold">
            <i class="ki-outline ki-plus fs-3"></i> Barang Keluar</a>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0 farm-list-table">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Nota</th><th>Tanggal</th><th>Agen</th><th>Barang</th>
              <th class="text-end">Jual</th><th class="text-end">Laba</th><th class="text-end pe-4">Status</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $r)
              <tr>
                <td class="ps-4"><a href="{{ route('farm.stock-out.show', $r->id) }}" class="fw-bold">{{ $r->invoice_no }}</a></td>
                <td class="text-muted fs-8">{{ $r->date->format('d/m/Y') }}</td>
                <td>{{ $r->agent?->name ?? 'Umum' }}</td>
                <td class="fs-8">
                  @foreach ($r->lines as $l)
                    <span class="badge badge-light-warning fs-9 me-1 mb-1">
                      {{ $l->item?->name }} · {{ (int) $l->qty_ekor }}/{{ number_format((float) $l->weight_kg, 2, ',', '.') }}kg
                    </span>
                  @endforeach
                </td>
                <td class="text-end fw-bold">{{ $rp($r->total_sale) }}</td>
                <td class="text-end fw-bold text-success">{{ $rp($r->gross_profit) }}
                  <div class="fs-9 text-muted">{{ $r->marginPercent() }}%</div></td>
                <td class="text-end pe-4">
                  @if ($r->isPaid())
                    <span class="badge badge-light-success">Lunas</span>
                  @elseif ($r->isOverdue())
                    <span class="badge badge-light-danger">Jatuh Tempo</span>
                    <div class="fs-9 text-muted">{{ $r->due_date->format('d/m/Y') }}</div>
                  @else
                    <span class="badge badge-light-warning">Belum Lunas</span>
                    @if ($r->due_date)<div class="fs-9 text-muted">{{ $r->due_date->format('d/m/Y') }}</div>@endif
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-10">Belum ada penjualan pada periode ini.</td></tr>
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
