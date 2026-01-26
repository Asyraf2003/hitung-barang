/* KawatApp Service Worker (simple, sane) */

const VERSION = 'Arbicon-v3';
const STATIC_CACHE = `${VERSION}-static`;
const RUNTIME_CACHE = `${VERSION}-runtime`;

const PRECACHE_URLS = [
  '/offline.html',
  '/manifest.webmanifest',
  '/pwa-icon.svg',
];

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(STATIC_CACHE);

    const results = await Promise.allSettled(
      PRECACHE_URLS.map(u => cache.add(u))
    );

    const hasSuccess = results.some(r => r.status === 'fulfilled');

    if (hasSuccess) {
      self.skipWaiting();
    }
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.map((k) => {
      if (!k.startsWith(VERSION)) return caches.delete(k);
    }));
    await self.clients.claim();
  })());
});

function isHtmlRequest(req) {
  return req.mode === 'navigate' || (req.headers.get('accept') || '').includes('text/html');
}

function shouldCacheHtmlResponse(res) {
  // Jangan cache redirect (biasanya ke /login)
  if (!res || !res.ok) return false;
  if (res.redirected) return false;
  return true;
}

self.addEventListener('fetch', (event) => {
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).catch(() => caches.match('/offline.html'))
    );
    return;
  }
  
  const req = event.request;
  const url = new URL(req.url);

  // Hanya handle GET & same-origin
  if (req.method !== 'GET') return;
  if (url.origin !== self.location.origin) return;

  // HTML navigation: network-first, fallback cache, fallback offline
  if (isHtmlRequest(req)) {
    event.respondWith((async () => {
      try {
        const res = await fetch(req);
                if (shouldCacheHtmlResponse(res)) {
          const cache = await caches.open(RUNTIME_CACHE);
          cache.put(req, res.clone());
        }
        return res;
      } catch {
        const cached = await caches.match(req);
        if (cached) return cached;
        return caches.match('/offline.html');
      }
    })());
    return;
  }

  // Static assets: cache-first (runtime)
  event.respondWith((async () => {
    const cached = await caches.match(req);
    if (cached) return cached;

    try {
      const res = await fetch(req);
      if (res && res.ok) {
        const cache = await caches.open(RUNTIME_CACHE);
        cache.put(req, res.clone());
      }
      return res;
    } catch {
      // asset fail: ya sudah, biarkan error
      return cached || Response.error();
    }
  })());
});
