package hu.mezobereny.galandras12

import android.content.Intent
import android.net.ConnectivityManager
import android.net.Network
import android.net.NetworkCapabilities
import android.net.NetworkRequest
import android.net.Uri
import android.net.http.SslError
import android.os.Build
import android.os.Bundle
import android.view.View
import android.view.WindowManager
import android.webkit.SslErrorHandler
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.activity.OnBackPressedCallback
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.WindowInsetsControllerCompat
import hu.mezobereny.galandras12.databinding.ActivityMainBinding

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private lateinit var connectivityManager: ConnectivityManager
    private var siteHost: String = ""

    private val networkCallback = object : ConnectivityManager.NetworkCallback() {
        override fun onAvailable(network: Network) {
            runOnUiThread { reloadIfNeeded() }
        }

        override fun onLost(network: Network) {
            runOnUiThread { showOfflineOverlay() }
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        siteHost = Uri.parse(getString(R.string.site_url)).host.orEmpty()
        connectivityManager = getSystemService(CONNECTIVITY_SERVICE) as ConnectivityManager

        setupFullscreen()
        setupWebView()
        setupBackNavigation()

        binding.webView.loadUrl(getString(R.string.site_url))
    }

    override fun onStart() {
        super.onStart()
        connectivityManager.registerNetworkCallback(NetworkRequest.Builder().build(), networkCallback)
    }

    override fun onStop() {
        super.onStop()
        connectivityManager.unregisterNetworkCallback(networkCallback)
    }

    // ---------------------------------------------------------------------
    // Fullscreen, edge-to-edge, but never drawn behind the camera cutout.
    // ---------------------------------------------------------------------
    private fun setupFullscreen() {
        WindowCompat.setDecorFitsSystemWindows(window, false)

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
            window.attributes.layoutInDisplayCutoutMode =
                WindowManager.LayoutParams.LAYOUT_IN_DISPLAY_CUTOUT_MODE_SHORT_EDGES
        }

        val controller = WindowInsetsControllerCompat(window, binding.root)
        controller.hide(WindowInsetsCompat.Type.systemBars())
        controller.systemBarsBehavior =
            WindowInsetsControllerCompat.BEHAVIOR_SHOW_TRANSIENT_BY_SWIPE

        ViewCompat.setOnApplyWindowInsetsListener(binding.rootContainer) { view, insets ->
            val bars = insets.getInsets(
                WindowInsetsCompat.Type.systemBars() or WindowInsetsCompat.Type.displayCutout()
            )
            view.setPadding(bars.left, bars.top, bars.right, bars.bottom)
            insets
        }
    }

    // ---------------------------------------------------------------------
    // WebView setup
    // ---------------------------------------------------------------------
    private fun setupWebView() {
        val webView = binding.webView
        val settings = webView.settings
        settings.javaScriptEnabled = true
        settings.domStorageEnabled = true
        settings.useWideViewPort = true
        settings.loadWithOverviewMode = true
        settings.mediaPlaybackRequiresUserGesture = false

        webView.isVerticalScrollBarEnabled = false
        webView.isHorizontalScrollBarEnabled = false
        webView.overScrollMode = View.OVER_SCROLL_NEVER

        webView.webViewClient = object : WebViewClient() {

            override fun shouldOverrideUrlLoading(
                view: WebView,
                request: WebResourceRequest
            ): Boolean {
                if (!request.isForMainFrame) return false

                val host = request.url.host.orEmpty()
                return if (isOwnSiteHost(host)) {
                    false
                } else {
                    openInExternalBrowser(request.url)
                    true
                }
            }

            override fun onPageStarted(view: WebView, url: String?, favicon: android.graphics.Bitmap?) {
                super.onPageStarted(view, url, favicon)
                showOfflineOverlay()
            }

            override fun onPageFinished(view: WebView, url: String?) {
                super.onPageFinished(view, url)
                disablePageInteractionExtras(view)
                hideOfflineOverlay()
            }

            override fun onReceivedError(
                view: WebView,
                request: WebResourceRequest,
                error: WebResourceError
            ) {
                super.onReceivedError(view, request, error)
                if (request.isForMainFrame) {
                    showOfflineOverlay()
                }
            }

            override fun onReceivedSslError(
                view: WebView,
                handler: SslErrorHandler,
                error: SslError
            ) {
                handler.cancel()
                showOfflineOverlay()
            }
        }
    }

    private fun isOwnSiteHost(host: String): Boolean {
        if (host.equals(siteHost, ignoreCase = true)) return true
        // Google's own auth/consent redirect during Sites navigation.
        return host.equals("accounts.google.com", ignoreCase = true)
    }

    private fun openInExternalBrowser(uri: Uri) {
        val intent = Intent(Intent.ACTION_VIEW, uri)
        try {
            startActivity(intent)
        } catch (_: android.content.ActivityNotFoundException) {
            // No browser available on the device; nothing sensible to do.
        }
    }

    /** Blocks text selection, image saving and the context menu from page script side too. */
    private fun disablePageInteractionExtras(view: WebView) {
        val js = """
            (function() {
                var style = document.createElement('style');
                style.innerHTML = [
                    '*{-webkit-user-select:none !important;user-select:none !important;',
                    '-webkit-touch-callout:none !important;}',
                    '::-webkit-scrollbar{display:none !important;width:0 !important;height:0 !important;}',
                    'html,body{overflow-x:hidden !important;}'
                ].join('');
                document.documentElement.appendChild(style);
                document.addEventListener('contextmenu', function(e){ e.preventDefault(); }, false);
                document.addEventListener('dragstart', function(e){ e.preventDefault(); }, false);
            })();
        """.trimIndent()
        view.evaluateJavascript(js, null)
    }

    // ---------------------------------------------------------------------
    // Offline / slow-load overlay
    // ---------------------------------------------------------------------
    private fun isNetworkAvailable(): Boolean {
        val network = connectivityManager.activeNetwork ?: return false
        val capabilities = connectivityManager.getNetworkCapabilities(network) ?: return false
        return capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
    }

    private fun showOfflineOverlay() {
        binding.connectionOverlay.visibility = View.VISIBLE
        binding.webView.visibility = View.INVISIBLE
    }

    private fun hideOfflineOverlay() {
        if (isNetworkAvailable()) {
            binding.connectionOverlay.visibility = View.GONE
            binding.webView.visibility = View.VISIBLE
        }
    }

    private fun reloadIfNeeded() {
        if (binding.connectionOverlay.visibility == View.VISIBLE) {
            binding.webView.reload()
        }
    }

    // ---------------------------------------------------------------------
    // Back button / gesture navigation
    // ---------------------------------------------------------------------
    private fun setupBackNavigation() {
        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (binding.webView.canGoBack()) {
                    binding.webView.goBack()
                } else {
                    isEnabled = false
                    onBackPressedDispatcher.onBackPressed()
                }
            }
        })
    }
}
