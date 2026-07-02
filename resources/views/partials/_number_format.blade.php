{{-- Auto-format ribuan untuk input angka (tampil 1.000, nilai ke server tetap 1000). --}}
<script>
    (function () {
        // Field yang TIDAK diformat ribuan (persen / qty / rate / dll).
        const BLOCK = ['qty', 'quantity', 'pax', 'capacity', 'discount_percent', 'tax_rate',
            'stock', 'percent', 'rate', 'year', 'month', 'day', 'age', 'rating'];

        function shouldFormat(el) {
            if (!el || el.tagName !== 'INPUT' || el.__moneyInit) return false;
            if ('noFormat' in el.dataset || el.classList.contains('js-no-format')) return false;
            if (el.classList.contains('js-money') || 'rupiah' in el.dataset) return true;
            const key = ((el.name || '') + ' ' + (el.id || '')).toLowerCase();
            if (BLOCK.some(b => key.includes(b))) return false;
            return el.type === 'number';
        }

        function format(el) {
            const start = el.selectionStart;
            const before = el.value || '';
            const raw = before.replace(/[^\d]/g, '');
            el.dataset.raw = raw;
            const formatted = raw ? Number(raw).toLocaleString('id-ID') : '';
            el.value = formatted;
            const diff = formatted.length - before.length;
            if (start != null) {
                const pos = Math.max(0, start + diff);
                try { el.setSelectionRange(pos, pos); } catch (e) {}
            }
        }

        function init(el) {
            if (el.__moneyInit) return;
            el.__moneyInit = true;
            if (el.type === 'number') { el.type = 'text'; el.setAttribute('inputmode', 'numeric'); }
            format(el);
            el.addEventListener('input', () => format(el));
        }

        function scan(root) {
            if (!root || !root.querySelectorAll) return;
            root.querySelectorAll('input').forEach(el => { if (shouldFormat(el)) init(el); });
        }

        // Bersihkan titik sebelum submit agar server menerima angka murni (mis. 1.000 -> 1000).
        document.addEventListener('submit', function (e) {
            if (!e.target || !e.target.querySelectorAll) return;
            e.target.querySelectorAll('input').forEach(el => {
                if (el.__moneyInit) el.value = (el.value || '').replace(/[^\d]/g, '');
            });
        }, true);

        // Helper global (dipakai kode POS untuk membaca nilai murni & memformat programatik).
        window.rawNum = v => Number(String(v == null ? '' : v).replace(/[^\d]/g, '') || 0);
        window.formatMoneyInput = el => { if (el) { el.__moneyInit ? format(el) : init(el); } };

        document.addEventListener('DOMContentLoaded', function () {
            scan(document);
            // Tangkap input yang ditambahkan dinamis (mis. modal edit via AJAX).
            const mo = new MutationObserver(muts => {
                muts.forEach(m => m.addedNodes.forEach(n => {
                    if (n.nodeType === 1) { if (shouldFormat(n)) init(n); scan(n); }
                }));
            });
            mo.observe(document.body, { childList: true, subtree: true });
        });
    })();
</script>
