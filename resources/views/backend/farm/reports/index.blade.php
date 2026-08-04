@extends('backend.layout.app')
@section('title', 'Laporan')
@section('content')
@include('backend.farm._style')
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    @php
      $qs = fn (array $ganti = []) => request()->fullUrlWithQuery($ganti);
    @endphp

    {{-- ===== Pemilih jenis laporan ===== --}}
    <div class="card card-flush mb-5">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Laporan</h3>
          <span class="text-muted fs-8">Pilih laporan, atur filternya, lalu unduh sebagai PDF.</span>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="row g-3">
          @foreach ($daftarJenis as $kode => $j)
            @php $aktif = $kode === $jenis; @endphp
            <div class="col-6 col-md-4 col-xl-3">
              <a href="{{ route('farm.reports.index', ['jenis' => $kode]) }}"
                 class="d-block border rounded p-4 h-100 text-decoration-none
                        {{ $aktif ? 'border-primary bg-light-primary' : 'border-gray-200 bg-white' }}">
                <div class="d-flex align-items-center mb-2">
                  <i class="ki-outline {{ $j['ikon'] }} fs-2 me-2 {{ $aktif ? 'text-primary' : 'text-gray-600' }}"></i>
                  <span class="fw-bold fs-7 {{ $aktif ? 'text-primary' : 'text-gray-800' }}">{{ $j['nama'] }}</span>
                </div>
                <div class="fs-9 text-muted lh-sm">{{ $j['untuk'] }}</div>
              </a>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- ===== Filter ===== --}}
    <div class="card card-flush mb-5">
      <div class="card-body py-5">
        <form method="GET" action="{{ route('farm.reports.index') }}" class="row g-3 align-items-end" id="f-filter">
          <input type="hidden" name="jenis" value="{{ $jenis }}">

          @if ($pakaiPeriode)
            <div class="col-12 col-md-3">
              <label class="form-label fw-semibold fs-8 text-muted">Periode</label>
              <select name="periode" id="in-periode" class="form-select form-select-sm form-select-solid">
                @foreach ($daftarPeriode as $k => $v)
                  <option value="{{ $k }}" {{ $periode === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
              </select>
            </div>
            {{-- Kolom tanggal hanya berguna saat periode "pilih sendiri";
                 disembunyikan agar tidak menimbulkan kesan bisa diisi bebas. --}}
            <div class="col-6 col-md-2 js-tanggal" style="{{ $periode === 'custom' ? '' : 'display:none' }}">
              <label class="form-label fw-semibold fs-8 text-muted">Dari</label>
              <input type="date" name="dari" value="{{ $dari }}" class="form-control form-control-sm form-control-solid">
            </div>
            <div class="col-6 col-md-2 js-tanggal" style="{{ $periode === 'custom' ? '' : 'display:none' }}">
              <label class="form-label fw-semibold fs-8 text-muted">Sampai</label>
              <input type="date" name="sampai" value="{{ $sampai }}" class="form-control form-control-sm form-control-solid">
            </div>
          @endif

          @if ($suppliers->count())
            <div class="col-12 col-md-3">
              <label class="form-label fw-semibold fs-8 text-muted">Supplier</label>
              <select name="supplier_id" class="form-select form-select-sm form-select-solid">
                <option value="">Semua supplier</option>
                @foreach ($suppliers as $s)
                  <option value="{{ $s->id }}" {{ (string) $supplierId === (string) $s->id ? 'selected' : '' }}>
                    {{ $s->name }}</option>
                @endforeach
              </select>
            </div>
          @endif

          @if ($agents->count())
            <div class="col-12 col-md-3">
              <label class="form-label fw-semibold fs-8 text-muted">Agen</label>
              <select name="agen_id" class="form-select form-select-sm form-select-solid">
                <option value="">Semua agen</option>
                @foreach ($agents as $ag)
                  <option value="{{ $ag->id }}" {{ (string) $agenId === (string) $ag->id ? 'selected' : '' }}>
                    {{ $ag->name }}</option>
                @endforeach
              </select>
            </div>
          @endif

          @if ($items->count())
            <div class="col-12 col-md-3">
              <label class="form-label fw-semibold fs-8 text-muted">Barang</label>
              <select name="item_id" class="form-select form-select-sm form-select-solid">
                <option value="">Semua barang</option>
                @foreach ($items as $it)
                  <option value="{{ $it->id }}" {{ (string) $itemId === (string) $it->id ? 'selected' : '' }}>
                    {{ $it->name }}</option>
                @endforeach
              </select>
            </div>
          @endif

          <div class="col-12 col-md-auto d-flex gap-2">
            <button class="btn btn-sm btn-primary fw-bold">Tampilkan</button>
            <a href="{{ route('farm.reports.pdf', request()->query()) }}"
               class="btn btn-sm btn-danger fw-bold" id="btn-pdf">
              <i class="ki-outline ki-file-down fs-4"></i> Unduh PDF</a>
          </div>
        </form>
      </div>
    </div>

    {{-- ===== Pratinjau laporan ===== --}}
    <div class="card card-flush">
      <div class="card-body p-0">
        <div class="p-6 pb-0">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
              <h2 class="fw-bold fs-2 mb-1 text-gray-900">{{ $data['judul'] }}</h2>
              <div class="fs-7 text-muted">
                @if ($pakaiPeriode) Periode: <b class="text-gray-800">{{ $labelPeriode }}</b> @endif
                @if (! empty($data['subjudul']))
                  @if ($pakaiPeriode) · @endif {{ $data['subjudul'] }}
                @endif
              </div>
            </div>
            <div class="text-end fs-9 text-muted">
              Dibuat {{ now()->locale('id')->translatedFormat('d F Y H:i') }}<br>
              oleh {{ auth()->user()->name ?? '-' }}
            </div>
          </div>

          {{-- KPI ringkas --}}
          <div class="row g-3 mt-3 farm-kpi">
            @foreach ($data['ringkas'] as $r)
              <div class="col-6 col-lg-3">
                <div class="border rounded p-4 h-100 bg-light">
                  <div class="text-muted fs-8">{{ $r['label'] }}</div>
                  <div class="fw-bold fs-3 text-gray-900">
                    @switch($r['jenis'])
                      @case('rp') Rp {{ number_format((float) $r['nilai'], 0, ',', '.') }} @break
                      @case('kg') {{ number_format((float) $r['nilai'], 2, ',', '.') }} <span class="fs-7">kg</span> @break
                      @case('ekor') {{ number_format((float) $r['nilai'], 0, ',', '.') }} <span class="fs-7">ekor</span> @break
                      @default {{ number_format((float) $r['nilai'], 0, ',', '.') }}
                    @endswitch
                  </div>
                  @if (! empty($r['ket']))<div class="fs-9 text-muted">{{ $r['ket'] }}</div>@endif
                </div>
              </div>
            @endforeach
          </div>
        </div>

        {{-- Tabel-tabel laporan --}}
        @foreach ($data['blok'] as $blok)
          <div class="px-6 pt-6">
            <h4 class="fw-bold fs-5 text-gray-800 mb-3">{{ $blok['judul'] }}</h4>
          </div>
          <div class="table-responsive px-6 pb-2">
            <table class="table table-row-bordered align-middle gy-2 mb-0 fs-8 farm-list-table">
              <thead><tr class="fw-bold text-muted bg-light">
                @foreach ($blok['kolom'] as $k)
                  <th class="text-{{ $k['align'] ?? 'left' }}"
                      @if (! empty($k['lebar'])) style="width:{{ $k['lebar'] }}" @endif>{{ $k['label'] }}</th>
                @endforeach
              </tr></thead>
              <tbody>
              @forelse ($blok['baris'] as $i => $baris)
                <tr class="{{ in_array($i, $blok['tebal'] ?? [], true) ? 'fw-bold bg-light-primary' : '' }}">
                  @foreach ($baris as $j => $sel)
                    <td class="text-{{ $blok['kolom'][$j]['align'] ?? 'left' }}"
                        data-label="{{ $blok['kolom'][$j]['label'] ?? '' }}">{{ $sel }}</td>
                  @endforeach
                </tr>
              @empty
                <tr><td colspan="{{ count($blok['kolom']) }}" class="text-center text-muted py-8">
                  Tidak ada data pada periode/filter ini.
                </td></tr>
              @endforelse
              </tbody>
              @if (! empty($blok['total']) && count($blok['baris']))
                <tfoot><tr class="fw-bold border-top border-2">
                  @foreach ($blok['total'] as $j => $sel)
                    <td class="text-{{ $blok['kolom'][$j]['align'] ?? 'left' }}">{{ $sel }}</td>
                  @endforeach
                </tr></tfoot>
              @endif
            </table>
          </div>
        @endforeach

        @if (! empty($data['catatan']))
          <div class="px-6 pb-6 pt-4">
            <div class="alert alert-light-primary border border-primary fs-8 py-3 mb-0">
              <b>Catatan:</b> {{ $data['catatan'] }}
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Kolom tanggal hanya muncul saat "Pilih tanggal sendiri", dan tautan Unduh PDF
  // selalu mengikuti filter yang sedang tampil di layar — bukan filter terakhir
  // yang tersimpan di alamat halaman.
  (function () {
    const form = document.getElementById('f-filter');
    const sel  = document.getElementById('in-periode');
    const pdf  = document.getElementById('btn-pdf');
    if (!form) return;

    function tanggalTampil() {
      const custom = sel && sel.value === 'custom';
      document.querySelectorAll('.js-tanggal').forEach(el => { el.style.display = custom ? '' : 'none'; });
    }

    function perbaruiTautanPdf() {
      if (!pdf) return;
      const p = new URLSearchParams(new FormData(form));
      pdf.href = @json(route('farm.reports.pdf')) + '?' + p.toString();
    }

    sel?.addEventListener('change', function () { tanggalTampil(); perbaruiTautanPdf(); });
    form.addEventListener('change', perbaruiTautanPdf);
    form.addEventListener('input', perbaruiTautanPdf);

    tanggalTampil();
    perbaruiTautanPdf();
  })();
</script>
@endpush
