<?php

namespace QuebecStudioMods\ConsentKit\Core;

/**
 * The browser side of a consent register: it reports a decision once it is
 * made, and carries nothing a visitor could not already see.
 *
 * `sendBeacon` rather than a request, because withdrawing consent reloads the
 * page 600 ms later and would cancel one. The last button pressed tells the
 * action and where it was pressed; a decision reached without a click is the
 * Global Privacy Control answering for the visitor.
 *
 * The endpoint must accept a cross-origin-style POST with no CSRF token:
 * `sendBeacon` sends none.
 */
final class RecordScript
{
    /**
     * @param array<string, mixed> $context Sent with every report, to say which
     *                                      site or application the decision
     *                                      belongs to. Never the fingerprint,
     *                                      which the server computes itself.
     */
    public static function build(string $endpoint, array $context = []): string
    {
        $config = json_encode(
            ['endpoint' => $endpoint, 'context' => (object)$context],
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        return <<<JS
            (function (config) {
                var pressed = null;

                document.addEventListener('click', function (event) {
                    var button = event.target.closest('[data-qsm-ck-action]');

                    if (button) {
                        pressed = {
                            action: button.getAttribute('data-qsm-ck-action'),
                            inDialog: !!button.closest('[data-qsm-ck-dialog]')
                        };
                    }
                }, true);

                document.addEventListener('qsm-consent-kit:change', function (event) {
                    var report = {};

                    for (var key in config.context) {
                        if (Object.prototype.hasOwnProperty.call(config.context, key)) {
                            report[key] = config.context[key];
                        }
                    }

                    report.action = 'save';
                    report.origin = 'gpc';
                    report.categories = (event.detail || {}).cat || {};

                    if (pressed) {
                        report.origin = pressed.inDialog ? 'dialog' : 'banner';

                        if (pressed.action === 'accept' || pressed.action === 'refuse') {
                            report.action = pressed.action === 'accept' ? 'accept-all' : 'refuse-all';
                        }
                    }

                    pressed = null;

                    var body = JSON.stringify(report);

                    if (navigator.sendBeacon) {
                        navigator.sendBeacon(config.endpoint, body);
                        return;
                    }

                    fetch(config.endpoint, { method: 'POST', body: body, keepalive: true }).catch(function () {});
                });
            })($config);
            JS;
    }
}
