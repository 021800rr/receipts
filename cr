
Szczegółowa lista braków / niedokończeń i sugestie napraw (posortowane wg priorytetu)

Krytyczne / Wysoki priorytet (wpływa na wymagania funkcjonalne)

UI: brak formatowania waluty i nieczytelne jednostki (grosze wyświetlane bez zł)
Gdzie: templates/reports/index.html.twig (pokazuje grosze bez formatu).
Problem: Wymaganie „PLN; w bazie grosze; w UI ładne formatowanie (z przecinkiem i 'zł')”.
Sugestia: dodać serwis/formater kwot lub użyć twig extension (np. money_format custom) i w szablonie używać np. {{ sum|money }} albo helpera do zamiany grosze->zł z 2 miejscami po przecinku i sufiksem "zł". Przy okazji przeliczyć sumy z ReportService (który zwraca int grosze) do zł w szablonie.
Priorytet: wysoki (UX/requirements).

    Raporty: brak filtrów dla sklepu/kategorii/produktu oraz brak porównań gospodarstw
    Gdzie: ReportService.php, ReportController.php, templates/reports/index.html.twig
    Problem: wymagania raportów mówią o filtrach: date range, household, store, category, product; a także o porównaniu Dom A vs Dom B. Obecna implementacja obsługuje tylko from/to/household.
    Sugestia:
    Rozszerzyć filtr helpera w ReportService::filters aby przyjmował parametry store, category, product i dołączał je do WHERE oraz wiązał parametry.
    Dodać metodę compareHouseholds(<span>from,</span>to, <span>store?,</span>category?) która zwróci sumy per household (hoping to support "Dom A vs Dom B").
    Rozszerzyć ReportController i szablon, by umożliwić wybór sklepu/kategorii/produktu (selecty/autocomplete), oraz pokazać porównanie w tabeli.
    Priorytet: wysoki (funkcjonalność raportów).

    ReportService: nieobsługiwane typy/bezpieczne bindowanie parametrów w byProductTop
    Gdzie: src/Service/ReportService.php byProductTop
    Problem: prepare + manual bindValue — obecna metoda binduje wartości bez typów (może ok) ale jeżeli gdzieś występują UUID-y konieczne typy/walidacja. Dodatkowo w sumByPeriod return nadal grosze (ok) ale nazwy zmiennych niejasne.
    Sugestia:
    Ujednolicić użycie DBAL: fetchAllAssociative z parametrami lub użyć prepared statements poprawnie, z prefixami :from, :to itd.
    Sanityzacja limit (już używa PARAM_INT — ok).
    Priorytet: średni.

    Funkcjonalne / Średni priorytet
    Szablon / dostępność: nie pokazuje nazw sklepów/kategorii/produktów (zwracane kolumny to identyfikatory)
    Gdzie: templates/reports/index.html.twig oraz ReportService zwraca category_id/store_id/product_id i sumy.
    Problem: Użytkownik nie chce widzieć UUID; w UI powinniśmy zamapować ID -> nazwa.
    Sugestia: ReportService może JOINować do słowników albo ReportController może użyć repo aby pobrać nazwy (mapa id->name) i przekazać je do szablonu. Dla wydajności najlepiej dopisać LEFT JOINy w zapytaniach agregujących albo przygotować małe helpery repo lookup.
    Priorytet: średni.

Quick-add inline (dodawanie sklepu/produktu w locie)
Gdzie: formularz Receipt (EasyAdmin ReceiptCrudController + ReceiptLineType)
Problem: spec wymaga quick-add inline — aktualnie stosowane EntityType nie ma mechanizmu quick-add.
Sugestia (opcje):
Implementacja prostego JS + modal form: obok pola EntityType dodać przycisk „+” otwierający modal, submituje AJAX do endpointu tworzącego sklep/produkt, a po powodzeniu aktualizuje select (można użyć Select2/choicesjs). To wymaga JS i endpointów (kontrollerów API) — to większa zmiana.
Minimalny MVP: dodać link „Dodaj sklep”/„Dodaj produkt” który otwiera nową kartę z formularzem (mniej wygodne).
Priorytet: niski-średni (zależy od potrzeb).

