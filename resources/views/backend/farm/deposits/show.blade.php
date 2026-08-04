@extends('backend.layout.app')
@section('title', 'Deposit ' . $supplier->name)
@section('content')
@include('backend.farm._style')
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    @php
      $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
      $minus = $saldo < -0.01;
    @endphp

    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
      <a href="{{ route('farm.deposits.index') }}" class="btn btn-sm btn-light">&larr; Semua Supplier</a>
      <h3 class="fw-bold fs-3 mb-0 ms-1">{{ $supplier->name }}</h3>
      @if ($supplier->phone)<span class="text-muted fs-8">{{ $supplier->phone }}</span>@endif
    </div>

    <div class="row g-4 mb-5">
      {{-- Saldo dicetak besar: inilah angka yang dilihat sebelum mencatat nota. --}}
      <div class="col-12 col-lg-5">
        <div class="card card-flush h-100 {{ $minus ? 'bg-light-danger' : 'bg-light-success' }}">
          <div class="card-body py-6">
            <div class="text-muted fs-7 mb-1">Saldo deposit saat ini</div>
            <div class="fs-2hx fw-bold {{ $minus ? 'text-danger' : 'text-success' }}">{{ $rp($saldo) }}</div>
            @if ($minus)
              <div class="fs-7 fw-bold text-danger mt-2">KITA BELUM BAYAR {{ $rp(abs($saldo)) }}</div>
              <div class="fs-8 text-muted">Barang sudah masuk tetapi setoran belum menutupinya.</div>
            @else
              <div class="fs-8 text-muted mt-2">Sisa uang kita yang masih dipegang supplier ini.</div>
            @endif
            <div class="fs-9 text-muted mt-3">Saldo per {{ now()->format('d/m/Y H:i') }}</div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-7">
        <div class="card card-flush h-100">
          <div class="card-body py-6">
            <div class="row g-4">
              <div class="col-6 col-md-3">
                <div class="text-muted fs-8">Total setoran</div>
                <div class="fw-bold fs-5 text-success">{{ $rp($ringkas['topup']) }}</div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted fs-8">Terpakai barang masuk</div>
                <div class="fw-bold fs-5 text-danger">{{ $rp($ringkas['purchase']) }}</div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted fs-8">Koreksi realisasi</div>
                <div class="fw-bold fs-5">{{ $ringkas['realization'] > 0 ? '+' : '' }}{{ $rp($ringkas['realization']) }}</div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-muted fs-8">Koreksi manual</div>
                <div class="fw-bold fs-5">{{ $ringkas['manual'] > 0 ? '+' : '' }}{{ $rp($ringkas['manual']) }}</div>
              </div>
            </div>

            @if ($bolehIsi)
              <div class="separator my-5"></div>
              <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#m-topup">
                  <i class="ki-outline ki-plus fs-3"></i> Tambah Deposit</button>
                <button class="btn btn-light-warning fw-bold" data-bs-toggle="modal" data-bs-target="#m-koreksi">
                  Koreksi Manual</button>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    {{-- Riwayat: buku besar apa adanya, dengan saldo berjalan supaya mudah dicocokkan. --}}
    <div class="card card-flush">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Riwayat Saldo</h3>
          <span class="text-muted fs-8">Tidak ada baris yang diubah atau dihapus — pembatalan dicatat sebagai jurnal balik.</span>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0 farm-list-table">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Tanggal</th><th>Keterangan</th><th>Bukti</th>
              <th class="text-end">Masuk</th><th class="text-end">Keluar</th>
              <th class="text-end">Saldo</th><th class="text-end pe-4">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse ($entries as $e)
              @php $nota = $e->stockIn(); @endphp
              <tr class="{{ $e->isReversal() ? 'bg-light-warning' : '' }}">
                <td class="ps-4" data-label="Tanggal">
                  {{ $e->date->format('d/m/Y') }}
                  <div class="fs-9 text-muted">#{{ $e->id }}</div>
                </td>
                <td data-label="Keterangan">
                  <span class="fw-bold text-gray-800">{{ $e->typeLabel() }}</span>
                  @if ($e->isReversal())<span class="badge badge-light-warning fs-9 ms-1">Pembalikan</span>@endif
                  @if ($nota)
                    <div class="fs-8">
                      <a href="{{ route('farm.stock-in.show', $nota->id) }}">{{ $nota->invoice_no }}</a>
                    </div>
                  @endif
                  @if ($e->notes)<div class="fs-8 text-muted">{{ $e->notes }}</div>@endif
                  @if ($e->user)<div class="fs-9 text-muted">oleh {{ $e->user->name }}</div>@endif
                </td>
                <td data-label="Bukti">
                  @if ($e->hasProof())
                    <a href="{{ Storage::url($e->proof_path) }}" target="_blank" class="btn btn-sm btn-light py-1 px-3 fs-8">
                      {{ \App\Models\Farm\SupplierDeposit::isImageProof($e->proof_path) ? 'Lihat Foto' : 'Lihat Berkas' }}</a>
                  @else
                    <span class="text-muted fs-8">-</span>
                  @endif
                </td>
                <td class="text-end text-success fw-bold" data-label="Masuk">
                  {{ (float) $e->amount > 0 ? $rp($e->amount) : '' }}</td>
                <td class="text-end text-danger fw-bold" data-label="Keluar">
                  {{ (float) $e->amount < 0 ? $rp(abs((float) $e->amount)) : '' }}</td>
                <td class="text-end fw-bold {{ ($saldoSetelah[$e->id] ?? 0) < 0 ? 'text-danger' : '' }}" data-label="Saldo">
                  {{ $rp($saldoSetelah[$e->id] ?? 0) }}</td>
                <td class="text-end pe-4" data-label="Aksi">
                  @if ($bolehIsi && in_array($e->type, ['topup', 'manual'], true) && ! $e->isReversal())
                    <form method="POST" action="{{ route('farm.deposits.reverse', $e->id) }}" class="d-inline"
                          onsubmit="return confirm('Batalkan baris ini? Saldo akan dikembalikan lewat jurnal balik.')">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-light-danger py-1 px-3 fs-8">Batalkan</button>
                    </form>
                  @else
                    <span class="text-muted fs-9">—</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted py-10">
                Belum ada transaksi deposit untuk supplier ini.
              </td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
        @if ($entries->hasPages())
          <div class="mt-4">{{ $entries->links() }}</div>
        @endif
      </div>
    </div>

    @if ($notaTerakhir->count())
      <div class="card card-flush mt-5">
        <div class="card-header pt-5">
          <h3 class="card-title fw-bold fs-5 mb-0">Nota Barang Masuk Terakhir</h3>
        </div>
        <div class="card-body pt-4">
          <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-2 mb-0 fs-8 farm-list-table">
              <thead><tr class="fw-bold text-muted bg-light">
                <th class="ps-4">Tanggal</th><th>No. Nota</th><th class="text-end">Nilai Nota</th><th class="text-end pe-4"></th>
              </tr></thead>
              <tbody>
              @foreach ($notaTerakhir as $n)
                <tr>
                  <td class="ps-4" data-label="Tanggal">{{ $n->date->format('d/m/Y') }}</td>
                  <td data-label="No. Nota">{{ $n->invoice_no }}</td>
                  <td class="text-end" data-label="Nilai Nota">{{ $rp($n->total) }}</td>
                  <td class="text-end pe-4">
                    <a href="{{ route('farm.stock-in.show', $n->id) }}" class="btn btn-sm btn-light py-1 px-3 fs-8">Buka</a>
                  </td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>

