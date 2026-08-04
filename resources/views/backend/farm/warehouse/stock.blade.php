@extends('backend.layout.app')
@section('title', 'Stok per Supplier')
@section('content')
@include('backend.farm._style')
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    @php
      $rp  = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
      $num = fn ($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    @endphp

    <div class="row g-4 mb-5 farm-kpi">
      <div class="col-12 col-md-6">
        <div class="card card-flush h-100">
          <div class="card-body py-6">
            <div class="text-muted fs-7 mb-1">Sisa stok seluruh supplier</div>
            <div class="fs-2hx fw-bold text-gray-900">{{ $num($total['sisa_kg'], 2) }} <span class="fs-4">kg</span></div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="card card-flush h-100">
          <div class="card-body py-6">
            <div class="text-muted fs-7 mb-1">Nilai persediaan (harga pokok)</div>
            <div class="fs-2hx fw-bold text-primary">{{ $rp($total['nilai_sisa']) }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card card-flush">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Stok per Supplier</h3>
          <span class="text-muted fs-8">Halaman baca saja. Angka dibangun dari lot: barang masuk, realisasi,
            barang keluar, dan penyesuaian stok.</span>
        </div>
        <div class="card-toolbar">
          <a href="{{ route('farm.warehouse.index') }}" class="btn btn-sm btn-light fw-bold">Buka/Tutup Gudang</a>
        </div>
      </div>
      <div class="card-body pt-4">
        @forelse ($rows as $s)
          <div class="border rounded p-4 mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
              <div>
                <div class="fw-bold fs-5 text-gray-900">{{ $s['nama'] }}</div>
                <div class="fs-8 text-muted">
                  Sisa <b>{{ $num($s['sisa_kg'], 2) }} kg</b> / {{ $num($s['sisa_ekor']) }} ekor ·
                  nilai persediaan <b>{{ $rp($s['nilai_sisa']) }}</b>
                </div>
              </div>
              @if ($s['supplier_id'])
                <a href="{{ route('farm.warehouse.stock.detail', $s['supplier_id']) }}"
                   class="btn btn-sm btn-light-primary fw-bold">Rincian HPP</a>
              @endif
            </div>

            <div class="row g-3 mb-3 fs-8">
              <div class="col-6 col-md-3">
                <div class="text-muted">Nilai barang masuk</div>
                <div class="fw-bold fs-6">{{ $rp($s['masuk']) }}</div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted">Terjual (HPP)</div>
                <div class="fw-bold fs-6 text-success">{{ $rp($s['terjual']) }}</div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted">Susut penyesuaian</div>
                <div class="fw-bold fs-6 text-danger">{{ $rp($s['susut']) }}</div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted">Koreksi realisasi</div>
                <div class="fw-bold fs-6">{{ $s['realisasi'] > 0 ? '+' : '' }}{{ $rp($s['realisasi']) }}</div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-row-bordered align-middle gy-2 mb-0 fs-8 farm-list-table">
                <thead><tr class="fw-bold text-muted bg-light">
                  <th class="ps-3">Barang</th><th class="text-end">Sisa</th>
                  <th class="text-end">Nilai</th><th class="text-end">HPP/kg</th><th class="text-end pe-3">Jml Lot</th>
                </tr></thead>
                <tbody>
                @foreach ($s['items'] as $it)
                  <tr>
                    <td class="ps-3 fw-bold text-gray-800" data-label="Barang">{{ $it['nama'] }}</td>
                    <td class="text-end" data-label="Sisa">
                      {{ $num($it['sisa_kg'], 2) }} kg
                      <div class="fs-9 text-muted">{{ $num($it['sisa_ekor']) }} ekor</div>
                    </td>
                    <td class="text-end fw-bold" data-label="Nilai">{{ $rp($it['nilai_sisa']) }}</td>
                    <td class="text-end" data-label="HPP/kg">
                      {{-- Tidak pernah dibagi nol: kalau kg sisa 0, HPP/kg memang belum bisa dihitung. --}}
                      @if ($it['sisa_kg'] > 0.001)
                        {{ $rp($it['nilai_sisa'] / $it['sisa_kg']) }}
                      @else
                        <span class="text-muted">—</span>
                        <div class="fs-9 text-muted">belum bisa dihitung</div>
                      @endif
                    </td>
                    <td class="text-end pe-3 text-muted" data-label="Jml Lot">{{ $it['lot'] }}</td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @empty
          <div class="text-center text-muted py-10">
            Belum ada lot stok. Catat <a href="{{ route('farm.stock-in.create') }}">barang masuk</a> dulu.
          </div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
