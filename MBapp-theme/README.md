# MBapp Theme

Webapp kinézetű WordPress téma mobilra, tabletre és asztali gépre, sötét-világos témaváltóval.
Telepítés: másold a mappát a `wp-content/themes/` alá, majd aktiváld a **Megjelenés → Témák** menüben.

Részletes leírás a projekt gyökerében található `README.md` fájlban.

## Fájlszerkezet

```
MBapp-theme/
├── style.css              téma fejléc
├── functions.php          betöltő, eszközök, widgetek
├── header.php             app bar + kereső panel
├── footer.php             lábléc + lebegő menü
├── front-page.php         kezdőlap (események + friss hírek)
├── index.php / archive.php / single.php / page.php / search.php / 404.php
├── searchform.php / sidebar.php
├── inc/
│   ├── settings.php       alapértékek, olvasó/író, hatókörök
│   ├── theme-support.php  téma támogatások, menühelyek, képméretek
│   ├── template-tags.php  ikonok, márka blokk, lapozás, dock
│   ├── components.php     fejléc kép, favicon, egyedi szövegek, diavetítés
│   ├── admin.php          Megjelenés → MBapp téma szerkesztő
│   └── customizer.php     testreszabó beállítások
├── template-parts/        kártya / lista / üres állapot
└── assets/
    ├── css/app.css        teljes felület (design tokenek, sötét mód)
    ├── css/admin.css      a szerkesztő felület stílusai
    ├── js/app.js          témaváltó, kereső, dock, diavetítés
    └── js/admin.js        fülek, médiaválasztó, AJAX mentés
```

## Testreszabás

**Megjelenés → MBapp téma** – részletes szerkesztő öt füllel: Fejléc (méret, logó, viselkedés),
Fejléc kép (banner képpel, magassággal, sötétítéssel, ráírt szöveggel), Favicon (ikon és annak
megjelenése a fejlécben), Diavetítés (bejegyzésekből, ki-be kapcsolható), Egyedi szövegek
(saját blokkok a tartalom elé vagy mögé). A mentés AJAX-szal történik.

**Megjelenés → Testreszabás → MBapp felület** – akcentus színek és alapértelmezett téma élő
előnézettel. A két felület ugyanazt a beállítástárat használja, így mindig szinkronban vannak.

Menühelyek: `primary` (fő menü), `dock` (tartalék lebegő menü), `footer` (lábléc menü).
