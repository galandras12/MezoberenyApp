# MezőberényApp – WordPress téma és bővítmény

Két önálló, de együttműködő WordPress csomag egy alkalmazás-jellegű városi webapphoz:

| Mappa | Mi ez | Telepítés helye |
|---|---|---|
| `MBapp-theme/` | Webapp kinézetű téma sötét/világos kapcsolóval | `wp-content/themes/MBapp-theme/` |
| `MBapp-plugin/` | Hírbeolvasó + eseménykezelő + lebegő menü | `wp-content/plugins/MBapp-plugin/` |
| `MBapp-Android/` | Android héj a webapphoz (WebView) | Android Studio projekt |

> **Megjegyzés:** a kéréshez csatolt képernyőkép nem érkezett meg, ezért a felület egy általános
> mobilalkalmazás-mintát követ (felső app bar, kártyás tartalom, alul lebegő menü). A színek,
> az ikonok, a menüpontok és az elrendezés admin felületről szabadon átállíthatók.

A téma és a bővítmény **v1.1.0**, az Android alkalmazás **1.1.0 (versionCode 1)**.

---

## Telepítés

1. Másold a két mappát a fenti helyekre (vagy tömörítsd ZIP-be, és töltsd fel a WordPress admin felületén).
2. **Megjelenés → Témák** → *MBapp Theme* aktiválása.
3. **Bővítmények** → *MBapp Plugin* aktiválása.
   Aktiváláskor létrejön az „Események” oldal a `[mbapp_events]` shortcode-dal, elindul a hírbeolvasó
   ütemezése és elkészül a napló adatbázistáblája.
4. **Megjelenés → MBapp téma**: fejléc mérete, fejléc kép, favicon, diavetítés, egyedi szövegek.
5. **MBapp → Kezdőlap**: állítsd össze, mi jelenjen meg a főoldalon (fejléc kép, hírek,
   események, saját HTML tartalom).
6. **MBapp → Hírbeolvasó**: állítsd be a forrást, majd a *Próbalekérés* gombbal ellenőrizd,
   hogy a beolvasó megtalálja-e a híreket.
7. **MBapp → Lebegő menü**: állítsd be a gombokat, ikonokat, az URL-eket és az animációkat.

Igény szerint a **Beállítások → Olvasás** menüben a kezdőlapot állítsd statikus oldalra – a téma
kezdőlapja ilyenkor is kiteszi alá a közelgő eseményeket és a friss híreket.

---

## MBapp Theme

### Részletes szerkesztő: Megjelenés → MBapp téma

A téma saját, fülekre osztott szerkesztő felületet kapott. AJAX-szal ment (nincs újratöltés,
a gomb mögött pipa jelenik meg), és ugyanabba a tárolóba ír, mint a Testreszabó — a kettő
mindig szinkronban marad.

| Fül | Mit állíthatsz |
|---|---|
| **Fejléc** | A fejléc **mérete** (alacsony 50 / közepes 58 / magas 70 px, előnézettel), logó/ikon mérete, tartalomszélesség, tapadó fejléc, üveg hatás, mottó / kereső / vissza gomb / lábléc ki-be. |
| **Fejléc kép** | Ki-be kapcsolható **banner**: kép a médiatárból, magasság (160–460 px), a kép igazítása (felső/közép/alsó rész), sötétítés 0–90%, lekerekített sarkok, és hogy hol jelenjen meg (kezdőlap / minden oldal / belső oldalak). Ráírható **cím és alcím** is. |
| **Favicon** | Egyedi ikon a médiatárból: böngészőfül ikon, `apple-touch-icon` a telefon kezdőképernyőjéhez, és felülírja a WordPress webhely ikonját. **Megjelenése**: kitehető a fejlécbe a webhely neve mellé, alakja kör / lekerekített / szögletes. |
| **Diavetítés** | **Ki-be kapcsolható diavetítés a bejegyzésekből**: forrás (Hírek / bejegyzések / kiemelt sticky), diák száma, kategória szűrő, magasság, hol jelenjen meg, automatikus léptetés és időköz, nyilak, pöttyök, csak képes bejegyzések, bevezető szöveg. |
| **Egyedi szövegek** | Tetszőleges számú **saját szövegblokk**: cím, emoji ikon, szöveg, hely (tartalom előtt / után), stílus (egyszerű / kártya / információ / figyelmeztetés / kiemelt), és mely oldalakon jelenjen meg. A sorrend húzással állítható. |

A diavetítés érintéssel is lapozható, megáll, ha az egeret fölé viszed vagy elhagyod a lapot,
és a `prefers-reduced-motion` beállítás mellett nem indul el magától.

* **Telefonos app felület minden oldalon**: a tartalom egy középre igazított hasáb, nagy kijelzőn is –
  nincs widget oldalsáv sehol, a bejegyzés oldalon sem. A hasáb szélessége a testreszabóban állítható
  (Telefon 560 px / Kompakt 720 px / Széles 1080 px).
