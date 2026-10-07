{{-- Analytics & ad pixels (Admin → Settings → Tracking & pixels, App\Support\Tracking). Nothing is printed until an ID
     is set. The small stubs below queue every call at once; the platforms' own scripts load later (setting "load"), and
     resources/js/tracking.js turns this page's events into each platform's format. --}}
@php
    $tracking = app(\App\Support\Tracking::class);
    $trackingSettings = app(\App\Settings\TrackingSettings::class);
@endphp
@if ($tracking->enabled())
    <script>
        (() => {
            const c = (window.tfTracking = {{ \App\Support\Tracking::js($tracking->config(auth('customer')->user())) }});
            const w = window, d = document;
            w.dataLayer = w.dataLayer || [];
            if (c.gtm) w.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
            if (c.ga4) {
                w.gtag = function () { w.dataLayer.push(arguments); };
                w.gtag('js', new Date());
                w.gtag('config', c.ga4);
            }
            if (c.pixel && !w.fbq) {
                const n = (w.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); });
                w._fbq = n; n.push = n; n.loaded = true; n.version = '2.0'; n.queue = [];
                w.fbq('init', c.pixel, c.user || {});
                w.fbq('track', 'PageView');
            }
            if (c.tiktok && !w.ttq) {
                // TikTok's official stub, without the automatic load (ttq.load runs with the other scripts below).
                !function (w, d, t) {
                    w.TiktokAnalyticsObject = t; var ttq = w[t] = w[t] || [];
                    ttq.methods = ['page', 'track', 'identify', 'instances', 'debug', 'on', 'off', 'once', 'ready', 'alias', 'group', 'enableCookie', 'disableCookie', 'holdConsent', 'revokeConsent', 'grantConsent'];
                    ttq.setAndDefer = function (t, e) { t[e] = function () { t.push([e].concat(Array.prototype.slice.call(arguments, 0))); }; };
                    for (var i = 0; i < ttq.methods.length; i++) ttq.setAndDefer(ttq, ttq.methods[i]);
                    ttq.instance = function (t) { for (var e = ttq._i[t] || [], n = 0; n < ttq.methods.length; n++) ttq.setAndDefer(e, ttq.methods[n]); return e; };
                    ttq.load = function (e, n) { var r = 'https://analytics.tiktok.com/i18n/pixel/events.js'; ttq._i = ttq._i || {}, ttq._i[e] = [], ttq._i[e]._u = r, ttq._t = ttq._t || {}, ttq._t[e] = +new Date(), ttq._o = ttq._o || {}, ttq._o[e] = n || {}; n = d.createElement('script'); n.type = 'text/javascript', n.async = !0, n.src = r + '?sdkid=' + e + '&lib=' + t; e = d.getElementsByTagName('script')[0]; e.parentNode.insertBefore(n, e); };
                }(w, d, 'ttq');
                if (c.user) w.ttq.identify({ email: c.user.em, phone_number: c.user.ph, external_id: c.user.external_id });
                w.ttq.page();
            }
            const add = (src) => { const s = d.createElement('script'); s.async = true; s.src = src; d.head.appendChild(s); };
            const load = () => {
                if (load.done) return;
                load.done = true;
                if (c.gtm) add('https://www.googletagmanager.com/gtm.js?id=' + c.gtm);
                if (c.ga4) add('https://www.googletagmanager.com/gtag/js?id=' + c.ga4);
                if (c.pixel) add('https://connect.facebook.net/en_US/fbevents.js');
                if (c.tiktok) w.ttq.load(c.tiktok);
            };
            if (c.load === 'immediately') load();
            else if (c.load === 'on_interaction') ['scroll', 'pointerdown', 'keydown', 'touchstart'].forEach((e) => w.addEventListener(e, load, { once: true, passive: true }));
            else w.addEventListener('load', () => setTimeout(load, 500), { once: true });
        })();
    </script>
@endif
{!! $trackingSettings->head_code !!}
