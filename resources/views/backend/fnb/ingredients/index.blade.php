@extends('backend.layout.app')
@section('title', 'Bahan Baku')
@section('content')
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">

    @include('backend.fnb._nav', ['active' => 'ingredients'])

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-6">
      <div class="col-lg-4">
        <div class="card card-flush">
          <div class="card-header pt-5"><h3 class="card-title fw-bold fs-5">Tambah Bahan Baku</h3></div>
          <div class="card-body">
            <form method="POST" action="{{ route('fnb.ingredients.store') }}">@csrf
              <label class="form-label fw-semibold fs-7 required">Nama Bahan</label>
              <input type="text" name="name" class="form-control form-control-solid mb-4" placeholder="mis. Kopi Bubuk" required>
              <label class="form-label fw-semibold fs-7 required">Satuan</label>
              <select name="unit" class="form-select form-select-solid mb-4">
                @foreach (['gram','ml','pcs','kg','liter'] as $u)<option value="{{ $u }}">{{ $u }}</option>@endforeach
              </select>
              <label class="form-label fw-semibold fs-7">Minimum Stok <span class="text-muted">(peringatan)</span></label>
              <input type="number" step="0.01" min="0" name="minimum_stock" class="form-control form-control-solid mb-5" value="0">
              <button class="btn btn-primary w-100">Simpan Bahan</button>
            </form>
          </div>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="card card-flush">
          <div class="card-header pt-5">
            <h3 class="card-title fw-bold fs-5">Daftar Bahan</h3>
            @if ($lowCount > 0)<span class="badge badge-light-danger">{{ $lowCount }} bahan menipis</span>@endif
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-row-bordered align-middle gy-3 mb-0">
                <thead><tr class="fw-bold text-muted bg-light">
                  <th class="ps-6">Bahan</th><th>Satuan</th><th class="text-end">Stok</th><th class="text-end">Min.</th><th class="text-end pe-6">Aksi</th>
                </tr></thead>
                <tbody>
                @forelse ($ingredients as $i)
                  @php $stock = (float) ($i->stock ?? 0); $low = (float) $i->minimum_stock > 0 && $stock <= (float) $i->minimum_stock; @endphp
                  <tr>
                    <td class="ps-6 fw-bold text-gray-800">{{ $i->name }}
                      @if ($low)<span class="badge badge-light-danger ms-2 fs-9">Menipis</span>@endif
                    </td>
                    <td class="text-muted">{{ $i->unit }}</td>
                    <td class="text-end fw-bold {{ $low ? 'text-danger' : '' }}">{{ rtrim(rtrim(number_format($stock, 2, '.', ''), '0'), '.') }}</td>
                    <td class="text-end text-muted">{{ rtrim(rtrim(number_format((float) $i->minimum_stock, 2, '.', ''), '0'), '.') }}</td>
                    <td class="text-end pe-6">
                      <form method="POST" action="{{ route('fnb.ingredients.destroy', $i->id) }}" class="d-inline"
                            onsubmit="return confirm('Hapus bahan {{ $i->name }}?')">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-light-danger py-1 px-3 fs-8">Hapus</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted py-10">Belum ada bahan baku.</td></tr>
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
