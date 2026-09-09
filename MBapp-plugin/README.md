# MBapp Plugin

Automatikus hírbeolvasó naplózással, shortcode alapú eseménykezelő visszaszámlálással,
és admin felületről állítható lebegő menü.
Telepítés: másold a mappát a `wp-content/plugins/` alá, majd aktiváld a **Bővítmények** menüben.

Részletes leírás a projekt gyökerében található `README.md` fájlban.

## Fájlszerkezet

```
MBapp-plugin/
├── mbapp-plugin.php                   fő fájl, betöltő
├── uninstall.php                      eltávolítás (csak kérésre töröl)
├── includes/
│   ├── helpers.php                    ikonok, forrás gomb, dátum segédek
│   ├── class-mbapp-settings.php       beállítások és alapértékek
│   ├── class-mbapp-install.php        adatbázis, „Események” oldal
│   ├── class-mbapp-logger.php         import napló (saját tábla)
│   ├── class-mbapp-post-types.php     Hírek / Események bejegyzéstípusok
│   ├── class-mbapp-selector.php       CSS → XPath fordító
│   ├── class-mbapp-source-detector.php  forrás diagnosztika, szelektor javaslat, JSON-LD
│   ├── class-mbapp-news-importer.php  RSS és HTML beolvasó
│   ├── class-mbapp-events.php         esemény mezők, lekérdezések, takarítás
│   ├── class-mbapp-shortcodes.php     [mbapp_events], [mbapp_news], [mbapp_source]
│   ├── class-mbapp-front-page.php     testreszabható kezdőlap (blokkok)
│   ├── class-mbapp-floating-menu.php  lebegő menü megjelenítése
│   ├── class-mbapp-ajax.php           „További” gomb, próbalekérés
│   ├── class-mbapp-cron.php           ütemezések
│   ├── class-mbapp-admin.php          admin oldalak
│   └── class-mbapp-log-table.php      napló táblázat
├── templates/                         event-card.php, news-card.php
└── assets/                            css/js (frontend + admin)
```

## Admin menü

* **MBapp → Áttekintés** – állapot, utolsó és következő futás, kézi import, shortcode súgó
* **MBapp → Kezdőlap** – a főoldal blokkjai: fejléc kép, hírek, események, saját HTML
* **MBapp → Hírek / Események** – a tartalmak kezelése
* **MBapp → Hírbeolvasó** – forrás, szerkezetfelismerés, szelektorok, próbalekérés, import beállítások
* **MBapp → Import napló** – naplózott futások, szűrés, keresés, törlés
* **MBapp → Esemény beállítások** – elrendezés (lista/rács), 25-ös lapméret, 7 napos türelmi idő, forrás
* **MBapp → Lebegő menü** – gombok, ikonok, URL-ek, pozíció, stílus

## Horgok

```php
// Egy hír importálása után fut.
do_action( 'mbapp_news_imported', $post_id, $item );
```
