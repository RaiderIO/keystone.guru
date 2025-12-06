<?php
use App\Models\Release;

/**
 * @var Release $latestRelease
 */
?>
    <!--suppress ALL -->
<script>
    var _rollbarConfig = {
        accessToken: '{{ config('keystoneguru.rollbar.client_access_token') }}',
        captureUncaught: true,
        captureUnhandledRejections: true,
        payload: {
            environment: '{{ config('app.env') }}',
            // context: 'rollbar/test'
            client: {
                javascript: {
                    code_version: '{{ $latestRelease->version }}',
                    // source_map_enabled: true,
                    // guess_uncaught_frames: true
                }
            },
            custom: {
                <?php // See sitescripts.blade.php ?>
                correlation_id: correlationId,
            }
        }
    };

    <?php /* The rest of the snippet is in lib/rollbar/rollbar.js*/ ?>
    // End Rollbar Snippet
</script>
