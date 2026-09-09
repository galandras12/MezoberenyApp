# MezőberényApp – WordPress téma és bővítmény

Két önálló, de együttműködő WordPress csomag egy alkalmazás-jellegű városi webapphoz:

| Mappa | Mi ez | Telepítés helye |
|---|---|---|
| `MBapp-theme/` | Webapp kinézetű téma sötét/világos kapcsolóval | `wp-content/themes/MBapp-theme/` |
| `MBapp-plugin/` | Hírbeolvasó + eseménykezelő + lebegő menü | `wp-content/plugins/MBapp-plugin/` |

> **Megjegyzés:** a kéréshez csatolt képernyőkép nem érkezett meg, ezért a felület egy általános
> mobilalkalmazás-mintát követ (felső app bar, kártyás tartalom, alul lebegő menü). A színek,
> az ikonok, a menüpontok és az elrendezés admin felületről szabadon átállíthatók.

---

## Telepítés

1. Másold a két mappát a fenti helyekre (vagy tömörítsd ZIP-be, és töltsd fel a WordPress admin felületén).
2. **Megjelenés → Témák** → *MBapp Theme* aktiválása.
3. **Bővítmények** → *MBapp Plugin* aktiválása.
   Aktiváláskor létrejön az „Események” oldal a `[mbapp_events]` shortcode-dal, elindul a hírbeolvasó
   ütemezése és elkészül a napló adatbázistáblája.
4. **MBapp → Hírbeolvasó**: állítsd be a forrást, majd a *Próbalekérés* gombbal ellenőrizd,
   hogy a beolvasó megtalálja-e a híreket.
5. **MBapp → Lebegő menü**: állítsd be a gombokat, ikonokat és az URL-eket.

Igény szerint a **Beállítások → Olvasás** menüben a kezdőlapot állítsd statikus oldalra – a téma
kezdőlapja ilyenkor is kiteszi alá a közelgő eseményeket és a friss híreket.

---

## MBapp Theme

* **Reszponzív, app-szerű felület**: mobil (1 oszlop), tablet (2 oszlop), asztali gép (3 oszlop + oldalsáv).
* **Sötét / világos téma kapcsoló** az app bar-ban és a láblécben.
  A választás `localStorage`-ban marad meg, alapból a rendszerbeállítást követi, és villogásmentesen tölt be.
* **Lebegő menü (dock)**: a bővítmény tölti fel tartalommal; ha az nincs bekapcsolva, a `dock`
  menühelyre kötött WordPress menüből épül tartalék változat.
* **Testreszabó** (Megjelenés → Testreszabás → *MBapp felület*): akcentus színek, alapértelmezett téma,
  mottó és kereső megjelenítése, lábléc szöveg.
* Sablonok: `index`, `front-page`, `single`, `page`, `archive`, `search`, `404`, plus `template-parts/`.
* Menühelyek: `primary`, `dock`, `footer`. Widget területek: `sidebar-1`, `footer-1`.

A bővítmény kártyasablonjai felülírhatók a témából: hozz létre egy `mbapp/` mappát a témán belül,
és tedd bele az `event-card.php` vagy `news-card.php` másolatát.

---

## MBapp Plugin

### 1. Automatikus hírbeolvasó

* Forrás: tetszőleges URL (alapértelmezés: `https://mezobereny.hu/s/hirek`).
* **Automatikus felismerés**: először RSS/Atom csatornát keres (a HTML `<link rel="alternate">`
  fejlécét is megnézi), és csak ha nincs, akkor olvassa ki a HTML-t CSS szelektorokkal.
* Beolvassa a **címet, szöveget, képet és dátumot**; a képet letölti a médiatárba, és kiemelt képnek állítja.
* A cikk saját oldaláról – ha kéred – letölti a **teljes szöveget** (`og:image`,
  `article:published_time` és a megadott tartalom-szelektor alapján).
* A tartalom végére beszúrja a forrás gombot:
  *„Pontos részleteket a Mezobereny.hu oldalon olvashatod”* (a felirat szerkeszthető).
