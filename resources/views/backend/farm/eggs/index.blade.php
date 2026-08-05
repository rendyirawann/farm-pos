@extends('backend.layout.app')
@section('title', 'Produksi Telur')
@section('content')
@php
  $rp = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
  $num = fn($n) => number_format((float)$n, 0, ',', '.');
@endphp
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.farm._flash')

    {{-- Rincian HPP telur: ditampilkan supaya angkanya bisa ditelusuri, bukan angka ajaib --}}
    <div class="card bg-light-warning border-0 mb-4">
      <div class="card-body p-5">
        <div class="fw-bold fs-5 text-gray-800 mb-1">Harga Pokok Telur — dihitung otomatis</div>
        <div class="fs-8 text-muted mb-3">
          Telur tidak dibeli dari supplier, jadi harga pokoknya diambil dari biaya operasional periode
          ({{ $rincian['periode'] }}) dibagi butir bersih. Catat pakan/obat/tenaga di menu
          <a href="{{ route('expenses.index') }}" class="fw-bold">Pengeluaran</a> agar angkanya akurat.
        </div>
        {{-- Harga pokok dibekukan saat produksi dicatat; kalau biaya bulan itu baru
             dimasukkan belakangan, lot yang belum terjual perlu dihitung ulang. --}}
        <form method="POST" action="{{ route('farm.eggs.recalc') }}" class="mb-3"
              onsubmit="return confirm('Hitung ulang harga pokok telur pada lot yang BELUM terjual?')">
          @csrf
          <button class="btn btn-sm btn-light-warning fw-bold">
            <i class="ki-outline ki-arrows-circle fs-5"></i> Hitung Ulang HPP Telur</button>
          <span class="fs-9 text-muted ms-2">Hanya lot yang belum terjual; nota lama tidak berubah.</span>
        </form>
        <div class="row g-3">
          <div class="col-6 col-md-3"><div class="bg-body rounded p-3">
            <div class="fs-9 text-muted text-uppercase fw-bold">Biaya Operasional</div>
            <div class="fs-4 fw-bold">{{ $rp($rincian['biaya']) }}</div></div></div>
          <div class="col-6 col-md-3"><div class="bg-body rounded p-3">
            <div class="fs-9 text-muted text-uppercase fw-bold">Butir Bersih</div>
            <div class="fs-4 fw-bold">{{ $num($rincian['butir']) }}</div>
            <div class="fs-9 text-muted">{{ $num($rincian['butir_kotor']) }} − {{ $num($rincian['butir_pecah']) }} pecah</div></div></div>
          <div class="col-6 col-md-3"><div class="bg-body rounded p-3">
            <div class="fs-9 text-muted text-uppercase fw-bold">HPP per Butir</div>
            <div class="fs-4 fw-bold text-warning">{{ $rp($rincian['cost_per_butir']) }}</div></div></div>
          <div class="col-6 col-md-3"><div class="bg-body rounded p-3">
            <div class="fs-9 text-muted text-uppercase fw-bold">Periode</div>
            <div class="fs-4 fw-bold">{{ $rincian['periode'] }}</div></div></div>
        </div>
      </div>
    </div>

    {{-- Stok telur REALTIME. Diambil dari lot produksi, jadi angka ini sudah
         dikurangi telur yang terjual lewat Barang Keluar dan yang kena penyesuaian. --}}
    <div class="row g-4 mb-5 farm-kpi">
      <div class="col-6 col-lg-3">
        <div class="card card-flush h-100"><div class="card-body py-5">
          <div class="text-muted fs-8">Sisa Stok Telur</div>
          <div class="fs-2hx fw-bold text-success">{{ $num($stok['sisa']) }}<span class="fs-5"> butir</span></div>
          <div class="fs-9 text-muted">seluruh periode, realtime</div>
        </div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="card card-flush h-100"><div class="card-body py-5">
          <div class="text-muted fs-8">Masuk Stok (total)</div>
          <div class="fs-2hx fw-bold text-gray-900">{{ $num($stok['masuk']) }}<span class="fs-5"> butir</span></div>
          <div class="fs-9 text-muted">butir bersih dari seluruh produksi</div>
        </div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="card card-flush h-100"><div class="card-body py-5">
          <div class="text-muted fs-8">Sudah Keluar</div>
          <div class="fs-2hx fw-bold text-warning">{{ $num($stok['terjual']) }}<span class="fs-5"> butir</span></div>
          <div class="fs-9 text-muted">terjual / kena penyesuaian</div>
        </div></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="card card-flush h-100"><div class="card-body py-5">
          <div class="text-muted fs-8">Nilai Stok Telur</div>
          <div class="fs-2hx fw-bold text-primary">{{ $rp($stok['nilai']) }}</div>
          <div class="fs-9 text-muted">sisa butir × HPP tiap lot</div>
        </div></div>
      </div>
    </div>

    <div class="card card-flush">
      <div class="card-header pt-5">
        <div>
          <h3 class="card-title fw-bold fs-4 mb-0">Produksi Telur</h3>
          <span class="text-muted fs-8">Telur layak jual otomatis masuk stok.</span>
        </div>
        <div class="card-toolbar gap-2">
          <form method="GET"><input type="month" name="month" value="{{ $bulan }}"
                 class="form-control form-control-sm form-control-solid" onchange="this.form.submit()"></form>
          <button class="btn btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#m-egg">
            <i class="ki-outline ki-plus fs-3"></i> Catat Produksi</button>
        </div>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0 farm-list-table">
            <thead><tr class="fw-bold text-muted bg-light fs-8">
              <th class="ps-4">Tanggal</th><th>Kandang</th><th>Item</th>
              <th class="text-end">Butir</th><th class="text-end">Pecah</th><th class="text-end">Bersih</th>
              <th class="text-end">Terjual</th><th class="text-end">Sisa Stok</th>
              <th class="text-end">HPP/butir</th>
              <th class="text-end pe-4">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $r)
              <tr>
                <td class="ps-4 fw-bold">{{ $r->date->format('d/m/Y') }}</td>
                <td>{{ $r->coop ?: '—' }}</td>
                <td>{{ $r->item?->name ?? '—' }}</td>
                <td class="text-end">{{ $num($r->qty_butir) }}</td>
                <td class="text-end text-danger">{{ $num($r->qty_broken) }}</td>
                <td class="text-end fw-bold text-success">{{ $num($r->netButir()) }}</td>
                @php
                  $sisa = $r->lot ? (int) $r->lot->qty_ekor_left : null;
                  $keluar = $r->lot ? max(0, (int) $r->lot->qty_ekor_initial - $sisa) : null;
                @endphp
                <td class="text-end">
                  @if ($keluar === null)<span class="text-muted fs-9">—</span>
                  @else <span class="text-warning fw-bold">{{ $num($keluar) }}</span> @endif
                </td>
                <td class="text-end">
                  @if ($sisa === null)
                    <span class="text-muted fs-9">tanpa stok</span>
                  @else
                    <span class="fw-bold {{ $sisa > 0 ? 'text-success' : 'text-muted' }}">{{ $num($sisa) }}</span>
                    @if ($sisa === 0)<div class="fs-9 text-muted">habis</div>@endif
                  @endif
                </td>
                <td class="text-end fs-8">{{ $r->lot ? $rp($r->lot->cost_per_ekor) : '—' }}</td>
                <td class="text-end pe-4">
                  <button type="button" class="btn btn-sm btn-light-primary py-1 px-3 fs-8 js-detail"
                          data-url="{{ route('farm.eggs.detail', $r->id) }}">Detail</button>
                  <form action="{{ route('farm.eggs.destroy', $r->id) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Hapus catatan produksi ini? Hanya bisa bila telurnya belum terjual.')">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-light-danger py-1 px-3 fs-8">Hapus</button></form>
                </td>
              </tr>
            @empty
              <tr><td colspan="10" class="text-center text-muted py-10">Belum ada produksi pada bulan ini.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="m-egg" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('farm.eggs.store') }}">
        @csrf
        <div class="modal-header py-4"><h3 class="fw-bold mb-0">Catat Produksi Telur</h3>
          <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
        </div>
        <div class="modal-body">
          @if ($items->isEmpty())
            <div class="alert alert-warning py-3 fs-8">Belum ada item kategori <b>Telur</b>.
              <a href="{{ route('farm.items.index') }}" class="fw-bold">Tambah dulu</a>.</div>
          @endif
          <div class="row g-3">
            <div class="col-6"><label class="form-label fw-semibold fs-7 required">Tanggal</label>
              <input type="date" name="date" class="form-control form-control-solid" value="{{ now()->format('Y-m-d') }}" required></div>
            <div class="col-6"><label class="form-label fw-semibold fs-7">Kandang</label>
              <input name="coop" class="form-control form-control-solid" maxlength="50" placeholder="mis. Kandang A"></div>
            <div class="col-12"><label class="form-label fw-semibold fs-7 required">Item Telur</label>
              <select name="item_id" class="form-select form-select-solid" required>
                @foreach ($items as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach
              </select></div>
            <div class="col-6"><label class="form-label fw-semibold fs-7 required">Jumlah Butir</label>
              <input type="number" name="qty_butir" class="form-control form-control-solid" min="1" required></div>
            <div class="col-6"><label class="form-label fw-semibold fs-7">Telur Pecah</label>
              <input type="number" name="qty_broken" class="form-control form-control-solid" min="0" value="0"></div>
            <div class="col-12"><label class="form-label fw-semibold fs-7">Catatan</label>
              <input name="notes" class="form-control form-control-solid" maxlength="255"></div>
          </div>
        </div>
        <div class="modal-footer py-3">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-warning fw-bold" {{ $items->isEmpty() ? 'disabled' : '' }}>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
{{-- Rincian satu catatan produksi: butirnya dipakai ke nota mana saja. --}}
<div class="modal fade" id="m-detail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header py-4">
        <div>
          <h3 class="fw-bold mb-0">Rincian Produksi Telur</h3>
          <span class="text-muted fs-8" id="d-judul">—</span>
        </div>
        <div class="btn btn-icon btn-sm btn-active-light" data-bs-dismiss="modal">
          <i class="ki-outline ki-cross fs-1"></i></div>
      </div>
      <div class="modal-body" id="d-isi">
        <div class="text-center text-muted py-8">Memuat…</div>
      </div>
      <div class="modal-footer py-3">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  /**
   * Rincian produksi diambil saat tombol Detail ditekan, bukan dimuat semuanya
   * di awal: satu bulan bisa berisi puluhan catatan dan hampir semuanya tidak
   * pernah dibuka.
   */
  (function () {
    var modalEl = document.getElementById('m-detail');
    if (!modalEl) return;
    var modal = new bootstrap.Modal(modalEl);

    var rp = function (n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); };
    var num = function (n) { return Number(n || 0).toLocaleString('id-ID'); };

    function baris(label, nilai, kelas) {
      return '<div class="d-flex justify-content-between border-bottom py-2">'
        + '<span class="text-muted fs-8">' + label + '</span>'
        + '<span class="fw-bold ' + (kelas || '') + '">' + nilai + '</span></div>';
    }

    function tabel(judul, kolom, baris) {
      if (!baris.length) {
        return '<div class="fw-bold fs-7 text-gray-800 mt-4 mb-2">' + judul + '</div>'
          + '<div class="text-muted fs-8 border border-dashed rounded p-3 text-center">Belum ada.</div>';
      }
      var th = kolom.map(function (k) {
        return '<th class="' + (k[1] || '') + '">' + k[0] + '</th>';
      }).join('');

      return '<div class="fw-bold fs-7 text-gray-800 mt-4 mb-2">' + judul + '</div>'
        + '<div class="table-responsive"><table class="table table-row-bordered align-middle gy-2 mb-0 fs-8">'
        + '<thead><tr class="fw-bold text-muted bg-light">' + th + '</tr></thead>'
        + '<tbody>' + baris.join('') + '</tbody></table></div>';
    }

    document.addEventListener('click', function (e) {
      var b = e.target.closest('.js-detail');
      if (!b) return;

      document.getElementById('d-isi').innerHTML =
        '<div class="text-center text-muted py-8">Memuat…</div>';
      modal.show();

      fetch(b.dataset.url, { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          document.getElementById('d-judul').textContent =
            d.item + ' · ' + d.tanggal + ' · kandang ' + d.kandang;

          var ringkas = '<div class="row g-3">'
            + '<div class="col-6 col-md-3"><div class="border rounded p-3"><div class="fs-9 text-muted">BUTIR</div>'
            + '<div class="fw-bold fs-4">' + num(d.butir) + '</div></div></div>'
            + '<div class="col-6 col-md-3"><div class="border rounded p-3"><div class="fs-9 text-muted">PECAH</div>'
            + '<div class="fw-bold fs-4 text-danger">' + num(d.pecah) + '</div></div></div>'
            + '<div class="col-6 col-md-3"><div class="border rounded p-3"><div class="fs-9 text-muted">SUDAH KELUAR</div>'
            + '<div class="fw-bold fs-4 text-warning">' + num(d.terjual) + '</div></div></div>'
            + '<div class="col-6 col-md-3"><div class="border rounded p-3 bg-light-success"><div class="fs-9 text-muted">SISA STOK</div>'
            + '<div class="fw-bold fs-4 text-success">' + num(d.sisa) + '</div></div></div>'
            + '</div>';

          var info = '<div class="mt-4">'
            + baris('Butir bersih masuk stok', num(d.bersih) + ' butir')
            + baris('Harga pokok per butir', rp(d.hpp))
            + baris('Nilai sisa stok', rp(d.sisa * d.hpp), 'text-primary')
            + (d.catatan ? baris('Catatan', d.catatan) : '')
            + '</div>';

          var jual = d.penjualan.map(function (p) {
            return '<tr><td>' + p.tanggal + '</td>'
              + '<td><a href="' + p.url + '">' + p.nota + '</a></td>'
              + '<td>' + p.agen + '</td>'
              + '<td class="text-end">' + num(p.butir) + '</td>'
              + '<td class="text-end">' + rp(p.harga) + '</td>'
              + '<td class="text-end">' + rp(p.hpp) + '</td></tr>';
          });

          var susut = d.penyesuaian.map(function (p) {
            return '<tr><td>' + p.tanggal + '</td><td>' + p.ref + '</td><td>' + p.sebab + '</td>'
              + '<td class="text-end">' + num(p.butir) + '</td>'
              + '<td class="text-end">' + rp(p.nilai) + '</td></tr>';
          });

          document.getElementById('d-isi').innerHTML = ringkas + info
            + tabel('Terjual lewat Barang Keluar',
                [['Tanggal'], ['No. Nota'], ['Pembeli'], ['Butir', 'text-end'],
                 ['Harga/butir', 'text-end'], ['Harga Pokok', 'text-end']], jual)
            + tabel('Penyesuaian Stok',
                [['Tanggal'], ['No. Ref'], ['Sebab'], ['Butir', 'text-end'], ['Nilai', 'text-end']], susut);
        })
        .catch(function () {
          document.getElementById('d-isi').innerHTML =
            '<div class="alert alert-danger fs-8 mb-0">Gagal memuat rincian. Coba lagi.</div>';
        });
    });
  })();
</script>
@endpush
