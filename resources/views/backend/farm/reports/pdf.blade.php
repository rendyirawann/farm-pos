@php
    /**
     * Tata letak PDF laporan — dipakai SELURUH jenis laporan.
     *
     * DomPDF hanya mengerti CSS sederhana: tanpa flexbox, tanpa grid, tanpa
     * variabel CSS. Jadi tata letak dibangun dengan tabel, dan nomor halaman
     * memakai counter(page)/counter(pages) di dalam elemen position:fixed —
     * satu-satunya cara yang bekerja tanpa mengaktifkan PHP di dalam berkas.
     */
    $hijau = '#15803d';
@endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $data['judul'] }}</title>
<style>
    @page { margin: 92px 30px 62px 30px; }

    * { font-family: "DejaVu Sans", sans-serif; }
    body { font-size: 9px; color: #1f2937; margin: 0; }

    /* ---------- Kop & kaki yang berulang di setiap halaman ---------- */
    header {
        position: fixed; top: -74px; left: 0; right: 0; height: 66px;
    }
    .kop { width: 100%; border-collapse: collapse; }
    .kop td { vertical-align: top; padding: 0; }
    .kop .usaha { font-size: 14px; font-weight: bold; color: {{ $hijau }}; letter-spacing: .2px; }
    .kop .alamat { font-size: 8px; color: #6b7280; padding-top: 2px; line-height: 1.35; }
    .kop .judul { text-align: right; }
    .kop .judul .nama { font-size: 13px; font-weight: bold; color: #111827; }
    .kop .judul .sub { font-size: 8.5px; color: #4b5563; padding-top: 2px; }
    .garis-kop { border-bottom: 2.5px solid {{ $hijau }}; margin-top: 6px; }
    .garis-kop-tipis { border-bottom: 1px solid #d1d5db; margin-top: 1.5px; }

    footer {
        position: fixed; bottom: -46px; left: 0; right: 0; height: 40px;
        font-size: 7.5px; color: #9ca3af;
    }
    .kaki { width: 100%; border-collapse: collapse; border-top: 1px solid #e5e7eb; }
    .kaki td { padding-top: 5px; }
    .kaki .kanan { text-align: right; }

    /* ---------- Baris keterangan laporan ---------- */
    .meta { width: 100%; border-collapse: collapse; margin-bottom: 9px; }
    .meta td {
        font-size: 8.5px; padding: 5px 8px; background: #f3f4f6;
        border: 1px solid #e5e7eb; vertical-align: top;
    }
    .meta .label { color: #6b7280; }
    .meta b { color: #111827; }

    /* ---------- Kartu angka utama ---------- */
    .kpi { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin-bottom: 11px; }
    .kpi td {
        width: 25%; border: 1px solid #d1d5db; border-top: 2.5px solid {{ $hijau }};
        padding: 7px 8px; background: #fbfdfb;
    }
    .kpi .label { font-size: 7.5px; color: #6b7280; text-transform: uppercase; letter-spacing: .4px; }
    .kpi .nilai { font-size: 13px; font-weight: bold; color: #111827; padding-top: 2px; }
    .kpi .nilai .satuan { font-size: 8px; font-weight: normal; color: #6b7280; }
    .kpi .ket { font-size: 7.5px; color: #6b7280; padding-top: 1px; }

    /* ---------- Tabel isi ---------- */
    h3.blok {
        font-size: 10px; margin: 12px 0 5px; color: #111827;
        border-left: 3px solid {{ $hijau }}; padding-left: 6px;
    }
    table.isi { width: 100%; border-collapse: collapse; }
    table.isi thead th {
        background: {{ $hijau }}; color: #fff; font-size: 8px; font-weight: bold;
        padding: 5px 5px; text-align: left; border: 1px solid {{ $hijau }};
    }
    table.isi tbody td {
        font-size: 8.5px; padding: 4px 5px; border: 1px solid #e5e7eb;
    }
    table.isi tbody tr.selang td { background: #f9fafb; }
    table.isi tbody tr.tebal td { background: #eef7f0; font-weight: bold; }
    table.isi tfoot td {
        font-size: 8.5px; font-weight: bold; padding: 5px;
        background: #e8f2ea; border: 1px solid #cbd5cd; border-top: 1.5px solid {{ $hijau }};
    }
    .kanan { text-align: right; }
    .tengah { text-align: center; }
    .kiri { text-align: left; }
    .kosong { text-align: center; color: #9ca3af; padding: 14px 0; font-style: italic; }

    /* ---------- Catatan & tanda tangan ---------- */
    .catatan {
        margin-top: 11px; font-size: 8px; color: #374151;
        background: #f0f7f2; border: 1px solid #cfe3d5; padding: 7px 9px; line-height: 1.45;
    }
    .ttd { width: 100%; margin-top: 26px; border-collapse: collapse; page-break-inside: avoid; }
    .ttd td { width: 33%; font-size: 8.5px; text-align: center; padding-top: 3px; }
    .ttd .ruang { height: 42px; }
    .ttd .garis { border-top: 1px solid #9ca3af; padding-top: 3px; color: #4b5563; }
</style>
</head>
<body>

<header>
    <table class="kop">
        <tr>
            <td>
                <div class="usaha">{{ $tenant->name ?? 'Mooda Stok' }}</div>
                <div class="alamat">
                    @php
                        $bagian = array_filter([
                            trim((string) ($tenant->address ?? '')),
                            ! empty($tenant->phone) ? 'Telp ' . $tenant->phone : null,
                        ]);
                    @endphp
                    {{ implode(' · ', $bagian) }}
                </div>
            </td>
            <td class="judul">
                <div class="nama">{{ $data['judul'] }}</div>
                <div class="sub">
                    @if ($labelPeriode){{ $labelPeriode }}@endif
                    @if (! empty($data['subjudul']))<br>{{ $data['subjudul'] }}@endif
                </div>
            </td>
        </tr>
    </table>
    <div class="garis-kop"></div>
    <div class="garis-kop-tipis"></div>
</header>

<footer>
    <table class="kaki">
        <tr>
            <td>Mooda Stok — sistem pencatatan stok & perdagangan ternak</td>
            <td class="kanan">&nbsp;</td>
        </tr>
    </table>
</footer>

<table class="meta">
    <tr>
        @if ($labelPeriode)
            <td width="30%"><span class="label">Periode</span><br><b>{{ $labelPeriode }}</b></td>
        @endif
        <td><span class="label">Cakupan</span><br><b>{{ $data['subjudul'] ?? 'Seluruh data' }}</b></td>
        <td width="24%"><span class="label">Dicetak</span><br>
            <b>{{ now()->locale('id')->translatedFormat('d F Y H:i') }}</b></td>
        <td width="20%"><span class="label">Oleh</span><br><b>{{ $dicetakOleh }}</b></td>
    </tr>
</table>

<table class="kpi">
    <tr>
        @foreach ($data['ringkas'] as $r)
            <td>
                <div class="label">{{ $r['label'] }}</div>
                <div class="nilai">
                    @switch($r['jenis'])
                        @case('rp') Rp {{ number_format((float) $r['nilai'], 0, ',', '.') }} @break
                        @case('kg')
                            {{ number_format((float) $r['nilai'], 2, ',', '.') }}<span class="satuan"> kg</span> @break
                        @case('ekor')
                            {{ number_format((float) $r['nilai'], 0, ',', '.') }}<span class="satuan"> ekor</span> @break
                        @default {{ number_format((float) $r['nilai'], 0, ',', '.') }}
                    @endswitch
                </div>
                @if (! empty($r['ket']))<div class="ket">{{ $r['ket'] }}</div>@endif
            </td>
        @endforeach
    </tr>
</table>

@foreach ($data['blok'] as $blok)
    <h3 class="blok">{{ $blok['judul'] }}</h3>
    <table class="isi">
        <thead>
            <tr>
                @foreach ($blok['kolom'] as $k)
                    <th class="{{ ($k['align'] ?? 'left') === 'right' ? 'kanan' : (($k['align'] ?? '') === 'center' ? 'tengah' : 'kiri') }}"
                        @if (! empty($k['lebar'])) width="{{ $k['lebar'] }}" @endif>{{ $k['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
        @forelse ($blok['baris'] as $i => $baris)
            @php
                $kelas = in_array($i, $blok['tebal'] ?? [], true) ? 'tebal' : ($i % 2 ? 'selang' : '');
            @endphp
            <tr class="{{ $kelas }}">
                @foreach ($baris as $j => $sel)
                    @php $al = $blok['kolom'][$j]['align'] ?? 'left'; @endphp
                    <td class="{{ $al === 'right' ? 'kanan' : ($al === 'center' ? 'tengah' : 'kiri') }}">{{ $sel }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td class="kosong" colspan="{{ count($blok['kolom']) }}">
                Tidak ada data pada periode atau filter ini.
            </td></tr>
        @endforelse
        </tbody>
        @if (! empty($blok['total']) && count($blok['baris']))
            <tfoot>
                <tr>
                    @foreach ($blok['total'] as $j => $sel)
                        @php $al = $blok['kolom'][$j]['align'] ?? 'left'; @endphp
                        <td class="{{ $al === 'right' ? 'kanan' : ($al === 'center' ? 'tengah' : 'kiri') }}">{{ $sel }}</td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
@endforeach

@if (! empty($data['catatan']))
    <div class="catatan"><b>Catatan:</b> {{ $data['catatan'] }}</div>
@endif

<table class="ttd">
    <tr>
        <td>Dibuat oleh,<div class="ruang"></div><div class="garis">{{ $dicetakOleh }}</div></td>
        <td></td>
        <td>Disetujui oleh,<div class="ruang"></div><div class="garis">Pemilik / Penanggung Jawab</div></td>
    </tr>
</table>

</body>
</html>
