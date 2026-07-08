<?php

namespace App\Logging\Handlers;

use Monolog\Handler\DeduplicationHandler;
use Monolog\LogRecord;
use Override;

/**
 * Monolog's DeduplicationHandler buffers records and only deduplicates + forwards them on close(), which under
 * Octane's long-lived workers can delay alerts indefinitely. This variant flushes after every record instead, so
 * a record is forwarded immediately while duplicates within the time window are still suppressed.
 */
class FlushingDeduplicationHandler extends DeduplicationHandler
{
    #[Override]
    public function handle(LogRecord $record): bool
    {
        $result = parent::handle($record);

        $this->flush();

        return $result;
    }
}
