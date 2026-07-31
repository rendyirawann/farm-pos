@extends('backend.layout.app')
@section('title', 'Resep Menu')
@push('stylesheets')
    <link rel="stylesheet" href="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.css') }}" />
@endpush

@section('content')
<div id="kt_app_content" class="app-content flex-column-fluid mt-5">
  <div id="kt_app_content_container" class="app-container container-xxl">
    @include('backend.fnb._nav', ['active' => 'recipes'])

    @if ($ingredients->isEmpty())
      <div class="alert alert-warning">Belum ada bahan baku. Tambahkan dulu di
        <a href="{{ route('fnb.ingredients.index') }}" class="fw-bold">Bahan Baku</a> sebelum menyusun resep.</div>
    @endif

    <div class="card card-flush">
      <div class="card-header pt-5">
        <h3 class="card-title fw-bold fs-5">Resep per Menu</h3>
        <span class="text-muted fs-8">Gramasi untuk <b>1 porsi</b>. Dipakai memotong stok & menghitung HPP saat dapur memasak.</span>
      </div>
      <div class="card-body pt-4">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0" id="tbl-recipes">
            <thead><tr class="fw-bold text-muted bg-light">
              <th class="ps-6">Menu</th><th>Kategori</th><th>Bahan (resep)</th><th class="text-end">Perkiraan HPP*</th><th class="text-end pe-6">Aksi</th>
            </tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="card-footer py-3">
        <span class="fs-8 text-muted">*Perkiraan memakai harga lot terbaru. <b>HPP nyata</b> dihitung dari lot yang benar-benar terpakai (FEFO) saat dapur memasak.</span>
      </div>
    </div>
  </div>
</div>

{{-- Modal atur resep --}}
<div class="modal fade" id="modal-recipe" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered mw-650px">
    <div class="modal-content rounded-4">
      <div class="modal-header">
        <h2 class="fw-bold fs-4 mb-0">Resep — <span id="rc-menu-name"></span></h2>
        <div class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-between text-muted fs-8 fw-bold text-uppercase mb-2">
          <span>Bahan</span><span>Jumlah per porsi</span>
        </div>
        <div id="rc-lines"></div>
        <button type="button" class="btn btn-sm btn-light-primary mt-3" id="rc-add">+ Tambah Bahan</button>
        <div class="fs-8 text-muted mt-3">Kosongkan semua baris lalu simpan untuk menghapus resep (menu tanpa resep → HPP 0).</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="rc-save">Simpan Resep</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
  const INGREDIENTS = @json($ingredients->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit]));
  const CSRF = "{{ csrf_token() }}";
  const SHOW_URL = "{{ url('/admin/fnb/recipes') }}";
  let curMenu = null;

  function lineHtml(sel = '', qty = '') {
    const opts = INGREDIENTS.map(i => `<option value="${i.id}" data-unit="${i.unit}" ${String(i.id) === String(sel) ? 'selected' : ''}>${i.name} (${i.unit})</option>`).join('');
    return `<div class="d-flex gap-2 mb-2 rc-line">
        <select class="form-select form-select-sm form-select-solid rc-ing"><option value="">— pilih bahan —</option>${opts}</select>
        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm form-control-solid rc-qty" style="max-width:130px" placeholder="jumlah" value="${qty}">
        <button type="button" class="btn btn-sm btn-icon btn-light-danger rc-del"><i class="ki-outline ki-cross fs-4"></i></button>
      </div>`;
  }

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-recipe');
    if (btn) {
      curMenu = btn.dataset.menu;
      document.getElementById('rc-menu-name').textContent = btn.dataset.name;
      const box = document.getElementById('rc-lines');
      box.innerHTML = '<div class="text-muted fs-8 py-3">Memuat…</div>';
      new bootstrap.Modal(document.getElementById('modal-recipe')).show();
      fetch(`${SHOW_URL}/${curMenu}`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(d => {
          box.innerHTML = (d.lines || []).map(l => lineHtml(l.ingredient_id, l.quantity)).join('') || lineHtml();
        })
        .catch(() => { box.innerHTML = lineHtml(); });
    }
    if (e.target.closest('.rc-del')) { e.target.closest('.rc-line').remove(); }
  });

  document.getElementById('rc-add').addEventListener('click', () => {
    document.getElementById('rc-lines').insertAdjacentHTML('beforeend', lineHtml());
  });

  document.getElementById('rc-save').addEventListener('click', function () {
    const lines = [];
    document.querySelectorAll('#rc-lines .rc-line').forEach(row => {
      const ing = row.querySelector('.rc-ing').value;
      const qty = parseFloat(row.querySelector('.rc-qty').value);
      if (ing && qty > 0) lines.push({ ingredient_id: +ing, quantity: qty });
    });
    const btn = this; btn.disabled = true; btn.textContent = 'Menyimpan…';
    fetch(`${SHOW_URL}/${curMenu}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
      body: JSON.stringify({ lines }),
    })
      .then(r => r.json())
      .then(() => { dtRecipes.ajax.reload(null, false); bootstrap.Modal.getInstance(document.getElementById('modal-recipe')).hide(); btn.disabled = false; btn.textContent = 'Simpan Resep'; })
      .catch(() => { alert('Gagal menyimpan resep.'); btn.disabled = false; btn.textContent = 'Simpan Resep'; });
  });

  // ============ TABEL RESEP: DataTables server-side, 5 baris per halaman ============
  const DATA_URL = "{{ route('fnb.recipes.data') }}";
  const rupiah = n => 'Rp ' + Number(n || 0).toLocaleString('id-ID');
  const escHtml = t => $('<div>').text(t == null ? '' : t).html();

  const dtRecipes = $('#tbl-recipes').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: DATA_URL, type: 'GET' },
    pageLength: 5,
    lengthChange: false,
    order: [[0, 'asc']],
    language: {
      search: 'Cari menu:',
      processing: 'Memuat…',
      zeroRecords: 'Menu tidak ditemukan.',
      emptyTable: 'Belum ada menu.',
      info: 'Menampilkan _START_-_END_ dari _TOTAL_ menu',
      infoEmpty: 'Tidak ada data',
      infoFiltered: '(disaring dari _MAX_ menu)',
      paginate: { previous: 'Sebelumnya', next: 'Berikutnya' },
    },
    columns: [
      {
        data: 'menu', className: 'ps-6',
        render: (d, t, row) => '<span class="fw-bold text-gray-800">' + escHtml(d) + '</span>'
          + '<div class="fs-8 text-muted">Harga jual ' + rupiah(row.price) + '</div>',
      },
      { data: 'category', className: 'text-muted fs-8', render: d => escHtml(d) },
      {
        data: 'recipe', orderable: false,
        render: function (r) {
          if (!r || !r.length) return '<span class="badge badge-light-warning fs-9">Belum ada resep</span>';
          return r.map(x => '<span class="badge badge-light-primary fs-9 me-1 mb-1">'
            + escHtml(x.name) + ' ' + escHtml(x.qty) + escHtml(x.unit || '') + '</span>').join('');
        },
      },
      {
        data: 'hpp_est', orderable: false, className: 'text-end fw-bold',
        render: d => d > 0 ? '<span class="text-gray-900">' + rupiah(d) + '</span>' : '<span class="text-muted">—</span>',
      },
      {
        data: null, orderable: false, searchable: false, className: 'text-end pe-6',
        render: row => '<button class="btn btn-sm btn-light-primary py-1 px-3 fs-8 btn-recipe" data-menu="'
          + row.id + '" data-name="' + escHtml(row.menu) + '">Atur Resep</button>',
      },
    ],
  });

</script>
@endpush