Migrations / widok: kilka migracji modyfikujących typy UUID — wydają się autogenowane, ale warto upewnić się, że są spójne.
Gdzie: migrations/*.php
Problem: Version20251027065400 zawiera operacje ALTER TYPE i tworzenie messenger_messages (autogen) — to może mieszać z oczekiwanym migracją init. Dobrze byłoby uporządkować migracje: jednoźródłowa inicjalna migracja zawierająca całość.
Sugestia: skonsolidować migracje lub dodać komentarz/TODO. Upewnić się, że migracje tworzą dokładnie tabele i view w porządku.
Priorytet: średni.

Nisko priorytetowe / kosmetyczne

Templates: brak tłumaczeń/treści po polsku w niektórych miejscach (większość jest po polsku), ujednolicić.
Tests: brak testów integracyjnych dla raportów. Dodać prosty test smoke: Request do /reports (GET) i assert 200 oraz struktura danych przekazana do twig (można mockować DBAL lub uruchomić testy z sqlite memory i migracjami).
README: jeśli nie kompletny, dodać instrukcje uruchomienia (make up, composer install, migrate). (Zgodnie z promptem — sprawdź czy README istnieje; jeśli nie, dodać).
Mapowanie braków na pliki (gdzie szukać / co zmienić)
Formatowanie pieniędzy:
Files: templates/reports/index.html.twig, templates/base.html.twig (można dodać globalny twig filter)
Add: src/Twig/MoneyExtension.php (Twig extension filter money), service config (autoconfigure).
Use: {{ sum|money }} w szablonie.
Rozszerzenie filtrów raportu:
Files: src/Service/ReportService.php — zmodyfikować private filters() aby przyjmowała store/category/product, aktualizować zapytania SQL.
Files: src/Controller/ReportController.php — pobrać dodatkowe parametry z Request i przekazywać do serwisu; ewentualnie przekazać listy sklepów/kategorii do widoku (do selectów).
Files: templates/reports/index.html.twig — dodać selecty dla sklepów/kategorii/produktów (z optionami) + submit.
CompareHouseholds:
Files: src/Service/ReportService.php — dodać compareHouseholds(<span>from,</span>to,<span>store,</span>category,$product) zwracającą agregację po household_id.
Files: templates/reports/index.html.twig — sekcja porównania.
Mapowanie ID -> nazwa:
Files: src/Repository/* lub w ReportService dodać LEFT JOIN do category/store/product aby zwracać name zamiast id. Najprościej: w byCategory/byStore/byProductTop zamiast group by id, do SELECT c.name AS label, SUM(amount_grosze) ... JOIN category c ON c.id = category_id. (Uwaga: group by c.name zamiast id — jeśli nazwy są nieunikalne to problem, ale nazwy maja constraint unique).
Quick-add inline:
Files: templates/admin forms and resources: Add JS + modal + API endpoints in Admin controllers (ReceiptCrudController or separate API controllers).
This is non-trivial: propose as TODO and minimal link fallback.
Tests:
Files: tests/Controller/ReportControllerTest.php — simple test requesting /reports and asserting 200.
Konkretnie — przykładowe, bezpieczne zmiany które proponuję w pierwszej iteracji
Dodać Twig money filter i użyć go w templates/reports/index.html.twig (UX szybkie do wdrożenia, niskie ryzyko).
Efekt: sumy będą wyświetlane np. "12,34 zł".
Rozszerzyć ReportService::filters o opcjonalne parametry store/category/product (zmiana niewielka).
Efekt: raporty będą mogły być filtrowane. Należy wtedy zaktualizować ReportController i szablon (formularz) by przesyłać te parametry.
Dodać compareHouseholds w ReportService (prostą agregację GROUP BY household_id).
Efekt: możliwość porównania Dom A vs Dom B.
Zmienić byCategory/byStore/byProductTop aby zwracały nazwy zamiast UUID (dodając JOINy do category/store/product).
Przykładowe konkrety techniczne (co wprowadzić)
MoneyExtension:
src/Twig/MoneyExtension.php:
public function money(int <span>grosze): string {</span>zloty = <span>grosze/100; return number_format(</span>zloty, 2, ',', ' ') . ' zł'; }
Register as twig extension via services.yaml autoconfigure (default in Symfony).
ReportService::filters signature now: filters(?string <span>from, ?string</span>to, ?string <span>household, ?string</span>store=null, ?string <span>category=null, ?string</span>product=null): array
Add to WHERE and parameter binding.
byCategory SQL:
SELECT c.name AS category, SUM(amount_grosze) AS sum FROM report_spend rs JOIN category c ON c.id = rs.category_id $w GROUP BY c.name ORDER BY sum DESC
compareHouseholds:
SELECT household_id, SUM(amount_grosze) AS sum FROM report_spend $w GROUP BY household_id ORDER BY sum DESC
Controller changes: accept additional query params and pass to service; also fetch option lists:
In ReportController::index get all households/stores/categories/products for select options (via EntityManager repositories or DBAL queries).
Template changes:
Change displayed headers from id to name (use returned label fields).
Use money filter for sums.
Requirements coverage mapping (quick):
Paragony model: Done (entities + migration) — Done.
Widok report_spend: Done in migrations — Done.
Service raportowy: Partially done (has core methods but missing compareHouseholds and extended filters) — Partial.
UI formatting money: Not done — ToDo.
Filters (store/category/product): Not done — ToDo.
Quick-add inline: Not implemented — ToDo.
Tests: minimal or none — ToDo.

Ryzyka i uwagi

Zmiany w migracjach: migracje już istnieją i działają. Zmiany w migracjach powinny być robione ostrożnie. Lepiej dodać oddzielne migracje dla poprawek, jeśli są potrzebne.
JOINy w raportach: dołączenie tabel słowników (category, store, product) do zapytań agregujących może minimalnie obciążyć DB, ale przy lokalnej skali jest to ok. Dla większych datasetów warto dodać cache/własne summary tables.
Quick-add inline: wymaga JS i endpointów; proponuję odłożyć do kolejnej iteracji lub dodać prostą implementację z modalem i minimalnym API.
Konkretny plan naprawy (sprint 1 — najmniejszy przyrost wartości)
Dodać Money twig filter i użyć go w templates/reports/index.html.twig (poziom: 15-30 minut).
Rozszerzyć ReportService::filters o store/category/product i zaktualizować ReportController + template form (poziom: 30-60 minut).
Zmienić byCategory/byStore/byProductTop aby zwracały nazwy (JOIN) i dodać compareHouseholds (15-30 minut).
Dodać prosty test: tests/Controller/ReportControllerTest.php żądanie GET /reports, assert 200 (15 minut).
Jeśli chcesz, mogę teraz:
wprowadzić zmiany nr 1 (dodanie MoneyExtension + aktualizacja template) i uruchomić sprawdzenie błędów (get_errors) — bezpieczna, mała zmiana;
lub wprowadzić też zmiany nr 2 i 3 (więcej edycji).
Powiedz które zmiany chcesz, żebym wprowadził teraz — proponuję zacząć od money formatting + drobne rozszerzenie filtrów (store/category/product) bo to szybko poprawi UX i funkcjonalność raportów.
