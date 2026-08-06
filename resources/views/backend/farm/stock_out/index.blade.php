@extends('backend.layout.app')
@section('title', 'Riwayat Barang Keluar')
@section('content')
@php $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.'); @endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <div class="row g-4 mb-4">
      <div class="col-4"><div class="card bg-light-primary border-0"><div class="card-body p-5">
        <div class="fs-8 text-muted fw-bold text-uppercase">Penjualan {{ $jenis === 'ecer' ? 'Ecer' : 'Agen' }}</div>
        <div class="fs-3 fw-bold text-gray-800">{{ $rp($rekap->jual) }}</div></div></div></div>
      <div class="col-4"><div class="card bg-light-warning border-0"><div class="card-body p-5">
        <div class="fs-8 text-muted fw-bold text-uppercase">Modal (FIFO)</div>
        <div class="fs-3 fw-bold text-gray-800">{{ $rp($rekap->modal) }}</div></div></div></div>
      <div class="col-4"><div class="card bg-light-success border-0"><div class="card-body p-5">
        <div class="fs-8 text-muted fw-bold text-uppercase">Laba Kotor</div>
        <div class="fs-3 fw-bold text-success">{{ $rp($rekap->laba) }}</div></div></div></div>
    </div>

    {{-- Dua jenis penjualan dipisah: nota agen dibaca bersama tempo & piutangnya,
         ecer selalu tunai. Filter tanggal/status ikut terbawa saat berpindah tab. --}}
    @php
      $tabAgen = request()->fullUrlWithQuery(['jenis' => 'agen', 'page' => null]);
      $tabEcer = request()->fullUrlWithQuery(['jenis' => 'ecer', 'page' => null]);
    @endphp
    <div class="card card-flush">
      {{-- Tab menempel pada tepi atas kartu seperti tab map arsip: tab yang aktif
           menyatu dengan isi kartunya, jadi terlihat jelas daftar mana yang
           sedang dibuka. Sebelumnya tab melayang di atas kartu dan nyaris tak
           terlihat karena tidak punya bidang sendiri. --}}
      <div class="card-header p-0 border-0 min-h-auto" style="background:#f6f7f9">
        <ul class="nav nav-tabs border-0 flex-nowrap overflow-auto w-100 farm-tab-arsip">
          @foreach ([
              ['agen', 'Ke Agen', 'ki-profile-user', $tabAgen, $jumlahAgen],
              ['ecer', 'Ecer / Umum', 'ki-handcart', $tabEcer, $jumlahEcer],
          ] as [$kode, $label, $ikon, $tautan, $jml])
            @php $aktif = $jenis === $kode; @endphp
            <li class="nav-item">
              <a href="{{ $tautan }}"
                 class="nav-link border-0 rounded-0 px-5 py-4 text-nowrap fw-bold fs-6
                        {{ $aktif ? 'active bg-body text-primary' : 'text-muted' }}">
                <i class="ki-outline {{ $ikon }} fs-4 me-2 {{ $aktif ? 'text-primary' : '' }}"></i>{{ $label }}
                <span class="badge badge-{{ $aktif ? 'primary' : 'light' }} ms-2">
                  {{ number_format($jml, 0, ',', '.') }}</span>
              </a>
            </li>
          @endforeach
        </ul>
      </div>

      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">
            Barang Keluar — {{ $jenis === 'ecer' ? 'Ecer / Umum' : 'Ke Agen' }}
          </h3>
          <span class="text-muted fs-8">
            {{ $disaring ? 'Hasil filter' : 'Seluruh riwayat' }}:
            <b class="text-gray-800">{{ number_format($jumlah, 0, ',', '.') }} nota</b>
            · {{ $jenis === 'ecer'
                  ? 'pembeli langsung, tanpa nama agen'
                  : 'tercatat atas nama agen & bisa dihutang' }}
          </span>
        </div>
        <div class="card-toolbar gap-2 flex-wrap">
          <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <input type="hidden" name="jenis" value="{{ $jenis }}">
            <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm form-control-solid" style="width:150px">
            <span class="text-muted">s/d</span>
            <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm form-control-solid" style="width:150px">
            <select name="status" class="form-select form-select-sm form-select-solid" style="width:150px">
              <option value="">Semua status</option>
              <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
              <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Lunas</option>
            </select>
            <button class="btn btn-sm btn-light-primary fw-bold">Filter</button>
            @if ($disaring)
              <a href="{{ route('farm.stock-out.index', ['jenis' => $jenis]) }}"
                 class="btn btn-sm btn-light fw-bold">Tampilkan Semua</a>
            @endif
          </form>
          <a href="{{ route('farm.stock-out.create') }}" class="btn btn-warning fw-bold">
            <i class="ki-outline ki-plus fs-3"></i> Barang Keluar</a>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0 farm-list-table">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Nota</th><th>Tanggal</th><th>Agen</th><th>Barang</th>
              <th class="text-end">Jual</th><th class="text-end">Laba</th><th class="text-end">Status</th>
              <th class="text-end pe-4">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $r)
              <tr>
                <td class="ps-4"><a href="{{ route('farm.stock-out.show', $r->id) }}" class="fw-bold">{{ $r->invoice_no }}</a></td>
                <td class="text-muted fs-8">{{ $r->date->format('d/m/Y') }}</td>
                <td>
                  @if ($r->agent)
                    {{ $r->agent->name }}
                  @elseif ($r->customer_name)
                    {{ $r->customer_name }}
                    <div class="fs-9 text-muted">ecer / umum</div>
                  @else
                    <span class="badge badge-light-info fs-9">Ecer / Umum</span>
                  @endif
                </td>
                <td class="fs-8">
                  @foreach ($r->lines as $l)
                    <span class="badge badge-light-warning fs-9 me-1 mb-1">
                      {{ $l->item?->name }} · {{ (int) $l->qty_ekor }}/{{ number_format((float) $l->weight_kg, 2, ',', '.') }}kg
                    </span>
                  @endforeach
                </td>
                <td class="text-end fw-bold">{{ $rp($r->total_sale) }}</td>
                <td class="text-end fw-bold text-success">{{ $rp($r->gross_profit) }}
                  <div class="fs-9 text-muted">{{ $r->marginPercent() }}%</div></td>
                <td class="text-end">
                  @if ($r->isPaid())
                    <span class="badge badge-light-success">Lunas</span>
                  @elseif ($r->isOverdue())
                    <span class="badge badge-light-danger">Jatuh Tempo</span>
                    <div class="fs-9 text-muted">{{ $r->due_date->format('d/m/Y') }}</div>
                  @else
                    <span class="badge badge-light-warning">Belum Lunas</span>
                    @if ($r->due_date)<div class="fs-9 text-muted">{{ $r->due_date->format('d/m/Y') }}</div>@endif
                  @endif
                </td>
                <td class="text-end pe-4">
                  {{-- Cetak nota juga berlaku untuk nota BELUM LUNAS: struknya memang
                       mencetak keterangan "BELUM LUNAS" supaya agen tahu masih berutang. --}}
                  @if ($r->isPaid())
                    <button type="button" class="btn btn-sm btn-light-primary py-1 px-3 fs-8 js-cetak"
                            data-url="{{ route('farm.stock-out.receipt', $r->id) }}" title="Cetak nota">
                      <i class="ki-outline ki-printer fs-5"></i></button>
                  @else
                    <button type="button" class="btn btn-sm btn-light py-1 px-3 fs-8" disabled
                            title="Belum lunas — nota bisa dicetak setelah pembayaran dicatat">
                      <i class="ki-outline ki-printer fs-5"></i></button>
                  @endif
                  <a href="{{ route('farm.stock-out.show', $r->id) }}"
                     class="btn btn-sm btn-light py-1 px-3 fs-8">Detail</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted py-10">
                @if ($disaring)
                  Tidak ada nota {{ $jenis === 'ecer' ? 'ecer' : 'agen' }} yang cocok dengan filter ini.
                  <a href="{{ route('farm.stock-out.index', ['jenis' => $jenis]) }}" class="fw-bold">Tampilkan semua</a>.
                @else
                  Belum ada penjualan {{ $jenis === 'ecer' ? 'ecer / umum' : 'ke agen' }} yang tercatat.
                  <a href="{{ route('farm.stock-out.create') }}" class="fw-bold">Catat barang keluar</a>.
                @endif
              </td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-4">{{ $rows->links() }}</div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@include('backend.farm._print_script')
<script>
  // Cetak langsung dari daftar: tidak perlu membuka detail dulu. Berlaku untuk
  // nota belum lunas maupun lunas — status bayarnya ikut tercetak di struk.
  document.addEventListener('click', function (e) {
    var b = e.target.closest('.js-cetak');
    if (!b) return;

    var semula = b.innerHTML;
    b.disabled = true;
    b.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch(b.dataset.url, { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!window.MoodaPrint || !window.farmNota) {
          throw new Error('Mesin cetak belum siap');
        }
        window.MoodaPrint.print(window.farmNota(d));
      })
      .catch(function (err) {
        alert('Gagal menyiapkan nota: ' + (err.message || 'coba lagi'));
      })
      .finally(function () { b.disabled = false; b.innerHTML = semula; });
  });
</script>
@endpush