* **Duplikátumszűrés** forrás-URL, GUID és cím alapján – ugyanaz a hír nem kerül be kétszer.
* Ütemezés: 15 perctől napi gyakoriságig, vagy csak kézi indítással.
* **Próbalekérés** gomb: azonnal megmutatja, mit talál a beolvasó az adott szelektorokkal –
  így a valós oldalszerkezethez lehet hangolni anélkül, hogy bármit importálnál.

### 2. Import napló (MBapp → Import napló)

Saját adatbázistáblában (`wp_mbapp_news_log`) minden lépés naplózódik: időpont, állapot
(beimportálva / már létezett / kihagyva / hiba / információ), a cikk címe, bélyegképe, a forrás URL-je,
az eredeti megjelenési dátum és a megjegyzés. Szűrhető állapot szerint, kereshető, futásonként
csoportosítható, sorai külön-külön vagy tömegesen törölhetők. A régi sorok a beállított idő után
automatikusan törlődnek.

### 3. Eseménykezelő – `[mbapp_events]`

* Saját „Események” bejegyzéstípus képpel, leírással, kezdés/befejezés időponttal,
  helyszínnel, forrás URL-lel és opcionális további linkkel.
* A lista **növekvő sorrendben**, a legközelebbi eseménnyel kezdve jelenik meg, minden kártyán
  a hátralévő napok számával („Ma!”, „Holnap”, „5 nap múlva”).
* Elsőre **25 esemény** látszik (állítható), a **„További”** gomb AJAX-szal tölt be még ennyit.
* A **véget ért események** kiszürkítve, külön „Véget ért események” listában maradnak
  **7 napig** (állítható), utána a bővítmény automatikusan lomtárba teszi vagy véglegesen törli őket.
* Az admin felületen választható a megjelenés: **felsorolás (lista)** vagy **rács (grid)**.
* A forrás gomb minden eseménynél kötelező: ha nincs megadva, az alapértelmezett forrást használjuk,
  és ha az sincs, az esemény nem publikálható (piszkozatba kerül, figyelmeztetéssel).

**Shortcode paraméterek**

```
[mbapp_events]                                  – az admin beállítás szerint
[mbapp_events layout="list" limit="25"]         – felsorolás, 25 elem
[mbapp_events layout="grid" past="no"]          – rács, véget ért események nélkül
[mbapp_events loadmore="no" category="unnep"]   – „További” gomb nélkül, kategóriára szűrve
[mbapp_events title="Közelgő programok"]        – saját címmel

[mbapp_news limit="6" layout="grid"]            – hírek listája
[mbapp_source url="https://mezobereny.hu/"]     – forrás gomb kézzel
```

### 4. Lebegő menü (MBapp → Lebegő menü)

Admin felületről állítható:

* **Pozíció**: alul (mobil app stílus), felül, bal vagy jobb oldalon.
* **Stílus**: áttetsző (üveg hatás) vagy tömör.
* **Feliratok** ki/be, **görgetéskor elrejtés** ki/be.
* **Menüpontonként**: felirat, URL (teljes cím vagy `/belso/utvonal/`), ikon
  (19 beépített SVG, emoji, Dashicon vagy saját kép URL), megnyitás módja,
  kiemelt („középső”) gomb, láthatóság (minden eszközön / csak mobilon / csak nagyobb kijelzőn),
  aktív állapot. A sorrend húzással állítható.

---

## Adatok és eltávolítás

A bővítmény eltávolításkor **alapból nem töröl adatot**. Ha mindent törölni szeretnél
(beállítások, hírek, események, napló tábla), az eltávolítás előtt állítsd be:

```php
update_option( 'mbapp_delete_data', 1 );
```

## Követelmények

* WordPress 6.0 vagy újabb
* PHP 7.4 vagy újabb (a `DOM` és `SimpleXML` kiterjesztéssel)
* Működő WP-Cron (vagy rendszerszintű cron) az automatikus beolvasáshoz

## Jogi megjegyzés

A hírbeolvasó más oldalról másol át tartalmat. A tényleges használat előtt győződj meg róla,
hogy a forrásoldal engedélyezi ezt (szerzői jog, felhasználási feltételek, `robots.txt`).
A forrásra mutató gomb minden átvett tartalomnál automatikusan megjelenik.
