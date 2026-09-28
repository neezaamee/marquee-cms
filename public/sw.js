/**
 * Marquee CMS — Service Worker (PWA & Offline Vault)
 * Handles offline caching of kitchen slips, payment receipts, static assets, and offline fallback.
 */

const CACHE_VERSION = 'v1.0.1';
const STATIC_CACHE = `marquee-static-${CACHE_VERSION}`;
const SLIPS_CACHE = `marquee-slips-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `marquee-dynamic-${CACHE_VERSION}`;

// Static resources to pre-cache on installation
const PRECACHE_ASSETS = [
    '/offline',
    '/manifest.json',
    '/assets/css/theme.css',
    '/assets/css/user.css',
    '/assets/js/config.js',
    '/vendors/simplebar/simplebar.min.js',
    '/vendors/simplebar/simplebar.min.css',
    '/assets/img/favicons/android-chrome-192x192.png',
    '/assets/img/favicons/android-chrome-512x512.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Installation: Cache core static assets & offline fallback
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => {
            return Promise.allSettled(
                PRECACHE_ASSETS.map((url) => {
                    return cache.add(new Request(url, { cache: 'reload' })).catch((err) => {
                        console.warn(`[PWA SW] Pre-cache skipped for: ${url}`, err);
                    });
                })
            );
        }).then(() => self.skipWaiting())
    );
});

// Activation: Clean up deprecated caches
self.addEventListener('activate', (event) => {
    const currentCaches = [STATIC_CACHE, SLIPS_CACHE, DYNAMIC_CACHE];
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (!currentCaches.includes(name)) {
                        console.log(`[PWA SW] Deleting obsolete cache: ${name}`);
                        return caches.delete(name);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Interceptor: Handles caching strategies
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Only intercept GET requests
    if (request.method !== 'GET') return;

    // Do not intercept Livewire internal POST/actions or Livewire updates
    if (url.pathname.includes('/livewire/')) return;

    // 1. KITCHEN SLIPS & PAYMENT RECEIPTS (Network First, Cache Fallback)
    const isSlipOrReceipt = 
        url.pathname.includes('/kitchen-slip') || 
        url.pathname.includes('/slip-v2') || 
        url.pathname.includes('/payment-receipt');

    if (isSlipOrReceipt) {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const copy = networkResponse.clone();
                        caches.open(SLIPS_CACHE).then((cache) => {
                            cache.put(request, copy);
                        });
                    }
                    return networkResponse;
                })
                .catch(() => {
                    // Offline fallback: Serve from slips cache
                    return caches.match(request).then((cachedResponse) => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        // If not in cache, fallback to offline notice
                        return caches.match('/offline');
                    });
                })
        );
        return;
    }

    // 2. STATIC ASSETS: Styles, Scripts, Fonts, Images (Stale-While-Revalidate)
    const isStaticAsset = 
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.woff') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.ttf') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.ico') ||
        url.hostname.includes('googleapis.com') ||
        url.hostname.includes('gstatic.com') ||
        url.hostname.includes('cdnjs.cloudflare.com') ||
        url.hostname.includes('cdn.jsdelivr.net');

    if (isStaticAsset) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request)
                    .then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            const copy = networkResponse.clone();
                            caches.open(STATIC_CACHE).then((cache) => {
                                cache.put(request, copy);
                            });
                        }
                        return networkResponse;
                    })
                    .catch(() => cachedResponse);

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // 3. HTML PAGE NAVIGATIONS (Network First, Cache Fallback, Offline Fallback)
    if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const copy = networkResponse.clone();
                        caches.open(DYNAMIC_CACHE).then((cache) => {
                            cache.put(request, copy);
                        });
                    }
                    return networkResponse;
                })
                .catch(() => {
                    return caches.match(request).then((cachedResponse) => {
                        return cachedResponse || caches.match('/offline');
                    });
                })
        );
        return;
    }

    // Default: Normal fetch with dynamic cache fallback
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});

// Client Message Listener: Allows frontend to queue URLs to pre-cache
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'PRECACHE_SLIPS' && Array.isArray(event.data.urls)) {
        caches.open(SLIPS_CACHE).then((cache) => {
            event.data.urls.forEach((url) => {
                fetch(url).then((res) => {
                    if (res && res.status === 200) {
                        cache.put(url, res);
                    }
                }).catch(() => {});
            });
        });
    }
});
