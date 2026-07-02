# Membangun Aplikasi Tablet Stakko POS (.apk)

Aplikasi tablet Stakko POS adalah **pembungkus (wrapper)** yang menampilkan sistem web
Stakko POS secara layar penuh. Aplikasi **tetap butuh internet** dan **terhubung ke server**
Anda (tidak ada logika bisnis di dalam APK — semua tetap di Laravel/PostgreSQL).

Ada 2 cara. **Opsi A paling mudah** (tanpa install Android Studio).

---

## Opsi A — PWABuilder (rekomendasi, tanpa tooling)

Sistem ini sudah PWA (punya `manifest.json` + service worker + ikon Stakko), jadi bisa
langsung dibungkus jadi APK oleh PWABuilder.

1. Deploy Stakko POS ke domain **HTTPS** publik (mis. `https://app.stakko.id`).
2. Buka **https://www.pwabuilder.com** → masukkan URL tersebut → **Start**.
3. Pilih **Android** → **Generate Package**.
   - Package ID: `id.stakko.pos` (atau domain Anda dibalik).
   - App name: `Stakko POS`.
4. Unduh paket. Isi ZIP: `app-release-signed.apk` (untuk dibagikan langsung) +
   `.aab` (untuk Play Store) + `assetlinks.json` + `signing-key-info.txt` (SIMPAN baik-baik).
5. **Fullscreen tanpa address bar (TWA):** taruh `assetlinks.json` dari PWABuilder ke
   `public/.well-known/assetlinks.json` di server Anda, lalu deploy. Pastikan bisa diakses di
   `https://domain-anda/.well-known/assetlinks.json`.
6. Rename `app-release-signed.apk` → **`stakko-pos.apk`** dan taruh di
   **`public/downloads/stakko-pos.apk`** pada server. Selesai — tombol Download di
   halaman "Aplikasi Tablet" langsung aktif.

> Update `MOBILE_APK_VERSION` di `.env` agar versi yang tampil sesuai.

---

## Opsi B — Android Studio (WebView, kontrol penuh)

Scaffold minimal ada di folder **`mobile/android-webview/`**. Ini WebView sederhana yang
memuat URL server, mendukung upload file (foto menu), dan tombol back.

1. Install **Android Studio** (butuh JDK + Android SDK).
2. Buka folder `mobile/android-webview/` di Android Studio → biarkan Gradle sync
   (wrapper akan dibuat otomatis).
3. Set URL server di `app/src/main/res/values/strings.xml` → `server_url`
   (contoh `https://app.stakko.id`). Untuk server HTTP lokal saat uji coba, lihat
   `network_security_config.xml`.
4. **Build → Build Bundle(s)/APK(s) → Build APK(s)** → hasilnya
   `app/build/outputs/apk/release/` (atau `debug/`).
5. Untuk rilis, buat keystore & tanda tangani (Build → Generate Signed Bundle/APK).
6. Rename hasilnya jadi **`stakko-pos.apk`** → taruh di **`public/downloads/`**.

---

## Catatan

- **HTTPS wajib** untuk PWA/TWA fullscreen. HTTP hanya untuk uji coba (WebView Opsi B
  perlu `usesCleartextTraffic`/network security config).
- Karena hanya wrapper, **update fitur cukup di sisi web** — tidak perlu rebuild APK
  setiap kali (kecuali mengganti ikon/nama/URL).
- Icon & splash sudah tersedia di `public/assets/media/logos/` (stakko-icon-*.png,
  favicon.ico) dan bisa dipakai PWABuilder/Android Studio.
