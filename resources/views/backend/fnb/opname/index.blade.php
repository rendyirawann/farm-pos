@extends('backend.layout.app')
@section('title', 'Stok Opname')
@section('content')
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.fnb._nav', ['active' => 'opname'])
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card card-flush mb-6">
      <div class="card-header pt-5">
        <h3 class="card-title fw-bold fs-5">Opname Baru</h3>
        <span class="text-muted fs-8">Isi jumlah FISIK hasil hitung; sistem akan menyesuaikan stok.</span>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('fnb.opname.store') }}">@csrf
          <div class="row g-3 mb-4">
            <div class="col-md-3">
              <label class="form-label fw-semibold fs-7 required">Tanggal</label>
              <input type="date" name="date" class="form-control form-control-solid" value="{{ now()->toDateString() }}" required>
            </div>
            <div class="col-md-9">
              <label class="form-label fw-semibold fs-7">Catatan</label>
              <input type="text" name="notes" class="form-control form-control-solid" placeholder="mis. opname akhir bulan">
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-2">
              <thead><tr class="fw-bold text-muted bg-light"><th class="ps-4">Bahan</th><th class="text-end">Stok Sistem</th><th style="width:180px">Stok Fisik</th></tr></thead>
              <tbody>
              @forelse ($ingredients as $idx => $i)
                <tr>
                  <td class="ps-4 fw-bold text-gray-800">{{ $i->name }} <span class="text-muted fs-8">({{ $i->unit }})</span>
                    <input type="hidden" name="items[{{ $idx }}][ingredient_id]" value="{{ $i->id }}">
                  </td>
                  <td class="text-end fw-bold">{{ rtrim(rtrim(number_format((float) ($i->stock ?? 0), 2, '.', ''), '0'), '.') }}</td>
                  <td><input type="number" step="0.01" min="0" name="items[{{ $idx }}][physical_qty]"
                        class="form-control form-control-sm form-control-solid" value="{{ (float) ($i->stock ?? 0) }}" required></td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-center text-muted py-8">Belum ada bahan baku.</td></tr>
              @endforelse
              </tbody>
            </table>
          </div>
          @if ($ingredients->count())
            <button class="btn btn-primary mt-4">Simpan Opname &amp; Sesuaikan Stok</button>
          @endif
        </form>
      </div>
    </div>

    <div class="card card-flush">
      <div class="card-header pt-5"><h3 class="card-title fw-bold fs-5">Riwayat Opname</h3></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0">
            <thead><tr class="fw-bold text-muted bg-light"><th class="ps-6">Tanggal</th><th>Catatan</th><th class="text-end pe-6">Bahan Disesuaikan</th></tr></thead>
            <tbody>
            @forelse ($history as $o)
              <tr>
                <td class="ps-6 fw-bold">{{ $o->date?->format('d M Y') }}</td>
                <td class="text-muted fs-8">{{ $o->notes ?: '-' }}</td>
                <td class="text-end pe-6">
                  {{ $o->details->count() }} bahan
                  @php $diff = $o->details->filter(fn ($d) => abs((float) $d->difference) > 0.001)->count(); @endphp
                  @if ($diff)<span class="badge badge-light-warning ms-2 fs-9">{{ $diff }} selisih</span>@endif
                </td>
              </tr>
            @empty
              <tr><td colspan="3" class="text-center text-muted py-10">Belum ada riwayat opname.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
