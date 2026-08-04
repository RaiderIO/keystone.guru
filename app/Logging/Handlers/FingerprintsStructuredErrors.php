<?php

namespace App\Logging\Handlers;

use Illuminate\Log\Logger;

/**
 * Log channel tap that wraps every handler of the channel in a {@see FingerprintsStructuredErrorsHandler}, so
 * error-level structured records arrive at the error tracker grouped by root cause and tagged with the identifiers a
 * triage pass searches on.
 *
 * Only ever applied to the `sentry` channel - the file logs and Discord already carry the full context inline and
 * have no concept of tags or fingerprints.
 */
class FingerprintsStructuredErrors
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke(Logger $logger): void
    {
        $handlers = [];
        foreach ($logger->getHandlers() as $handler) {
            $handlers[] = new FingerprintsStructuredErrorsHandler($handler);
        }

        $logger->setHandlers($handlers);
    }
}
