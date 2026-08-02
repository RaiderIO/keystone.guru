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
     *
     * @param string $store Name of the deduplication store to use, passed as a tap argument
     *                      (`DeduplicateHandlers::class . ':sentry'`). Every channel must use its own store:
     *                      the store is keyed on level + message only, so channels sharing one would suppress
     *                      each other's records - an error already sent to Discord would silently never reach
     *                      Sentry, and vice versa.
     */
    public function __invoke(Logger $logger, string $store = 'discord'): void
    {
        $handlers = [];
        foreach ($logger->getHandlers() as $handler) {
            $handlers[] = new FlushingDeduplicationHandler(
                $handler,
                storage_path(sprintf('logs/%s-deduplication.log', $store)),
                Level::Error,
                self::DEDUPLICATION_TIME_SECONDS,
            );
        }

        $logger->setHandlers($handlers);
    }
}
