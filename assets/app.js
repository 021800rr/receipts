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

})();
