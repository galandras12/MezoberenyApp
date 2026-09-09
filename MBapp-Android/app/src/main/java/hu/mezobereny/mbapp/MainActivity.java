package hu.mezobereny.mbapp;

import android.annotation.SuppressLint;
import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.os.Message;
import android.os.SystemClock;
import android.provider.Settings;
import android.text.TextUtils;
import android.view.View;
import android.webkit.CookieManager;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.OnBackPressedCallback;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

/**
 * A webapp héja.
 *
 * <p>Feladatai:</p>
 * <ul>
 *     <li>kizárólag a saját webhelyet jeleníti meg a beépített nézetben,</li>
 *     <li>minden más hivatkozást az alapértelmezett böngészőnek ad át,</li>
 *     <li>internetkapcsolat nélkül érthető hibaüzenetet mutat,</li>
 *     <li>a visszalépést a böngészőelőzmények szerint kezeli, a kilépést pedig
 *         csak a főoldalon, három visszalépés után engedi.</li>
 * </ul>
 */
public class MainActivity extends AppCompatActivity {

    private static final String STATE_WEBVIEW = "mbapp_webview_state";

    private WebView webView;
    private ProgressBar progressBar;
    private View offlineView;
    private TextView offlineMessage;

    private NetworkMonitor networkMonitor;

    /** Igaz, amíg a hibaképernyő látszik (ilyenkor a tartalom nincs betöltve). */
    private boolean showingOffline;

    /** Az utolsó betöltési kísérlet címe, hogy az „Újra” gomb tudja, mit töltsön. */
    private String pendingUrl = Config.HOME_URL;

    /* --------------------------------------------------------------------
     * Életciklus
     * ----------------------------------------------------------------- */

    @Override
    protected void onCreate(@Nullable Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        webView = findViewById(R.id.web_view);
        progressBar = findViewById(R.id.progress);
        offlineView = findViewById(R.id.offline);
        offlineMessage = findViewById(R.id.offline_message);

        Button retry = findViewById(R.id.offline_retry);
        Button settings = findViewById(R.id.offline_settings);

        retry.setOnClickListener(v -> retry());
        settings.setOnClickListener(v -> openNetworkSettings());

        configureWebView();
        registerBackHandler();

        networkMonitor = new NetworkMonitor(this);

        if (savedInstanceState != null) {
            Bundle state = savedInstanceState.getBundle(STATE_WEBVIEW);

            if (state != null) {
                webView.restoreState(state);
                return;
            }
        }

        // Első indítás: előbb a kapcsolatot nézzük meg.
        if (networkMonitor.isOnline()) {
            loadHome();
        } else {
            showOffline(getString(R.string.offline_message));
        }
    }

    @Override
    protected void onStart() {
        super.onStart();

        networkMonitor.start(online -> {
            if (online && showingOffline) {
                // Visszajött a kapcsolat: magától újratölt.
                retry();
            } else if (!online && !showingOffline) {
                Toast.makeText(this, R.string.connection_lost, Toast.LENGTH_LONG).show();
            }
        });
    }

    @Override
    protected void onStop() {
        networkMonitor.stop();
        super.onStop();
    }

    @Override
    protected void onResume() {
        super.onResume();
        webView.onResume();
    }

    @Override
    protected void onPause() {
        webView.onPause();
        super.onPause();
    }

    @Override
    protected void onSaveInstanceState(@NonNull Bundle outState) {
        super.onSaveInstanceState(outState);

        Bundle state = new Bundle();
        webView.saveState(state);
        outState.putBundle(STATE_WEBVIEW, state);
    }

    @Override
    protected void onDestroy() {
        if (webView != null) {
            webView.destroy();
        }

        super.onDestroy();
    }

    /* --------------------------------------------------------------------
     * WebView
     * ----------------------------------------------------------------- */

    @SuppressLint("SetJavaScriptEnabled")
    private void configureWebView() {
        WebSettings settings = webView.getSettings();

        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setLoadWithOverviewMode(true);
        settings.setUseWideViewPort(true);
        settings.setSupportZoom(false);
        settings.setBuiltInZoomControls(false);
        settings.setDisplayZoomControls(false);
        settings.setCacheMode(WebSettings.LOAD_DEFAULT);
        settings.setMediaPlaybackRequiresUserGesture(true);

        // A target="_blank" hivatkozásokat így tudjuk elkapni és böngészőbe küldeni.
        settings.setSupportMultipleWindows(true);
        settings.setJavaScriptCanOpenWindowsAutomatically(false);

        // Helyi fájlokhoz nincs dolga a webappnak.
        settings.setAllowFileAccess(false);
        settings.setAllowContentAccess(false);
        settings.setGeolocationEnabled(false);

        // Csak titkosított kapcsolat.
        settings.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);

