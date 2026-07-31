@extends('backend.layout.app')
@section('title', 'Kartu Stok')
@section('content')
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.fnb._nav', ['active' => 'card'])

    <div class="card card-flush">
      <div class="card-header pt-5">
        <h3 class="card-title fw-bold fs-5">Kartu Stok (Ledger)</h3>
        <form method="GET" class="card-toolbar">
          <select name="ingredient_id" class="form-select form-select-sm form-select-solid w-250px" onchange="this.form.submit()">
            @foreach ($ingredients as $i)
              <option value="{{ $i->id }}" @selected($ingredientId == $i->id)>{{ $i->name }} ({{ $i->unit }})</option>
            @endforeach
          </select>
        </form>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0">
            <thead><tr class="fw-bold text-muted bg-light">
              <th class="ps-6">Waktu</th><th>Jenis</th><th>Alasan</th><th class="text-end">Jumlah</th><th class="text-end">Nilai (COGS)</th><th class="pe-6">Lot</th>
            </tr></thead>
            <tbody>
            @forelse ($movements as $m)
              <tr>
                <td class="ps-6 text-muted fs-8">{{ $m->created_at?->format('d M Y H:i') }}</td>
                <td><span class="badge badge-light-{{ $m->type === 'in' ? 'success' : 'danger' }}">{{ $m->type === 'in' ? 'Masuk' : 'Keluar' }}</span></td>
                <td class="fs-8">{{ $m->reasonLabel() }}</td>
                <td class="text-end fw-bold">{{ rtrim(rtrim(number_format((float) $m->quantity, 2, '.', ''), '0'), '.') }}</td>
                <td class="text-end">Rp {{ number_format($m->cost_total, 0, ',', '.') }}</td>
                <td class="pe-6 fs-8 text-muted">
                  @if ($m->batch)#{{ $m->batch->id }} · Rp{{ number_format($m->batch->buy_price, 0, ',', '.') }}/satuan
                  @else <span class="text-danger">tanpa lot (stok kurang)</span>@endif
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted py-10">Belum ada gerakan stok untuk bahan ini.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
