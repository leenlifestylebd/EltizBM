const CACHE_NAME = 'eltiz-bm-v1';

// Assets to cache on install (shell only — keeps it small)
const SHELL_ASSETS = [
    '/',
    '/offline',
];

// ── Install ──────────────────────────────────────────────
self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(SHELL_ASSETS).catch(() => {}))
    );
});

// ── Activate (clean old caches) ───────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

// ── Fetch strategy: Network-first, fallback to cache ─────
self.addEventListener('fetch', event => {
    const { request } = event;

    // Only handle GET requests to our own origin
    if (request.method !== 'GET') return;
    if (!request.url.startsWith(self.location.origin)) return;

    // Skip API / admin / auth routes from caching
    const url = new URL(request.url);
    const skipPaths = ['/api/', '/sanctum/', '/telescope/', '/_debugbar/'];
    if (skipPaths.some(p => url.pathname.startsWith(p))) return;

    event.respondWith(
        fetch(request)
            .then(response => {
                // Cache successful HTML & static responses
                if (response.ok && (
                    request.destination === 'document' ||
                    request.destination === 'style' ||
                    request.destination === 'script' ||
                    request.destination === 'image' ||
                    request.destination === 'font'
                )) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
                }
                return response;
            })
            .catch(() => {
                // Offline fallback
                return caches.match(request).then(cached => {
                    if (cached) return cached;
                    if (request.destination === 'document') {
                        return caches.match('/offline');
                    }
                })
            })
    );
});
