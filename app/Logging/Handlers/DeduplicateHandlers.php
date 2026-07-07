<?php

namespace App\Logging\Handlers;

use Illuminate\Log\Logger;
use Monolog\Level;

/**
 * Log channel tap that wraps every handler of the channel in a deduplication handler: the same error repeated
 * within the time window (e.g. an error inside a per-combat-log-line loop) is forwarded once instead of flooding
 * the channel and hitting Discord webhook rate limits exactly when the signal matters most.
 */
class DeduplicateHandlers
{
    private const int DEDUPLICATION_TIME_SECONDS = 60;

    /**
     * Customize the given logger instance.
     */
    public function __invoke(Logger $logger): void
    {
        $handlers = [];
        foreach ($logger->getHandlers() as $handler) {
            $handlers[] = new FlushingDeduplicationHandler(
                $handler,
                storage_path('logs/discord-deduplication.log'),
                Level::Error,
                self::DEDUPLICATION_TIME_SECONDS,
            );
        }

        $logger->setHandlers($handlers);
    }
}
