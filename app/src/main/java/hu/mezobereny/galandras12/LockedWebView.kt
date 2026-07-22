package hu.mezobereny.galandras12

import android.content.Context
import android.util.AttributeSet
import android.view.ActionMode
import android.view.View
import android.webkit.WebView

/**
 * WebView that never shows the native text-selection toolbar or a long-press
 * context menu, so page content (text, images) cannot be selected, copied or
 * saved via long-press / right-click.
 */
class LockedWebView @JvmOverloads constructor(
    context: Context,
    attrs: AttributeSet? = null,
    defStyleAttr: Int = android.R.attr.webViewStyle
) : WebView(context, attrs, defStyleAttr) {

    init {
        isLongClickable = false
        isHapticFeedbackEnabled = false
        setOnLongClickListener { true }
    }

    // Returning null prevents the selection/copy action mode from ever starting.
    override fun startActionMode(callback: ActionMode.Callback?): ActionMode? = null

    override fun startActionMode(callback: ActionMode.Callback?, type: Int): ActionMode? = null

    override fun performLongClick(): Boolean = true

    override fun showContextMenu(): Boolean = false

    override fun showContextMenu(x: Float, y: Float): Boolean = false
}
