<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown by ProcessRouteFloorThumbnail so the queue worker retries a failed render. The failure itself is
 * already logged by ThumbnailService (a warning while retries remain, an error on the final attempt), so
 * reporting this exception as well would only duplicate that record in Sentry.
 */
class ThumbnailRenderFailedException extends Exception
{
    public function report(): void
    {
    }
}
