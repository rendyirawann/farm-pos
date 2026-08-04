@extends('backend.layout.app')
@section('title', 'HPP ' . $nama)
@section('content')
@include('backend.farm._style')
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    @php
      $rp  = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
      $num = fn ($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    @endphp

    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
      <a href="{{ route('farm.warehouse.stock') }}" class="btn btn-sm btn-light">&larr; Stok per Supplier</a>
      <h3 class="fw-bold fs-3 mb-0 ms-1">Rincian HPP — {{ $nama }}</h3>
    </div>

    {{-- Rumusnya ditulis apa adanya supaya angkanya bisa ditelusuri, bukan dipercaya. --}}
    <div class="card card-flush mb-5">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-5 mb-0">Perhitungan</h3>
          <span class="text-muted fs-8">Barang masuk − terjual − susut penyesuaian = sisa persediaan.</span>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="row g-4">
          <div class="col-6 col-lg-3">
            <div class="border rounded p-4 h-100">
              <div class="text-muted fs-8">Nilai barang masuk</div>
              <div class="fw-bold fs-4 text-gray-900">{{ $rp($ringkas['masuk']) }}</div>
              <div class="fs-9 text-muted mt-1">sudah termasuk koreksi realisasi</div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="border rounded p-4 h-100">
              <div class="text-muted fs-8">Terjual (HPP)</div>
              <div class="fw-bold fs-4 text-success">{{ $rp($ringkas['terjual']) }}</div>
              <div class="fs-9 text-muted mt-1">harga pokok saat nota keluar dibuat</div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="border rounded p-4 h-100">
              <div class="text-muted fs-8">Susut penyesuaian</div>
              <div class="fw-bold fs-4 text-danger">{{ $rp($ringkas['susut']) }}</div>
              <div class="fs-9 text-muted mt-1">mati / susut di gudang — beban kita</div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="border rounded p-4 h-100 bg-light-primary">
              <div class="text-muted fs-8">Sisa persediaan</div>
              <div class="fw-bold fs-4 text-primary">{{ $rp($ringkas['sisa']) }}</div>
              <div class="fs-9 text-muted mt-1">
                {{ $num($ringkas['sisa_kg'], 2) }} kg / {{ $num($ringkas['sisa_ekor']) }} ekor
              </div>
            </div>
          </div>
        </div>

        <div class="separator my-5"></div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="fs-7">
            <span class="text-muted">Koreksi realisasi terhadap saldo supplier:</span>
            <b class="{{ $ringkas['realisasi'] > 0 ? 'text-success' : ($ringkas['realisasi'] < 0 ? 'text-danger' : '') }}">
              {{ $ringkas['realisasi'] > 0 ? '+' : '' }}{{ $rp($ringkas['realisasi']) }}</b>
            <div class="fs-9 text-muted">Positif = barang kurang dari nota, saldo supplier bertambah.</div>
          </div>
          <div class="fs-7">
            <span class="text-muted">Selisih rekonsiliasi:</span>
            @if (abs($ringkas['selisih']) < 1)
              <b class="text-success">Rp 0 — cocok</b>
            @else
              <b class="text-danger">{{ $rp($ringkas['selisih']) }}</b>
              <div class="fs-9 text-danger">Ada mutasi yang tidak tercatat — periksa nota keluar & penyesuaian.</div>
            @endif
          </div>
        </div>

        @if ($ringkas['sisa_kg'] > 0.001)
          <div class="alert alert-light-primary border border-primary mt-5 mb-0 py-3 fs-7">
            HPP rata-rata sisa stok supplier ini:
            <b class="fs-5">{{ $rp($ringkas['sisa'] / $ringkas['sisa_kg']) }}/kg</b>
            @if ($ringkas['sisa_ekor'] > 0)
              · rata-rata {{ $num($ringkas['sisa_kg'] / $ringkas['sisa_ekor'], 2) }} kg/ekor
            @endif
          </div>
        @endif
      </div>
    </div>

    {{-- Per lot, urut FIFO: lot paling atas adalah yang akan terpakai lebih dulu. --}}
    <div class="card card-flush">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-5 mb-0">Rincian per Lot (urutan FIFO)</h3>
          <span class="text-muted fs-8">Lot paling atas yang akan dipakai lebih dulu saat barang keluar.</span>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-2 mb-0 fs-8 farm-list-table">
            <thead><tr class="fw-bold text-muted bg-light">
              <th class="ps-3">Lot / Tanggal</th><th>Barang</th>
              <th class="text-end">Masuk</th><th class="text-end">Realisasi</th>
              <th class="text-end">Terjual</th><th class="text-end">Susut</th>
              <th class="text-end">Sisa</th><th class="text-end pe-3">HPP/kg</th>
            </tr></thead>
            <tbody>
            @foreach ($lots as $l)
              @php $habis = $l['sisa_kg'] <= 0.001 && $l['sisa_ekor'] <= 0; @endphp
              <tr class="{{ $habis ? 'text-muted' : '' }}">
                <td class="ps-3" data-label="Lot / Tanggal">
                  #{{ $l['lot_id'] }}
                  <div class="fs-9 text-muted">{{ $l['tanggal']->format('d/m/Y') }}</div>
                  @if (! $habis && $loop->first)
                    <span class="badge badge-light-primary fs-9 mt-1">dipakai berikutnya</span>
                  @endif
                  @if ($habis)<span class="badge badge-light-secondary fs-9 mt-1">habis</span>@endif
                </td>
                <td data-label="Barang" class="fw-bold text-gray-800">{{ $l['item_nama'] }}</td>
                <td class="text-end" data-label="Masuk">
                  {{ $num($l['awal_kg'], 2) }} kg
                  <div class="fs-9 text-muted">{{ $num($l['awal_ekor']) }} ekor · {{ $rp($l['nilai_masuk']) }}</div>
                </td>
                <td class="text-end" data-label="Realisasi">
                  @if ($l['realisasi_label'])
                    <span class="badge badge-light-{{ $l['nilai_realisasi'] > 0 ? 'success' : 'warning' }} fs-9">
                      {{ $l['realisasi_label'] }}</span>
                    <div class="fs-9 text-muted">
                      nota {{ $num($l['nota_kg'], 2) }} kg ·
                      {{ $l['nilai_realisasi'] > 0 ? '+' : '' }}{{ $rp($l['nilai_realisasi']) }}
                    </div>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-end" data-label="Terjual">
                  @if ($l['terjual_kg'] > 0.001 || $l['terjual_ekor'] > 0)
                    {{ $num($l['terjual_kg'], 2) }} kg
                    <div class="fs-9 text-success">{{ $rp($l['nilai_terjual']) }}</div>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-end" data-label="Susut">
                  @if ($l['susut_kg'] > 0.001 || $l['susut_ekor'] > 0)
                    {{ $num($l['susut_kg'], 2) }} kg
                    <div class="fs-9 text-danger">{{ $rp($l['nilai_susut']) }}</div>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-end fw-bold" data-label="Sisa">
                  {{ $num($l['sisa_kg'], 2) }} kg
                  <div class="fs-9 text-muted fw-normal">{{ $num($l['sisa_ekor']) }} ekor · {{ $rp($l['nilai_sisa']) }}</div>
                </td>
                <td class="text-end pe-3" data-label="HPP/kg">
                  {{ $l['hpp_kg'] > 0 ? $rp($l['hpp_kg']) : '—' }}
                  @if ($l['hpp_ekor'] > 0)
                    <div class="fs-9 text-muted">{{ $rp($l['hpp_ekor']) }}/ekor</div>
                  @endif
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
