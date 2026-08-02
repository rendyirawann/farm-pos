/**
 * Penerjemah nota MOODA FARM -> format yang dimengerti MoodaPrint.
 *
 * Nota peternakan punya dua besaran (ekor & kg) yang tidak dikenal struk POS,
 * jadi keterangannya dititipkan pada baris catatan tiap item agar tetap tercetak
 * rapi di kertas 58/80mm tanpa mengubah mesin cetaknya.
 */
window.farmNota = function (d) {
    var num = function (n, dec) {
        return Number(n || 0).toLocaleString('id-ID', {
            minimumFractionDigits: dec || 0, maximumFractionDigits: dec || 0,
        });
    };

    var items = (d.items || []).map(function (it) {
        var satuan = it.basis === 'kg' ? 'kg' : (it.basis === 'butir' ? 'butir' : 'ekor');
        var jumlah = it.basis === 'kg' ? Number(it.weight_kg || 0) : Number(it.qty_ekor || 0);

        // Keterangan ekor + kg selalu ikut tercetak supaya susut bobot bisa dicek
        // dari lembar nota, bukan hanya dari layar.
        var ket = num(it.qty_ekor) + ' ekor / ' + num(it.weight_kg, 2) + ' kg';
        if (it.basis === 'butir') { ket = num(it.qty_ekor) + ' butir'; }

        return {
            name: it.name,
            qty: jumlah,
            price: Number(it.price || 0),
            subtotal: Number(it.subtotal || 0),
            addons: [],
            notes: ket + ' @ ' + satuan,
        };
    });

    var status = d.payment_status === 'paid' ? 'paid' : 'unpaid';

    return {
        store_name: (window.FARM_STORE_NAME || 'Mooda Stok'),
        invoice_no: d.invoice_no,
        queue_number: null,
        customer_name: (d.party_label || 'Pihak') + ': ' + (d.party || '-'),
        table_no: null,
        datetime: d.datetime,
        items: items,
        subtotal: Number(d.total || 0),
        discount_amount: 0,
        tax: 0,
        tax_rate: 0,
        grand_total: Number(d.total || 0),
        payment_method: status === 'paid' ? 'cash' : '-',
        payment_status: status,
        receipt_footer: d.title + (d.due_date ? '\nJatuh tempo: ' + d.due_date : '') +
            (d.notes ? '\n' + d.notes : '') + '\nTerima kasih!',
    };
};
