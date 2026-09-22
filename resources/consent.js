/*
 * quebecstudio-mods/consent-kit-core — <qsm-consent-kit>
 *
 * No dependencies: a custom element is inert to Alpine, Vue and Svelte alike.
 * The markup comes from Twig; controls are found through data-qsm-ck-action, not
 * through their classes, so a site can restyle the banner freely.
 *
 * If this file never loads, nothing is activated and the consent mode set by
 * the bootstrap stays denied.
 */
(function () {
    'use strict';

    if (!('customElements' in window) || customElements.get('qsm-consent-kit')) {
        return;
    }

    var EVENT = 'qsm-consent-kit:change';

    function readCookie(name) {
        var parts = document.cookie ? document.cookie.split('; ') : [];

        for (var i = 0; i < parts.length; i++) {
            var eq = parts[i].indexOf('=');

            if (eq > -1 && parts[i].slice(0, eq) === name) {
                return parts[i].slice(eq + 1);
            }
        }

        return null;
    }

    function writeCookie(name, value, maxAge) {
        var parts = [
            name + '=' + encodeURIComponent(value),
            'path=/',
            'max-age=' + maxAge,
            'samesite=lax'
        ];

        // Never unconditional: the dev server runs over HTTP on Windows.
        if (location.protocol === 'https:') {
            parts.push('secure');
        }

        document.cookie = parts.join('; ');
    }

    /**
     * Deletes a cookie across every plausible domain variant. Analytics sets
     * _ga on the registrable domain with a leading dot, so a host-only delete
     * fails silently.
     */
    function deleteCookie(name) {
        var expired = '; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT';
        var host = location.hostname;
        var parts = host.split('.');

        document.cookie = name + '=' + expired;

        for (var i = 0; i < parts.length - 1; i++) {
            var domain = parts.slice(i).join('.');

            document.cookie = name + '=' + expired + '; domain=' + domain;
            document.cookie = name + '=' + expired + '; domain=.' + domain;
        }
    }

    function deleteCookiesMatching(pattern) {
        var parts = document.cookie ? document.cookie.split('; ') : [];

        for (var i = 0; i < parts.length; i++) {
            var name = parts[i].split('=')[0];

            if (pattern.test(name)) {
                deleteCookie(name);
            }
        }
    }

    function QsmConsentKit() {
        return Reflect.construct(HTMLElement, [], QsmConsentKit);
    }

    QsmConsentKit.prototype = Object.create(HTMLElement.prototype);
    QsmConsentKit.prototype.constructor = QsmConsentKit;
    Object.setPrototypeOf(QsmConsentKit, HTMLElement);

    QsmConsentKit.prototype.connectedCallback = function () {
        if (this.initialised) {
            return;
        }

        this.initialised = true;
        this.config = this.readConfig();

        if (!this.config) {
            return;
        }

        this.state = this.readState();
        this.setupRoot();
        this.bindActions();
        this.bindDialog();
        this.reflect();

        window.qsmConsentKit = this.api();
        window.qsmConsentKit.ready = true;

        if (this.state) {
            this.activateAll();
        }
    };

    QsmConsentKit.prototype.readConfig = function () {
        var script = this.querySelector('script[type="application/json"]');

        if (!script) {
            return null;
        }

        try {
            return JSON.parse(script.textContent);
        } catch (e) {
            // Unreadable config must activate nothing.
            return null;
        }
    };

    /**
     * Current state, or null when no valid decision exists.
     *
     * A browser sending Global Privacy Control has already refused, so the
     * banner has nothing to ask: the state comes back as a refusal, which
     * keeps the banner shut and every optional category off.
     *
     * That refusal is never written to a cookie. It belongs to the browser,
     * is re-read on every page, and stops applying the moment the visitor
     * turns the signal off. A cookie would outlive it.
     *
     * A stored decision wins over the signal: clicking Accept on this site is
     * more specific than a setting that covers every site, and the visitor
     * would not understand a choice that refuses to stick.
     */
    QsmConsentKit.prototype.readState = function () {
        var raw = readCookie(this.config.cookieName);

        if (!raw) {
            return this.refusedByBrowser();
        }

        try {
            var value = JSON.parse(decodeURIComponent(raw));

            if (!value || value.v !== this.config.version || typeof value.cat !== 'object') {
                return this.refusedByBrowser();
            }

            return value;
        } catch (e) {
            return this.refusedByBrowser();
        }
    };

    QsmConsentKit.prototype.refusedByBrowser = function () {
        // A site that would rather still tell visitors what it uses keeps the
        // banner: the refusal holds either way, since every optional category
        // starts unchecked and nothing is set before a choice.
        if (!this.signalsRefusal() || !this.config.gpcHidesBanner) {
            return null;
        }

        return {
            v: this.config.version,
            ts: Math.floor(Date.now() / 1000),
            cat: {},
            gpc: true
        };
    };

    QsmConsentKit.prototype.signalsRefusal = function () {
        return navigator.globalPrivacyControl === true;
    };

    QsmConsentKit.prototype.setupRoot = function () {
        this.root = this;
    };

    QsmConsentKit.prototype.bindActions = function () {
        var self = this;

        this.root.addEventListener('click', function (event) {
            var target = event.target.closest('[data-qsm-ck-action]');

            if (!target || !self.contains(target) && self.root !== target.getRootNode()) {
                return;
            }

            var action = target.dataset.qsmAction;

            if (action === 'accept') {
                self.acceptAll();
            } else if (action === 'refuse') {
                self.refuseAll();
            } else if (action === 'manage') {
                self.openPanel(target);
            } else if (action === 'cancel') {
                self.closePanel();
            } else if (action === 'save') {
                self.savePanel();
            } else if (action === 'details') {
                self.toggleDetails(target);
            } else if (action === 'reopen') {
                self.openBanner();
            }
        });
    };

    /** Escape closes the dialog natively, so focus is restored from `close`. */
    QsmConsentKit.prototype.bindDialog = function () {
        var dialog = this.dialog();
        var self = this;

        if (!dialog) {
            return;
        }

        dialog.addEventListener('close', function () {
            self.onPanelClose();
        });
    };

    QsmConsentKit.prototype.dialog = function () {
        return this.root.querySelector('[data-qsm-ck-dialog]');
    };

    /** Checkboxes always start from the stored cookie, so Escape cancels. */
    QsmConsentKit.prototype.openPanel = function (invoker) {
        var dialog = this.dialog();

        if (!dialog) {
            return;
        }

        this.panelInvoker = invoker || null;
        this.syncCheckboxes();

        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            // No <dialog> support: usable, without focus trap or top layer.
            dialog.setAttribute('open', '');
        }

        var title = dialog.querySelector('.qsm-ck-dialog-title');

        if (title) {
            title.focus();
        }
    };

    QsmConsentKit.prototype.closePanel = function () {
        var dialog = this.dialog();

        if (!dialog) {
            return;
        }

        if (typeof dialog.close === 'function') {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
        }
    };

    QsmConsentKit.prototype.onPanelClose = function () {
        if (this.panelInvoker && this.panelInvoker.isConnected) {
            this.panelInvoker.focus();
        }

        this.panelInvoker = null;
    };

    QsmConsentKit.prototype.syncCheckboxes = function () {
        var self = this;

        this.root.querySelectorAll('[data-qsm-ck-category]').forEach(function (input) {
            if (input.disabled) {
                return;
            }

            input.checked = self.granted(input.dataset.qsmCategory);
        });
    };

    QsmConsentKit.prototype.savePanel = function () {
        var categories = {};

        this.root.querySelectorAll('[data-qsm-ck-category]').forEach(function (input) {
            if (!input.disabled) {
                categories[input.dataset.qsmCategory] = input.checked;
            }
        });

        this.closePanel();
        this.commit(categories);
    };

    QsmConsentKit.prototype.toggleDetails = function (button) {
        var id = button.getAttribute('aria-controls');

        // getElementById, not querySelector: an id needing escaping would throw.
        var details = id ? (this.root.getElementById
            ? this.root.getElementById(id)
            : document.getElementById(id)) : null;

        if (!details) {
            return;
        }

        var opening = button.getAttribute('aria-expanded') !== 'true';

        // inert, not hidden: the content stays rendered so it can animate.
        if (opening) {
            details.setAttribute('data-open', '');
            details.removeAttribute('inert');
        } else {
            details.removeAttribute('data-open');
            details.setAttribute('inert', '');
        }

        button.setAttribute('aria-expanded', opening ? 'true' : 'false');
    };

    /**
     * Reflects the state in the DOM: `banner` while a decision is awaited,
     * `idle` once it is made. The class on <html> is a styling hook.
     */
    QsmConsentKit.prototype.reflect = function () {
        var showing = !this.state || this.hasAttribute('open');
        var wrapper = this.root.querySelector('[data-qsm-ck-wrapper]');
        var notice = this.root.querySelector('[data-qsm-ck-gpc]');

        if (wrapper) {
            wrapper.dataset.state = showing ? 'banner' : 'idle';
        }

        // Only while the refusal is the browser's: once the visitor saves a
        // choice of their own, the notice would describe something that no
        // longer governs.
        if (notice) {
            notice.hidden = !(this.signalsRefusal() && (!this.state || this.state.gpc));
        }

        document.documentElement.classList.toggle('qsm-consent-kit-needed', showing);
    };

    QsmConsentKit.prototype.openBanner = function () {
        this.setAttribute('open', '');
        this.reflect();

        var banner = this.root.querySelector('[data-qsm-ck-banner]');

        if (banner) {
            banner.focus();
        }
    };

    QsmConsentKit.prototype.closeBanner = function () {
        this.removeAttribute('open');
        this.reflect();
    };

    QsmConsentKit.prototype.announce = function (message) {
        var status = this.root.querySelector('[data-qsm-ck-status]');

        if (status) {
            status.textContent = message || '';
        }
    };

    QsmConsentKit.prototype.categoryHandles = function () {
        return (this.config.categories || [])
            .filter(function (category) { return !category.required; })
            .map(function (category) { return category.handle; });
    };

    QsmConsentKit.prototype.acceptAll = function () {
        var granted = {};

        this.categoryHandles().forEach(function (handle) { granted[handle] = true; });
        this.commit(granted);
    };

    QsmConsentKit.prototype.refuseAll = function () {
        var refused = {};

        this.categoryHandles().forEach(function (handle) { refused[handle] = false; });
        this.commit(refused);
    };

    /**
     * Stores a decision and applies it. A withdrawal forces a reload: there is
     * no reliable way to unload gtag.js once it has run.
     */
    QsmConsentKit.prototype.commit = function (categories) {
        var previous = this.state;
        var revoked = this.revokedSince(previous, categories);

        this.state = {
            v: this.config.version,
            ts: Math.floor(Date.now() / 1000),
            cat: categories
        };

        writeCookie(
            this.config.cookieName,
            JSON.stringify(this.state),
            this.config.cookieMaxAge
        );

        this.signal(categories);
        this.activateAll();
        this.removeAttribute('open');
        this.reflect();

        document.dispatchEvent(new CustomEvent(EVENT, { detail: this.state }));

        if (revoked.length) {
            this.purge(revoked);
            this.announce(this.config.texts.reloadNotice);
            window.setTimeout(function () { location.reload(); }, 600);
            return;
        }

        this.announce(this.config.texts.confirmation);
    };

    QsmConsentKit.prototype.revokedSince = function (previous, next) {
        if (!previous || !previous.cat) {
            return [];
        }

        return Object.keys(previous.cat).filter(function (handle) {
            return previous.cat[handle] && !next[handle];
        });
    };

    QsmConsentKit.prototype.signal = function (categories) {
        var analytics = !!categories[this.config.analyticsCategory];
        var marketing = !!categories[this.config.marketingCategory];

        window._paq = window._paq || [];
        window._paq.push([analytics ? 'setCookieConsentGiven' : 'forgetCookieConsentGiven']);

        if (typeof window.gtag === 'function') {
            window.gtag('consent', 'update', {
                analytics_storage: analytics ? 'granted' : 'denied',
                ad_storage: marketing ? 'granted' : 'denied',
                ad_user_data: marketing ? 'granted' : 'denied',
                ad_personalization: marketing ? 'granted' : 'denied'
            });
        }
    };

    /** Only Matomo and Analytics are known here; the reload covers the rest. */
    QsmConsentKit.prototype.purge = function (revoked) {
        if (revoked.indexOf(this.config.analyticsCategory) === -1) {
            return;
        }

        deleteCookiesMatching(/^_ga/);
        deleteCookiesMatching(/^_pk_/);
    };

    QsmConsentKit.prototype.granted = function (handle) {
        var category = (this.config.categories || []).filter(function (item) {
            return item.handle === handle;
        })[0];

        if (category && category.required) {
            return true;
        }

        return !!(this.state && this.state.cat && this.state.cat[handle]);
    };

    QsmConsentKit.prototype.activateAll = function () {
        var self = this;

        (this.config.categories || []).forEach(function (category) {
            if (self.granted(category.handle)) {
                self.activate(category.handle);
            }
        });
    };

    /**
     * Activates the tags marked for a category. Changing a script's `type` in
     * place never runs it: the node has to be recreated.
     */
    QsmConsentKit.prototype.activate = function (handle) {
        var selector = '[data-consent="' + handle + '"]:not([data-consent-done])';

        document.querySelectorAll('script' + selector).forEach(function (original) {
            var script = document.createElement('script');

            for (var i = 0; i < original.attributes.length; i++) {
                var attribute = original.attributes[i];

                if (attribute.name === 'type' || attribute.name === 'data-consent-src') {
                    continue;
                }

                script.setAttribute(attribute.name, attribute.value);
            }

            if (original.dataset.consentSrc) {
                script.src = original.dataset.consentSrc;
            } else {
                script.textContent = original.textContent;
            }

            original.setAttribute('data-consent-done', '');
            original.parentNode.insertBefore(script, original.nextSibling);
        });

        document.querySelectorAll('iframe' + selector).forEach(function (frame) {
            if (frame.dataset.consentSrc) {
                frame.src = frame.dataset.consentSrc;
                frame.setAttribute('data-consent-done', '');
            }
        });
    };

    QsmConsentKit.prototype.api = function () {
        var self = this;

        return {
            ready: true,

            get: function () { return self.state; },

            granted: function (handle) { return self.granted(handle); },

            /** Runs immediately when consent has already been given. */
            on: function (handle, callback) {
                if (self.granted(handle)) {
                    callback();
                    return;
                }

                document.addEventListener(EVENT, function listener() {
                    if (self.granted(handle)) {
                        document.removeEventListener(EVENT, listener);
                        callback();
                    }
                });
            },

            set: function (categories) { self.commit(categories || {}); },

            acceptAll: function () { self.acceptAll(); },

            refuseAll: function () { self.refuseAll(); },

            open: function () { self.openBanner(); },

            close: function () { self.closeBanner(); }
        };
    };

    customElements.define('qsm-consent-kit', QsmConsentKit);

    /**
     * Replaces a facade with the player.
     *
     * `autoplay` is on for a click and off for a lift: a video appearing on
     * page load and starting its own sound is worse than the friction the
     * lift removes.
     */
    function loadVideo(wrapper, options) {
        var id = wrapper.dataset.qsmVideoId;

        if (!id || wrapper.dataset.qsmVideoLoaded) {
            return;
        }

        var settings = options || {};
        var frame = document.createElement('iframe');

        frame.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(id) +
            '?rel=0' + (settings.autoplay ? '&autoplay=1' : '');
        frame.title = wrapper.dataset.qsmVideoTitle || 'YouTube';
        frame.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        frame.allowFullscreen = true;
        frame.setAttribute('frameborder', '0');

        wrapper.dataset.qsmVideoLoaded = 'true';
        wrapper.replaceChildren(frame);

        // Focus follows a click, since the control the visitor activated is
        // gone. It must not follow a lift: nothing was activated, and moving
        // focus on load would throw a screen reader out of its place.
        if (settings.focus) {
            frame.setAttribute('tabindex', '-1');
            frame.focus();
        }
    }

    /**
     * Video facade. Delegated on the document, not on <qsm-consent-kit>: a page can
     * hold a video without a banner, and content may arrive later.
     */
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-qsm-ck-action="video"]');
        var wrapper = button && button.closest('[data-qsm-ck-video]');

        if (wrapper) {
            loadVideo(wrapper, { autoplay: true, focus: true });
        }
    });

    /**
     * Lifts the facades whose category the visitor has accepted.
     *
     * Reads the bootstrap state rather than the banner: the banner may not be
     * on this page, while the `<head>` script always is. The server only
     * writes the attribute for a category it actually declares, so a consent
     * naming a category the inventory has dropped lifts nothing.
     */
    function liftFacades() {
        var state = (window.qsmConsentKit && window.qsmConsentKit.get()) ||
            (window.qsmConsentKitBootstrap && window.qsmConsentKitBootstrap.state);

        if (!state || !state.cat) {
            return;
        }

        document.querySelectorAll('[data-qsm-ck-video-consent]').forEach(function (wrapper) {
            if (state.cat[wrapper.dataset.qsmVideoConsent]) {
                loadVideo(wrapper, { autoplay: false, focus: false });
            }
        });
    }

    // A withdrawal reloads the page, so a lifted video becomes a facade again.
    document.addEventListener(EVENT, liftFacades);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', liftFacades);
    } else {
        liftFacades();
    }
})();
