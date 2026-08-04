@extends('backend.layout.app')
@section('title', 'Deposit Supplier')
@section('content')
@include('backend.farm._style')
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    @php $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.'); @endphp

    {{-- Ringkasan: satu angka besar untuk total uang kita yang masih dipegang supplier. --}}
    <div class="row g-4 mb-5 farm-kpi">
      <div class="col-12 col-md-6">
        <div class="card card-flush h-100">
          <div class="card-body py-6">
            <div class="text-muted fs-7 mb-1">Total saldo di seluruh supplier</div>
            <div class="fs-2hx fw-bold {{ $totalSaldo < 0 ? 'text-danger' : 'text-success' }}">{{ $rp($totalSaldo) }}</div>
            <div class="fs-8 text-muted mt-2">Uang yang sudah kita setor dan belum terpakai barang masuk.</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="card card-flush h-100">
          <div class="card-body py-6">
            <div class="text-muted fs-7 mb-1">Supplier bersaldo minus</div>
            <div class="fs-2hx fw-bold {{ $minus->count() ? 'text-danger' : 'text-gray-800' }}">{{ $minus->count() }}</div>
            <div class="fs-8 text-muted mt-2">
              Saldo minus artinya <b>kita belum bayar</b> — barang sudah masuk tapi setorannya belum menutupi.
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card card-flush">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Deposit Supplier</h3>
          <span class="text-muted fs-8">Setor uang lebih dulu ke supplier, lalu tiap nota barang masuk memotong saldonya.</span>
        </div>
        <div class="card-toolbar">
          <form method="GET" class="d-flex gap-2">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm w-150px w-md-200px"
                   placeholder="Cari supplier...">
            <button class="btn btn-sm btn-light-primary fw-bold">Cari</button>
          </form>
        </div>
      </div>
      <div class="card-body pt-4">
        @unless ($bolehIsi)
          <div class="alert alert-light-warning border border-warning fs-8 py-3 mb-4">
            Anda bisa melihat saldo, tetapi <b>menambah atau mengoreksi deposit hanya boleh oleh owner/admin</b>.
          </div>
        @endunless

        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0 farm-card-table">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Supplier</th>
              <th class="text-end">Total Setoran</th>
              <th class="text-end">Terpakai Barang Masuk</th>
              <th class="text-end">Koreksi Realisasi</th>
              <th class="text-end">Saldo</th>
              <th class="text-end pe-4">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $s)
              <tr>
                <td class="ps-4 fw-bold text-gray-800" data-label="Supplier">
                  {{ $s->name }}
                  @if ($s->phone)<div class="fs-8 text-muted fw-normal">{{ $s->phone }}</div>@endif
                  @unless ($s->is_active)<span class="badge badge-light-secondary fs-9 mt-1">Nonaktif</span>@endunless
                </td>
                <td class="text-end text-success" data-label="Total Setoran">{{ $rp($s->ringkas['topup']) }}</td>
                <td class="text-end text-danger" data-label="Terpakai Barang Masuk">{{ $rp($s->ringkas['purchase']) }}</td>
                <td class="text-end" data-label="Koreksi Realisasi">
                  <span class="{{ $s->ringkas['realization'] > 0 ? 'text-success' : ($s->ringkas['realization'] < 0 ? 'text-danger' : 'text-muted') }}">
                    {{ $s->ringkas['realization'] > 0 ? '+' : '' }}{{ $rp($s->ringkas['realization']) }}</span>
                </td>
                <td class="text-end fw-bold fs-6 {{ $s->saldo < -0.01 ? 'text-danger' : 'text-gray-900' }}" data-label="Saldo">
                  {{ $rp($s->saldo) }}
                  @if ($s->saldo < -0.01)
                    <div class="fs-9 text-danger fw-normal">Kita belum bayar</div>
                  @endif
                </td>
                <td class="text-end pe-4" data-label="Aksi">
                  {{-- Setor langsung dari daftar: menambah saldo adalah pekerjaan
                       paling sering di halaman ini, jadi tidak perlu masuk detail dulu. --}}
                  @if ($bolehIsi)
                    <button class="btn btn-sm btn-success py-1 px-3 fs-8 fw-bold js-topup"
                            data-bs-toggle="modal" data-bs-target="#m-topup"
                            data-aksi="{{ route('farm.deposits.topup', $s->id) }}"
                            data-nama="{{ $s->name }}" data-saldo="{{ (float) $s->saldo }}">
                      <i class="ki-outline ki-plus fs-5"></i> Setor</button>
                  @endif
                  <a href="{{ route('farm.deposits.show', $s->id) }}" class="btn btn-sm btn-light-primary py-1 px-3 fs-8">
                    Riwayat</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted py-10">
                Belum ada supplier. Tambahkan dulu di menu <a href="{{ route('farm.suppliers.index') }}">Supplier</a>.
              </td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@if ($bolehIsi)
  @include('backend.farm.deposits._topup_modal', ['supplier' => null])
@endif
@endsection