@if ($bolehIsi)
{{-- ---------- Modal: tambah deposit ---------- --}}
<div class="modal fade" id="m-topup" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="{{ route('farm.deposits.topup', $supplier->id) }}"
          enctype="multipart/form-data">
      @csrf
      <div class="modal-header py-4">
        <h4 class="modal-title fs-5">Tambah Deposit — {{ $supplier->name }}</h4>
        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
          <i class="ki-outline ki-cross fs-2"></i></button>
      </div>
      <div class="modal-body">
        <div class="mb-4">
          <label class="form-label fw-bold fs-7 required">Jumlah setoran (Rp)</label>
          <input type="text" name="amount" id="dep-amount" class="form-control form-control-lg js-money" required
                 inputmode="numeric" autocomplete="off" placeholder="0">
          <div class="fs-8 text-muted mt-1" id="dep-huruf"></div>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold fs-7 required">Tanggal transfer</label>
          <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold fs-7">Bukti transfer</label>
          {{-- capture="environment" membuat HP langsung membuka kamera; di laptop
               tetap menjadi pemilih berkas biasa (termasuk PDF dari m-banking). --}}
          <input type="file" name="proof" class="form-control" accept="image/*,application/pdf" capture="environment">
          <div class="fs-8 text-muted mt-1">Foto struk transfer atau berkas PDF dari m-banking. Maksimal 8 MB.</div>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold fs-7">Catatan</label>
          <input type="text" name="notes" class="form-control" maxlength="255" placeholder="mis. transfer BCA an. Budi">
        </div>
        <label class="form-check form-check-sm">
          <input class="form-check-input" type="checkbox" name="confirm_duplicate" value="1">
          <span class="form-check-label fs-8">Ya, ini setoran berbeda (lewati pemeriksaan setoran kembar)</span>
        </label>
      </div>
      <div class="modal-footer py-4">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-success fw-bold">Simpan Deposit</button>
      </div>
    </form>
  </div>
