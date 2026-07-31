@extends('backend.layout.app')
@section('title', 'Resep Menu')
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
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-row-bordered align-middle gy-3 mb-0">
            <thead><tr class="fw-bold text-muted bg-light">
              <th class="ps-6">Menu</th><th>Kategori</th><th>Bahan (resep)</th><th class="text-end">Perkiraan HPP*</th><th class="text-end pe-6">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse ($menus as $m)
              @php
                // Perkiraan HPP standar = Σ(gramasi × harga lot terbaru). HPP nyata tetap dari konsumsi FEFO.
                $est = 0;
                foreach ($m->menuIngredients as $l) {
                  $price = (float) (\App\Models\Fnb\IngredientBatch::where('ingredient_id', $l->ingredient_id)
                      ->orderByDesc('id')->value('buy_price') ?? 0);
                  $est += (float) $l->quantity * $price;
                }
              @endphp
              <tr>
                <td class="ps-6 fw-bold text-gray-800">{{ $m->name }}
                  <div class="fs-8 text-muted">Harga jual Rp {{ number_format($m->price ?? 0, 0, ',', '.') }}</div>
                </td>
                <td class="text-muted fs-8">{{ $m->category?->name ?? '-' }}</td>
                <td>
                  @if ($m->menuIngredients->isEmpty())
                    <span class="badge badge-light-warning fs-9">Belum ada resep</span>
                  @else
                    @foreach ($m->menuIngredients as $l)
                      <span class="badge badge-light-primary fs-9 me-1 mb-1">{{ $l->ingredient?->name }}
                        {{ rtrim(rtrim(number_format((float) $l->quantity, 2, '.', ''), '0'), '.') }}{{ $l->ingredient?->unit }}</span>
                    @endforeach
                  @endif
                </td>
                <td class="text-end fw-bold {{ $est > 0 ? 'text-gray-900' : 'text-muted' }}">
                  {{ $est > 0 ? 'Rp ' . number_format($est, 0, ',', '.') : '—' }}
                </td>
                <td class="text-end pe-6">
                  <button class="btn btn-sm btn-light-primary py-1 px-3 fs-8 btn-recipe"
                    data-menu="{{ $m->id }}" data-name="{{ $m->name }}">Atur Resep</button>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted py-10">Belum ada menu.</td></tr>
            @endforelse
            </tbody>
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
      .then(() => window.location.reload())
      .catch(() => { alert('Gagal menyimpan resep.'); btn.disabled = false; btn.textContent = 'Simpan Resep'; });
  });
</script>
@endpush
