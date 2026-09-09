# MBapp Android

A [mezoberenyapp.rf.gd](https://mezoberenyapp.rf.gd/) webapp Android héja: egyetlen
teljes képernyős WebView, amely **kizárólag** ezt a webhelyet jeleníti meg.
Minden más hivatkozás a telefon alapértelmezett böngészőjében nyílik meg.

* Csomagnév: `hu.mezobereny.mbapp`
* Verzió: 1.1.0 (versionCode 1)
* Minimum Android: 6.0 (API 23) · Cél: Android 14 (API 34)
* Nyelv: Java, AndroidX (Material 3)

---

## Mit tud

### Csak a saját webhely a beépített nézetben

A `UrlPolicy` osztály dönt minden hivatkozásról: ha a gazdagép a
`mezoberenyapp.rf.gd` (vagy `www.` előtaggal), a lap a WebView-ban marad,
minden más `Intent.ACTION_VIEW`-val a rendszer alapértelmezett alkalmazásához kerül
(böngésző, levelező, telefon). Ez érvényes a `target="_blank"` hivatkozásokra és a
letöltésekre is.

Az ellenőrzés a gazdagép **pontos egyezését** nézi, ezért az olyan megtévesztő címek is
kívül nyílnak, mint a `mezoberenyapp.rf.gd.valami-mas.hu`.

Az alkalmazás csak titkosított kapcsolatot enged (`usesCleartextTraffic="false"`),
ezért a saját webhelyre mutató `http://` hivatkozásokat automatikusan HTTPS-re írja át,
nem pedig hibára futtatja.

### Internetkapcsolat ellenőrzése

Indításkor és menet közben a `NetworkMonitor` a `ConnectivityManager`-től kérdezi le,
hogy van-e **mobilnet**, **Wi-Fi** vagy **Ethernet** kapcsolat (VPN alatt is), és hogy a
rendszer szerint az adott hálózat tényleg kijut-e az internetre
(`NET_CAPABILITY_INTERNET` **és** `NET_CAPABILITY_VALIDATED`). Így a bejelentkezést kérő,
„csatlakozva, de nincs net” Wi-Fi sem téveszti meg.

Kapcsolat nélkül nem üres oldal fogadja a felhasználót, hanem egy hibaképernyő:

> **Nincs internetkapcsolat**
> Az alkalmazás használatához internetkapcsolat szükséges. Kapcsold be a mobilnetet vagy
> csatlakozz egy Wi-Fi hálózathoz, majd próbáld újra.

Két gombbal: *Újrapróbálom* és *Hálózati beállítások megnyitása*. Amint visszatér a
kapcsolat, az alkalmazás **magától újratölt**; ha menet közben szakad meg, rövid
üzenetben jelzi.

### Vissza gomb

| Helyzet | Mi történik |
|---|---|
| Van böngészőelőzmény | Visszalép egy oldalt – **akárhányszor**, egészen a főoldalig |
| Nincs előzmény, de nem a főoldalon vagyunk | Visszavisz a főoldalra |
| Főoldal, 1. visszalépés | Nem történik semmi |
| Főoldal, 2. visszalépés | „A következő visszalépéssel bezárul az alkalmazás.” |
| Főoldal, 3. visszalépés 2,5 másodpercen belül | Az alkalmazás bezárul |

Ha a harmadik visszalépés az időkorláton kívül érkezik, a számlálás elölről indul.
Az időkorlát és a küszöbök a `Config` osztályban állíthatók
(`BACK_WINDOW_MS`, `BACK_WARN_AT`, `BACK_EXIT_AT`).

---

## Fordítás

### Android Studio (ajánlott)

1. **File → Open**, és válaszd ki ezt a mappát (`MBapp-Android`).
2. Az Android Studio letölti a Gradle-t és a függőségeket, és létrehozza a
   `local.properties` fájlt az SDK elérési útjával.
3. **Run** gombbal telepítheted eszközre vagy emulátorra.

### Parancssorból

A repóban szándékosan nincs benne a `gradle-wrapper.jar` (bináris), ezért a wrappert
egyszer létre kell hozni:

```bash
gradle wrapper          # ha van rendszerszintű Gradle 8.x
./gradlew assembleDebug # innentől a wrapper használható
```

Kiadási változat aláíráshoz:

```bash
./gradlew assembleRelease
```

Az `app/build.gradle` `release` blokkja bekapcsolja a kódrövidítést
(`minifyEnabled` + `shrinkResources`); az aláíró kulcsot neked kell hozzáadni.

---

## Testreszabás

Ami a leggyakrabban kell, egy helyen van: `app/src/main/java/hu/mezobereny/mbapp/Config.java`

| Beállítás | Mit jelent |
|---|---|
| `HOME_URL` | A megnyitott kezdőlap |
| `ALLOWED_HOSTS` | Mely gazdagépek maradnak a beépített nézetben |
| `BACK_WINDOW_MS` | Meddig számít egybe a visszalépés-sorozat (alap: 2500 ms) |
| `BACK_WARN_AT` / `BACK_EXIT_AT` | Hányadik visszalépésnél figyelmeztet, illetve lép ki |
| `USER_AGENT_SUFFIX` | A User-Agent végére fűzött jelölés, hogy a webhely felismerje az appot |

Szövegek: `app/src/main/res/values/strings.xml` · Színek: `app/src/main/res/values/colors.xml` ·
Ikon: `app/src/main/res/drawable/ic_launcher*.xml` (vektoros; Android Studio *Image Asset* eszközével
lecserélhető saját képre).

---

## Fájlszerkezet

```
MBapp-Android/
├── settings.gradle · build.gradle · gradle.properties
├── gradle/wrapper/gradle-wrapper.properties
└── app/
    ├── build.gradle · proguard-rules.pro
    └── src/main/
        ├── AndroidManifest.xml
        ├── java/hu/mezobereny/mbapp/
        │   ├── Config.java             állandók egy helyen
        │   ├── MBappApplication.java   rendszer szerinti sötét/világos mód
        │   ├── MainActivity.java       WebView, hibaképernyő, vissza gomb
        │   ├── NetworkMonitor.java     mobilnet / Wi-Fi / Ethernet figyelése
        │   └── UrlPolicy.java          belső vagy külső hivatkozás?
        └── res/
            ├── layout/activity_main.xml
            ├── values/ · values-night/  szövegek, színek, témák
            ├── drawable/ · drawable-anydpi-v26/
            └── xml/network_security_config.xml
```

## Jogosultságok

| Jogosultság | Miért kell |
|---|---|
| `INTERNET` | A tartalom a webhelyről érkezik |
| `ACCESS_NETWORK_STATE` | A kapcsolat típusának és meglétének ellenőrzése |

Más jogosultságot nem kér az alkalmazás: nincs helymeghatározás, fájlelérés vagy
harmadik féltől származó süti.
