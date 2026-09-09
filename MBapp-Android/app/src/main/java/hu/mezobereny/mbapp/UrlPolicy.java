package hu.mezobereny.mbapp;

import android.net.Uri;
import android.text.TextUtils;

import androidx.annotation.Nullable;

import java.util.Locale;

/**
 * Eldönti, hogy egy hivatkozás a beépített nézetben maradjon-e,
 * vagy az alapértelmezett böngészőben (illetve más alkalmazásban) nyíljon meg.
 */
public final class UrlPolicy {

    private UrlPolicy() {
    }

    /**
     * A megadott cím a saját webhelyünkhöz tartozik-e.
     *
     * @param uri vizsgált cím
     * @return igaz, ha a WebView-ban kell megnyitni
     */
    public static boolean isInternal(@Nullable Uri uri) {
        if (uri == null) {
            return false;
        }

        String scheme = uri.getScheme();

        if (scheme == null) {
            return false;
        }

        scheme = scheme.toLowerCase(Locale.ROOT);

        // Csak a webes protokollok maradhatnak a nézetben.
        if (!"https".equals(scheme) && !"http".equals(scheme)) {
            return false;
        }

        String host = uri.getHost();

        if (TextUtils.isEmpty(host)) {
            return false;
        }

        host = host.toLowerCase(Locale.ROOT);

        for (String allowed : Config.ALLOWED_HOSTS) {
            if (allowed.equals(host)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Titkosítatlan cím átírása HTTPS-re.
     *
     * <p>Az alkalmazás csak titkosított kapcsolatot enged, ezért a saját webhelyre
     * mutató {@code http://} hivatkozásokat nem elutasítjuk, hanem felminősítjük.</p>
     *
     * @param url eredeti cím
     * @return HTTPS-re átírt cím, vagy az eredeti, ha nem HTTP volt
     */
    public static String toHttps(@Nullable String url) {
        if (TextUtils.isEmpty(url)) {
            return url;
        }

        if (url.regionMatches(true, 0, "http://", 0, 7)) {
            return "https://" + url.substring(7);
        }

        return url;
    }

    /**
     * A cím a webapp kezdőlapja-e.
     *
     * <p>A lekérdezés és a horgony nem számít, a záró perjel sem.</p>
     *
     * @param url vizsgált cím
     * @return igaz, ha a kezdőlapról van szó
     */
    public static boolean isHome(@Nullable String url) {
        if (TextUtils.isEmpty(url)) {
            return false;
        }

        Uri uri = Uri.parse(url);

        if (!isInternal(uri)) {
            return false;
        }

        String path = uri.getPath();

        return TextUtils.isEmpty(path) || "/".equals(path);
    }
}
