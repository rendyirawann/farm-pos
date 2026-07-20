<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan — {{ $setting->store_name ?? 'Mooda' }}</title>
    <style>
        @page { size: A4; margin: 13mm; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            font-size: 12px; color: #1e2129; margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .accent { color: #4f46e5; }

        /* Header */
        .head { display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 3px solid #4f46e5; padding-bottom: 14px; margin-bottom: 4px; }
        .brand-name { font-size: 24px; font-weight: 800; letter-spacing: .5px; }
        .brand-sub { font-size: 11px; color: #6b7280; margin-top: 2px; }
        .doc-title { text-align: right; }
        .doc-title .t { font-size: 18px; font-weight: 800; color: #4f46e5; text-transform: uppercase; letter-spacing: 1px; }
        .doc-title .d { font-size: 10.5px; color: #6b7280; margin-top: 3px; }

        .meta { width: 100%; border-collapse: collapse; margin: 14px 0 18px; }
        .meta td { font-size: 11px; padding: 2px 0; color: #444; }
        .meta .k { color: #6b7280; width: 130px; }

        /* Summary cards */
        .cards { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px 20px; }
        .cards td { width: 25%; vertical-align: top; }
        .card { border: 1px solid #e6e7eb; border-radius: 10px; padding: 12px 14px; }
        .card .lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
        .card .val { font-size: 18px; font-weight: 800; margin-top: 4px; color: #1e2129; }
        .card .sub { font-size: 9px; color: #9aa0ac; margin-top: 2px; }
        .c-primary { background: #eef0ff; border-color: #c7ccff; } .c-primary .lbl { color: #4f46e5; }
        .c-info    { background: #eaf5ff; border-color: #bfe0ff; } .c-info .lbl { color: #1b84ff; }
        .c-danger  { background: #fff0f3; border-color: #ffd0da; } .c-danger .lbl { color: #f1416c; }
        .c-success { background: #eafff2; border-color: #b8f2cf; } .c-success .lbl { color: #17a862; }
        .c-success .val { color: #12905a; }

        .voided-note { background: #fff0f3; border: 1px dashed #f1416c; color: #a3123a;
            border-radius: 8px; padding: 9px 12px; font-size: 10.5px; margin-bottom: 16px; }

        .section-title { font-size: 13px; font-weight: 800; margin: 4px 0 10px; }

        /* Invoice blocks */
        .inv { border: 1px solid #e6e7eb; border-radius: 10px; margin-bottom: 10px; overflow: hidden;
            page-break-inside: avoid; }
        .inv-head { background: #f7f8fa; padding: 8px 12px; display: flex; justify-content: space-between; align-items: center; }
        .inv-head .l .no { font-weight: 800; }
        .inv-head .l .cust { font-size: 10.5px; color: #6b7280; margin-top: 1px; }
        .inv-head .r { text-align: right; }
        .badge { display: inline-block; font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 20px; text-transform: uppercase; }
        .b-cash { background: #eafff2; color: #17a862; } .b-qris { background: #eaf5ff; color: #1b84ff; }
        .b-void { background: #fff0f3; color: #f1416c; margin-left: 4px; }
        .inv-total { font-size: 15px; font-weight: 800; color: #12905a; }
        .inv-total.void { color: #b0b4bd; text-decoration: line-through; }

        .items { width: 100%; border-collapse: collapse; }
        .items th { font-size: 9px; text-transform: uppercase; color: #9aa0ac; text-align: left;
            padding: 6px 12px; border-bottom: 1px solid #eee; letter-spacing: .3px; }
        .items td { font-size: 11px; padding: 5px 12px; border-bottom: 1px solid #f4f4f6; }
        .items tr:last-child td { border-bottom: none; }
        .items .qty { text-align: center; width: 44px; }
        .items .price, .items .sub { text-align: right; width: 95px; white-space: nowrap; }
        .item-name { font-weight: 600; color: #333; }
        .item-meta { font-size: 9px; color: #9aa0ac; }
        .inv-foot { padding: 6px 12px; text-align: right; font-size: 10.5px; color: #6b7280; border-top: 1px dashed #e6e7eb; }
        .inv-foot .disc { color: #f1416c; }

        .grand { margin-top: 18px; border-top: 3px solid #4f46e5; padding-top: 10px; }
        .grand table { width: 46%; margin-left: auto; border-collapse: collapse; }
        .grand td { padding: 3px 0; font-size: 12px; }
        .grand .k { color: #4a4a4a; } .grand .v { text-align: right; font-weight: 700; }
        .grand .neg { color: #f1416c; }
        .grand .net td { border-top: 2px solid #1e2129; padding-top: 8px; font-size: 15px; font-weight: 800; }
        .grand .net .v { color: #12905a; }

        .footer { margin-top: 22px; text-align: center; font-size: 9.5px; color: #9aa0ac;
            border-top: 1px solid #eee; padding-top: 8px; }

        @media print { body { margin: 0; } .no-print { display: none; } }
    </style>
</head>

<body onload="window.print()">

    @php
        $money = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
        $validOrders = $orders->whereNull('voided_at');
    @endphp

    <div class="head">
        <div>
            <div class="brand-name">{{ $setting->store_name ?? 'Mooda' }}</div>
            <div class="brand-sub">Laporan Penjualan (F&B) &middot; dihasilkan oleh Mooda POS</div>
        </div>
        <div class="doc-title">
            <div class="t">Laporan Penjualan</div>
            <div class="d">Dicetak: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}</div>
        </div>
    </div>

    <table class="meta">
        <tr><td class="k">Rentang Tanggal</td><td>: {{ $filterDate }}</td></tr>
        <tr><td class="k">Metode Pembayaran</td><td>: {{ $filterPayment }}</td></tr>
    </table>

    {{-- Ringkasan --}}
    <table class="cards">
        <tr>
            <td>
                <div class="card c-primary"><div class="lbl">Total Nota</div>
                    <div class="val">{{ number_format($totalOrders, 0, ',', '.') }}</div><div class="sub">nota sah</div></div>
            </td>
            <td>
                <div class="card c-info"><div class="lbl">Total Pendapatan</div>
                    <div class="val">{{ $money($totalRevenue) }}</div><div class="sub">tanpa pesanan salah</div></div>
            </td>
            @if (($expenseApplies ?? true))
            <td>
                <div class="card c-danger"><div class="lbl">Total Pengeluaran</div>
                    <div class="val">{{ $money($totalExpense) }}</div><div class="sub">total per tanggal (semua shift)</div></div>
            </td>
            @endif
            <td>
                <div class="card c-success"><div class="lbl">Omzet Bersih</div>
                    <div class="val">{{ $money($netRevenue) }}</div><div class="sub">pendapatan &minus; pengeluaran</div></div>
            </td>
        </tr>
    </table>

    @if (($voidedCount ?? 0) > 0)
        <div class="voided-note">
            <b>{{ $voidedCount }} pesanan ditandai SALAH</b> senilai {{ $money($voidedAmount) }} —
            tetap tampil di bawah sebagai riwayat, tetapi <b>tidak dihitung</b> ke omzet/pendapatan.
        </div>
    @endif

    <div class="section-title">Rincian Transaksi &amp; Pesanan</div>

    @forelse ($orders as $index => $order)
        @php $isVoid = (bool) $order->voided_at; $pm = strtolower((string) $order->payment_method); @endphp
        <div class="inv">
            <div class="inv-head">
                <div class="l">
                    <span class="no">#{{ $index + 1 }} &middot; {{ $order->invoice_no }}</span>
                    <span class="badge {{ $pm === 'cash' ? 'b-cash' : 'b-qris' }}">{{ $pm ?: '-' }}</span>
                    @if ($isVoid)<span class="badge b-void">Salah</span>@endif
                    <div class="cust">
                        {{ $order->customer_name ?: 'Pelanggan' }}
                        &middot; No. Antrian {{ $order->queue_number ?? '-' }}
                        &middot; {{ \Carbon\Carbon::parse($order->created_at)->translatedFormat('d M Y H:i') }}
                    </div>
                </div>
                <div class="r">
                    <div class="inv-total {{ $isVoid ? 'void' : '' }}">{{ $money($order->grand_total) }}</div>
                </div>
            </div>

            <table class="items">
                <thead>
                    <tr><th>Item</th><th class="qty">Qty</th><th class="price">Harga</th><th class="sub">Subtotal</th></tr>
                </thead>
                <tbody>
                    @forelse ($order->details as $d)
                        <tr>
                            <td>
                                <span class="item-name">{{ $d->menu->name ?? 'Menu dihapus' }}</span>
                                @if (!empty($d->addons))
                                    <div class="item-meta">+ {{ collect($d->addons)->map(fn ($a) => is_array($a) ? (($a['name'] ?? '') . (($a['qty'] ?? 1) > 1 ? ' ×' . $a['qty'] : '')) : $a)->filter()->implode(', ') }}</div>
                                @endif
                                @if ($d->notes)<div class="item-meta">Catatan: {{ $d->notes }}</div>@endif
                            </td>
                            <td class="qty">{{ $d->qty }}</td>
                            <td class="price">{{ $money($d->price) }}</td>
                            <td class="sub">{{ $money($d->subtotal) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="color:#9aa0ac;">Tidak ada rincian item.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if ($order->subtotal != $order->grand_total || $order->discount_amount > 0 || $order->tax > 0)
                <div class="inv-foot">
                    Subtotal {{ $money($order->subtotal) }}
                    @if ($order->discount_amount > 0)
                        &nbsp;&middot;&nbsp; <span class="disc">Diskon {{ $order->promo->name ? '(' . $order->promo->name . ') ' : '' }}- {{ $money($order->discount_amount) }}</span>
                    @endif
                    @if ($order->tax > 0)&nbsp;&middot;&nbsp; Pajak {{ $money($order->tax) }}@endif
                    &nbsp;&middot;&nbsp; <b>Total {{ $money($order->grand_total) }}</b>
                </div>
            @endif
        </div>
    @empty
        <div style="text-align:center; padding:30px; color:#9aa0ac;">Tidak ada data penjualan untuk filter ini.</div>
    @endforelse

    {{-- Grand total --}}
    <div class="grand">
        <table>
            <tr><td class="k">Total Transaksi (Nota)</td><td class="v">{{ number_format($totalOrders, 0, ',', '.') }}</td></tr>
            <tr><td class="k">Total Diskon Diberikan</td><td class="v neg">- {{ $money($totalDiscount) }}</td></tr>
            @if (($voidedCount ?? 0) > 0)
                <tr><td class="k">Pesanan Salah ({{ $voidedCount }})</td><td class="v neg">({{ $money($voidedAmount) }})</td></tr>
            @endif
            <tr><td class="k"><b>Total Pendapatan</b></td><td class="v">{{ $money($totalRevenue) }}</td></tr>
            @if (($expenseApplies ?? true))
                <tr><td class="k">Total Pengeluaran (kas tunai)</td><td class="v neg">- {{ $money($totalExpense) }}</td></tr>
            @endif
            <tr class="net"><td class="k">OMZET BERSIH</td><td class="v">{{ $money($netRevenue) }}</td></tr>
        </table>
    </div>

    <div class="footer">
        Dokumen ini dihasilkan otomatis oleh sistem Mooda POS &middot; {{ $setting->store_name ?? 'Mooda' }}
    </div>

</body>

</html>
