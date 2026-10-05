/*
 * Service worker of kadir.gulec.tr (registered by resources/js/pwa.js).
 *
 * Pages always come from the network: the notebook is live (Livewire,
 * admin), so nothing is served stale. Only when there is no connection a
 * page falls back to the offline sheet. Push messages carry
 * {title, body, url, tag} (App\Support\Push\PushNotifier); a tap opens url.
 *
 * Bump VERSION when the offline sheet or the icons change.
 */
const VERSION = 'kg-v1';
const OFFLINE_URL = '/offline.html';
const PRECACHE = [OFFLINE_URL, '/icons/icon-192.png', '/icons/badge-96.png', '/favicon.svg'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(VERSION).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== VERSION).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') {
        return;
    }

    event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE_URL)));
});

self.addEventListener('push', (event) => {
    let message = {};

    try {
        message = event.data ? event.data.json() : {};
    } catch {
        message = { body: event.data ? event.data.text() : '' };
    }

    event.waitUntil(
        self.registration.showNotification(message.title || 'kadir.gulec.tr', {
            body: message.body || '',
            icon: '/icons/icon-192.png',
            badge: '/icons/badge-96.png',
            lang: 'tr',
            tag: message.tag || undefined,
            renotify: Boolean(message.tag),
            data: { url: message.url || '/' },
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = new URL(event.notification.data?.url || '/', self.location.origin).href;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
            const open = windows.find((client) => client.url === url);

            if (open) {
                return open.focus();
            }

            return self.clients.openWindow(url);
        }),
    );
});
