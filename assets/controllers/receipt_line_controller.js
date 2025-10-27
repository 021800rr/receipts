import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.onInput = this.onInput.bind(this);
        this.element.addEventListener('input', this.onInput);
        // compute for existing rows
        this.updateAllRows();
    }

    disconnect() {
        this.element.removeEventListener('input', this.onInput);
    }

    _parseNumber(val) {
        if (val === null || val === undefined || val === '') return 0;
        // remove spaces and apostrophes used as thousand separators
        let s = String(val).trim().replace(/\s+/g, '').replace(/'/g, '');
        // replace comma with dot
        s = s.replace(',', '.');
        const n = parseFloat(s);
        return Number.isFinite(n) ? n : 0;
    }

    onInput(e) {
        const target = e.target;
        const row = this._findRowForElement(target);
        if (!row) return;

        const qtyEl = row.querySelector('.rl-quantity');
        const priceEl = row.querySelector('.rl-unit-price');
        const totalEl = row.querySelector('.rl-line-total');
        if (!qtyEl || !priceEl || !totalEl) return;

        const qty = this._parseNumber(qtyEl.value);
        const price = this._parseNumber(priceEl.value);
        totalEl.value = (qty * price).toFixed(2);
    }

    _findRowForElement(el) {
        if (!el) return null;
        // climb up looking for an element with id like Receipt_lines_0, Receipt_lines_1, etc.
        let cur = el;
        while (cur && cur !== document.documentElement) {
            if (cur.id && cur.id.startsWith('Receipt_lines_')) return cur;
            if (cur.matches && (cur.matches('.ea-collection-item') || cur.matches('.collection-entry') || cur.matches('.form-row'))) return cur;
            cur = cur.parentElement;
        }
        return null;
    }

    updateAllRows() {
        // find all unit price inputs in this collection and compute their rows
        const priceEls = this.element.querySelectorAll('.rl-unit-price');
        priceEls.forEach(priceEl => {
            const row = this._findRowForElement(priceEl);
            if (!row) return;
            const qtyEl = row.querySelector('.rl-quantity');
            const totalEl = row.querySelector('.rl-line-total');
            if (!qtyEl || !totalEl) return;
            const qty = this._parseNumber(qtyEl.value);
            const price = this._parseNumber(priceEl.value);
            totalEl.value = (qty * price).toFixed(2);
        });
    }
}
