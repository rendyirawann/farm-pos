@extends('backend.layout.app')
@section('title', 'Produksi Telur')
@section('content')
@php
  $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
  $num = fn($n) => number_format((float)$n, 0, ',', '.');
@endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    {{-- Rincian HPP telur: ditampilkan supaya angkanya bisa ditelusuri, bukan angka ajaib --}}
    <div class="card bg-light-warning border-0 mb-4">
      <div class="card-body p-5">
        <div class="fw-bold fs-5 text-gray-800 mb-1">Harga Pokok Telur — dihitung otomatis</div>
        <div class="fs-8 text-muted mb-3">
          Telur tidak dibeli dari supplier, jadi harga pokoknya diambil dari biaya operasional periode
          ({{ $rincian['periode'] }}) dibagi butir bersih. Catat pakan/obat/tenaga di menu
          <a href="{{ route('expenses.index') }}" class="fw-bold">Pengeluaran</a> agar angkanya akurat.
        </div>
        <div class="row g-3">
          <div class="col-6 col-md-3"><div class="bg-body rounded p-3">
            <div class="fs-9 text-muted text-uppercase fw-bold">Biaya Operasional</div>
            <div class="fs-4 fw-bold">{{ $rp($rincian['biaya']) }}</div></div></div>
          <div class="col-6 col-md-3"><div class="bg-body rounded p-3">
            <div class="fs-9 text-muted text-uppercase fw-bold">Butir Bersih</div>
            <div class="fs-4 fw-bold">{{ $num($rincian['butir']) }}</div>
            <div class="fs-9 text-muted">{{ $num($rincian['butir_kotor']) }} − {{ $num($rincian['butir_pecah']) }} pecah</div></div></div>
          <div class="col-6 col-md-3"><div class="bg-body rounded p-3">
            <div class="fs-9 text-muted text-uppercase fw-bold">HPP per Butir</div>
            <div class="fs-4 fw-bold text-warning">{{ $rp($rincian['cost_per_butir']) }}</div></div></div>
          <div class="col-6 col-md-3"><div class="bg-body rounded p-3">
            <div class="fs-9 text-muted text-uppercase fw-bold">Periode</div>
            <div class="fs-4 fw-bold">{{ $rincian['periode'] }}</div></div></div>
        </div>
      </div>
    </div>

    <div class="card card-flush">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Produksi Telur</h3>
          <span class="text-muted fs-8">Telur layak jual otomatis masuk stok.</span>
        </div>
        <div class="card-toolbar gap-2">
          <form method="GET"><input type="month" name="month" value="{{ $bulan }}"
                 class="form-control form-control-sm form-control-solid" onchange="this.form.submit()"></form>
          <button class="btn btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#m-egg">
            <i class="ki-outline ki-plus fs-3"></i> Catat Produksi</button>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Tanggal</th><th>Kandang</th><th>Item</th>
              <th class="text-end">Butir</th><th class="text-end">Pecah</th><th class="text-end">Bersih</th>
              <th class="text-end pe-4">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $r)
              <tr>
                <td class="ps-4 fw-bold">{{ $r->date->format('d/m/Y') }}</td>
                <td>{{ $r->coop ?: '—' }}</td>
                <td>{{ $r->item?->name ?? '—' }}</td>
                <td class="text-end">{{ $num($r->qty_butir) }}</td>
                <td class="text-end text-danger">{{ $num($r->qty_broken) }}</td>
                <td class="text-end fw-bold text-success">{{ $num($r->netButir()) }}</td>
                <td class="text-end pe-4">
                  <form action="{{ route('farm.eggs.destroy', $r->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Hapus catatan produksi ini?')">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-light-danger py-1 px-3 fs-8">Hapus</button></form>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-10">Belum ada produksi pada bulan ini.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="m-egg" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('farm.eggs.store') }}">
        @csrf
        <div class="modal-header py-4"><h3 class="fw-bold mb-0">Catat Produksi Telur</h3>
          <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
        </div>
        <div class="modal-body">
          @if ($items->isEmpty())
            <div class="alert alert-warning py-3 fs-8">Belum ada item kategori <b>Telur</b>.
              <a href="{{ route('farm.items.index') }}" class="fw-bold">Tambah dulu</a>.</div>
          @endif
          <div class="row g-3">
            <div class="col-6"><label class="form-label fw-semibold fs-7 required">Tanggal</label>
              <input type="date" name="date" class="form-control form-control-solid" value="{{ now()->format('Y-m-d') }}" required></div>
            <div class="col-6"><label class="form-label fw-semibold fs-7">Kandang</label>
              <input name="coop" class="form-control form-control-solid" maxlength="50" placeholder="mis. Kandang A"></div>
            <div class="col-12"><label class="form-label fw-semibold fs-7 required">Item Telur</label>
              <select name="item_id" class="form-select form-select-solid" required>
                @foreach ($items as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach
              </select></div>
            <div class="col-6"><label class="form-label fw-semibold fs-7 required">Jumlah Butir</label>
              <input type="number" name="qty_butir" class="form-control form-control-solid" min="1" required></div>
            <div class="col-6"><label class="form-label fw-semibold fs-7">Telur Pecah</label>
              <input type="number" name="qty_broken" class="form-control form-control-solid" min="0" value="0"></div>
            <div class="col-12"><label class="form-label fw-semibold fs-7">Catatan</label>
              <input name="notes" class="form-control form-control-solid" maxlength="255"></div>
          </div>
        </div>
        <div class="modal-footer py-3">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-warning fw-bold" {{ $items->isEmpty() ? 'disabled' : '' }}>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
