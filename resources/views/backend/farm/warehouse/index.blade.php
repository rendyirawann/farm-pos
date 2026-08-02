@extends('backend.layout.app')
@section('title', 'Buka / Tutup Gudang')
@section('content')
@php $num = fn($n,$d=0) => number_format((float)$n, $d, ',', '.'); @endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    <div class="alert alert-primary d-flex align-items-start py-3 fs-8">
      <i class="ki-outline ki-information-5 fs-2 me-2"></i>
      <div>Ini pengganti "shift" kasir. <b>Tidak ada modal & uang kembalian</b> — yang dipertanggungjawabkan
        adalah <b>stok fisik</b>. Selisih hitung fisik dicatat apa adanya, tidak menimpa stok;
        koreksinya dilakukan sadar lewat <a href="{{ route('farm.adjustments.index') }}" class="fw-bold">Penyesuaian Stok</a>.</div>
    </div>

    @if ($active)
      <div class="card card-flush mb-4 border-start border-4 border-success">
        <div class="card-header pt-5">
          <div>
            <h3 class="card-title fw-bold fs-4 mb-0">Gudang Sedang Buka</h3>
            <span class="text-muted fs-8">Dibuka {{ $active->opened_at->locale('id')->translatedFormat('d F Y H:i') }}
              oleh {{ $active->opener?->name ?? '-' }}</span>
          </div>
        </div>
        <div class="card-body pt-4">
          <form method="POST" action="{{ route('farm.warehouse.close', $active->id) }}">
            @csrf
            <div class="fw-bold fs-6 text-gray-800 mb-3">Hitung Fisik Sebelum Tutup</div>
            <div class="table-responsive">
              <table class="table table-row-bordered align-middle gy-2 mb-0 farm-form-table">
                <thead><tr class="fw-bold text-muted bg-light fs-8">
                  <th class="ps-3">Item</th><th class="text-end">Stok Sistem</th>
                  <th class="text-center" style="width:150px">Fisik (ekor)</th>
                  <th class="text-center" style="width:150px">Fisik (kg)</th>
                </tr></thead>
                <tbody>
                @foreach ($items as $i)
                  @php $s = $i->stock(); @endphp
                  <tr>
                    <td data-label="Item" class="ps-3 fw-bold text-gray-800">{{ $i->name }}</td>
                    <td data-label="Stok Sistem" class="text-end">{{ $num($s['ekor']) }} ekor
                      <div class="fs-8 text-muted">{{ $num($s['kg'], 2) }} kg</div></td>
                    <td data-label="Fisik (ekor)"><input type="number" name="physical[{{ $i->id }}][ekor]" class="form-control form-control-sm form-control-solid text-center"
                               min="0" value="{{ $s['ekor'] }}"></td>
                    <td data-label="Fisik (kg)"><input type="number" name="physical[{{ $i->id }}][kg]" class="form-control form-control-sm form-control-solid text-center"
                               min="0" step="0.01" value="{{ $s['kg'] }}"></td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
            <div class="mt-3"><label class="form-label fw-semibold fs-7">Catatan</label>
              <input name="notes" class="form-control form-control-solid" maxlength="255" placeholder="mis. 3 ekor mati sore"></div>
            <div class="text-end mt-4">
              <button class="btn btn-danger fw-bold btn-lg"><i class="ki-outline ki-lock fs-3"></i> Tutup Gudang</button>
            </div>
          </form>
        </div>
      </div>
    @else
      <div class="card card-flush mb-4">
        <div class="card-body text-center py-10">
          <i class="ki-outline ki-lock-2 fs-4x text-warning mb-4 d-block"></i>
          <h3 class="fw-bold text-gray-800">Gudang Tertutup</h3>
          <div class="text-muted fs-7 mb-5">Buka gudang untuk memulai pencatatan hari ini. Stok awal terekam otomatis.</div>
          <form method="POST" action="{{ route('farm.warehouse.open') }}" class="d-inline-flex gap-2 align-items-center farm-inline-form">
            @csrf
            <input name="notes" class="form-control form-control-solid" placeholder="Catatan (opsional)" style="max-width:280px" maxlength="255">
            <button class="btn btn-success fw-bold btn-lg"><i class="ki-outline ki-entrance-left fs-3"></i> Buka Gudang</button>
          </form>
        </div>
      </div>
    @endif

    <div class="card card-flush">
      <div class="card-header pt-5"><h3 class="card-title fw-bold fs-5 mb-0">Riwayat Sesi Gudang</h3></div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0 farm-list-table">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Buka</th><th>Tutup</th><th>Petugas</th><th>Selisih Fisik</th><th class="pe-4">Catatan</th>
            </tr></thead>
            <tbody>
            @forelse ($history as $h)
              <tr>
                <td class="ps-4 fw-bold fs-8">{{ $h->opened_at->format('d/m/Y H:i') }}</td>
                <td class="fs-8">{{ $h->closed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td class="fs-8">{{ $h->opener?->name ?? '-' }}
                  @if ($h->closer && $h->closer->id !== $h->opener?->id)<div class="text-muted">tutup: {{ $h->closer->name }}</div>@endif
                </td>
                <td class="fs-8">
                  @php $adaSelisih = false; @endphp
                  @foreach (($h->difference ?? []) as $d)
                    @if (($d['ekor'] ?? 0) != 0 || ($d['kg'] ?? 0) != 0)
                      @php $adaSelisih = true; @endphp
                      <span class="badge badge-light-danger fs-9 me-1 mb-1">
                        {{ $d['nama'] ?? '?' }}: {{ $d['ekor'] > 0 ? '+' : '' }}{{ $d['ekor'] }} ekor /
                        {{ $d['kg'] > 0 ? '+' : '' }}{{ $num($d['kg'], 2) }} kg</span>
                    @endif
                  @endforeach
                  @if (! $adaSelisih && $h->closed_at)<span class="badge badge-light-success fs-9">Cocok</span>@endif
                  @if (! $h->closed_at)<span class="badge badge-light-primary fs-9">Sedang berjalan</span>@endif
                </td>
                <td class="pe-4 fs-8 text-muted">{{ $h->notes ?: '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted py-10">Belum ada riwayat.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
