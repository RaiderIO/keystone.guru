<?php

namespace App\Logging\Handlers;

use Illuminate\Support\Facades\Cache;
use Monolog\Handler\HandlerWrapper;
use Monolog\LogRecord;
use Override;

/**
 * Caps how many times the same failure is forwarded to the error tracker within a window.
 *
 * The error tracker groups events, but it still meters every one of them. A single bad WoW patch can fail every
 * combat log line of every run, and unthrottled that burns the account's whole event budget in hours - at which point
 * events are dropped indiscriminately and genuinely novel errors are lost precisely when the site is least healthy.
 *
 * The file and stderr channels are untouched and keep the complete record, so nothing is actually lost; this only
 * bounds the mirror. The trade is that a throttled issue's event count becomes a lower bound rather than the true
 * occurrence count - read volume off the logs, not off the tracker.
 *
 * Deliberately not built on Monolog's DeduplicationHandler: that one buffers and forwards through handleBatch(), and
 * the SDK's handler compares a Monolog 3 Level enum against an integer there, so the batch filters itself empty and
 * every record is silently discarded (#3792). This handler makes its decision inline and never buffers.
 */
class ThrottlesRepeatedEventsHandler extends HandlerWrapper
{
    /** How many events of the same signature are forwarded per window before the rest are dropped. */
    private const int MAX_EVENTS_PER_WINDOW = 10;

    private const int WINDOW_SECONDS = 3600;

    /** Cache key prefix, kept distinct so a cache sweep can target throttle state alone. */
    private const string CACHE_KEY_PREFIX = 'logging:error-tracker-throttle';

    #[Override]
    public function handle(LogRecord $record): bool
    {
        if (!$this->shouldForward($record)) {
            // Returning false rather than true so the record still bubbles to the other handlers of a stack
            return false;
        }

        return parent::handle($record);
    }

    /**
     * Records are forwarded in batches when a buffering handler sits in front of this one, which would otherwise
     * bypass the throttle entirely.
     *
     * @param array<int, LogRecord> $records
     */
    #[Override]
    public function handleBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    /**
     * Counts this record against its signature's window and reports whether it is still within the allowance.
     *
     * A cache failure must never cost us an error report, so anything unexpected forwards the record.
     */
    private function shouldForward(LogRecord $record): bool
    {
        $key = sprintf('%s:%s', self::CACHE_KEY_PREFIX, sha1($this->signature($record)));

        // add() only succeeds when the key is absent, which both starts the window and counts the first event
        if (Cache::add($key, 1, self::WINDOW_SECONDS)) {
            return true;
        }

        $count = Cache::increment($key);

        // Stores that cannot increment (or a key that expired between the two calls) return false/null - forward it
        if (!is_int($count)) {
            return true;
        }

        return $count <= self::MAX_EVENTS_PER_WINDOW;
    }

    /**
     * The throttling key. Prefers the fingerprint, so the throttle bounds exactly what the tracker will group into
     * one issue; falls back to the message, which is what the tracker groups on when no fingerprint is set.
     */
    private function signature(LogRecord $record): string
    {
        $fingerprint = $record->context['fingerprint'] ?? null;

        if (is_array($fingerprint) && $fingerprint !== []) {
            return implode('|', array_map(static fn(mixed $part): string => (string)$part, $fingerprint));
        }

        return $record->message;
    }
}
