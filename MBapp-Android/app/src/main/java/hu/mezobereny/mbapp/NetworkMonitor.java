package hu.mezobereny.mbapp;

import android.content.Context;
import android.net.ConnectivityManager;
import android.net.Network;
import android.net.NetworkCapabilities;
import android.net.NetworkRequest;
import android.os.Handler;
import android.os.Looper;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;

/**
 * A hálózati kapcsolat figyelése.
 *
 * <p>Kifejezetten azt nézzük, van-e <b>mobilnet</b>, <b>Wi-Fi</b> vagy <b>Ethernet</b>
 * kapcsolat (VPN esetén az alatta lévő hordozót), és hogy az adott hálózat
 * valóban ki is jut az internetre.</p>
 */
public class NetworkMonitor {

    /** Értesítés a kapcsolat változásáról. */
    public interface Listener {
        /**
         * Akkor hívjuk, amikor a kapcsolat állapota megváltozik.
         *
         * @param online igaz, ha van használható internetkapcsolat
         */
        void onConnectivityChanged(boolean online);
    }

    private final ConnectivityManager connectivityManager;
    private final Handler mainHandler = new Handler(Looper.getMainLooper());

    @Nullable
    private ConnectivityManager.NetworkCallback callback;

    @Nullable
    private Listener listener;

    private boolean lastKnownState;

    public NetworkMonitor(@NonNull Context context) {
        connectivityManager =
                (ConnectivityManager) context.getApplicationContext()
                        .getSystemService(Context.CONNECTIVITY_SERVICE);
        lastKnownState = isOnline();
    }

    /**
     * Van-e most használható internetkapcsolat.
     *
     * @return igaz, ha mobilneten, Wi-Fin vagy Ethernetem keresztül elérhető az internet
     */
    public boolean isOnline() {
        if (connectivityManager == null) {
            return false;
        }

        Network network = connectivityManager.getActiveNetwork();

        if (network == null) {
            return false;
        }

        NetworkCapabilities caps = connectivityManager.getNetworkCapabilities(network);

        return hasUsableTransport(caps);
    }

    /**
     * A kapcsolat típusa emberi olvasásra (a hibaüzenethez).
     *
     * @return "wifi", "mobil", "ethernet" vagy üres szöveg
     */
    @NonNull
    public String activeTransportName() {
        if (connectivityManager == null) {
            return "";
        }

        Network network = connectivityManager.getActiveNetwork();

        if (network == null) {
            return "";
        }

        NetworkCapabilities caps = connectivityManager.getNetworkCapabilities(network);

        if (caps == null) {
            return "";
        }

        if (caps.hasTransport(NetworkCapabilities.TRANSPORT_WIFI)) {
            return "wifi";
        }

        if (caps.hasTransport(NetworkCapabilities.TRANSPORT_CELLULAR)) {
            return "mobil";
        }

        if (caps.hasTransport(NetworkCapabilities.TRANSPORT_ETHERNET)) {
            return "ethernet";
        }

        return "";
    }

    /**
     * A hálózat alkalmas-e böngészésre.
     *
     * @param caps a hálózat képességei
     * @return igaz, ha a kért hordozók egyikén van internet
     */
    private boolean hasUsableTransport(@Nullable NetworkCapabilities caps) {
        if (caps == null) {
            return false;
        }

        boolean transportOk =
                caps.hasTransport(NetworkCapabilities.TRANSPORT_WIFI)
                        || caps.hasTransport(NetworkCapabilities.TRANSPORT_CELLULAR)
                        || caps.hasTransport(NetworkCapabilities.TRANSPORT_ETHERNET)
                        || caps.hasTransport(NetworkCapabilities.TRANSPORT_VPN);

        if (!transportOk) {
            return false;
        }

        // Nem elég, hogy van hálózat: a rendszernek igazolnia is kell, hogy
        // tényleg kijut az internetre (így a bejelentkezős Wi-Fi sem téveszt meg).
        return caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
                && caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_VALIDATED);
    }

    /**
     * Figyelés indítása.
     *
     * @param listener értesítendő hallgató
     */
    public void start(@NonNull Listener listener) {
        this.listener = listener;

        if (connectivityManager == null || callback != null) {
            return;
        }

        NetworkRequest request = new NetworkRequest.Builder()
                .addCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
                .addTransportType(NetworkCapabilities.TRANSPORT_WIFI)
                .addTransportType(NetworkCapabilities.TRANSPORT_CELLULAR)
                .addTransportType(NetworkCapabilities.TRANSPORT_ETHERNET)
                .addTransportType(NetworkCapabilities.TRANSPORT_VPN)
                .build();

        callback = new ConnectivityManager.NetworkCallback() {
            @Override
            public void onAvailable(@NonNull Network network) {
                publish();
            }

            @Override
            public void onLost(@NonNull Network network) {
                publish();
            }

            @Override
            public void onCapabilitiesChanged(@NonNull Network network,
                                              @NonNull NetworkCapabilities caps) {
                publish();
            }
        };

        try {
            connectivityManager.registerNetworkCallback(request, callback);
        } catch (SecurityException | IllegalArgumentException e) {
            // Ha a rendszer nem engedi a figyelést, a kézi ellenőrzés akkor is működik.
            callback = null;
        }
    }

    /** Figyelés leállítása. */
    public void stop() {
        listener = null;

        if (connectivityManager != null && callback != null) {
            try {
                connectivityManager.unregisterNetworkCallback(callback);
            } catch (IllegalArgumentException ignored) {
                // Már le volt választva.
            }

            callback = null;
        }
    }

    /** Az aktuális állapot közlése a fő szálon, csak tényleges változáskor. */
    private void publish() {
        final boolean online = isOnline();

        mainHandler.post(() -> {
            if (listener == null || online == lastKnownState) {
                return;
            }

            lastKnownState = online;
            listener.onConnectivityChanged(online);
        });
    }
}
