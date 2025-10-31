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

        // helper: show a minimal modal to let user pick existing category or type a new one
        function showCategoryChooser(defaultName) {
            return new Promise(async (resolve) => {
                // avoid creating multiple modals
                if (document.getElementById('ts-category-chooser')) {
                    resolve(null);
                    return;
                }

                const modal = document.createElement('div');
                modal.id = 'ts-category-chooser';
                modal.style.position = 'fixed';
                modal.style.left = '0';
                modal.style.top = '0';
                modal.style.right = '0';
                modal.style.bottom = '0';
                modal.style.background = 'rgba(0,0,0,0.4)';
                modal.style.zIndex = '99999';
                modal.style.display = 'flex';
                modal.style.alignItems = 'center';
                modal.style.justifyContent = 'center';

                const box = document.createElement('div');
                box.style.background = 'white';
                box.style.padding = '12px';
                box.style.borderRadius = '6px';
                box.style.minWidth = '320px';
                box.style.maxWidth = '90%';
                box.style.boxShadow = '0 6px 18px rgba(0,0,0,0.2)';

                box.innerHTML = `
                    <div style="margin-bottom:8px;font-weight:600">Wybierz kategorię lub wpisz nową</div>
                    <div style="margin-bottom:8px"><select id="ts-cat-select" style="width:100%"><option value="">-- wybierz istniejącą --</option></select></div>
                    <div style="margin-bottom:8px"><input id="ts-cat-new" placeholder="Lub wpisz nową kategorię" style="width:100%;padding:6px;border:1px solid #ccc;border-radius:4px" value="${defaultName ? defaultName.replace(/"/g,'') : ''}" /></div>
                    <div style="text-align:right"><button id="ts-cat-cancel" style="margin-right:8px;padding:6px 10px">Anuluj</button><button id="ts-cat-ok" style="padding:6px 10px">OK</button></div>
                `;

                modal.appendChild(box);
                document.body.appendChild(modal);

                const selEl = box.querySelector('#ts-cat-select');
                const newEl = box.querySelector('#ts-cat-new');
                const okBtn = box.querySelector('#ts-cat-ok');
                const cancelBtn = box.querySelector('#ts-cat-cancel');

                // load categories
                try {
                    const res = await fetch('/admin/api/categories');
                    if (res.ok) {
                        const json = await res.json();
                        const items = json.items || json;
                        items.forEach(it => {
                            const o = document.createElement('option');
                            o.value = it.id;
                            o.textContent = it.text;
                            selEl.appendChild(o);
                        });
                    }
                } catch (e) {
                    // ignore - categories may not be available
                }

                function cleanupAndResolve(result) {
                    try { modal.remove(); } catch (e) {}
                    resolve(result);
                }

                cancelBtn.addEventListener('click', function() { cleanupAndResolve(null); });
                okBtn.addEventListener('click', function() {
                    const chosenId = selEl.value || null;
                    const newName = (newEl.value || '').trim() || null;
                    if (!chosenId && !newName) {
                        // nothing chosen
                        // keep modal open but flash border
                        newEl.style.border = '1px solid #e00';
                        setTimeout(()=> newEl.style.border='1px solid #ccc', 900);
                        return;
                    }
                    cleanupAndResolve({ categoryId: chosenId, categoryName: newName });
                });

                // handle Escape
                modal.addEventListener('keydown', function(ev) {
                    if (ev.key === 'Escape') { cleanupAndResolve(null); }
                });
                // focus
                newEl.focus();
            });
        }

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
                // when user creates a new product, ask for category (choose existing or provide new)
                showCategoryChooser(input).then(choice => {
                    if (!choice) return callback(); // cancelled
                    const body = { name: input };
                    if (choice.categoryId) body.categoryId = choice.categoryId;
                    else if (choice.categoryName) body.categoryName = choice.categoryName;

                    fetch(createUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(body)
                    })
                    .then(r => r.json().then(j => ({ ok: r.ok, status: r.status, json: j })).catch(()=> ({ ok: r.ok, status: r.status })))
                    .then(resObj => {
                        if (resObj && resObj.ok && resObj.json && (resObj.status === 201 || resObj.status === 200)) {
                            const data = resObj.json;
                            callback({ id: data.id, text: data.text || input });
                        } else {
                            // show error to user
                            try { alert((resObj.json && (resObj.json.message || resObj.json.error)) || 'Błąd zapisu produktu'); } catch(e){}
                            callback();
                        }
                    })
                    .catch(()=> { callback(); });
                });
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
