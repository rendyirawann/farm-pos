package id.stakko.pos

import android.Manifest
import android.annotation.SuppressLint
import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothDevice
import android.bluetooth.BluetoothGatt
import android.bluetooth.BluetoothGattCallback
import android.bluetooth.BluetoothGattCharacteristic
import android.bluetooth.BluetoothManager
import android.bluetooth.BluetoothProfile
import android.bluetooth.BluetoothSocket
import android.bluetooth.BluetoothStatusCodes
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
import java.util.concurrent.CountDownLatch
import java.util.concurrent.TimeUnit

/**
 * Pembungkus WebView Mooda + jembatan cetak thermal ESC/POS (Bluetooth).
 * Web memanggil: window.AndroidPrinter.printReceipt(text), getPrinters(), setPrinter(mac).
 */
class MainActivity : AppCompatActivity() {

    private lateinit var webView: WebView
    private var filePathCallback: ValueCallback<Array<Uri>>? = null

    // Perintah ESC/POS dasar
    private val SPP_UUID: UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")

    // Socket cetak dipertahankan (keep-alive) supaya tidak reconnect tiap kali cetak.
    private var printSocket: BluetoothSocket? = null
    private var printMac: String? = null
    private val printLock = Any()

    // --- BLE / GATT (printer BLE, mis. EcoPrint: service 0x18F0, karakteristik 0x2AF1) ---
    private val BLE_SERVICE_UUID: UUID = UUID.fromString("000018f0-0000-1000-8000-00805f9b34fb")
    private val BLE_CHAR_UUID: UUID = UUID.fromString("00002af1-0000-1000-8000-00805f9b34fb")
    private var bleGatt: BluetoothGatt? = null
    @Volatile private var bleWriteChar: BluetoothGattCharacteristic? = null
    @Volatile private var bleMtu = 20
    private var bleConnLatch: CountDownLatch? = null

    private val gattCallback = object : BluetoothGattCallback() {
        @SuppressLint("MissingPermission")
        override fun onConnectionStateChange(gatt: BluetoothGatt, status: Int, newState: Int) {
            if (newState == BluetoothProfile.STATE_CONNECTED) {
                try { if (!gatt.requestMtu(200)) gatt.discoverServices() }
                catch (_: Exception) { try { gatt.discoverServices() } catch (_: Exception) {} }
            } else if (newState == BluetoothProfile.STATE_DISCONNECTED) {
                bleWriteChar = null
                bleConnLatch?.countDown()
            }
        }
        @SuppressLint("MissingPermission")
        override fun onMtuChanged(gatt: BluetoothGatt, mtu: Int, status: Int) {
            bleMtu = if (mtu > 23) mtu - 3 else 20
            try { gatt.discoverServices() } catch (_: Exception) {}
        }
        override fun onServicesDiscovered(gatt: BluetoothGatt, status: Int) {
            bleWriteChar = findBleWritable(gatt)
            bleConnLatch?.countDown()
        }
    }

    private fun findBleWritable(gatt: BluetoothGatt): BluetoothGattCharacteristic? {
        gatt.getService(BLE_SERVICE_UUID)?.getCharacteristic(BLE_CHAR_UUID)?.let { return it }
        for (svc in gatt.services) for (c in svc.characteristics) {
            val p = c.properties
            if (p and BluetoothGattCharacteristic.PROPERTY_WRITE != 0 ||
                p and BluetoothGattCharacteristic.PROPERTY_WRITE_NO_RESPONSE != 0) return c
        }
        return null
    }

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

