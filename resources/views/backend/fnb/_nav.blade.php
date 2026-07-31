{{-- Navigasi modul HPP/Inventory (F&B, paket Customize) --}}
<div class="d-flex flex-wrap align-items-center justify-content-between mb-5 gap-3">
  <div>
    <h1 class="fw-bold text-gray-900 mb-1">HPP &amp; Inventory</h1>
    <span class="text-muted fs-7">Bahan baku, resep, stok berbasis lot (FIFO/FEFO) &amp; modal per menu.</span>
  </div>
  <ul class="nav nav-pills nav-pills-sm gap-2">
    @php $tabs = [
      'ingredients' => ['Bahan Baku', 'fnb.ingredients.index'],
      'suppliers'   => ['Supplier', 'fnb.suppliers.index'],
      'recipes'     => ['Resep', 'fnb.recipes.index'],
      'stock'       => ['Stok', 'fnb.stock.index'],
      'card'        => ['Kartu Stok', 'fnb.stock.card'],
      'opname'      => ['Opname', 'fnb.opname.index'],
    ]; @endphp
    @foreach ($tabs as $key => [$label, $route])
      <li class="nav-item">
        <a class="nav-link btn btn-sm fw-bold {{ ($active ?? '') === $key ? 'btn-primary' : 'btn-light' }}"
           href="{{ route($route) }}">{{ $label }}</a>
      </li>
    @endforeach
  </ul>
</div>
