const CACHE_NAME = 'topitup-v2';
const ASSETS_TO_CACHE = [
    '/',
    '/index.php',
    '/dashboard.php',
    '/style.css?v=1.3.8',
    '/manifest.webmanifest',
    '/files/logo.png',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(ASSETS_TO_CACHE).catch(err => {
                console.warn('Cache addAll failed:', err);
            });
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.filter(name => name !== CACHE_NAME)
                    .map(name => caches.delete(name))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    // Simple network-first fallback to cache strategy
    event.respondWith(
        fetch(event.request)
            .catch(() => caches.match(event.request))
    );
});
