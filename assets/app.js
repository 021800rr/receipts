import './bootstrap.js';
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
console.log('fallback receipt-line handler active');

(function(){
    function _parseNumber(val) {
        if (val === null || val === undefined || val === '') return 0;
        let s = String(val).trim().replace(/\s+/g, '').replace(/'/g, '');
        s = s.replace(',', '.');
        const n = parseFloat(s);
        return Number.isFinite(n) ? n : 0;
    }

    function findRow(el) {
        if (!el) return null;
        let cur = el;
        while (cur && cur !== document.documentElement) {
            if (cur.id && cur.id.startsWith('Receipt_lines_')) return cur;
            if (cur.matches && (cur.matches('.ea-collection-item') || cur.matches('.collection-entry') || cur.matches('.form-row'))) return cur;
            cur = cur.parentElement;
        }
        return null;
    }

    function updateRowFromElement(el) {
        const row = findRow(el);
        if (!row) return false;
        const qtyEl = row.querySelector('.rl-quantity');
        const priceEl = row.querySelector('.rl-unit-price');
        const totalEl = row.querySelector('.rl-line-total');
        if (!qtyEl || !priceEl || !totalEl) return false;
        const qty = _parseNumber(qtyEl.value);
        const price = _parseNumber(priceEl.value);
        totalEl.value = (qty * price).toFixed(2);
        return true;
    }

    function attachListenersTo(el) {
        if (!el) return;
        if (el.dataset && el.dataset.receiptListenerAttached) return;
        const handler = () => updateRowFromElement(el);
        el.addEventListener('input', handler);
        // mark as attached
        if (el.dataset) el.dataset.receiptListenerAttached = '1';
    }

    function attachToAll() {
        document.querySelectorAll('.rl-quantity, .rl-unit-price').forEach(el => attachListenersTo(el));
    }

    function updateAllRows() {
        document.querySelectorAll('.rl-unit-price').forEach(el => updateRowFromElement(el));
        document.querySelectorAll('.rl-quantity').forEach(el => updateRowFromElement(el));
    }

    // initial attach and compute
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => { attachToAll(); updateAllRows(); });
    } else {
        attachToAll(); updateAllRows();
    }

    // observe DOM changes and bind listeners to new inputs
    const mo = new MutationObserver((mutations) => {
        mutations.forEach(m => {
            m.addedNodes && m.addedNodes.forEach(n => {
                if (!(n instanceof HTMLElement)) return;
                // if a whole row added, attach for its inputs
                n.querySelectorAll && n.querySelectorAll('.rl-quantity, .rl-unit-price').forEach(el => attachListenersTo(el));
                // if the added node itself is an input
                if (n.matches && (n.matches('.rl-quantity') || n.matches('.rl-unit-price'))) attachListenersTo(n);
            });
        });
        // also ensure we compute in case of additions
        updateAllRows();
    });
    mo.observe(document.body, { childList: true, subtree: true });

    // also hook clicks on add buttons to attempt attach shortly after
    document.addEventListener('click', (e) => {
        const el = e.target;
        if (!el) return;
        const text = (el.textContent || '').toLowerCase();
        const classes = el.className || '';
        const looksLikeAdd = /add|dodaj|nowy|new/i.test(text) || /add|collection-add|ea-add|btn-add/i.test(classes);
        if (looksLikeAdd) setTimeout(() => { attachToAll(); updateAllRows(); }, 120);
    }, {capture: true});

})();
