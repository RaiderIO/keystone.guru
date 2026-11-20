<?php

use App\Http\View\Composers\SentryBrowserComposer;

/**
 * @var array<string, mixed>|null $sentryBrowserConfig
 * @var array<int, string>        $sentryBrowserScrubFields
 * @var string                    $sentryBrowserBundleRevision
 * @var array<int, string>        $sentryBrowserOwnOrigins
 */
?>
@if($sentryBrowserConfig !== null)
    <script src="{{ SentryBrowserComposer::SDK_URL }}" integrity="{{ SentryBrowserComposer::SDK_INTEGRITY }}"
            crossorigin="anonymous"></script>
    <script>
        (function (config, scrubFields, bundleRevision, ownOrigins) {
            if (typeof Sentry === 'undefined') {
                return;
            }

            let scrubKeys = new Set(scrubFields.map(field => field.toLowerCase()));
            let scrubUrl = function (text) {
                return text.replace(/([?&])([^=&#\s]+)=([^&#\s]*)/g, function (match, separator, name) {
                    return scrubKeys.has(name.toLowerCase()) ? `${separator}${name}=[Filtered]` : match;
                });
            };
            let scrub = function (value, depth) {
                if (depth > 8 || value === null || typeof value !== 'object') {
                    return value;
                }
                Object.keys(value).forEach(function (key) {
                    if (scrubKeys.has(key.toLowerCase())) {
                        value[key] = '[Filtered]';
                    } else if (typeof value[key] === 'string') {
                        value[key] = scrubUrl(value[key]);
                    } else {
                        scrub(value[key], depth + 1);
                    }
                });
                return value;
            };

            <?php // Errors from a bundle cached from a previous release are not ours to fix ?>
            let isFromStaleBundle = function (event) {
                return ((event.exception && event.exception.values) || []).some(function (exception) {
                    return ((exception.stacktrace && exception.stacktrace.frames) || []).some(function (frame) {
                        let match = typeof frame.filename === 'string' ? frame.filename.match(/\/js\/(?:app|custom|lib)-([^\/?#]+)\.js/) : null;
                        return match !== null && match[1] !== bundleRevision;
                    });
                });
            };

            config.allowUrls = ownOrigins;
            config.denyUrls = [/^(chrome|moz|safari|safari-web|ms-browser)-extension:\/\//];
            config.beforeSend = function (event) {
                if (isFromStaleBundle(event)) {
                    return null;
                }
                ['request', 'extra', 'tags', 'breadcrumbs'].forEach(part => scrub(event[part], 0));
                ((event.exception && event.exception.values) || []).forEach(function (exception) {
                    if (typeof exception.value === 'string') {
                        exception.value = scrubUrl(exception.value);
                    }
                });
                if (typeof event.message === 'string') {
                    event.message = scrubUrl(event.message);
                }
                return event;
            };
            <?php // See sitescripts.blade.php ?>
            config.initialScope = Object.assign({}, config.initialScope, {tags: {correlation_id: correlationId}});

            Sentry.init(config);
        })(@json($sentryBrowserConfig), @json($sentryBrowserScrubFields), @json($sentryBrowserBundleRevision), @json($sentryBrowserOwnOrigins).concat([window.location.origin]));
    </script>
@endif
