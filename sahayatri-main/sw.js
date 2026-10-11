// system/sw.js
// SAHAYATRI Service Worker for Offline User Companion Mode

const CACHE_NAME = 'sahayatri-v2.0';
const ASSETS_TO_CACHE = [
    './',
    'index.php',
    'traveler_dashboard.php',
    'assets/css/style.css',
    'assets/css/leaflet.css',
    'assets/js/app.js',
    'assets/js/map.js',
    'assets/js/safety.js',
    'assets/js/pwa-sync.js',
    'manifest.json'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    // Stale-while-revalidate strategy for seamless offline map & travel info rendering
    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            const fetchPromise = fetch(event.request).then((networkResponse) => {
                if (networkResponse.status === 200 && event.request.method === 'GET') {
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, networkResponse.clone());
                    });
                }
                return networkResponse;
            }).catch(() => {
                // Return cached version when offline
                return cachedResponse;
            });
            return cachedResponse || fetchPromise;
        })
    );
});
