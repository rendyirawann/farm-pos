// Stakko POS Service Worker — subfolder-aware.
// BASE otomatis mengikuti lokasi sw.js: "/" di localhost, "/subfolder/" di server.
const BASE = self.location.href.replace(/sw\.js.*$/, '');
const CACHE_NAME = 'stakko-pos-cache-v5';
const ASSETS_TO_CACHE = [
  BASE + 'admin/kasir',
  BASE + 'assets/css/style.bundle.css',
  BASE + 'assets/css/stakko-brand.css',
  BASE + 'assets/plugins/global/plugins.bundle.css',
  BASE + 'assets/plugins/global/plugins.bundle.js',
  BASE + 'assets/js/scripts.bundle.js',
  BASE + 'assets/media/logos/stakko-logo.png',
  BASE + 'assets/plugins/custom/dexie/dexie.min.js',
  'https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700'
];

// Install: cache app shell (per-file, agar 1 file gagal tidak menggagalkan semua)
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => Promise.allSettled(ASSETS_TO_CACHE.map(url => cache.add(url))))
      .then(() => self.skipWaiting())
  );
});

// Activate: hapus cache versi lama
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.map(key => key !== CACHE_NAME ? caches.delete(key) : null)))
      .then(() => self.clients.claim())
  );
});

// Fetch: Network-first, fallback ke cache (untuk mode offline)
self.addEventListener('fetch', event => {
  const req = event.request;

  // Jangan intercept API / sinkronisasi / non-GET
  if (req.method !== 'GET' || req.url.includes('/api/') || req.url.includes('/sync-offline')) {
    return;
  }

  event.respondWith(
    fetch(req)
      .then(response => {
        if (response && response.status === 200 && response.type === 'basic') {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(req, clone));
        }
        return response;
      })
      .catch(() =>
        caches.match(req).then(cached => {
          if (cached) return cached;
          // Halaman (navigasi) offline -> jatuhkan ke layar Kasir yang sudah di-cache
          if (req.mode === 'navigate') return caches.match(BASE + 'admin/kasir');
        })
      )
  );
});
