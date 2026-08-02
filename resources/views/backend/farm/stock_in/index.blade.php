@extends('backend.layout.app')
@section('title', 'Riwayat Barang Masuk')
@section('content')
@include('backend.farm._style')
@php $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.'); @endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <div class="card card-flush">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Barang Masuk</h3>
          <span class="text-muted fs-8">Total periode ini: <b class="text-gray-800">{{ $rp($total) }}</b></span>
        </div>
        <div class="card-toolbar gap-2">
          <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm form-control-solid" style="width:150px">
            <span class="text-muted">s/d</span>
            <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm form-control-solid" style="width:150px">
            <button class="btn btn-sm btn-light-primary fw-bold">Filter</button>
          </form>
          <a href="{{ route('farm.stock-in.create') }}" class="btn btn-success fw-bold">
            <i class="ki-outline ki-plus fs-3"></i> Barang Masuk</a>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0 farm-list-table">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Nota</th><th>Tanggal</th><th>Supplier</th><th>Barang</th>
              <th class="text-end pe-4">Total</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $r)
              <tr>
                <td class="ps-4"><a href="{{ route('farm.stock-in.show', $r->id) }}" class="fw-bold">{{ $r->invoice_no }}</a></td>
                <td class="text-muted fs-8">{{ $r->date->format('d/m/Y') }}</td>
                <td>{{ $r->supplier?->name ?? '—' }}</td>
                <td class="fs-8">
                  @foreach ($r->lines as $l)
                    <span class="badge badge-light-success fs-9 me-1 mb-1">
                      {{ $l->item?->name }} · {{ (int) $l->qty_ekor }} ekor / {{ number_format((float) $l->weight_kg, 2, ',', '.') }} kg
                    </span>
                  @endforeach
                </td>
                <td class="text-end pe-4 fw-bold">{{ $rp($r->total) }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted py-10">Belum ada pembelian pada periode ini.</td></tr>
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
