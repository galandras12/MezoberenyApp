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
│   ├── theme-support.php  téma támogatások, menühelyek, képméretek
│   ├── template-tags.php  ikonok, márka blokk, lapozás, dock
│   └── customizer.php     testreszabó beállítások
├── template-parts/        kártya / lista / üres állapot
└── assets/
    ├── css/app.css        teljes felület (design tokenek, sötét mód)
    └── js/app.js          témaváltó, kereső, dock viselkedés
```

## Testreszabás

**Megjelenés → Testreszabás → MBapp felület**: akcentus színek, alapértelmezett téma
(rendszer/világos/sötét), mottó és kereső megjelenítése, lebegő menü viselkedése, lábléc szöveg.

Menühelyek: `primary` (fő menü), `dock` (tartalék lebegő menü), `footer` (lábléc menü).
