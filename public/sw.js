const CACHE_NAME = 'futebol-na-tv-v3';
const OFFLINE_URL = '/offline.html';
const STATIC_ASSETS = [
    OFFLINE_URL,
    '/images/futebol-na-tv-logo.png',
    '/images/pwa/icon-192.png',
    '/images/pwa/icon-512.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    if (event.request.mode === 'navigate') {
        const url = new URL(event.request.url);
        const excludedPaths = ['/admin', '/push', '/pwa', '/buscar', '/cdn-cgi'];
        const isPublicPage = url.origin === self.location.origin
            && url.search === ''
            && url.pathname !== '/jogos'
            && !excludedPaths.some((path) => url.pathname === path || url.pathname.startsWith(`${path}/`));

        if (!isPublicPage) {
            event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE_URL)));
            return;
        }

        const cachedResponse = caches.match(event.request);
        const freshResponse = fetch(event.request).then((response) => {
            if (response.ok && response.type === 'basic') {
                const copy = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
            }

            return response;
        });

        event.waitUntil(freshResponse.catch(() => undefined));
        event.respondWith(
            cachedResponse
                .then((cached) => cached || freshResponse)
                .catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) return;

    if (['style', 'script', 'image', 'font'].includes(event.request.destination)) {
        event.respondWith(
            caches.match(event.request).then((cached) => cached || fetch(event.request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                }
                return response;
            }))
        );
    }
});

self.addEventListener('push', (event) => {
    let payload = {};
    try {
        payload = event.data?.json() || {};
    } catch (_) {
        payload = { body: event.data?.text() || 'Confira as novidades do Futebol na TV.' };
    }

    const title = payload.title || 'Futebol na TV';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/images/pwa/icon-192.png',
        badge: payload.badge || '/images/pwa/icon-192.png',
        image: payload.image,
        data: payload.data || { url: '/' },
        tag: payload.tag || 'futebol-na-tv',
        lang: payload.lang || 'pt-BR',
        vibrate: payload.vibrate || [180, 80, 180],
        actions: payload.actions || [],
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const destination = new URL(event.notification.data?.url || '/', self.location.origin).href;

    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
        for (const client of windows) {
            if (client.url === destination && 'focus' in client) return client.focus();
        }
        return clients.openWindow(destination);
    }));
});
