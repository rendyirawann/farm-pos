<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk {{ $order->invoice_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; color: #000; width: 58mm; padding: 6px; }
        .c { text-align: center; } .r { text-align: right; } .b { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 1px 0; }
        .sm { font-size: 10px; }
        @media print { .noprint { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <div class="c b" style="font-size:14px;">{{ $tenant->name ?? 'Mooda Laundry' }}</div>
    <div class="c sm">Laundry POS — Mooda</div>
    <div class="line"></div>
    <table class="sm">
        <tr><td>Nota</td><td class="r b">{{ $order->invoice_no }}</td></tr>
        <tr><td>Tgl</td><td class="r">{{ $order->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Pelanggan</td><td class="r">{{ $order->customer_name }}</td></tr>
        @if ($order->customer_phone)<tr><td>HP</td><td class="r">{{ $order->customer_phone }}</td></tr>@endif
    </table>
    <div class="line"></div>
    <table>
        @foreach ($order->items as $it)
            <tr class="b"><td colspan="2">{{ $it->service_name }}</td></tr>
            <tr class="sm">
                <td>{{ rtrim(rtrim(number_format($it->qty, 2, ',', '.'), '0'), ',') }} {{ $it->unit }} × {{ number_format($it->price, 0, ',', '.') }}</td>
                <td class="r">{{ number_format($it->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if ($it->item_condition)<tr class="sm"><td colspan="2">* {{ $it->item_condition }}</td></tr>@endif
        @endforeach
    </table>
    <div class="line"></div>
    <table>
        <tr><td>Subtotal</td><td class="r">{{ number_format($order->subtotal, 0, ',', '.') }}</td></tr>
        @if ($order->discount_amount > 0)<tr><td>Diskon</td><td class="r">-{{ number_format($order->discount_amount, 0, ',', '.') }}</td></tr>@endif
        @if ($order->delivery_fee > 0)<tr><td>Ongkir</td><td class="r">{{ number_format($order->delivery_fee, 0, ',', '.') }}</td></tr>@endif
        <tr class="b" style="font-size:13px;"><td>TOTAL</td><td class="r">{{ number_format($order->grand_total, 0, ',', '.') }}</td></tr>
        <tr class="sm"><td>Bayar</td><td class="r">{{ $order->payment_status === 'paid' ? 'LUNAS (' . strtoupper($order->payment_method ?? 'cash') . ')' : 'BELUM BAYAR' }}</td></tr>
        @if ($order->payment_status === 'paid' && $order->cash_received)
            <tr class="sm"><td>Tunai</td><td class="r">{{ number_format($order->cash_received, 0, ',', '.') }}</td></tr>
            <tr class="sm"><td>Kembali</td><td class="r">{{ number_format($order->cash_change, 0, ',', '.') }}</td></tr>
        @endif
    </table>
    <div class="line"></div>
    <div class="sm">Estimasi selesai:<br><b>{{ $order->estimated_completed_at ? $order->estimated_completed_at->translatedFormat('d M Y H:i') : '-' }}</b></div>
    <div class="line"></div>
    <div class="c sm">Terima kasih 🙏<br>Simpan struk ini sbg bukti pengambilan.</div>
    <div class="noprint c" style="margin-top:10px;">
        <button onclick="window.print()">Cetak</button> <button onclick="window.close()">Tutup</button>
    </div>
</body>
</html>
