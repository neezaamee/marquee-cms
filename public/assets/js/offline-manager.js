/**
 * Marquee CMS — Offline & PWA Client Manager
 * Registers service worker, detects network state, shows status pills, and manages slip pre-caching.
 */
(function() {
    'use strict';

    window.MarqueeOffline = {
        isOnline: navigator.onLine,

        // Register Service Worker
        registerServiceWorker: function() {
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js')
                        .then(function(registration) {
                            // Service Worker Registered
                        })
                        .catch(function(err) {
                            console.warn('[PWA] Service Worker registration failed:', err);
                        });
                });
            }
        },

        // Request Service Worker to pre-cache slip URLs
        precacheSlips: function(urls) {
            if (!Array.isArray(urls) || urls.length === 0) return;
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({
                    type: 'PRECACHE_SLIPS',
                    urls: urls
                });
            }
        },

        // Create or update floating offline status pill
        updateStatusPill: function(isOnline) {
            let pill = document.getElementById('marquee-offline-pill');
            if (!pill) {
                pill = document.createElement('div');
                pill.id = 'marquee-offline-pill';
                pill.className = 'marquee-offline-pill';
                document.body.appendChild(pill);
            }

            if (!isOnline) {
                pill.className = 'marquee-offline-pill is-offline show';
                pill.innerHTML = `
                    <span class="pill-dot"></span>
                    <i class="fas fa-wifi-slash me-1"></i> 
                    <span>Offline Mode &mdash; Serving Cached Slips & Data</span>
                `;
            } else {
                if (pill.classList.contains('is-offline')) {
                    pill.className = 'marquee-offline-pill is-online show';
                    pill.innerHTML = `
                        <span class="pill-dot"></span>
                        <i class="fas fa-check-circle me-1"></i> 
                        <span>Back Online &mdash; Cloud Connected</span>
                    `;
                    setTimeout(function() {
                        pill.classList.remove('show');
                    }, 3500);
                }
            }
        }
    };

    // Initialize Service Worker
    window.MarqueeOffline.registerServiceWorker();

    // Setup network listeners
    window.addEventListener('online', function() {
        window.MarqueeOffline.isOnline = true;
        window.MarqueeOffline.updateStatusPill(true);
        window.dispatchEvent(new CustomEvent('marquee:network-change', { detail: { isOnline: true } }));
    });

    window.addEventListener('offline', function() {
        window.MarqueeOffline.isOnline = false;
        window.MarqueeOffline.updateStatusPill(false);
        window.dispatchEvent(new CustomEvent('marquee:network-change', { detail: { isOnline: false } }));
    });

    // Initial check on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        if (!navigator.onLine) {
            window.MarqueeOffline.updateStatusPill(false);
        }
    });
})();
