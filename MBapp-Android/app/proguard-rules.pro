# A WebView-s héjnak nincs szüksége egyedi szabályokra, de a JS interfészek
# nevét meg kell tartani, ha később hozzáadunk ilyet.
-keepclassmembers class * {
    @android.webkit.JavascriptInterface <methods>;
}