* **Vissza gomb** a fejlécben minden oldalon a kezdőlapon kívül, mint egy mobilalkalmazásban.
* **Lábléc kapcsoló**: alapból nincs lábléc – a mobilalkalmazásoknak sincs. Egy kattintással
  visszakapcsolható (Testreszabás → MBapp felület → Lábléc), ilyenkor megjelenik a lábléc menü,
  a widgetek, a szerzői jogi sor és a téma váltó gomb is.
* **Sötét / világos téma kapcsoló** az app bar-ban és a láblécben.
  A választás `localStorage`-ban marad meg, alapból a rendszerbeállítást követi, és villogásmentesen tölt be.
* **Lebegő menü (dock)**: a bővítmény tölti fel tartalommal; ha az nincs bekapcsolva, a `dock`
  menühelyre kötött WordPress menüből épül tartalék változat.
* **Testreszabó** (Megjelenés → Testreszabás → *MBapp felület*): akcentus színek és alapértelmezett téma
  élő előnézettel. A részletesebb beállítások a fenti szerkesztő felületen érhetők el.
* Sablonok: `index`, `front-page`, `single`, `page`, `archive`, `search`, `404`, plus `template-parts/`.
* Menühelyek: `primary`, `dock`, `footer`. Widget terület: `footer-1` (oldalsáv szándékosan nincs).

A bővítmény kártyasablonjai felülírhatók a témából: hozz létre egy `mbapp/` mappát a témán belül,
és tedd bele az `event-card.php` vagy `news-card.php` másolatát.

---

## MBapp Plugin

### 1. Automatikus hírbeolvasó

* Forrás: tetszőleges URL (alapértelmezés: `https://mezobereny.hu/s/hirek`).
* **Automatikus felismerés**: először RSS/Atom csatornát keres (a HTML `<link rel="alternate">`
  fejlécét is megnézi), majd a HTML-t olvassa CSS szelektorokkal, végül a **JSON-LD** strukturált
  adatra esik vissza – így akkor is működhet, ha a szelektorok nem találnak semmit.
* **Szerkezetfelismerés**: a „Szerkezet felismerése” gomb megkeresi az oldalon az ismétlődő
  hírblokkokat, és egy kattintással kitölti az összes szelektort (lásd a Hibaelhárítás fejezetet).
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

### 4. Testreszabható kezdőlap (MBapp → Kezdőlap)

A kezdőlap **blokkokból** épül fel. Mindegyik ki-be kapcsolható, húzással átrendezhető,
és bármennyi újat hozzáadhatsz:

| Blokk | Mit tud |
|---|---|
| **Fejléc (hero)** | Cím, alcím, gomb (felirat + URL). A **háttérkép külön kapcsolható ki-be**: a médiatárból választható, állítható a magasság (alacsony / közepes / magas), a kép sötétítése (0–90%) és az igazítás (balra / középre). Kép nélkül színátmenetes fejléc jelenik meg. |
| **Események** | Közelgő programok: darabszám, rács vagy felsorolás, véget ért események, „További” gomb, „Összes” link. |
| **Hírek** | A legfrissebb cikkek: darabszám, rács vagy felsorolás, „Összes” link az archívumra. |
| **Egyedi tartalom** | **Saját HTML**, tetszőleges helyre beszúrva. Kapcsolható, hogy fussanak-e benne a shortcode-ok, és hogy kártyás keretben jelenjen-e meg. |
| **Oldal tartalma** | Egy meglévő WordPress oldal szövegének beemelése. |

Ugyanaz a blokk többször is szerepelhet – lehet például két hírblokk különböző beállításokkal,
vagy egy HTML blokk a hírek és az események között.

A HTML blokk tartalmára ugyanaz a szabály vonatkozik, mint a bejegyzésekre: akinek van
`unfiltered_html` jogosultsága (általában az adminisztrátor), nyers HTML-t is menthet,
mindenki másnál a `wp_kses_post` szűri a tartalmat.

Ha az egyedi kezdőlapot kikapcsolod, a téma alapértelmezett kezdőlapja jelenik meg
(a statikus kezdőlap tartalma, alatta az eseményekkel és a hírekkel).

### 5. Lebegő menü és AJAX navigáció (MBapp → Lebegő menü)

Admin felületről állítható:

* **Pozíció**: alul (mobil app stílus), felül, bal vagy jobb oldalon.
* **Stílus**: áttetsző (üveg hatás) vagy tömör.
* **Feliratok** ki/be, **görgetéskor elrejtés** ki/be.
* **AJAX oldalváltás**: az oldalak újratöltés nélkül, animálva váltanak – ettől viselkedik a felület
  igazi mobilalkalmazásként. Hatóköre lehet csak a lebegő menü, vagy minden oldalon belüli hivatkozás.
* **Animációk**: átmenet típusa (áttűnés, oldalirányú csúsztatás, felfelé csúsztatás, nagyítás, nincs),
  hossza (80–900 ms), időzítési görbe (lágy lassítás, lágy indítás-lassítás, rugós, egyenletes).
  Az admin oldalon egy kis telefon-előnézetben **azonnal le is játszható** a beállított animáció.
* **Betöltésjelző** csík a képernyő tetején, **gombnyomás visszajelzése** (hullám / benyomódás / nincs),
  és a menü **megjelenési animációja** (felcsúszik / áttűnik / azonnal).

