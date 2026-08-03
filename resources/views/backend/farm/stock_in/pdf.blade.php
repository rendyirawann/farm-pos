@php
    $rp  = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $num = fn ($n, $d = 0) => number_format((float) $n, $d, ',', '.');
@endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Nota Pembelian {{ $row->invoice_no }}</title>
<style>
    /* DomPDF hanya mendukung CSS sederhana — hindari flex/grid. */
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 10px; color: #111; margin: 0; }
    .kop { border-bottom: 2px solid #111; padding-bottom: 6px; margin-bottom: 10px; }
    .kop h1 { font-size: 15px; margin: 0 0 2px; }
    .kop .alamat { font-size: 9px; color: #555; }
    .judul { text-align: right; }
    .judul .tipe { font-size: 13px; font-weight: bold; }
    .judul .no { font-size: 9px; color: #555; }
    table { width: 100%; border-collapse: collapse; }
    .meta td { padding: 1px 0; font-size: 9.5px; }
    .meta .label { color: #666; width: 70px; }
    .barang { margin-top: 10px; }
    .barang th {
        background: #f0f0f0; border-bottom: 1px solid #999;
        padding: 5px 4px; font-size: 9px; text-align: left;
    }
    .barang td { padding: 5px 4px; border-bottom: 1px solid #e5e5e5; font-size: 9.5px; }
    .kanan { text-align: right; }
    .tengah { text-align: center; }
    .total td { border-top: 2px solid #111; border-bottom: none; font-weight: bold; font-size: 12px; padding-top: 7px; }
    .catatan { margin-top: 10px; font-size: 9px; color: #444; border: 1px solid #ddd; padding: 6px; }
    .ttd { margin-top: 26px; width: 100%; }
    .ttd td { font-size: 9px; text-align: center; width: 50%; padding-top: 34px; }
    .ttd .garis { border-top: 1px solid #999; padding-top: 3px; }
    .kaki { margin-top: 14px; font-size: 8px; color: #888; text-align: center; }
</style>
</head>
<body>

<table class="kop"><tr>
    <td>
        <h1>{{ $tenant->name ?? 'Mooda Stok' }}</h1>
        <div class="alamat">
            {{ $tenant->address ?? '' }}
            @if (! empty($tenant->phone)) · Telp {{ $tenant->phone }} @endif
        </div>
    </td>
    <td class="judul">
        <div class="tipe">NOTA PEMBELIAN</div>
        <div class="no">{{ $row->invoice_no }}</div>
    </td>
</tr></table>

<table class="meta">
    <tr>
        <td class="label">Tanggal</td>
        <td>: {{ $row->date->locale('id')->translatedFormat('d F Y') }}</td>
        <td class="label">Supplier</td>
        <td>: {{ $row->supplier?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Dicatat</td>
        <td>: {{ $row->user?->name ?? '-' }}</td>
        <td class="label">Telp</td>
        <td>: {{ $row->supplier?->phone ?? '—' }}</td>
    </tr>
</table>

<table class="barang">
    <thead><tr>
        <th style="width:34%">Barang</th>
        <th class="tengah" style="width:12%">Ekor</th>
        <th class="tengah" style="width:14%">Berat (kg)</th>
        <th class="kanan" style="width:18%">Harga</th>
        <th class="kanan" style="width:22%">Subtotal</th>
    </tr></thead>
    <tbody>
    @foreach ($row->lines as $l)
        <tr>
            <td>{{ $l->item?->name ?? '-' }}</td>
            <td class="tengah">{{ $num($l->qty_ekor) }}</td>
            <td class="tengah">{{ $num($l->weight_kg, 2) }}</td>
            <td class="kanan">{{ $rp($l->unit_price) }}<span style="color:#777"> /{{ $l->price_basis }}</span></td>
            <td class="kanan">{{ $rp($l->subtotal) }}</td>
        </tr>
    @endforeach
    <tr class="total">
        <td colspan="4" class="kanan">TOTAL</td>
        <td class="kanan">{{ $rp($row->total) }}</td>
    </tr>
    </tbody>
</table>

@if ($row->notes)
    <div class="catatan"><b>Catatan:</b> {{ $row->notes }}</div>
@endif

<table class="ttd"><tr>
    <td><div class="garis">Penerima / Gudang</div></td>
    <td><div class="garis">Supplier</div></td>
</tr></table>

<div class="kaki">
    Dicetak {{ now()->locale('id')->translatedFormat('d F Y H:i') }}
    @if ($row->hasPhotos()) · {{ count($row->photoList()) }} lembar foto bon terlampir di sistem @endif
</div>

</body>
</html>
