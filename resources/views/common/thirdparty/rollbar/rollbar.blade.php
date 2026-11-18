<?php
/**
 * @var array<string, mixed>|null $rollbarConfig
 * @var string                    $rollbarBundleRevision
 * @var array<int, string>        $rollbarOwnOrigins
 */
?>
@if($rollbarConfig !== null)
    <!--suppress ALL -->
    <script>
        var _rollbarConfig = @json($rollbarConfig);
        <?php // See sitescripts.blade.php ?>
        _rollbarConfig.payload.custom = {correlation_id: correlationId};
        <?php // Errors from browser extensions, third-party scripts or a bundle cached from a previous release are not ours to fix ?>
        _rollbarConfig.checkIgnore = (function (bundleRevision, ownOrigins) {
            return function (isUncaught, args, payload) {
                let body = payload && payload.body ? payload.body : {};
                let traces = body.trace_chain || (body.trace ? [body.trace] : []);
                let urls = [];
                traces.forEach(function (trace) {
                    (trace.frames || []).forEach(function (frame) {
                        if (typeof frame.filename === 'string') {
                            urls.push(frame.filename);
                        }
                    });
                });

                if (urls.some(url => /^(chrome|moz|safari|safari-web|ms-browser)-extension:\/\//.test(url))) {
                    return true;
                }

                if (urls.some(function (url) {
                    let match = url.match(/\/js\/(?:app|custom|lib)-([^\/?#]+)\.js/);
                    return match !== null && match[1] !== bundleRevision;
                })) {
                    return true;
                }

                let webUrls = urls.filter(url => /^https?:\/\//.test(url));
                return webUrls.length > 0 && !webUrls.some(url => ownOrigins.some(origin => url.indexOf(origin) === 0));
            };
        })(@json($rollbarBundleRevision), @json($rollbarOwnOrigins).concat([window.location.origin]));
        <?php /* The rest of the snippet is in lib/rollbar/rollbar.js */ ?>
    </script>
@endif
