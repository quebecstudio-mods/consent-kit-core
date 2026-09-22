/* quebecstudio-mods/consent-kit-core */
(function (conf) {
    var state = null;

    try {
        var match = document.cookie.match(new RegExp('(?:^|; )' + conf.name + '=([^;]*)'));

        if (match) {
            var parsed = JSON.parse(decodeURIComponent(match[1]));

            /* A stale or tampered cookie is treated as absent. */
            if (parsed && parsed.v === conf.version && typeof parsed.cat === 'object') {
                state = parsed;
            }
        }
    } catch (e) {
        state = null;
    }

    /* A browser sending Global Privacy Control has refused, and the banner
       has nothing left to ask. A decision already stored wins over it: it was
       made on this site, where the signal covers every site at once. */
    if (!state && conf.gpcHidesBanner && navigator.globalPrivacyControl === true) {
        state = { v: conf.version, ts: 0, cat: {}, gpc: true };
    }

    var analytics = !!(state && state.cat && state.cat[conf.analytics]);
    var marketing = !!(state && state.cat && state.cat[conf.marketing]);

    window._paq = window._paq || [];
    window._paq.push(['requireCookieConsent']);

    /* Consent Mode v2: denied by default. */
    window.dataLayer = window.dataLayer || [];

    if (typeof window.gtag !== 'function') {
        window.gtag = function () { window.dataLayer.push(arguments); };
    }

    window.gtag('consent', 'default', {
        analytics_storage: 'denied',
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        wait_for_update: 500
    });

    if (analytics) {
        window._paq.push(['setCookieConsentGiven']);
    }

    if (analytics || marketing) {
        window.gtag('consent', 'update', {
            analytics_storage: analytics ? 'granted' : 'denied',
            ad_storage: marketing ? 'granted' : 'denied',
            ad_user_data: marketing ? 'granted' : 'denied',
            ad_personalization: marketing ? 'granted' : 'denied'
        });
    }

    if (!state) {
        document.documentElement.className += ' qsm-consent-kit-needed';
    }

    window.qsmConsentKitBootstrap = { state: state };
})(__CONF__);
