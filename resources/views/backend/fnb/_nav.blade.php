{{-- Navigasi modul HPP/Inventory (F&B, paket Customize) — hero + kartu ber-ikon --}}
@php
    $fnbTabs = [
        'ingredients' => ['Bahan Baku', 'Master bahan & min. stok',   'ki-bucket',           'primary', 'fnb.ingredients.index'],
        'suppliers'   => ['Supplier',   'Pemasok bahan',              'ki-truck',            'info',    'fnb.suppliers.index'],
        'recipes'     => ['Resep',      'Bahan per porsi menu',       'ki-chart-pie-simple', 'success', 'fnb.recipes.index'],
        'stock'       => ['Stok',       'Pembelian & lot FIFO/FEFO',  'ki-package',          'warning', 'fnb.stock.index'],
        'card'        => ['Kartu Stok', 'Riwayat gerakan & COGS',     'ki-book-open',        'dark',    'fnb.stock.card'],
        'opname'      => ['Opname',     'Sistem vs fisik',            'ki-clipboard',        'danger',  'fnb.opname.index'],
    ];
    $act = $active ?? '';
@endphp

<style>
    .fnb-hero{background:linear-gradient(120deg,#4f46e5 0%,#6d28d9 60%,#7c3aed 100%);border-radius:20px;color:#fff;
        padding:22px 26px;position:relative;overflow:hidden}
    .fnb-hero::after{content:"";position:absolute;right:-40px;top:-50px;width:190px;height:190px;border-radius:50%;
        background:rgba(255,255,255,.09)}
    .fnb-tab{display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #eceef7;border-radius:16px;
        padding:14px 16px;text-decoration:none;height:100%;transition:transform .15s,box-shadow .2s,border-color .15s;
        box-shadow:0 8px 22px -20px rgba(15,23,42,.6)}
    .fnb-tab:hover{transform:translateY(-3px);border-color:#dfe3f6;box-shadow:0 18px 34px -22px rgba(79,70,229,.5)}
    .fnb-tab.is-active{border-color:#4f46e5;background:linear-gradient(180deg,#fff,#f6f6ff);
        box-shadow:0 14px 30px -18px rgba(79,70,229,.55)}
    .fnb-tab .ic{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;flex:0 0 auto}
    .fnb-tab .ic i{font-size:20px}
    .fnb-tab .tl{font-weight:700;font-size:13.5px;color:#0f172a;line-height:1.2}
    .fnb-tab .ds{font-size:10.5px;color:#94a3b8;margin-top:2px;line-height:1.3}
</style>

<div class="fnb-hero mb-5">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge badge-light fw-bold fs-9">Paket Customize</span>
                <span class="badge badge-light-success fw-bold fs-9">FIFO / FEFO</span>
            </div>
            <h1 class="text-white fw-bold mb-1 fs-2">HPP &amp; Inventory</h1>
            <span class="text-white opacity-75 fs-7">Bahan baku, resep, stok berbasis lot &amp; modal (HPP) nyata per menu.</span>
        </div>
        <a href="{{ route('reports.sales.index') }}" class="btn btn-light fw-bold">
            <i class="ki-outline ki-chart-simple fs-3 me-1"></i> Laporan HPP
        </a>
    </div>
</div>

<div class="row g-3 mb-6">
    @foreach ($fnbTabs as $key => [$label, $desc, $icon, $color, $route])
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route($route) }}" class="fnb-tab {{ $act === $key ? 'is-active' : '' }}">
                <span class="ic bg-light-{{ $color }}"><i class="ki-outline {{ $icon }} text-{{ $color }}"></i></span>
                <span>
                    <span class="tl d-block">{{ $label }}</span>
                    <span class="ds d-block">{{ $desc }}</span>
                </span>
            </a>
        </div>
    @endforeach
</div>
