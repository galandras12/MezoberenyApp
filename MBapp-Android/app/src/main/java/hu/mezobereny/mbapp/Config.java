package hu.mezobereny.mbapp;

/**
 * Az alkalmazás állandói egy helyen.
 *
 * <p>Ha másik címre kell átállítani az alkalmazást, elég a {@link #HOME_URL} és az
 * {@link #ALLOWED_HOSTS} értékét átírni.</p>
 */
public final class Config {

    /** A webapp kezdőlapja – kizárólag ez nyílik meg a beépített nézetben. */
    public static final String HOME_URL = "https://mezoberenyapp.rf.gd/";

    /**
     * Azok a gazdagépek, amelyek a WebView-ban maradnak.
     * Minden más hivatkozás az alapértelmezett böngészőbe kerül.
     */
    public static final String[] ALLOWED_HOSTS = {
            "mezoberenyapp.rf.gd",
            "www.mezoberenyapp.rf.gd"
    };

    /** Ennyi ideig számít egybe a kilépéshez vezető visszalépés-sorozat. */
    public static final long BACK_WINDOW_MS = 2500L;

    /** Ennyi visszalépés után jelenik meg a figyelmeztetés a főoldalon. */
    public static final int BACK_WARN_AT = 2;

    /** Ennyi visszalépésre lép ki az alkalmazás a főoldalon. */
    public static final int BACK_EXIT_AT = 3;

    /** A User-Agent végére fűzött jelölés, hogy a webhely felismerje az alkalmazást. */
    public static final String USER_AGENT_SUFFIX = " MBappAndroid/1.1.0";

    private Config() {
    }
}
