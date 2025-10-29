(function () {
  'use strict';

  // informacja, że plik został załadowany
  try { console.log('line-total.js loaded'); } catch (e) {}

  function parseNumber(val) {
    if (val === null || val === undefined) return NaN;
    var s = String(val).trim();
    if (s === '') return NaN;
    s = s.replace(/\s+/g, '').replace(/'/g, '');
    s = s.replace(',', '.');
    var n = parseFloat(s);
    return Number.isFinite(n) ? n : NaN;
  }

  function findOtherByIndex(target, otherName) {
    // spróbuj wyciągnąć indeks z id lub name
    if (target.id) {
      var m = target.id.match(/^Receipt_lines_(\d+)_/);
      if (m) {
        return document.getElementById('Receipt_lines_' + m[1] + '_' + otherName) || document.querySelector('[name="Receipt[lines][' + m[1] + '][' + otherName + ']"]');
      }
    }

    if (target.name) {
      // bezpieczniejsze dopasowanie indeksu ze stringu name
      var idxMatch = target.name.match(/Receipt\[lines]\[(\d+)]/);
      if (idxMatch) {
        return document.querySelector('[name="Receipt[lines[' + idxMatch[1] + '][' + otherName + ']"]') || document.getElementById('Receipt_lines_' + idxMatch[1] + '_' + otherName);
      }
    }

    return null;
  }

  function dispatchUpdatedEvent(el, data) {
    try {
      // emit multiple names for compatibility
      var names = ['receipt-line:updated', 'receipt-line.updated', 'receipt_line_updated'];
      var container = el && (el.closest && (el.closest('.receipt-line') || el.closest('.receipt-line-row') || el.closest('tr') || el.closest('.form-row') || el.closest('.form-group')));
      var targetNode = (container || el || document);
      // debug
      console.log('line-total.js: dispatching updated event on', targetNode === document ? 'document' : (container ? 'container' : el && el.id ? el.id : 'element'));
      names.forEach(function(name) {
        try {
          var ev = new CustomEvent(name, { detail: data, bubbles: true });
          targetNode.dispatchEvent(ev);
          console.log('line-total.js: dispatched', name, data);
        } catch (e) {
          // ignore individual failures
        }
      });
    } catch (e) {
      // nie przerywamy logiki jeśli CustomEvent nie działa w old browsers
    }
  }

  function handleKeyup(event) {
    var target = event.target;
    if (!target) return;

    try {
      // rozszerzona detekcja dla quantity - dopasuj dowolny indeks
      var isQuantityId = !!(target.id && /^Receipt_lines_\d+_quantity$/.test(target.id));
      var hasQuantityClass = !!(target.classList && target.classList.contains('rl-quantity'));
      var hasQuantityDataAttr = target.getAttribute && target.getAttribute('data-receipt-line-target') === 'quantity';

      // rozszerzona detekcja dla unitPrice
      var isUnitPriceId = !!(target.id && /^Receipt_lines_\d+_unitPrice$/.test(target.id));
      var hasUnitPriceClass = !!(target.classList && target.classList.contains('rl-unit-price'));
      var hasUnitPriceDataAttr = target.getAttribute && target.getAttribute('data-receipt-line-target') === 'unitPrice';

      var matched = isQuantityId || hasQuantityClass || hasQuantityDataAttr ||
          isUnitPriceId || hasUnitPriceClass || hasUnitPriceDataAttr;

      if (matched) {
        // zawsze logujemy wartość kontrolki, jak wcześniej
        console.log('line-total.js: keyup on', target.id || target.className || target.name, 'value=', target.value);

        var otherName = (isQuantityId || hasQuantityClass || hasQuantityDataAttr) ? 'unitPrice' : 'quantity';
        console.log('line-total.js: looking for other field', otherName);

        // 1) spróbuj znaleźć przez index z id/name
        var otherEl = findOtherByIndex(target, otherName);
        console.log('line-total.js: findOtherByIndex ->', otherEl && (otherEl.id || otherEl.name || otherEl.className));

        // 2) spróbuj w najbliższym logicznym kontenerze
        if (!otherEl) {
          var container = (target.closest && (target.closest('.receipt-line') || target.closest('.receipt-line-row') || target.closest('tr') || target.closest('.form-row') || target.closest('.form-group'))) || target.parentElement;
          if (container) {
            otherEl = container.querySelector('.rl-' + otherName) || container.querySelector('[data-receipt-line-target="' + otherName + '"]') || container.querySelector('#Receipt_lines_0_' + otherName);
          }
          console.log('line-total.js: container search ->', otherEl && (otherEl.id || otherEl.name || otherEl.className));
        }

        // 3) próba szersza: szukaj w najbliższym form lub w całym dokumencie, ale najpierw ogranicz do pola z tym samym rodzicem formularza
        if (!otherEl) {
          var form = target.closest && target.closest('form');
          if (form) {
            otherEl = form.querySelector('[name*="[' + otherName + ']"]') || form.querySelector('.rl-' + otherName);
          }
          console.log('line-total.js: form search ->', otherEl && (otherEl.id || otherEl.name || otherEl.className));
        }

        // 4) fallback globalny
        if (!otherEl) {
          otherEl = document.querySelector('.rl-' + otherName) || document.querySelector('[data-receipt-line-target="' + otherName + '"]') || document.getElementById('Receipt_lines_0_' + otherName);
          console.log('line-total.js: global fallback ->', otherEl && (otherEl.id || otherEl.name || otherEl.className));
        }

        // jeżeli znaleziono drugi element i nie jest pusty -> oblicz iloczyn
        if (otherEl) {
          var otherVal = (otherEl.value !== undefined && otherEl.value !== null) ? String(otherEl.value).trim() : '';
          console.log('line-total.js: otherVal=', otherVal);
          if (otherVal !== '') {
            var qtyVal = (isQuantityId || hasQuantityClass || hasQuantityDataAttr) ? target.value : otherEl.value;
            var priceVal = (isUnitPriceId || hasUnitPriceClass || hasUnitPriceDataAttr) ? target.value : otherEl.value;

            var qty = parseNumber(qtyVal);
            var price = parseNumber(priceVal);

            console.log('line-total.js: parsed -> qty=', qty, 'price=', price);

            if (!isNaN(qty) && !isNaN(price)) {
              var total = qty * price;
              console.log('quantity x unitPrice =', total);

              // znajdź pole lineTotal i wpisz wynik
              var lineTotalEl = findOtherByIndex(target, 'lineTotal');
              if (!lineTotalEl) {
                var containerForTotal = (target.closest && (target.closest('.receipt-line') || target.closest('.receipt-line-row') || target.closest('tr') || target.closest('.form-row') || target.closest('.form-group'))) || target.parentElement;
                if (containerForTotal) {
                  lineTotalEl = containerForTotal.querySelector('.rl-line-total') || containerForTotal.querySelector('[data-receipt-line-target="lineTotal"]') || containerForTotal.querySelector('#Receipt_lines_0_lineTotal');
                }
              }
              if (!lineTotalEl) {
                lineTotalEl = document.querySelector('.rl-line-total') || document.querySelector('[data-receipt-line-target="lineTotal"]') || document.getElementById('Receipt_lines_0_lineTotal');
              }

              if (lineTotalEl) {
                try {
                  // formatujemy do dwóch miejsc po przecinku
                  var formatted = (Math.round(total * 100) / 100).toFixed(2);
                  lineTotalEl.value = formatted;
                  // wyślij event input, aby inne listener'y mogły zareagować
                  try { lineTotalEl.dispatchEvent(new Event('input', { bubbles: true })); } catch (e) {}
                  console.log('line-total.js: wrote lineTotal ->', formatted, 'into', lineTotalEl.id || lineTotalEl.name || lineTotalEl.className);
                } catch (e) {
                  console.log('line-total.js: failed to write lineTotal', e);
                }
              }

              // wyemituj event z danymi - inne skrypty mogą na niego nasłuchiwać
              dispatchUpdatedEvent(otherEl || target, { quantity: qty, unitPrice: price, total: total });
            } else {
              console.log('quantity x unitPrice = NaN (parsing failed)', { qtyRaw: qtyVal, priceRaw: priceVal });
            }
          } else {
            // debug: second field empty
            console.log('line-total.js: other field exists but empty', otherName, otherEl);
          }
        } else {
          console.log('line-total.js: other field not found for', otherName);
        }
      }
    } catch (e) {
      // defensive: nie przerywać innych handlerów
      // eslint-disable-next-line no-console
      console.error('line-total.js handler error', e);
    }
  }

  // expose small helper for manual testing from console
  try {
    window.__quantity_trigger = function() {
      var q = document.querySelector('.rl-quantity') || document.getElementById('Receipt_lines_0_quantity');
      var p = document.querySelector('.rl-unit-price') || document.getElementById('Receipt_lines_0_unitPrice');
      if (!q || !p) { console.log('line-total.js: test fields not found', { q: !!q, p: !!p }); return; }
      q.value = q.value || '2';
      p.value = p.value || '5.99';
      var ev = new KeyboardEvent('keyup', { key: '1', bubbles: true });
      q.dispatchEvent(ev);
      p.dispatchEvent(ev);
    };
  } catch (e) {}

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      document.addEventListener('keyup', handleKeyup);
    });
  } else {
    document.addEventListener('keyup', handleKeyup);
  }
})();