    override fun onDestroy() {
        synchronized(printLock) { closeAll() }
        super.onDestroy()
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

    // Sambungkan RFCOMM dengan beberapa strategi. Banyak kombinasi printer/HP butuh
    // fallback: secure -> insecure -> refleksi createRfcommSocket(channel 1) yang
    // terkenal memperbaiki kegagalan connect klasik di Android.
    @SuppressLint("MissingPermission")
    private fun openSocket(device: BluetoothDevice): BluetoothSocket {
        adapter()?.cancelDiscovery()
        try {
            val s = device.createRfcommSocketToServiceRecord(SPP_UUID); s.connect(); return s
        } catch (_: Exception) {}
        try {
            val s = device.createInsecureRfcommSocketToServiceRecord(SPP_UUID); s.connect(); return s
        } catch (_: Exception) {}
        val m = device.javaClass.getMethod("createRfcommSocket", Int::class.javaPrimitiveType)
        val s = m.invoke(device, 1) as BluetoothSocket; s.connect(); return s
    }

    // Pakai ulang socket bila masih tersambung; kalau tidak, sambungkan (dengan retry).
    @SuppressLint("MissingPermission")
    private fun ensureSocket(device: BluetoothDevice): BluetoothSocket {
        val cached = printSocket
        if (cached != null && cached.isConnected && printMac == device.address) return cached
        closeSocket()
        var err: Exception? = null
        for (i in 0 until 3) {
            try {
                val s = openSocket(device)
                printSocket = s; printMac = device.address
                return s
            } catch (e: Exception) { err = e; Thread.sleep(300L * (i + 1)) }
        }
        throw err ?: Exception("Gagal menyambung printer.")
    }

    private fun closeSocket() {
        try { printSocket?.close() } catch (_: Exception) {}
        printSocket = null; printMac = null
    }

    // Cetak via Bluetooth Classic (SPP/RFCOMM). true bila sukses.
    @SuppressLint("MissingPermission")
    private fun printSpp(device: BluetoothDevice, bytes: ByteArray): Boolean {
        return try {
            val out = ensureSocket(device).outputStream
            out.write(bytes); out.flush(); Thread.sleep(250); true
        } catch (e: Exception) { closeSocket(); false }
    }

    // Cetak via BLE/GATT (printer BLE, mis. EcoPrint). true bila sukses. Keep-alive.
    @Suppress("DEPRECATION")
    @SuppressLint("MissingPermission")
    private fun printBle(device: BluetoothDevice, bytes: ByteArray): Boolean {
        if (bleWriteChar == null || bleGatt == null) {
            closeBle()
            bleConnLatch = CountDownLatch(1)
            bleGatt = device.connectGatt(this, false, gattCallback)
            try { bleConnLatch?.await(10, TimeUnit.SECONDS) } catch (_: Exception) {}
        }
        val gatt = bleGatt ?: return false
        val ch = bleWriteChar ?: return false
        val noResp = ch.properties and BluetoothGattCharacteristic.PROPERTY_WRITE_NO_RESPONSE != 0
        val type = if (noResp) BluetoothGattCharacteristic.WRITE_TYPE_NO_RESPONSE
                   else BluetoothGattCharacteristic.WRITE_TYPE_DEFAULT
        val chunk = if (bleMtu in 20..512) bleMtu else 20
        return try {
            var i = 0
            while (i < bytes.size) {
                val part = bytes.copyOfRange(i, minOf(i + chunk, bytes.size))
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                    if (gatt.writeCharacteristic(ch, part, type) != BluetoothStatusCodes.SUCCESS) return false
                } else {
                    ch.writeType = type
                    ch.value = part
                    if (!gatt.writeCharacteristic(ch)) return false
                }
                i += part.size
                Thread.sleep(if (noResp) 22 else 45)
            }
            true
        } catch (e: Exception) { false }
    }

    private fun closeBle() {
        try { bleGatt?.disconnect() } catch (_: Exception) {}
        try { bleGatt?.close() } catch (_: Exception) {}
        bleGatt = null; bleWriteChar = null; bleMtu = 20
    }

    private fun closeAll() { closeSocket(); closeBle() }

    @SuppressLint("MissingPermission")
    private fun doPrint(text: String) {
        synchronized(printLock) {
            if (!hasBtPermission()) { ensureBtPermission(); toast("Beri izin Bluetooth lalu coba lagi."); return }
            val ad = adapter()
            if (ad == null || !ad.isEnabled) { toast("Bluetooth mati / tidak tersedia."); return }
            val device = pickDevice()
            if (device == null) { toast("Belum ada printer terpasang (pair di Setelan Bluetooth)."); return }

            val bytes = java.io.ByteArrayOutputStream().apply {
                write(byteArrayOf(0x1B, 0x40))                     // ESC @ init
                write(text.toByteArray(charset("ISO-8859-1")))     // isi struk
                write("\n\n\n".toByteArray())                      // feed
                write(byteArrayOf(0x1D, 0x56, 0x00))               // GS V 0 full cut
            }.toByteArray()

            // Pilih transport sesuai tipe perangkat, coba transport satunya sebagai cadangan
            // (printer dual / salah deteksi). BLE utk EcoPrint; SPP utk classic (mis. IWARE).
            val leFirst = try { device.type == BluetoothDevice.DEVICE_TYPE_LE } catch (_: Exception) { false }
            val order = if (leFirst) booleanArrayOf(true, false) else booleanArrayOf(false, true)

            for (useBle in order) {
                for (attempt in 0 until 2) {
                    val ok = if (useBle) printBle(device, bytes) else printSpp(device, bytes)
                    if (ok) { toast("Struk dikirim ke printer."); return }
                    if (useBle) closeBle()
                    Thread.sleep(300)
                }
            }
            toast("Gagal mencetak. Pastikan printer menyala, dekat, & sudah di-pair di Setelan Bluetooth.")
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
            synchronized(printLock) { closeAll() }   // drop socket lama -> cetak berikutnya sambung ke printer baru
        }

        @JavascriptInterface
        fun getSelectedPrinter(): String = selectedMac() ?: ""

        @JavascriptInterface
        fun requestPermission() { ensureBtPermission() }

        @JavascriptInterface
        fun disconnect() { synchronized(printLock) { closeAll() } }
    }
}