</div>

{{-- ---------- Modal: koreksi manual ---------- --}}
<div class="modal fade" id="m-koreksi" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="{{ route('farm.deposits.adjust', $supplier->id) }}">
      @csrf
      <div class="modal-header py-4">
        <h4 class="modal-title fs-5">Koreksi Manual Saldo</h4>
        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
          <i class="ki-outline ki-cross fs-2"></i></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-light-warning border border-warning fs-8 py-3">
          Dipakai hanya untuk meluruskan saldo yang tidak berasal dari setoran atau nota
          (mis. uang dikembalikan supplier). Alasannya wajib ditulis.
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold fs-7 required">Arah koreksi</label>
          <select name="arah" class="form-select">
            <option value="tambah">Tambah saldo (+)</option>
            <option value="kurang">Kurangi saldo (−)</option>
          </select>
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold fs-7 required">Jumlah (Rp)</label>
          <input type="text" name="amount" class="form-control js-money" required inputmode="numeric" placeholder="0">
        </div>
        <div class="mb-4">
          <label class="form-label fw-bold fs-7 required">Tanggal</label>
          <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
        </div>
        <div class="mb-2">
          <label class="form-label fw-bold fs-7 required">Alasan koreksi</label>
          <input type="text" name="notes" class="form-control" maxlength="255" required
                 placeholder="mis. supplier mengembalikan uang tunai Rp 500.000">
        </div>
      </div>
      <div class="modal-footer py-4">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-warning fw-bold">Simpan Koreksi</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  // Jumlah ditulis ulang dalam huruf: satu digit nol yang kelebihan adalah selisih
  // sepuluh kali lipat, dan itu paling cepat tertangkap dengan membacanya.
  (function () {
    const el = document.getElementById('dep-amount');
    const out = document.getElementById('dep-huruf');
    if (!el || !out) return;

    const SATUAN = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
                    'sepuluh', 'sebelas'];

    function huruf(n) {
      n = Math.floor(n);
      if (n < 12) return SATUAN[n];
      if (n < 20) return SATUAN[n - 10] + ' belas';
      if (n < 100) return SATUAN[Math.floor(n / 10)] + ' puluh ' + huruf(n % 10);
      if (n < 200) return 'seratus ' + huruf(n - 100);
      if (n < 1000) return SATUAN[Math.floor(n / 100)] + ' ratus ' + huruf(n % 100);
      if (n < 2000) return 'seribu ' + huruf(n - 1000);
      if (n < 1e6) return huruf(Math.floor(n / 1000)) + ' ribu ' + huruf(n % 1000);
      if (n < 1e9) return huruf(Math.floor(n / 1e6)) + ' juta ' + huruf(n % 1e6);
      return huruf(Math.floor(n / 1e9)) + ' miliar ' + huruf(n % 1e9);
    }

    el.addEventListener('input', function () {
      const n = parseInt(String(el.value).replace(/[^\d]/g, ''), 10) || 0;
      out.textContent = n > 0 ? huruf(n).replace(/\s+/g, ' ').trim() + ' rupiah' : '';
    });
  })();
</script>
@endpush
@endif
@endsection
