DEV – Instrukcja szybkiego debugowania i naprawy assets (JS) — krótkie i na temat

Cel
- Szybko zdiagnozować i naprawić problem z ładowaniem `app.js` i live‑liczeniem pozycji w formularzu paragonu.

Checklist (po kolei)
1) Wyczyść cache Symfony
2) Hard‑refresh przeglądarki (Disable cache + Ctrl/Cmd+F5)
3) Sprawdź, czy `/ _assets/app.js` jest serwowany
4) Spróbuj dynamicznego importu modułu (cache‑buster)
5) Jeśli import się nie udaje, wstrzyknij moduł jako <script type="module">
6) Wymuś jednorazowe przeliczenie wszystkich wierszy (konsola)
7) Jeśli działa — zacommituj zmiany i usuń tymczasowe pliki public/_assets

Krótkie komendy (uruchamiaj z katalogu projektu)
- Wyczyść cache Symfony:

```bash
make console cmd="cache:clear"
```

- (opcjonalnie) sprawdź mapę assetów:

```bash
make console cmd="debug:asset-map --full"
```

Przeglądarka — DevTools → Console (wklejaj pojedynczo i Enter)
1) Opcjonalnie sprawdź HEAD pliku:

```javascript
fetch('/_assets/app.js', { method: 'HEAD' })
  .then(r => console.log('HEAD', r.status, r.headers.get('content-type')))
  .catch(e => console.error('HEAD failed', e));
```

2) Import z cache‑busterem:

```javascript
import('/_assets/app.js?ts=' + Date.now())
  .then(()=>console.log('import ok'))
  .catch(e=>console.error('import failed:', e));
```

3) Jeśli (2) zgłasza błąd — obejście przez tag script (spróbuje załadować moduł inaczej):

```javascript
(() => {
  const s = document.createElement('script');
  s.type = 'module';
  s.src = '/_assets/app.js?ts=' + Date.now();
  s.onload = () => console.log('module script loaded');
  s.onerror = (e) => console.error('module script failed', e);
  document.head.appendChild(s);
})();
```

4) Jeżeli moduł się załadował (zobaczysz w konsoli logi z pliku), wymuś jednorazowe przeliczenie wszystkich wierszy:

```javascript
document.querySelectorAll('[id^="Receipt_lines_"]').forEach(row=>{
  const q = row.querySelector('.rl-quantity');
  const p = row.querySelector('.rl-unit-price');
  const t = row.querySelector('.rl-line-total');
  if(!q || !p || !t) return;
  const num = v => {
    if (v === null || v === undefined || v === '') return 0;
    let s = String(v).trim().replace(/\s+/g,'').replace(/'/g,'').replace(',','.');
    const n = parseFloat(s);
    return Number.isFinite(n) ? n : 0;
  };
  t.value = (num(q.value) * num(p.value)).toFixed(2);
});
```

Diagnostyka (jeśli dalej nie działa, wklej te trzy liczby):
1) `document.querySelectorAll('[id^="Receipt_lines_"]').length`
2) `document.querySelectorAll('.rl-unit-price').length`
3) `document.querySelectorAll('.rl-quantity').length`

Jeżeli import z `/_assets/app.js` rzuca błąd, wklej pierwszą linię błędu (np. "TypeError: Failed to fetch dynamically imported module: ..." albo "Expected a JavaScript-or-Wasm module script but the server responded with a MIME type of \"text/css\"" ) — dam natychmiastową, konkretną naprawę.

Szybkie uwagi i naprawy które zrobiłem tymczasowo w repo
- Dodałem fallbackowy skrypt testowy pod `public/_assets/app.js` i `public/_assets/controllers/...` — to tymczasowe i pomaga w debugowaniu lokalnym (możesz je usunąć przed commitem finalnym).
- Dodałem w `assets` Stimulus controller i fallbacky JS (attach per-input, MutationObserver, click hook) — mają działać zarówno dla istniejących, jak i dynamicznie dodawanych wierszy.

Jak zapisać na stałe (jeśli wszystko działa)
1) Usuń pliki tymczasowe w `public/_assets/` (jeśli chcesz czystości):

```bash
git rm -r public/_assets
git add -A
git commit -m "assets: add fallback app.js for local debug (remove before prod)" || true
```

2) Zacommituj zmiany w `assets/app.js` i kontrolerach (jeśli chcesz zachować fallback lub przenieść do właściwego pipeline):

```bash
git add assets/app.js assets/controllers/receipt_line_controller.js src/Controller/Admin/ReceiptCrudController.php
git commit -m "feat: live calc for receipt lines (frontend+backend)"
```

3) Wyczyść cache i przetestuj na czystym: `make console cmd="cache:clear"` i hard‑refresh przeglądarkę.

Kontakt i krótkie wsparcie
- Wklej wynik importu (`import failed:`) lub trzy diagnostyczne liczby, a naprawię dalej w 1–2 krokach.

--
Plik utworzony automatycznie. Jeśli chcesz, zapiszę go w innym miejscu lub rozszerzę o kroki do deployu/commitowania. Napisz krótko: "zostaw plik" lub "usuń tymczasowe public/_assets i zrób commit".
