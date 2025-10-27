// public/_assets/app.js — self-contained fallback module for quick local testing
console.log('public/_assets/app.js loaded');

// inject stylesheet (so we don't import CSS as a module)
(function(){
    if (!document.querySelector('link[data-asset="app-css"]')) {
        const l = document.createElement('link');
        l.rel = 'stylesheet';
        l.href = '/_assets/styles/app.css';
        l.setAttribute('data-asset', 'app-css');
        document.head.appendChild(l);
    }
})();

// Fallback live calculation (attach per-input listeners, observe additions)
(function(){
    function parseNumber(val){
        if (val === null || val === undefined || val === '') return 0;
        let s = String(val).trim().replace(/\s+/g,'').replace(/'/g,'');
        s = s.replace(',', '.');
        const n = parseFloat(s);
        return Number.isFinite(n) ? n : 0;
    }

    function findRow(el){
        if (!el) return null;
        let cur = el;
        while (cur && cur !== document.documentElement){
            if (cur.id && cur.id.startsWith('Receipt_lines_')) return cur;
            if (cur.matches && (cur.matches('.ea-collection-item') || cur.matches('.collection-entry') || cur.matches('.form-row'))) return cur;
            try{ if (cur.querySelector && cur.querySelector('.rl-quantity') && cur.querySelector('.rl-line-total')) return cur; }catch(e){}
            cur = cur.parentElement;
        }
        return null;
    }

    function updateRow(row){
        if(!row) return false;
        const q = row.querySelector('.rl-quantity');
        const p = row.querySelector('.rl-unit-price');
        const t = row.querySelector('.rl-line-total');
        if(!q || !p || !t) return false;
        t.value = (parseNumber(q.value) * parseNumber(p.value)).toFixed(2);
        return true;
    }

    function attachInput(el){
        if(!el) return;
        if (el.dataset && el.dataset._receiptBind) return;
        const handler = ()=>{ const row = findRow(el); updateRow(row); };
        el.addEventListener('input', handler);
        el.addEventListener('keyup', handler);
        el.addEventListener('blur', handler);
        el.addEventListener('focusout', handler);
        el.addEventListener('change', handler);
        el.addEventListener('paste', ()=> setTimeout(handler,50));
        if (el.dataset) el.dataset._receiptBind = '1';
    }

    function attachAll(){
        document.querySelectorAll('.rl-quantity, .rl-unit-price').forEach(el => attachInput(el));
    }

    function updateAll(){
        const rows = Array.from(document.querySelectorAll('[id^="Receipt_lines_"]'));
        if (rows.length){ rows.forEach(r=>updateRow(r)); return; }
        document.querySelectorAll('.rl-unit-price').forEach(p=>{ const r = findRow(p); updateRow(r); });
    }

    if (document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', ()=>{ attachAll(); updateAll(); });
    } else {
        attachAll(); updateAll();
    }

    const mo = new MutationObserver(muts => { muts.forEach(m=>{ m.addedNodes && m.addedNodes.forEach(n=>{ if(!(n instanceof HTMLElement)) return; n.querySelectorAll && n.querySelectorAll('.rl-quantity, .rl-unit-price').forEach(attachInput); if (n.matches && (n.matches('.rl-quantity')||n.matches('.rl-unit-price'))) attachInput(n); }); }); updateAll(); });
    mo.observe(document.body, { childList: true, subtree: true });

    document.addEventListener('click', e=>{ const el=e.target; if(!el) return; const text=(el.textContent||'').toLowerCase(); const classes=el.className||''; const looksLikeAdd=/add|dodaj|nowy|new/i.test(text)||/add|collection-add|ea-add|btn-add/i.test(classes); if(looksLikeAdd) setTimeout(()=>{ attachAll(); updateAll(); }, 120); }, {capture:true});

    console.log('public/_assets/app.js: receipt-line fallback initialized');
})();
