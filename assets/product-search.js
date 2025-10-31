console.log('product-search.js loaded');

function whenTomSelectReady(callback, attempts = 0) {
    if (typeof TomSelect !== 'undefined') return callback();
    if (attempts > 40) {
        console.error('product-search.js: TomSelect not available after waiting');
        return;
    }
    setTimeout(function() { whenTomSelectReady(callback, attempts + 1); }, 50);
}

whenTomSelectReady(function() {
    // Product select (Tom Select) — init + dynamic observer
    function initProductSelect(sel) {
        if (!sel || sel.dataset._tomInit) return;
        sel.dataset._tomInit = '1';
        const searchUrl = sel.dataset.searchUrl || '/admin/api/products';
        const createUrl = sel.dataset.createUrl || '/admin/api/products';

        const tsOptions = {
            valueField: 'id',
            labelField: 'text',
            searchField: 'text',
            maxOptions: 50,
            load: function(query, callback) {
                if (!query.length) return callback();
                fetch(searchUrl + '?term=' + encodeURIComponent(query))
                    .then(res => { if (!res.ok) return callback(); return res.json(); })
                    .then(json => callback(json.items || json))
                    .catch(()=> callback());
            },
            create: function(input, callback) {
                fetch(createUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: input })
                })
                    .then(r => { if (!r.ok) return callback(); return r.json(); })
                    .then(data => { if (data && data.id) callback({ id: data.id, text: data.text || input }); else callback(); })
                    .catch(()=> callback());
            }
        };

        try {
            // eslint-disable-next-line no-undef
            new TomSelect(sel, tsOptions);
        } catch (e) {
            // ignore init errors silently in production-like behavior
        }
    }

    function initAllProductSelects() {
        var sels = document.querySelectorAll('select.js-product-select');
        sels.forEach(initProductSelect);
    }

    // attach a single MutationObserver on body to pick up dynamic inserts
    function setupObserver() {
        const root = document.body;
        const mo = new MutationObserver((muts) => {
            for (const m of muts) {
                if (m.addedNodes && m.addedNodes.length) {
                    setTimeout(initAllProductSelects, 50);
                    break;
                }
                if (m.type === 'attributes' && m.target && m.target.classList && m.target.classList.contains('js-product-select')) {
                    setTimeout(function(){ initProductSelect(m.target); }, 50);
                }
            }
        });
        mo.observe(root, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
    }

    // init on turbo events
    try {
        window.addEventListener('turbo:load', function() { setTimeout(initAllProductSelects, 20); });
        window.addEventListener('turbo:render', function() { setTimeout(initAllProductSelects, 20); });
    } catch(e) {}

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initAllProductSelects();
            setupObserver();
        });
    } else {
        initAllProductSelects();
        setupObserver();
    }
});
