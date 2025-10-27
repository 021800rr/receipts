// fallback controller copy for debugging
// simply export attach function
export function attachToRow(row){
    const qty = row.querySelector('.rl-quantity');
    const price = row.querySelector('.rl-unit-price');
    const total = row.querySelector('.rl-line-total');
    if(!qty || !price || !total) return;
    const parse = v=>{ if(!v && v!==0) return 0; let s=String(v).trim().replace(/\s+/g,'').replace(/'/g,'').replace(',','.'); const n=parseFloat(s); return Number.isFinite(n)?n:0 };
    const update = ()=> total.value = (parse(qty.value)*parse(price.value)).toFixed(2);
    qty.addEventListener('input', update);
    price.addEventListener('input', update);
    update();
}
console.log('public/_assets/controllers/receipt_line_controller.js loaded');
// Generated fallback copy of assets/bootstrap.js for immediate serving
import ReceiptLineController from '../../assets/controllers/receipt_line_controller.js';
// minimal stub: Stimulus loader in production handles registration; here we attach to window for debugging
window.ReceiptLineController = ReceiptLineController;
console.log('public/_assets/bootstrap.js loaded');

