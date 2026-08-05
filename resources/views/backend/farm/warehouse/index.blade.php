@extends('backend.layout.app')
@section('title', 'Gudang')
@section('content')
@include('backend.farm._style')
@php
  $rp  = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
  $num = fn ($n, $d = 0) => number_format((float) $n, $d, ',', '.');
@endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    {{-- Ringkasan stok REALTIME — inilah angka yang dicari saat orang membuka menu Gudang. --}}
    <div class="row g-4 mb-5 farm-kpi">
      <div class="col-6 col-lg-3">
        <div class="card card-flush h-100">
          <div class="card-body py-5">
            <div class="text-muted fs-8">Stok sekarang</div>
            <div class="fs-2hx fw-bold text-gray-900">{{ $num($total['sisa_kg'], 2) }}<span class="fs-5"> kg</span></div>
            <div class="fs-9 text-muted">{{ $num($total['sisa_ekor']) }} ekor / butir</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="card card-flush h-100">
          <div class="card-body py-5">
            <div class="text-muted fs-8">Nilai persediaan</div>
            <div class="fs-2hx fw-bold text-primary">{{ $rp($total['nilai']) }}</div>
            <div class="fs-9 text-muted">harga pokok tiap lot</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="card card-flush h-100">
          <div class="card-body py-5">
            <div class="text-muted fs-8">Masuk {{ $labelPeriode }}</div>
            <div class="fs-2hx fw-bold text-success">{{ $num($total['masuk_kg'], 2) }}<span class="fs-5"> kg</span></div>
            <div class="fs-9 text-muted">{{ $num($total['masuk_ekor']) }} ekor / butir</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="card card-flush h-100">
          <div class="card-body py-5">
            <div class="text-muted fs-8">Keluar {{ $labelPeriode }}</div>
            <div class="fs-2hx fw-bold text-warning">{{ $num($total['keluar_kg'], 2) }}<span class="fs-5"> kg</span></div>
            <div class="fs-9 text-muted">{{ $num($total['keluar_ekor']) }} ekor / butir</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card card-flush">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Gudang — Stok Barang</h3>
          <span class="text-muted fs-8">
            Halaman baca saja. Kolom <b>Stok Sekarang</b> selalu realtime (barang masuk − barang keluar − susut);
            kolom Masuk/Keluar/Susut mengikuti periode yang dipilih.
          </span>
        </div>
        <div class="card-toolbar gap-2 flex-wrap">
          <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <select name="periode" class="form-select form-select-sm form-select-solid" style="width:170px">
              @foreach ($daftarPeriode as $k => $v)
                <option value="{{ $k }}" {{ $periode === $k ? 'selected' : '' }}>{{ $v }}</option>
              @endforeach
            </select>
            <button class="btn btn-sm btn-light-primary fw-bold">Tampilkan</button>
          </form>
          <a href="{{ route('farm.warehouse.stock') }}" class="btn btn-sm btn-light-primary fw-bold">
            <i class="ki-outline ki-chart-simple fs-5"></i> Stok per Supplier</a>
          <a href="{{ route('farm.reports.index', ['jenis' => 'kartu-stok']) }}" class="btn btn-sm btn-light fw-bold">
            Kartu Stok (PDF)</a>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0 farm-card-table">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Barang</th>
              <th class="text-end">Masuk ({{ $labelPeriode }})</th>
              <th class="text-end">Keluar ({{ $labelPeriode }})</th>
              <th class="text-end">Susut ({{ $labelPeriode }})</th>
              <th class="text-end">Stok Sekarang</th>
              <th class="text-end pe-4">Nilai Persediaan</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $r)
              <tr>
                <td class="ps-4 fw-bold text-gray-800" data-label="Barang">
                  {{ $r['nama'] }}
                  <div class="fs-9 text-muted fw-normal">
                    {{ $r['produksi'] ? 'produksi sendiri' : 'dibeli dari supplier' }}
                    @if ($r['lot'] > 0) · {{ $r['lot'] }} lot aktif @endif
                  </div>
                </td>
                <td class="text-end text-success" data-label="Masuk">
                  {{ $num($r['masuk_kg'], 2) }} kg
                  <div class="fs-9 text-muted">{{ $num($r['masuk_ekor']) }} ekor/butir</div>
                </td>
                <td class="text-end text-warning" data-label="Keluar">
                  {{ $num($r['keluar_kg'], 2) }} kg
                  <div class="fs-9 text-muted">{{ $num($r['keluar_ekor']) }} ekor/butir</div>
                </td>
                <td class="text-end text-danger" data-label="Susut">
                  @if ($r['susut_kg'] > 0.001 || $r['susut_ekor'] > 0)
                    {{ $num($r['susut_kg'], 2) }} kg
                    <div class="fs-9 text-muted">{{ $num($r['susut_ekor']) }} ekor/butir</div>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td class="text-end fw-bold fs-6" data-label="Stok Sekarang">
                  {{ $num($r['sisa_kg'], 2) }} kg
                  <div class="fs-9 text-muted fw-normal">{{ $num($r['sisa_ekor']) }} ekor/butir</div>
                </td>
                <td class="text-end pe-4 fw-bold" data-label="Nilai Persediaan">{{ $rp($r['nilai']) }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted py-10">
                Belum ada barang aktif. Tambahkan dulu di menu <a href="{{ route('farm.items.index') }}">Item</a>.
              </td></tr>
            @endforelse
            </tbody>
            @if (count($rows))
              <tfoot><tr class="fw-bold border-top border-2">
                <td class="ps-4">TOTAL</td>
                <td class="text-end">{{ $num($total['masuk_kg'], 2) }} kg</td>
                <td class="text-end">{{ $num($total['keluar_kg'], 2) }} kg</td>
                <td class="text-end">{{ $num($total['susut_kg'], 2) }} kg</td>
                <td class="text-end">{{ $num($total['sisa_kg'], 2) }} kg</td>
                <td class="text-end pe-4">{{ $rp($total['nilai']) }}</td>
              </tr></tfoot>
            @endif
          </table>
        </div>

        @if ($total['tanpa_stok'] > 0.001)
          <div class="alert alert-danger fs-8 py-3 mt-4 mb-0">
            <b>{{ $num($total['tanpa_stok'], 2) }} kg tidak menemukan stok</b> pada periode ini — ada nota keluar
            (atau penyesuaian) yang jumlahnya melebihi stok yang tercatat. Biasanya karena
            <b>nota barang masuk belum dicatat</b>. Bagian itu tersimpan berharga pokok 0, jadi labanya terlihat
            lebih besar dari kenyataan. Catat pembelian yang tertinggal, lalu buka
            <a href="{{ route('farm.reports.index', ['jenis' => 'kartu-stok']) }}" class="fw-bold">Kartu Stok</a>
            untuk memastikan sudah beres.
          </div>
        @endif

        <div class="alert alert-light-primary border border-primary fs-8 py-3 mt-4 mb-0">
          Stok berkurang otomatis saat <b>Barang Keluar</b> disimpan (FIFO) dan saat ada
          <a href="{{ route('farm.adjustments.index') }}" class="fw-bold">Penyesuaian Stok</a>.
          Bila hitungan fisik di kandang berbeda dengan angka di sini, luruskan lewat Penyesuaian Stok —
          jangan diubah langsung, supaya sebab selisihnya tetap tercatat.
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
