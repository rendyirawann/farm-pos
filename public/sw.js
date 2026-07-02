const CACHE_NAME = 'stakko-pos-cache-v4';
const ASSETS_TO_CACHE = [
  '/admin/kasir',
  '/assets/css/style.bundle.css',
  '/assets/css/stakko-brand.css',
  '/assets/plugins/global/plugins.bundle.css',
  '/assets/plugins/global/plugins.bundle.js',
  '/assets/js/scripts.bundle.js',
  '/assets/media/logos/stakko-logo.png',
  '/assets/plugins/custom/dexie/dexie.min.js',
  'https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700'
];

// Install Event
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('[Service Worker] Caching app shell assets');
      return cache.addAll(ASSETS_TO_CACHE);
    }).then(() => self.skipWaiting())
  );
});

// Activate Event
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.map(key => {
          if (key !== CACHE_NAME) {
            console.log('[Service Worker] Removing old cache:', key);
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Intercept (Network First, fallback to cache for pages/assets)
self.addEventListener('fetch', event => {
  // Prevent caching for API calls, midtrans webhook, or login POST requests
  if (
    event.request.url.includes('/api/') || 
    event.request.url.includes('/sync-offline') ||
    event.request.method !== 'GET'
  ) {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // If response is valid, clone it and put in cache for offline use
        if (response && response.status === 200 && response.type === 'basic') {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseClone);
          });
        }
        return response;
      })
      .catch(() => {
        // Network failed, try to serve from cache
        return caches.match(event.request).then(cachedResponse => {
          if (cachedResponse) {
            return cachedResponse;
          }
          // Special fallback for root or main pages if offline
          if (event.request.mode === 'navigate') {
            // Check if we are trying to go to an order page
            if (event.request.url.includes('/admin/kasir/order/')) {
              // Try to find ANY cached order page in our cache
              return caches.open(CACHE_NAME).then(cache => {
                return cache.keys().then(requests => {
                  const matchedRequest = requests.find(req => req.url.includes('/admin/kasir/order/'));
                  if (matchedRequest) {
                    console.log('[Service Worker] Serving cached order shell:', matchedRequest.url);
                    return cache.match(matchedRequest);
                  }
                  // Fallback if no order page is cached at all
                  return caches.match('/admin/kasir');
                });
              });
            }
            return caches.match('/admin/kasir');
          }
        });
      })
  );
});