Ha a böngésző nem támogatja az AJAX navigációt, vagy bármi hiba történik, a felület magától
visszaáll a hagyományos oldalbetöltésre. A `prefers-reduced-motion` beállítást minden animáció tiszteletben tartja.
* **Menüpontonként**: felirat, URL (teljes cím vagy `/belso/utvonal/`), ikon
  (19 beépített SVG, emoji, Dashicon vagy saját kép URL), megnyitás módja,
  kiemelt („középső”) gomb, láthatóság (minden eszközön / csak mobilon / csak nagyobb kijelzőn),
  aktív állapot. A sorrend húzással állítható.

---

## Hibaelhárítás: „A megadott szelektorral nem találtunk hírt”

Ez az üzenet azt jelenti, hogy a bővítmény letöltötte az oldalt, de a megadott CSS szelektorral
nem talált benne hírblokkot. Nem kell szelektorokat találgatni – a bővítmény ki tudja deríteni magától:

### 1. Indítsd el a szerkezetfelismerést

**MBapp → Hírbeolvasó → Forrás felderítése → „Szerkezet felismerése”**

A gomb letölti az oldalt a szerverről, és megmutatja:

* mekkora a letöltött HTML, és mennyi benne az **olvasható szöveg**,
* talált-e **RSS/Atom csatornát** (a fejlécben hirdetettet és a gyakori útvonalakat is végigpróbálja:
  `/rss`, `/feed`, `/rss.xml`, `?format=rss` stb.),
* van-e **JSON-LD** strukturált adat a hírekről,
* milyen **ismétlődő blokkok** vannak az oldalon (menüt, fejlécet, láblécet kihagyva), darabszámmal,
  mintaszöveggel és kész szelektor-javaslattal.

A javaslat melletti **„Ezt használom”** gombra kattintva az összes szelektor mező kitöltődik
(hír elem, cím, link, kép, kivonat, dátum). Utána a **„Próbalekérés”** gombbal ellenőrizd,
és mentsd el.

### 2. Értelmezd az eredményt

| Amit a felderítés mutat | Mit jelent | Mit tegyél |
|---|---|---|
| Talált RSS csatornát | A legjobb eset | „Beállítom forrásnak” → a forrás típusa *RSS / Atom* |
| Van JSON-LD hír | Az oldal strukturált adatban is kiadja a híreket | „JSON-LD forrásra váltok” – szelektor nem is kell |
| Van szelektor-javaslat | A hírek benne vannak a HTML-ben | „Ezt használom”, majd *Próbalekérés* |
| Kevés olvasható szöveg (< 800 karakter), nincs javaslat | Az oldal **JavaScripttel** tölti be a híreket, a szerver csak egy üres vázat kap | RSS csatorna, JSON-LD vagy az oldal saját API címe kell forrásnak |
| HTTP 403 / 404 hiba | Az oldal blokkolja a kérést vagy rossz a cím | Próbálj más URL-t, vagy adj meg egyedi User-Agentet a *Haladó* résznél |

A felderítés alján a **„A letöltött HTML eleje”** blokk megmutatja, mit kapott valójában a szerver –
ebből azonnal látszik, ha bot-védelem, cookie-fal vagy üres SPA-váz jött vissza.

### 3. Ha kézzel keresnéd meg a szelektort

Nyisd meg a hírek oldalát böngészőben, jobb gomb egy hír címén → **Elem vizsgálata**.
Keresd meg azt a legkülső elemet, amely **pontosan egy hírt** fog körbe, és nézd meg az osztálynevét.
Ha például ez látszik:

```html
<div class="news-list__item">…</div>
```

akkor a *Hír elem* mezőbe `div.news-list__item` (vagy elég: `.news-list__item`) kerül,
a *Cím* mezőbe `h3 a`, a *Kép* mezőbe `img`, a *Dátum* mezőbe `time` vagy `.news-date`.

Az automatikus felismerés is ezt csinálja, csak gyorsabban.

---

## Az admin panel mentése

A beállítások **AJAX-szal mentődnek**: az oldal nem töltődik újra, nem ugrik a tetejére, és a
mentés gomb mögött megjelenik egy zöld **pipa** („Elmentve”), amely néhány másodperc után elhalványul.
Ha JavaScript nélkül használod az admint, a hagyományos űrlapbeküldés változatlanul működik.

---

## MBapp Android

A `MBapp-Android/` mappa egy önálló Android Studio projekt: teljes képernyős WebView,
amely kizárólag a `https://mezoberenyapp.rf.gd/` címet nyitja meg, minden más
hivatkozást pedig az alapértelmezett böngészőnek ad át. Ellenőrzi a mobilnet / Wi-Fi /
Ethernet kapcsolatot, kapcsolat nélkül érthető hibaüzenetet mutat, a vissza gomb pedig a
böngészőelőzmények szerint működik – kilépni csak a főoldalon, három visszalépéssel lehet.

Részletek és fordítási útmutató: [`MBapp-Android/README.md`](MBapp-Android/README.md)

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
