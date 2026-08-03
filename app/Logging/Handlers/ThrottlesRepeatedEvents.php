<?php

namespace App\Logging\Handlers;

use Illuminate\Log\Logger;

/**
 * Log channel tap that wraps every handler of the channel in a {@see ThrottlesRepeatedEventsHandler}, bounding how
 * much of a single repeating failure reaches the error tracker.
 *
 * Only ever applied to the `sentry` channel - the file logs are the complete record and must stay complete.
 */
class ThrottlesRepeatedEvents
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke(Logger $logger): void
    {
        $handlers = [];
        foreach ($logger->getHandlers() as $handler) {
            $handlers[] = new ThrottlesRepeatedEventsHandler($handler);
        }

        $logger->setHandlers($handlers);
    }
}
