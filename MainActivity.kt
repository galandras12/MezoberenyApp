package com.example.mbapp

import android.annotation.SuppressLint
import android.content.Intent
import android.graphics.Color.DKGRAY
import android.net.Uri
import android.os.Bundle
import android.view.View
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.LinearLayout
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import kotlin.Boolean as Boolean
import com.google.android.material.floatingactionbutton.FloatingActionButton

class MainActivity : AppCompatActivity() {

    private lateinit var webView: WebView
    private lateinit var errorView: LinearLayout
    private lateinit var errorTextView: TextView
    private val websiteUrl = "https://sites.google.com/view/mezobereny-app/"
    private lateinit var exitButton: FloatingActionButton
    private lateinit var backButton: FloatingActionButton

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
        webView = findViewById(R.id.webView)
        errorView = findViewById(R.id.errorView)
        errorTextView = findViewById(R.id.errorTextView)

        supportActionBar?.hide()
       // window.statusBarColor = DKGRAY

        webView.settings.javaScriptEnabled = true
        webView.settings.setSupportZoom(true)
        webView.settings.displayZoomControls = false
        webView.settings.useWideViewPort = true
        webView.settings.loadWithOverviewMode = true
        exitButton = findViewById(R.id.exitButton)
        backButton = findViewById(R.id.backButton)
        webView.webViewClient = object : WebViewClient() {
            @Deprecated("Deprecated in Java")
            override fun onReceivedError(
                view: WebView?,
                errorCode: Int,
                description: String?,
                failingUrl: String?
            ) {
                if (errorCode == ERROR_HOST_LOOKUP) {
                    webView.visibility = View.GONE
                    errorView.visibility = View.VISIBLE
                }
            }
        }

        webView.loadUrl(websiteUrl)

        exitButton.setOnClickListener {
            finish()
        }

        backButton.setOnClickListener {
            if (webView.canGoBack()) {
                webView.goBack()
            }
        }
        class MyWebViewClient : WebViewClient() {
            @Deprecated("Deprecated in Java")
            override fun shouldOverrideUrlLoading(view: WebView?, url: String?):
                    Boolean {
                if (url != null && url.startsWith(websiteUrl)) {
                    return false
                }
                val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                startActivity(intent)
                return true
            }
        }
        webView.webViewClient = MyWebViewClient()
    }
}