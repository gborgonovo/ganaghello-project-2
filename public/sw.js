// Service worker conservativo per BiG-Log.
// Obiettivo: l'app si apre installata anche con rete assente/instabile, senza
// schermata di errore del browser. NON cachiamo dati o pagine autenticate
// (rischio stato vecchio / di altri utenti): le navigazioni sono network-first
// con fallback offline; solo gli asset statici GET vengono messi in cache.
const CACHE = 'biglog-shell-v1';

// App shell statica (non gli asset buildati con hash: quelli si cachano a runtime).
const PRECACHE = [
    '/offline.html',
    '/manifest.webmanifest',
    '/icons/gp-logo_192.png',
    '/icons/gp-logo_512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Solo GET di stessa origine; tutto il resto (POST, Livewire, esterni) passa diretto.
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;
    if (url.pathname.startsWith('/livewire')) return;

    // Asset statici (build con hash, icone, manifest): stale-while-revalidate.
    const isStatic = url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icons/')
        || url.pathname === '/manifest.webmanifest'
        || url.pathname.endsWith('.woff2');

    if (isStatic) {
        event.respondWith(
            caches.open(CACHE).then(async (cache) => {
                const cached = await cache.match(req);
                const network = fetch(req).then((res) => {
                    if (res && res.ok) cache.put(req, res.clone());
                    return res;
                }).catch(() => cached);
                return cached || network;
            })
        );
        return;
    }

    // Navigazioni HTML: sempre rete (dati freschi), fallback offline se giù.
    // Non mettiamo in cache le pagine: sono autenticate e per-utente.
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() => caches.match('/offline.html'))
        );
        return;
    }
});
