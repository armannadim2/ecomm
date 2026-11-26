/*
 * Cookie consent (store/partials/cookie-banner.blade.php). Reads the tm_consent cookie in the browser,
 * so cached pages work for everybody; analytics/ads tags are injected only after consent.
 */
const COOKIE = 'tm_consent';

function readConsent() {
    const raw = document.cookie.split('; ').find((c) => c.startsWith(COOKIE + '='));
    if (!raw) return null;
    try {
        return JSON.parse(decodeURIComponent(raw.slice(COOKIE.length + 1)));
    } catch {
        return null;
    }
}

function loadScript(src) {
    const s = document.createElement('script');
    s.async = true;
    s.src = src;
    document.head.appendChild(s);
}

function loadGa4(id) {
    if (!id || window.gtag) return;
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', id, { anonymize_ip: true });
    loadScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id));
}

function loadPixel(id) {
    if (!id || window.fbq) return;
    const fbq = function () { fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments); };
    fbq.push = fbq; fbq.loaded = true; fbq.version = '2.0'; fbq.queue = [];
    window.fbq = window._fbq = fbq;
    loadScript('https://connect.facebook.net/en_US/fbevents.js');
    window.fbq('init', id);
    window.fbq('track', 'PageView');
}

export default function cookieConsent({ version, endpoint, ga4, pixel }) {
    const saved = readConsent();
    const valid = saved && saved.v === version;

    return {
        open: !valid,
        details: false,
        choice: { analytics: valid ? !!saved.analytics : false, marketing: valid ? !!saved.marketing : false },

        init() {
            if (valid) this.apply(saved);
        },

        apply(c) {
            if (c.analytics) loadGa4(ga4);
            if (c.marketing) loadPixel(pixel);
        },

        async save(analytics, marketing) {
            this.choice = { analytics, marketing };
            this.open = false;
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            try {
                await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token ?? '' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ analytics, marketing }),
                });
            } catch {
                // Offline: the banner will simply be shown again next time.
            }
            this.apply({ analytics, marketing });
        },
    };
}