        settings.setUserAgentString(settings.getUserAgentString() + Config.USER_AGENT_SUFFIX);

        CookieManager.getInstance().setAcceptCookie(true);
        CookieManager.getInstance().setAcceptThirdPartyCookies(webView, false);

        webView.setWebViewClient(new AppWebViewClient());
        webView.setWebChromeClient(new AppWebChromeClient());

        // A letöltéseket a rendszerre bízzuk.
        webView.setDownloadListener((url, userAgent, disposition, mimeType, length) ->
                openExternally(Uri.parse(url)));

        webView.setOverScrollMode(View.OVER_SCROLL_NEVER);
    }

    /** A saját webhely eseményeit kezelő kliens. */
    private class AppWebViewClient extends WebViewClient {

        @Override
        public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
            return handleUrl(request.getUrl());
        }

        @Override
        public void onPageStarted(WebView view, String url, android.graphics.Bitmap favicon) {
            progressBar.setVisibility(View.VISIBLE);
        }

        @Override
        public void onPageFinished(WebView view, String url) {
            progressBar.setVisibility(View.GONE);
        }

        @Override
        public void onReceivedError(WebView view, WebResourceRequest request, WebResourceError error) {
            // Csak a fő dokumentum hibája érdekel: egy kép hiánya ne dobjon hibaképernyőt.
            if (!request.isForMainFrame()) {
                return;
            }

            progressBar.setVisibility(View.GONE);

            showOffline(networkMonitor.isOnline()
                    ? getString(R.string.load_error_message)
                    : getString(R.string.offline_message));
        }
    }

    /** A folyamatjelzőt és a felugró ablakokat kezeli. */
    private class AppWebChromeClient extends WebChromeClient {

        @Override
        public void onProgressChanged(WebView view, int newProgress) {
            progressBar.setProgress(newProgress);
            progressBar.setVisibility(newProgress >= 100 ? View.GONE : View.VISIBLE);
        }

        @Override
        public boolean onCreateWindow(WebView view, boolean isDialog,
                                      boolean isUserGesture, Message resultMsg) {
            // A target="_blank" hivatkozásokhoz a rendszer új nézetet kér. Nem nyitunk
            // másodikat: egy eldobható nézettel csak kiolvassuk a címet, és a szabály
            // szerint irányítjuk tovább.
            final WebView probe = new WebView(view.getContext());

            probe.setWebViewClient(new WebViewClient() {
                @Override
                public boolean shouldOverrideUrlLoading(WebView probeView, WebResourceRequest request) {
                    handleUrl(request.getUrl());
                    probeView.destroy();
                    return true;
                }
            });

            WebView.WebViewTransport transport = (WebView.WebViewTransport) resultMsg.obj;
            transport.setWebView(probe);
            resultMsg.sendToTarget();

            return true;
        }
    }

    /**
     * Egy hivatkozás továbbítása a szabály szerint.
     *
     * @param uri a megnyitandó cím
     * @return igaz, ha az alkalmazás kezelte (tehát a WebView ne töltse be)
     */
    private boolean handleUrl(@Nullable Uri uri) {
        if (uri == null) {
            return false;
        }

        if (UrlPolicy.isInternal(uri)) {
            // Saját tartalom: maradjon a beépített nézetben. A titkosítatlan
            // hivatkozásokat előbb felminősítjük, mert az alkalmazás csak
            // HTTPS-t enged – enélkül hibaképernyőn kötnének ki.
            if ("http".equalsIgnoreCase(uri.getScheme())) {
                webView.loadUrl(UrlPolicy.toHttps(uri.toString()));
                return true;
            }

            return false;
        }

        openExternally(uri);

        return true;
    }

    /**
     * Cím megnyitása a rendszer alapértelmezett alkalmazásában (böngésző, telefon, levelező).
     *
     * @param uri a megnyitandó cím
     */
    private void openExternally(@NonNull Uri uri) {
        Intent intent = new Intent(Intent.ACTION_VIEW, uri);
        intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);

        try {
            startActivity(intent);
        } catch (ActivityNotFoundException e) {
            Toast.makeText(this, R.string.no_browser, Toast.LENGTH_LONG).show();
        }
    }

    /* --------------------------------------------------------------------
     * Betöltés és hibaképernyő
     * ----------------------------------------------------------------- */

    private void loadHome() {
        pendingUrl = Config.HOME_URL;
        showContent();
        webView.loadUrl(Config.HOME_URL);
    }

    /**
     * Újrapróbálkozás: ha van kapcsolat, betölti az utolsó címet.
     */
    private void retry() {
        if (!networkMonitor.isOnline()) {
            showOffline(getString(R.string.offline_message));
            Toast.makeText(this, R.string.still_offline, Toast.LENGTH_SHORT).show();
            return;
        }

        showContent();

        String url = TextUtils.isEmpty(webView.getUrl()) ? pendingUrl : webView.getUrl();

        if (TextUtils.isEmpty(url) || "about:blank".equals(url)) {
            url = Config.HOME_URL;
        }

        webView.loadUrl(url);
    }

    /**
     * A hibaképernyő megjelenítése.
     *
     * @param message a felhasználónak szóló üzenet
     */
    private void showOffline(@NonNull String message) {
        showingOffline = true;

        offlineMessage.setText(message);
        offlineView.setVisibility(View.VISIBLE);
        webView.setVisibility(View.GONE);
        progressBar.setVisibility(View.GONE);
    }

    /** Vissza a tartalomhoz. */
    private void showContent() {
        showingOffline = false;

        offlineView.setVisibility(View.GONE);
        webView.setVisibility(View.VISIBLE);
    }

    /** A rendszer hálózati beállításainak megnyitása. */
    private void openNetworkSettings() {
        try {
            startActivity(new Intent(Settings.ACTION_WIRELESS_SETTINGS));
        } catch (ActivityNotFoundException e) {
            try {
                startActivity(new Intent(Settings.ACTION_SETTINGS));
            } catch (ActivityNotFoundException ignored) {
                Toast.makeText(this, R.string.no_settings, Toast.LENGTH_SHORT).show();
            }
        }
    }

    /* --------------------------------------------------------------------
     * Vissza gomb
     * ----------------------------------------------------------------- */

    /** Hányadik visszalépésnél tartunk a főoldalon. */
    private int backPressCount;

    /** Az utolsó visszalépés ideje, hogy a sorozat időkorlátos legyen. */
    private long lastBackPressAt;

    private void registerBackHandler() {
        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override
            public void handleOnBackPressed() {
                onBackRequested();
            }
        });
    }

    private void onBackRequested() {
        // A hibaképernyőről a vissza gomb egyből a kilépés-számlálóra fut.
        if (!showingOffline) {
            // Amíg van böngészőelőzmény, akárhányszor lehet visszalépni.
            if (webView.canGoBack()) {
                webView.goBack();
                resetBackCounter();
                return;
            }

            // Nincs előzmény, de nem a főoldalon vagyunk (pl. megosztott hivatkozásról
            // indultunk): előbb vigyük vissza a felhasználót a főoldalra.
            if (!UrlPolicy.isHome(webView.getUrl())) {
                loadHome();
                resetBackCounter();
                return;
            }
        }

        countBackPressOnHome();
    }

    /**
     * A főoldalon a kilépéshez vezető visszalépés-sorozat számlálása.
     *
     * <p>A második visszalépésnél figyelmeztetünk, a harmadikra – ha rövid időn
     * belül érkezik – az alkalmazás bezárul.</p>
     */
    private void countBackPressOnHome() {
        long now = SystemClock.elapsedRealtime();

        if (now - lastBackPressAt > Config.BACK_WINDOW_MS) {
            backPressCount = 0;
        }

        backPressCount++;
        lastBackPressAt = now;

        if (backPressCount >= Config.BACK_EXIT_AT) {
            finishAndRemoveTask();
            return;
        }

        if (backPressCount == Config.BACK_WARN_AT) {
            Toast.makeText(this, R.string.back_exit_warning, Toast.LENGTH_SHORT).show();
        }
    }

    private void resetBackCounter() {
        backPressCount = 0;
        lastBackPressAt = 0L;
    }
}
