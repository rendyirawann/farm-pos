@extends('backend.layout.app')
@section('title', 'Riwayat Barang Masuk')
@section('content')
@php $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.'); @endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    {{-- Peringatan nota yang masih menggantung — dihitung dari SELURUH riwayat,
         bukan hanya rentang tanggal terpilih, karena nota lama yang belum dibayar
         justru yang paling perlu terlihat. --}}
    @if ($jumlahBelum > 0)
      <div class="alert alert-warning d-flex flex-wrap align-items-center py-4 gap-2">
        <i class="ki-outline ki-information-5 fs-2x me-2 text-warning"></i>
        <div class="flex-grow-1">
          <div class="fw-bold fs-6 text-gray-800">
            Ada {{ $jumlahBelum }} nota pembelian yang belum dilunasi ke supplier
          </div>
          <div class="fs-8 text-muted">Total yang masih harus dibayar:
            <b class="text-danger">{{ $rp($sisaBelum) }}</b></div>
        </div>
        {{-- Tanpa tanggal: kalau rentang yang sedang dipakai ikut dibawa, nota lama
             yang belum lunas tetap tidak muncul dan tombol ini terasa "tidak bisa diklik". --}}
        <a href="{{ route('farm.stock-in.index', ['status' => 'unpaid']) }}"
           class="btn btn-sm btn-warning fw-bold">Lihat yang Belum Lunas</a>
      </div>
    @endif

    <div class="card card-flush">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Barang Masuk</h3>
          <span class="text-muted fs-8">
            {{ $disaring ? 'Hasil filter' : 'Seluruh riwayat' }}:
            <b class="text-gray-800">{{ number_format($jumlah, 0, ',', '.') }} nota</b> ·
            <b class="text-gray-800">{{ $rp($total) }}</b>
          </span>
        </div>
        <div class="card-toolbar gap-2 flex-wrap">
          <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm form-control-solid"
                   style="width:150px" title="Dari tanggal (boleh dikosongkan)">
            <span class="text-muted">s/d</span>
            <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm form-control-solid"
                   style="width:150px" title="Sampai tanggal (boleh dikosongkan)">
            <select name="status" class="form-select form-select-sm form-select-solid" style="width:160px">
              <option value="">Semua status</option>
              <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
              <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Lunas</option>
            </select>
            <button class="btn btn-sm btn-light-primary fw-bold">Filter</button>
            @if ($disaring)
              {{-- Jalan keluar dari filter: tanpa ini orang harus mengosongkan tiga
                   kolom satu-satu untuk kembali melihat semua data. --}}
              <a href="{{ route('farm.stock-in.index') }}" class="btn btn-sm btn-light fw-bold">Tampilkan Semua</a>
            @endif
          </form>
          <a href="{{ route('farm.stock-in.create') }}" class="btn btn-success fw-bold">
            <i class="ki-outline ki-plus fs-3"></i> Barang Masuk</a>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0 farm-list-table">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Nota</th><th>Tanggal</th><th>Supplier</th><th>Barang</th>
              <th class="text-end">Total</th><th class="pe-4">Status Bayar</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $r)
              <tr>
                <td class="ps-4"><a href="{{ route('farm.stock-in.show', $r->id) }}" class="fw-bold">{{ $r->invoice_no }}</a></td>
                <td class="text-muted fs-8">{{ $r->date->format('d/m/Y') }}</td>
                <td>{{ $r->supplier?->name ?? '—' }}</td>
                <td class="fs-8">
                  @foreach ($r->lines as $l)
                    <span class="badge badge-light-success fs-9 me-1 mb-1">
                      {{ $l->item?->name }} · {{ (int) $l->qty_ekor }} ekor / {{ number_format((float) $l->weight_kg, 2, ',', '.') }} kg
                    </span>
                  @endforeach
                </td>
                <td class="text-end fw-bold">{{ $rp($r->total) }}
                  @php $nilaiRl = (float) ($r->realization->value ?? 0); @endphp
                  @if (abs($nilaiRl) > 0.01)
                    <div class="fs-9 {{ $nilaiRl > 0 ? 'text-success' : 'text-danger' }}">
                      realisasi {{ $nilaiRl > 0 ? '−' : '+' }}{{ $rp(abs($nilaiRl)) }}</div>
                    <div class="fs-9 text-muted">bersih {{ $rp($r->netTotal()) }}</div>
                  @endif
                </td>
                <td class="pe-4">
                  @if ($r->isPaid())
                    <span class="badge badge-light-success fw-bold">Lunas</span>
                    @if ($r->paid_at)<div class="fs-9 text-muted">{{ $r->paid_at->format('d/m/Y') }}</div>@endif
                  @else
                    <span class="badge badge-light-danger fw-bold">Belum Lunas</span>
                    <div class="fs-9 text-danger fw-bold">sisa {{ $rp($r->remainingToPay()) }}</div>
                  @endif
                  @if ($r->realization)
                    <div class="fs-9 text-muted">sudah direalisasi</div>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted py-10">
                @if ($disaring)
                  Tidak ada nota yang cocok dengan filter ini.
                  <a href="{{ route('farm.stock-in.index') }}" class="fw-bold">Tampilkan semua</a>.
                @else
                  Belum ada pembelian yang tercatat.
                  <a href="{{ route('farm.stock-in.create') }}" class="fw-bold">Catat barang masuk</a>.
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
