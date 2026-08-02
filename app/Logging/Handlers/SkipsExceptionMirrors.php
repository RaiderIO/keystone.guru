<?php

namespace App\Logging\Handlers;

use Illuminate\Log\Logger;

/**
 * Log channel tap that wraps every handler of the channel in a {@see SkipsExceptionMirrorsHandler}, so records
 * mirroring a natively reported exception never reach it.
 *
 * Only ever applied to the `sentry` channel - Discord and the file logs are the human-facing narrative and want
 * those lines.
 */
class SkipsExceptionMirrors
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke(Logger $logger): void
    {
        $handlers = [];
        foreach ($logger->getHandlers() as $handler) {
            $handlers[] = new SkipsExceptionMirrorsHandler($handler);
        }

        $logger->setHandlers($handlers);
    }
}
