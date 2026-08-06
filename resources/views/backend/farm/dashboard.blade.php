@extends('backend.layout.app')
@section('title', 'Dashboard Peternakan')

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $num = fn ($n, $d = 0) => number_format((float) $n, $d, ',', '.');
@endphp

<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">

    {{-- ===================== HERO + FILTER PERIODE ===================== --}}
    <div class="card border-0 mb-5 farm-hero" style="background:linear-gradient(120deg,#15803d 0%,#16a34a 55%,#22c55e 100%)">
      <div class="card-body d-flex flex-wrap align-items-center justify-content-between py-6">
        <div class="text-white">
          <h2 class="text-white fw-bold mb-1">Halo, {{ auth()->user()->name }} 👋</h2>
          <div class="text-white opacity-75 fs-6">
            {{ optional($currentTenant)->name ?? 'Mooda Stok' }} •
            <span class="fw-bold">{{ $periodLabel }}</span>
            <span class="badge badge-light ms-1 fs-9">{{ $range === 'day' ? 'Harian' : 'Bulanan' }}</span>
          </div>
        </div>

        <div class="d-flex gap-2 mt-3 mt-sm-0 align-items-center flex-wrap farm-hero-actions">
          <form method="GET" class="d-flex align-items-center gap-2" id="form-periode">
            <div class="btn-group btn-group-sm">
              <input type="radio" class="btn-check" name="range" id="r-day" value="day"
                     {{ $range === 'day' ? 'checked' : '' }} onchange="document.getElementById('form-periode').submit()">
              <label class="btn btn-sm btn-light fw-bold" for="r-day">Harian</label>
              <input type="radio" class="btn-check" name="range" id="r-month" value="month"
                     {{ $range === 'month' ? 'checked' : '' }} onchange="document.getElementById('form-periode').submit()">
              <label class="btn btn-sm btn-light fw-bold" for="r-month">Bulanan</label>
            </div>
            @if ($range === 'day')
              <input type="date" name="date" value="{{ $selectedDate }}" max="{{ now()->format('Y-m-d') }}"
                     class="form-control form-control-sm fw-bold border-0" style="min-width:160px"
                     onchange="document.getElementById('form-periode').submit()">
              <input type="hidden" name="month" value="{{ $selectedMonth }}">
            @else
              <select name="month" class="form-select form-select-sm fw-bold border-0" style="min-width:150px"
                      onchange="document.getElementById('form-periode').submit()">
                @foreach ($monthOptions as $o)
                  <option value="{{ $o['value'] }}" {{ $selectedMonth === $o['value'] ? 'selected' : '' }}>{{ $o['label'] }}</option>
                @endforeach
              </select>
              <input type="hidden" name="date" value="{{ $selectedDate }}">
            @endif
          </form>

          <a href="{{ route('farm.stock-in.create') }}" class="btn btn-light fw-bold">
            <i class="ki-outline ki-entrance-left fs-3 me-1"></i> Barang Masuk
          </a>
          <a href="{{ route('farm.stock-out.create') }}" class="btn btn-warning fw-bold">
            <i class="ki-outline ki-entrance-right fs-3 me-1"></i> Barang Keluar
          </a>
        </div>
      </div>
    </div>

    {{-- ===================== PANDUAN SETUP (khas peternakan) ===================== --}}
    {{-- Sakelar tampil/sembunyi — tetap bisa dibuka lagi walau setup sudah selesai. --}}
    <div class="d-flex justify-content-end mb-3">
      <form method="POST" action="{{ route('farm.onboarding-toggle') }}" id="onb-form" class="m-0">
        @csrf
        <label class="form-check form-switch form-check-custom form-check-solid">
          <input class="form-check-input" type="checkbox" name="show" value="1"
                 {{ $onboarding['show'] ? 'checked' : '' }}
                 onchange="document.getElementById('onb-form').submit()">
          <span class="form-check-label fw-semibold text-gray-600 fs-8 ms-2">Panduan Setup Awal</span>
        </label>
      </form>
    </div>

    @if ($onboarding['show'])
      <div class="card card-flush mb-5 border-start border-4 border-success">
        <div class="card-body py-5">
          <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
              <span class="badge badge-light-success fw-bold me-2">Setup Awal</span>
              <span class="fs-4 fw-bold text-gray-800">Siapkan data peternakan Anda 🐔</span>
              <div class="fs-7 text-muted mt-1">Lengkapi langkah berikut agar pencatatan stok bisa berjalan.</div>
            </div>
            <span class="badge badge-light-{{ $onboarding['done'] ? 'success' : 'primary' }} fs-8">
              {{ $onboarding['selesai'] }}/{{ $onboarding['total'] }} selesai</span>
          </div>

          @if ($onboarding['done'])
            <div class="alert alert-success d-flex align-items-center py-3 fs-8 mb-4">
              <i class="ki-outline ki-check-circle fs-2 me-2"></i>
              <div class="flex-grow-1">Semua langkah selesai — data peternakan Anda siap dipakai. 🎉</div>
              <form method="POST" action="{{ route('farm.onboarding-toggle') }}" class="m-0">
                @csrf
                <button class="btn btn-sm btn-light-success fw-bold">
                  <i class="ki-outline ki-eye-slash fs-6 me-1"></i>Sembunyikan panduan</button>
              </form>
            </div>
          @endif
          <div class="d-flex flex-column gap-2">
            @foreach ($onboarding['steps'] as $i => $s)
              <div class="d-flex align-items-center justify-content-between border rounded p-3 {{ $s['selesai'] ? 'bg-light-success border-success' : '' }}">
                <div class="d-flex align-items-center">
                  <span class="badge badge-circle {{ $s['selesai'] ? 'badge-success' : 'badge-secondary' }} me-3" style="width:32px;height:32px">
                    {!! $s['selesai'] ? '<i class="ki-outline ki-check fs-4 text-white"></i>' : ($i + 1) !!}
                  </span>
                  <div>
                    <div class="fw-bold text-gray-800">{{ $s['judul'] }}</div>
                    <div class="fs-8 text-muted">{{ $s['ket'] }}</div>
                  </div>
                </div>
                <a href="{{ $s['url'] }}" class="btn btn-sm {{ $s['selesai'] ? 'btn-light-success' : 'btn-success' }} fw-bold">
                  {{ $s['selesai'] ? 'Lihat' : 'Atur Sekarang' }}
                </a>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endif

    {{-- ===================== KPI STOK ===================== --}}
    <div class="row g-4 mb-5 farm-kpi">
      <div class="col-6 col-lg-3">
        <div class="card bg-light-success border-0 h-100"><div class="card-body p-6">
          <div class="fs-7 fw-semibold text-success mb-2">Stok Ayam</div>
          <div class="fs-2hx fw-bold text-gray-800">{{ $num($summary['stok_ayam_ekor']) }} <span class="fs-5 text-muted">ekor</span></div>
          <div class="fs-7 text-gray-600 mt-1">{{ $num($summary['stok_ayam_kg'], 2) }} kg</div>
        </div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="card bg-light-warning border-0 h-100"><div class="card-body p-6">
          <div class="fs-7 fw-semibold text-warning mb-2">Stok Telur</div>
          <div class="fs-2hx fw-bold text-gray-800">{{ $num($summary['stok_telur']) }} <span class="fs-5 text-muted">butir</span></div>
          <div class="fs-7 text-gray-600 mt-1">HPP {{ $rp($summary['hpp_telur']) }}/butir</div>
        </div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="card bg-light-primary border-0 h-100"><div class="card-body p-6">
          <div class="fs-7 fw-semibold text-primary mb-2">Nilai Persediaan</div>
          <div class="fs-2hx fw-bold text-gray-800">{{ $rp($summary['nilai_persediaan']) }}</div>
          <div class="fs-7 text-gray-600 mt-1">harga pokok stok tersisa</div>
        </div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="card bg-light-danger border-0 h-100"><div class="card-body p-6">
          <div class="fs-7 fw-semibold text-danger mb-2">Piutang Agen</div>
          <div class="fs-2hx fw-bold text-gray-800">{{ $rp($piutang) }}</div>
          <div class="fs-7 {{ $piutangTempo > 0 ? 'text-danger fw-bold' : 'text-gray-600' }} mt-1">
            {{ $piutangTempo > 0 ? 'Jatuh tempo: ' . $rp($piutangTempo) : 'tidak ada yang jatuh tempo' }}
          </div>
        </div></div>
      </div>
    </div>

    {{-- ===================== KPI TRANSAKSI PERIODE ===================== --}}
    <div class="row g-4 mb-5 farm-kpi">
      <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body p-6">
          <div class="fs-7 fw-semibold text-muted mb-2">Pembelian</div>
          <div class="fs-2hx fw-bold text-gray-800">{{ $rp($summary['pembelian']) }}</div>
        </div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body p-6">
          <div class="fs-7 fw-semibold text-muted mb-2">Penjualan · {{ $summary['jumlah_nota'] }} nota</div>
          <div class="fs-2hx fw-bold text-gray-800">{{ $rp($summary['penjualan']) }}</div>
        </div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body p-6">
          <div class="fs-7 fw-semibold text-muted mb-2">Laba Kotor · margin {{ $summary['margin_persen'] }}%</div>
          <div class="fs-2hx fw-bold text-success">{{ $rp($summary['laba_kotor']) }}</div>
          <div class="fs-8 text-muted mt-1">jual − modal FIFO</div>
        </div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body p-6">
          <div class="fs-7 fw-semibold text-muted mb-2">Laba Bersih</div>
          <div class="fs-2hx fw-bold {{ $summary['laba_bersih'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $rp($summary['laba_bersih']) }}</div>
          <div class="fs-8 text-muted mt-1">− biaya {{ $rp($summary['biaya']) }} − susut {{ $rp($summary['kerugian']) }}</div>
        </div></div>
      </div>
    </div>

    {{-- ===================== GRAFIK + STOK PER ITEM ===================== --}}
    <div class="row g-4 mb-5 farm-kpi">
      <div class="col-12 col-xl-8">
        <div class="card card-flush h-100">
          <div class="card-header pt-5">
            <h3 class="card-title fw-bold fs-5">Pembelian vs Penjualan</h3>
            <span class="text-muted fs-8">{{ $range === 'day' ? '14 hari terakhir' : $periodLabel }}</span>
          </div>
          <div class="card-body pt-3"><div id="chart-farm" style="height:320px"></div></div>
        </div>
      </div>
      <div class="col-12 col-xl-4">
        <div class="card card-flush h-100">
          <div class="card-header pt-5">
            <h3 class="card-title fw-bold fs-5">Stok per Item</h3>
          </div>
          <div class="card-body pt-3">
            @forelse ($stokPerItem as $s)
              <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div>
                  <div class="fw-bold text-gray-800 fs-7">{{ $s['nama'] }}
                    @if ($s['menipis']) <span class="badge badge-light-danger fs-9 ms-1">menipis</span> @endif
                  </div>
                  <div class="fs-8 text-muted">{{ $s['kategori'] }}</div>
                </div>
                <div class="text-end">
                  <div class="fw-bold text-gray-800 fs-7">{{ $num($s['ekor']) }} <span class="fs-9 text-muted">ekor/butir</span></div>
                  <div class="fs-8 text-muted">{{ $num($s['kg'], 2) }} kg · {{ $rp($s['nilai']) }}</div>
                </div>
              </div>
            @empty
              <div class="text-center text-muted py-10">
                Belum ada item. <a href="{{ route('farm.items.index') }}" class="fw-bold">Tambah item</a> dulu.
              </div>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    {{-- ===================== NOTA TERAKHIR ===================== --}}
    <div class="card card-flush mb-5">
      <div class="card-header pt-5">
        <h3 class="card-title fw-bold fs-5">Penjualan Terakhir</h3>
        <a href="{{ route('farm.stock-out.index') }}" class="btn btn-sm btn-light-primary">Lihat Semua</a>
      </div>
      <div class="card-body pt-3">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0 farm-list-table">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Nota</th><th>Tanggal</th><th>Agen / Pembeli</th>
              <th class="text-end">Jual</th><th class="text-end">Laba</th><th class="text-end pe-4">Status</th>
            </tr></thead>
            <tbody>
            @forelse ($notaTerakhir as $n)
              <tr>
                <td class="ps-4"><a href="{{ route('farm.stock-out.show', $n->id) }}" class="fw-bold">{{ $n->invoice_no }}</a></td>
                <td class="text-muted fs-8">{{ $n->date->format('d/m/Y') }}</td>
                <td>{{ $n->pembeli() }}</td>
                <td class="text-end fw-bold">{{ $rp($n->total_sale) }}</td>
                <td class="text-end fw-bold text-success">{{ $rp($n->gross_profit) }}</td>
                <td class="text-end pe-4">
                  @if ($n->isPaid())
                    <span class="badge badge-light-success">Lunas</span>
                  @elseif ($n->isOverdue())
                    <span class="badge badge-light-danger">Jatuh Tempo</span>
                  @else
                    <span class="badge badge-light-warning">Belum Lunas</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted py-8">Belum ada penjualan.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
  (function () {
    var el = document.getElementById('chart-farm');
    if (!el || typeof ApexCharts === 'undefined') return;
    var rp = function (v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID'); };

    new ApexCharts(el, {
      chart: { type: 'bar', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
      series: [
        { name: 'Pembelian', type: 'column', data: @json($chart['beli']) },
        { name: 'Penjualan', type: 'column', data: @json($chart['jual']) },
        { name: 'Laba Kotor', type: 'line',  data: @json($chart['laba']) },
      ],
      colors: ['#f59e0b', '#16a34a', '#4f46e5'],
      stroke: { width: [0, 0, 3], curve: 'smooth' },
      plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
      dataLabels: { enabled: false },
      xaxis: { categories: @json($chart['categories']), labels: { style: { fontSize: '11px' } } },
      yaxis: { labels: { formatter: function (v) { return 'Rp ' + (Number(v) / 1000).toFixed(0) + 'rb'; } } },
      legend: { position: 'top', horizontalAlign: 'right' },
      tooltip: { y: { formatter: rp } },
      grid: { borderColor: '#eef2f7', strokeDashArray: 4 },
    }).render();
  })();
</script>
@endpush
