package id.stakko.pos

import android.Manifest
import android.annotation.SuppressLint
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothDevice
import android.bluetooth.BluetoothManager
import android.bluetooth.BluetoothSocket
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.webkit.JavascriptInterface
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import org.json.JSONArray
import org.json.JSONObject
import java.util.UUID

/**
 * Pembungkus WebView Mooda + jembatan cetak thermal ESC/POS (Bluetooth).
 * Web memanggil: window.AndroidPrinter.printReceipt(text), getPrinters(), setPrinter(mac).
 */
class MainActivity : AppCompatActivity() {

    private lateinit var webView: WebView
    private var filePathCallback: ValueCallback<Array<Uri>>? = null

    // Perintah ESC/POS dasar
    private val SPP_UUID: UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")

    private val fileChooser =
        registerForActivityResult(ActivityResultContracts.StartActivityForResult()) { result ->
            val data = if (result.resultCode == RESULT_OK) result.data else null
            val uris = data?.data?.let { arrayOf(it) }
            filePathCallback?.onReceiveValue(uris ?: arrayOf())
            filePathCallback = null
        }

    private val requestBtPermission =
        registerForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
            if (!granted) toast("Izin Bluetooth diperlukan untuk mencetak.")
        }

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        // Minta izin Bluetooth (Android 12+) sejak awal
        ensureBtPermission()

        webView = findViewById(R.id.webview)
        with(webView.settings) {
            javaScriptEnabled = true
            domStorageEnabled = true
            databaseEnabled = true
            cacheMode = WebSettings.LOAD_DEFAULT
            useWideViewPort = true
            loadWithOverviewMode = true
            setSupportZoom(false)
            mediaPlaybackRequiresUserGesture = false
            allowFileAccess = true
        }

        webView.addJavascriptInterface(PrinterBridge(), "AndroidPrinter")

        webView.webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                val url = request.url.toString()
                if (url.startsWith("http://") || url.startsWith("https://")) return false
                return try {
                    startActivity(Intent(Intent.ACTION_VIEW, request.url)); true
                } catch (e: Exception) { true }
            }
        }

        webView.webChromeClient = object : WebChromeClient() {
            override fun onShowFileChooser(
                webView: WebView,
                callback: ValueCallback<Array<Uri>>,
                params: FileChooserParams
            ): Boolean {
                filePathCallback?.onReceiveValue(null)
                filePathCallback = callback
                return try {
                    fileChooser.launch(params.createIntent()); true
                } catch (e: Exception) { filePathCallback = null; false }
            }
        }

        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (webView.canGoBack()) webView.goBack() else finish()
            }
        })

        if (savedInstanceState == null) webView.loadUrl(getString(R.string.server_url))
        else webView.restoreState(savedInstanceState)
    }

    override fun onSaveInstanceState(outState: Bundle) {
        super.onSaveInstanceState(outState); webView.saveState(outState)
    }

    // ---------- Bluetooth helpers ----------

    private fun hasBtPermission(): Boolean {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S) return true
        return ContextCompat.checkSelfPermission(this, Manifest.permission.BLUETOOTH_CONNECT) ==
            PackageManager.PERMISSION_GRANTED
    }

    private fun ensureBtPermission() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S && !hasBtPermission()) {
            requestBtPermission.launch(Manifest.permission.BLUETOOTH_CONNECT)
        }
    }

    private fun adapter(): BluetoothAdapter? =
        (getSystemService(Context.BLUETOOTH_SERVICE) as? BluetoothManager)?.adapter

    private fun selectedMac(): String? =
        getSharedPreferences("stakko", Context.MODE_PRIVATE).getString("printer_mac", null)

    private fun toast(msg: String) = runOnUiThread {
        Toast.makeText(this, msg, Toast.LENGTH_SHORT).show()
    }

    @SuppressLint("MissingPermission")
    private fun pickDevice(): BluetoothDevice? {
        val ad = adapter() ?: return null
        val bonded = ad.bondedDevices ?: return null
        val mac = selectedMac()
        return bonded.firstOrNull { it.address == mac } ?: bonded.firstOrNull()
    }

    @SuppressLint("MissingPermission")
    private fun doPrint(text: String) {
        if (!hasBtPermission()) { ensureBtPermission(); toast("Beri izin Bluetooth lalu coba lagi."); return }
        val ad = adapter()
        if (ad == null || !ad.isEnabled) { toast("Bluetooth mati / tidak tersedia."); return }
        val device = pickDevice()
        if (device == null) { toast("Belum ada printer terpasang (pair di Setelan Bluetooth)."); return }

        var socket: BluetoothSocket? = null
        try {
            socket = device.createRfcommSocketToServiceRecord(SPP_UUID)
            ad.cancelDiscovery()
            socket.connect()
            val out = socket.outputStream
            out.write(byteArrayOf(0x1B, 0x40))                       // ESC @ init
            out.write(text.toByteArray(charset("ISO-8859-1")))       // isi struk
            out.write("\n\n\n".toByteArray())                        // feed
            out.write(byteArrayOf(0x1D, 0x56, 0x00))                 // GS V 0 full cut
            out.flush()
            Thread.sleep(300)
            toast("Struk dikirim ke printer.")
        } catch (e: Exception) {
            toast("Gagal mencetak: ${e.message}")
        } finally {
            try { socket?.close() } catch (_: Exception) {}
        }
    }

    // ---------- Bridge yang dipanggil dari JavaScript ----------
    inner class PrinterBridge {
        @JavascriptInterface
        fun printReceipt(text: String) { Thread { doPrint(text) }.start() }

        @SuppressLint("MissingPermission")
        @JavascriptInterface
        fun getPrinters(): String {
            val arr = JSONArray()
            if (hasBtPermission()) {
                adapter()?.bondedDevices?.forEach { d ->
                    arr.put(JSONObject().put("name", d.name ?: d.address).put("address", d.address))
                }
            }
            return arr.toString()
        }

        @JavascriptInterface
        fun setPrinter(mac: String) {
            getSharedPreferences("stakko", Context.MODE_PRIVATE).edit()
                .putString("printer_mac", mac).apply()
        }

        @JavascriptInterface
        fun getSelectedPrinter(): String = selectedMac() ?: ""

        @JavascriptInterface
        fun requestPermission() { ensureBtPermission() }
    }
}
