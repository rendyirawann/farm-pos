@extends('backend.layout.app')
@section('title', 'Stok Bahan')
@section('content')
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.fnb._nav', ['active' => 'stock'])
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="row g-4 mb-6">
      <div class="col-md-4"><div class="card card-flush"><div class="card-body py-5">
        <div class="fs-8 text-muted fw-bold text-uppercase">Nilai Persediaan</div>
        <div class="fs-2 fw-bolder text-gray-900">Rp {{ number_format($stockValue, 0, ',', '.') }}</div>
        <div class="fs-8 text-muted">Σ sisa lot × harga beli lot</div>
      </div></div></div>
      <div class="col-md-4"><div class="card card-flush"><div class="card-body py-5">
        <div class="fs-8 text-muted fw-bold text-uppercase">Jumlah Bahan</div>
        <div class="fs-2 fw-bolder text-gray-900">{{ $ingredients->count() }}</div>
      </div></div></div>
      <div class="col-md-4"><div class="card card-flush"><div class="card-body py-5">
        <div class="fs-8 text-muted fw-bold text-uppercase">Lot Aktif</div>
        <div class="fs-2 fw-bolder text-gray-900">{{ $batches->count() }}</div>
        <div class="fs-8 text-muted">Lot dengan sisa &gt; 0</div>
      </div></div></div>
    </div>

    <div class="row g-6">
      <div class="col-lg-5">
        <div class="card card-flush mb-6">
          <div class="card-header pt-5"><h3 class="card-title fw-bold fs-5">Stok Masuk (Pembelian)</h3></div>
          <div class="card-body">
            <form method="POST" action="{{ route('fnb.stock.purchase') }}">@csrf
              <label class="form-label fw-semibold fs-7 required">Bahan</label>
              <select name="ingredient_id" class="form-select form-select-solid mb-3" required>
                @foreach ($ingredients as $i)<option value="{{ $i->id }}">{{ $i->name }} ({{ $i->unit }})</option>@endforeach
              </select>
              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label class="form-label fw-semibold fs-7 required">Jumlah</label>
                  <input type="number" step="0.01" min="0.01" name="quantity" class="form-control form-control-solid" required>
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold fs-7 required">Total Harga (Rp)</label>
                  <input type="number" step="1" min="0" name="buy_price_total" class="form-control form-control-solid" required>
                </div>
              </div>
              <div class="fs-8 text-muted mb-3">Harga satuan dihitung otomatis = total ÷ jumlah.</div>
              <label class="form-label fw-semibold fs-7">Supplier</label>
              <select name="supplier_id" class="form-select form-select-solid mb-3">
                <option value="">— tanpa supplier —</option>
                @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
              </select>
              <div class="row g-3 mb-4">
                <div class="col-6">
                  <label class="form-label fw-semibold fs-7">Tanggal Masuk</label>
                  <input type="date" name="entry_date" class="form-control form-control-solid" value="{{ now()->toDateString() }}">
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold fs-7">Kadaluarsa</label>
                  <input type="date" name="expiry_date" class="form-control form-control-solid">
                </div>
              </div>
              <button class="btn btn-primary w-100">Catat Stok Masuk</button>
            </form>
          </div>
        </div>

        <div class="card card-flush">
          <div class="card-header pt-5"><h3 class="card-title fw-bold fs-5">Stok Keluar Manual</h3></div>
          <div class="card-body">
            <form method="POST" action="{{ route('fnb.stock.issue') }}">@csrf
              <label class="form-label fw-semibold fs-7 required">Bahan</label>
              <select name="ingredient_id" class="form-select form-select-solid mb-3" required>
                @foreach ($ingredients as $i)<option value="{{ $i->id }}">{{ $i->name }} ({{ $i->unit }})</option>@endforeach
              </select>
              <div class="row g-3 mb-4">
                <div class="col-6">
                  <label class="form-label fw-semibold fs-7 required">Jumlah</label>
                  <input type="number" step="0.01" min="0.01" name="quantity" class="form-control form-control-solid" required>
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold fs-7 required">Alasan</label>
                  <select name="reason" class="form-select form-select-solid">
                    <option value="waste">Rusak / tumpah / buang</option>
                    <option value="adjustment">Koreksi</option>
                  </select>
                </div>
              </div>
              <button class="btn btn-light-danger w-100">Catat Stok Keluar</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="card card-flush">
          <div class="card-header pt-5">
            <h3 class="card-title fw-bold fs-5">Lot Stok (urut FEFO)</h3>
            <span class="badge badge-light-primary fs-8">Dikuras dari atas</span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-row-bordered align-middle gy-3 mb-0">
                <thead><tr class="fw-bold text-muted bg-light">
                  <th class="ps-6">Bahan</th><th class="text-end">Sisa / Awal</th><th class="text-end">Harga/satuan</th><th>Masuk</th><th>Kadaluarsa</th>
                </tr></thead>
                <tbody>
                @forelse ($batches as $b)
                  @php $exp = $b->expiry_date; $soon = $exp && $exp->lte(now()->addDays(7)); @endphp
                  <tr>
                    <td class="ps-6"><span class="fw-bold text-gray-800">{{ $b->ingredient?->name }}</span>
                      <div class="fs-8 text-muted">{{ $b->supplier?->name ?: 'tanpa supplier' }}</div></td>
                    <td class="text-end fw-bold">{{ rtrim(rtrim(number_format((float) $b->remaining_quantity, 2, '.', ''), '0'), '.') }}
                      <span class="text-muted fs-8">/ {{ rtrim(rtrim(number_format((float) $b->initial_quantity, 2, '.', ''), '0'), '.') }} {{ $b->ingredient?->unit }}</span></td>
                    <td class="text-end">Rp {{ number_format($b->buy_price, 0, ',', '.') }}</td>
                    <td class="text-muted fs-8">{{ $b->entry_date?->format('d M Y') ?: '-' }}</td>
                    <td class="fs-8 {{ $soon ? 'text-danger fw-bold' : 'text-muted' }}">{{ $exp?->format('d M Y') ?: '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted py-10">Belum ada stok. Catat pembelian dulu.</td></tr>
                @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
