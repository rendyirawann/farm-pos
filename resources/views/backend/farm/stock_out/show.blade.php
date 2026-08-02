@extends('backend.layout.app')
@section('title', 'Nota Penjualan')
@section('content')
@include('backend.farm._style')
@php
  $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
  $num = fn($n,$d=0) => number_format((float)$n, $d, ',', '.');
@endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <div class="card card-flush mw-900px mx-auto">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Nota Penjualan</h3>
          <span class="text-muted fs-8">{{ $row->invoice_no }}</span>
        </div>
        <div class="card-toolbar gap-2">
          <a href="{{ route('farm.stock-out.index') }}" class="btn btn-sm btn-light">Kembali</a>
          <button class="btn btn-sm btn-primary fw-bold" id="btn-cetak"><i class="ki-outline ki-printer fs-4"></i> Cetak Nota</button>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="row mb-5 fs-7">
          <div class="col-4">
            <div class="text-muted">Tanggal</div>
            <div class="fw-bold">{{ $row->date->locale('id')->translatedFormat('d F Y') }}</div>
          </div>
          <div class="col-4 text-center">
            <div class="text-muted">Agen</div>
            <div class="fw-bold">{{ $row->agent?->name ?? 'Umum' }}</div>
          </div>
          <div class="col-4 text-end">
            <div class="text-muted">Status</div>
            <div>
              @if ($row->isPaid())
                <span class="badge badge-success">Lunas</span>
              @else
                <span class="badge badge-{{ $row->isOverdue() ? 'danger' : 'warning' }}">
                  {{ $row->isOverdue() ? 'Jatuh Tempo' : 'Belum Lunas' }}</span>
                @if ($row->due_date)<div class="fs-8 text-muted">{{ $row->due_date->format('d/m/Y') }}</div>@endif
              @endif
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-3">Item</th><th class="text-center">Ekor</th><th class="text-center">Berat</th>
              <th class="text-end">Harga</th><th class="text-end">Subtotal</th>
              <th class="text-end">Modal</th><th class="text-end pe-3">Laba</th>
            </tr></thead>
            <tbody>
            @foreach ($row->lines as $l)
              <tr>
                <td class="ps-3 fw-bold text-gray-800">{{ $l->item?->name }}
                  @if ($l->lotUsages->count())
                    <div class="fs-9 text-muted">
                      @foreach ($l->lotUsages as $u)
                        lot {{ $u->lot?->date?->format('d/m/Y') }}
                        {{ $u->lot?->supplier?->name ? '(' . $u->lot->supplier->name . ')' : '' }}:
                        {{ $num($u->weight_kg, 2) }} kg · {{ $rp($u->cost) }}<br>
                      @endforeach
                    </div>
                  @endif
                </td>
                <td class="text-center">{{ $num($l->qty_ekor) }}</td>
                <td class="text-center">{{ $num($l->weight_kg, 2) }} kg</td>
                <td class="text-end">{{ $rp($l->unit_price) }} <span class="fs-9 text-muted">/{{ $l->price_basis }}</span></td>
                <td class="text-end fw-bold">{{ $rp($l->subtotal) }}</td>
                <td class="text-end text-muted">{{ $rp($l->cost) }}</td>
                <td class="text-end pe-3 fw-bold text-success">{{ $rp($l->profit) }}</td>
              </tr>
            @endforeach
            </tbody>
            <tfoot>
              <tr class="fw-bold fs-5">
                <td colspan="4" class="text-end">TOTAL</td>
                <td class="text-end">{{ $rp($row->total_sale) }}</td>
                <td class="text-end text-muted">{{ $rp($row->total_cost) }}</td>
                <td class="text-end pe-3 text-success">{{ $rp($row->gross_profit) }}</td>
              </tr>
              <tr><td colspan="7" class="text-end fs-8 text-muted">Margin {{ $row->marginPercent() }}%</td></tr>
            </tfoot>
          </table>
        </div>

        @if (! $row->isPaid())
          <div class="card bg-light-warning border-0 mt-4">
            <div class="card-body p-5">
              <div class="fw-bold text-gray-800 mb-3">Catat Pembayaran
                <span class="fs-8 text-muted">— sisa {{ $rp($row->remaining()) }}</span></div>
              <form method="POST" action="{{ route('farm.receivables.pay', $row->id) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-6 col-md-3"><label class="form-label fs-8 fw-semibold">Tanggal</label>
                  <input type="date" name="date" class="form-control form-control-sm form-control-solid" value="{{ now()->format('Y-m-d') }}" required></div>
                <div class="col-6 col-md-3"><label class="form-label fs-8 fw-semibold">Jumlah</label>
                  <input type="number" name="amount" class="form-control form-control-sm form-control-solid"
                         min="1" step="1000" value="{{ (int) $row->remaining() }}" required></div>
                <div class="col-6 col-md-3"><label class="form-label fs-8 fw-semibold">Metode</label>
                  <select name="method" class="form-select form-select-sm form-select-solid">
                    <option value="cash">Tunai</option><option value="transfer">Transfer</option></select></div>
                <div class="col-6 col-md-3"><button class="btn btn-sm btn-warning fw-bold w-100">Catat Pembayaran</button></div>
              </form>
            </div>
          </div>
        @endif

        @if ($row->payments->count())
          <div class="mt-4">
            <div class="fw-bold fs-7 text-gray-800 mb-2">Riwayat Pembayaran</div>
            @foreach ($row->payments as $p)
              <div class="d-flex justify-content-between border-bottom py-2 fs-8">
                <span>{{ $p->date->format('d/m/Y') }} · {{ ucfirst($p->method) }}</span>
                <span class="fw-bold">{{ $rp($p->amount) }}</span>
              </div>
            @endforeach
          </div>
        @endif

        @if ($row->notes)
          <div class="alert alert-light-primary py-3 fs-8 mt-3"><b>Catatan:</b> {{ $row->notes }}</div>
        @endif
        <div class="fs-8 text-muted mt-3">Dicatat oleh {{ $row->user?->name ?? '-' }} · {{ $row->created_at->format('d/m/Y H:i') }}</div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/mooda-print.js') }}"></script>
<script src="{{ asset('assets/js/farm-nota.js') }}"></script>
<script>
  window.FARM_STORE_NAME = @json(optional($currentTenant)->name ?? 'Mooda Stok');
  function cetakNota() {
    fetch('{{ route('farm.stock-out.receipt', $row->id) }}', { headers: { Accept: 'application/json' } })
      .then(r => r.json())
      .then(d => window.MoodaPrint && window.MoodaPrint.print(window.farmNota(d)))
      .catch(() => alert('Gagal menyiapkan nota.'));
  }
  document.getElementById('btn-cetak').addEventListener('click', cetakNota);
  @if (session('autoprint')) setTimeout(cetakNota, 600); @endif
</script>
@endpush
