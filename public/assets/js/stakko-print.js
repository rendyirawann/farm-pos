/*!
 * Stakko POS — unified receipt printing engine.
 * Metode: auto | native(APK) | browser | qztray | webbluetooth | rawbt
 * Config: window.STAKKO_PRINT = { method, paper_width, store_name }
 * Referensi ESC/POS & UUID diverifikasi (58mm=32 kolom, 80mm=48 kolom;
 * BLE service 0x18F0 / char 0x2AF1; RawBT scheme rawbt:base64,<data>).
 */
window.StakkoPrint = (function () {
    'use strict';

    const CFG = window.STAKKO_PRINT || { method: 'auto', paper_width: 58, store_name: 'Stakko POS' };
    const QZ_JS = "/assets/plugins/custom/qz/qz-tray.js";
    const BLE_SERVICE = 0x18f0;
    const BLE_SERVICE_UUID = '000018f0-0000-1000-8000-00805f9b34fb';
    const BLE_WRITE_CHAR = 0x2af1;

    const ESC = 0x1b, GS = 0x1d;
    const cols = () => (Number(CFG.paper_width) >= 80 ? 48 : 32);
    const sleep = (ms) => new Promise(r => setTimeout(r, ms));
    const money = (n) => 'Rp' + Number(n || 0).toLocaleString('id-ID');

    function toast(icon, title) {
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 2500 });
    }
    function alertErr(msg) { if (window.Swal) Swal.fire('Gagal Cetak', msg, 'error'); else alert(msg); }

    // -------- helpers --------
    function b64(bytes) {
        let s = ''; const CH = 0x8000;
        for (let i = 0; i < bytes.length; i += CH) s += String.fromCharCode.apply(null, bytes.subarray(i, i + CH));
        return btoa(s);
    }
    function loadScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector('script[data-stakko="qz"]')) return resolve();
            const s = document.createElement('script');
            s.src = src; s.dataset.stakko = 'qz';
            s.onload = resolve; s.onerror = () => reject(new Error('Gagal memuat qz-tray.js'));
            document.head.appendChild(s);
        });
    }

    // -------- ESC/POS byte builder (raw) --------
    function bytesFromReceipt(r) {
        const W = cols();
        const buf = [];
        const push = (...b) => { for (const x of b) Array.isArray(x) ? buf.push(...x) : buf.push(x); };
        const enc = (s) => { for (const ch of String(s)) { const c = ch.codePointAt(0); buf.push(c <= 0xff ? c : 0x3f); } };
        const line = (s = '') => { enc(s); push(0x0a); };
        const rule = () => line('-'.repeat(W));
        const row = (l, rr) => { l = String(l); rr = String(rr); const gap = Math.max(1, W - l.length - rr.length); line(l + ' '.repeat(gap) + rr); };

        push(ESC, 0x40);            // init
        push(ESC, 0x74, 0x00);      // codepage CP437
        push(ESC, 0x61, 0x01);      // center
        push(ESC, 0x45, 0x01);      // bold on
        push(GS, 0x21, 0x11);       // double height+width
        line((r.store_name || CFG.store_name || 'Stakko POS').toUpperCase());
        push(GS, 0x21, 0x00); push(ESC, 0x45, 0x00);
        if (r.store_address) line(r.store_address);
        if (r.store_phone) line('Telp: ' + r.store_phone);
        rule();
        push(ESC, 0x61, 0x01); push(GS, 0x21, 0x01);   // center, double height
        line('No. Antrian ' + (r.queue_number ?? '-'));
        push(GS, 0x21, 0x00); push(ESC, 0x61, 0x00);
        rule();
        if (r.invoice_no) row('No', r.invoice_no);
        if (r.datetime) row('Tgl', r.datetime);
        if (r.customer_name) row('Plg', r.customer_name);
        rule();
        (r.items || []).forEach(it => {
            line(String(it.name));
            if (it.addons && it.addons.length) it.addons.forEach(a => line('  + ' + (a.name || '')));
            row('  ' + it.qty + ' x ' + money(it.price), money(it.subtotal));
            if (it.notes) line('  * ' + it.notes);
        });
        rule();
        row('Subtotal', money(r.subtotal));
        if (Number(r.discount_amount) > 0) row('Diskon', '-' + money(r.discount_amount));
        row('Pajak', money(r.tax));
        push(ESC, 0x45, 0x01); row('TOTAL', money(r.grand_total)); push(ESC, 0x45, 0x00);
        row('Metode', (r.payment_method || '-').toUpperCase());
        if (r.payment_method === 'cash' && r.cash_received != null) {
            row('Tunai', money(r.cash_received));
            row('Kembali', money(r.change_amount));
        }
        rule();
        push(ESC, 0x61, 0x01);
        line(r.payment_status === 'paid' ? '*** LUNAS ***' : '** BELUM LUNAS **');
        line('Terima kasih!');
        push(0x0a, 0x0a, 0x0a);
        push(GS, 0x56, 0x42, 0x03); // feed + partial cut (Function B; diabaikan printer tanpa cutter)
        return new Uint8Array(buf);
    }

    // -------- plain text (untuk bridge APK yang menambah ESC/POS sendiri) --------
    function plainText(r) {
        const W = cols();
        const center = (s) => { s = String(s); return s.length >= W ? s.slice(0, W) : ' '.repeat(Math.floor((W - s.length) / 2)) + s; };
        const row = (l, rr) => { l = String(l); rr = String(rr); const gap = Math.max(1, W - l.length - rr.length); return l + ' '.repeat(gap) + rr; };
        const sep = '-'.repeat(W); const o = [];
        o.push(center((r.store_name || CFG.store_name || 'Stakko POS').toUpperCase()));
        o.push(sep); o.push(center('NO. ANTRIAN ' + (r.queue_number ?? '-'))); o.push(sep);
        if (r.invoice_no) o.push(row('No', r.invoice_no));
        if (r.datetime) o.push(row('Tgl', r.datetime));
        if (r.customer_name) o.push(row('Plg', r.customer_name));
        o.push(sep);
        (r.items || []).forEach(it => {
            o.push(String(it.name));
            if (it.addons && it.addons.length) it.addons.forEach(a => o.push('  + ' + (a.name || '')));
            o.push(row('  ' + it.qty + ' x ' + money(it.price), money(it.subtotal)));
            if (it.notes) o.push('  * ' + it.notes);
        });
        o.push(sep);
        o.push(row('Subtotal', money(r.subtotal)));
        if (Number(r.discount_amount) > 0) o.push(row('Diskon', '-' + money(r.discount_amount)));
        o.push(row('Pajak', money(r.tax)));
        o.push(row('TOTAL', money(r.grand_total)));
        o.push(row('Metode', (r.payment_method || '-').toUpperCase()));
        if (r.payment_method === 'cash' && r.cash_received != null) {
            o.push(row('Tunai', money(r.cash_received))); o.push(row('Kembali', money(r.change_amount)));
        }
        o.push(sep); o.push(center(r.payment_status === 'paid' ? '*** LUNAS ***' : '** BELUM LUNAS **'));
        o.push(center('Terima kasih!'));
        return o.join('\n');
    }

    // -------- method resolution --------
    const hasNative = () => !!(window.AndroidPrinter && typeof window.AndroidPrinter.printReceipt === 'function');
    function resolveMethod() {
        let m = CFG.method || 'auto';
        if (m === 'auto') return hasNative() ? 'native' : 'browser';
        if (m === 'native' && !hasNative()) return 'browser';
        return m;
    }

    // -------- QZ Tray --------
    async function ensureQz() {
        if (!window.qz) await loadScript(QZ_JS);
        if (!window.qz) throw new Error('qz-tray.js tidak termuat.');
        if (!qz.websocket.isActive()) await qz.websocket.connect();
    }
    async function qzPrinters() { await ensureQz(); return await qz.printers.find(); }
    async function printQz(r) {
        await ensureQz();
        let printer = localStorage.getItem('stakko_qz_printer');
        if (!printer) printer = await qz.printers.getDefault();
        const cfg = qz.configs.create(printer);
        const data = [{ type: 'raw', format: 'command', flavor: 'base64', data: b64(bytesFromReceipt(r)) }];
        await qz.print(cfg, data);
    }

    // -------- Web Bluetooth --------
    let bleChar = null, bleDevice = null;
    async function discoverWritable(server) {
        const svcs = await server.getPrimaryServices();
        for (const s of svcs) {
            const chs = await s.getCharacteristics();
            for (const c of chs) if (c.properties.write || c.properties.writeWithoutResponse) return c;
        }
        throw new Error('Karakteristik tulis tidak ditemukan di printer ini.');
    }
    async function connectBle() {
        if (!navigator.bluetooth) throw new Error('Browser ini tidak mendukung Web Bluetooth (pakai Chrome/Edge).');
        const dev = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: [BLE_SERVICE, BLE_SERVICE_UUID],
        });
        const server = await dev.gatt.connect();
        let char;
        try { const svc = await server.getPrimaryService(BLE_SERVICE); char = await svc.getCharacteristic(BLE_WRITE_CHAR); }
        catch (e) { char = await discoverWritable(server); }
        bleChar = char; bleDevice = dev;
        dev.addEventListener('gattserverdisconnected', () => { bleChar = null; });
        return dev.name || 'Printer BT';
    }
    async function printBle(r) {
        if (!bleChar) {
            if (bleDevice) { const s = await bleDevice.gatt.connect(); bleChar = await discoverWritable(s).catch(() => null); }
            if (!bleChar) throw new Error('Printer Bluetooth belum terhubung. Klik "Hubungkan Printer BT" dulu.');
        }
        const bytes = bytesFromReceipt(r);
        for (let i = 0; i < bytes.length; i += 180) {
            const chunk = bytes.slice(i, i + 180);
            if (bleChar.writeValueWithoutResponse) await bleChar.writeValueWithoutResponse(chunk);
            else await bleChar.writeValue(chunk);
            await sleep(20);
        }
    }

    // -------- RawBT (Android) --------
    function printRawbt(r) {
        window.location.href = 'rawbt:base64,' + b64(bytesFromReceipt(r));
    }

    // -------- Browser / OS dialog --------
    function printBrowser(printUrl) {
        if (printUrl) window.open(printUrl, '_blank'); else window.print();
    }

    // -------- public: print --------
    function print(receipt, printUrl) {
        const m = resolveMethod();
        try {
            if (m === 'native') { window.AndroidPrinter.printReceipt(plainText(receipt)); return; }
            if (m === 'qztray') { printQz(receipt).catch(e => alertErr('QZ Tray: ' + e.message + '. Pastikan aplikasi QZ Tray berjalan.')); return; }
            if (m === 'webbluetooth') { printBle(receipt).catch(e => alertErr(e.message)); return; }
            if (m === 'rawbt') { printRawbt(receipt); return; }
            printBrowser(printUrl);
        } catch (e) { printBrowser(printUrl); }
    }

    // -------- public: quickConnect (dipakai tombol di Kasir/Setelan) --------
    function needsButton() { return ['native', 'webbluetooth', 'qztray'].includes(resolveMethod()); }
    function buttonLabel() {
        const m = resolveMethod();
        if (m === 'webbluetooth') return 'Hubungkan Printer BT';
        if (m === 'qztray') return 'Pilih Printer';
        return 'Printer';
    }
    async function quickConnect() {
        const m = resolveMethod();
        try {
            if (m === 'native') {
                let list = []; try { list = JSON.parse(window.AndroidPrinter.getPrinters() || '[]'); } catch (e) {}
                if (!list.length) return Swal.fire('Belum ada printer', 'Pasangkan printer Bluetooth di Setelan Android, lalu coba lagi.', 'info');
                const opts = {}; list.forEach(p => opts[p.address] = p.name);
                const res = await Swal.fire({ title: 'Pilih Printer', input: 'select', inputOptions: opts, showCancelButton: true });
                if (res.isConfirmed && res.value) { window.AndroidPrinter.setPrinter(res.value); toast('success', 'Printer dipilih'); }
            } else if (m === 'webbluetooth') {
                const name = await connectBle(); toast('success', 'Terhubung: ' + name);
            } else if (m === 'qztray') {
                const printers = await qzPrinters();
                if (!printers || !printers.length) return Swal.fire('Tidak ada printer', 'QZ Tray tidak menemukan printer terpasang.', 'info');
                const opts = {}; printers.forEach(p => opts[p] = p);
                const res = await Swal.fire({ title: 'Pilih Printer (QZ Tray)', input: 'select', inputOptions: opts, showCancelButton: true });
                if (res.isConfirmed && res.value) { localStorage.setItem('stakko_qz_printer', res.value); toast('success', 'Printer disimpan'); }
            }
        } catch (e) { alertErr(e.message); }
    }

    // -------- public: test print --------
    function test() {
        const sample = {
            store_name: CFG.store_name, invoice_no: 'TEST-0001', queue_number: 1, customer_name: 'Uji Cetak',
            datetime: new Date().toLocaleString('id-ID'),
            items: [
                { name: 'Kopi Susu', qty: 2, price: 18000, subtotal: 36000, addons: [{ name: 'Extra Shot' }] },
                { name: 'Roti Bakar', qty: 1, price: 15000, subtotal: 15000 },
            ],
            subtotal: 51000, discount_amount: 0, tax: 5100, grand_total: 56100,
            payment_method: 'cash', payment_status: 'paid', cash_received: 60000, change_amount: 3900,
        };
        const m = resolveMethod();
        if (m === 'browser') return Swal.fire('Metode Browser', 'Mode ini mencetak lewat dialog print OS saat Anda menekan Cetak Struk pada transaksi. Pastikan printer thermal terpasang sebagai printer OS & set sebagai default.', 'info');
        print(sample, null);
    }

    return { print, quickConnect, needsButton, buttonLabel, test, cols, resolveMethod, hasNative, connectBle };
})();
