<?php

namespace App\Logging\Handlers;

use Monolog\Handler\HandlerWrapper;
use Monolog\LogRecord;
use Override;

/**
 * Makes error-level {@see \App\Logging\StructuredLogging} records triageable once they reach the error tracker.
 *
 * These records are messages, not exceptions, and the error tracker groups message events by their message. Since
 * StructuredLogging logs under a constant `ClassLogging::method`, every failure of a given log site collapses into a
 * single issue no matter how many distinct root causes it represents - a hundred different combat log parse failures
 * look like one problem. All the distinguishing detail sits in the context, where nothing can search or group on it.
 *
 * This handler fixes both halves:
 *
 * 1. It derives an explicit fingerprint from the log site plus the failure's exception class and the shape of its
 *    message, so one root cause becomes one issue.
 * 2. It promotes the short domain identifiers to tags, which are the only searchable and aggregatable surface the
 *    error tracker offers. That is what lets a triage pass answer "which runs does this affect" without opening
 *    events one by one.
 *
 * Everything not promoted stays in the context and is filed as extra data by the SDK's handler.
 *
 * Deliberately done as a channel tap rather than at the call sites: adding `tags`/`fingerprint` keys to a
 * StructuredLogging context would leak them into the file, stderr and Discord output, which are the human-facing
 * narrative, and would have to be repeated at every log site that ever wants them.
 */
class FingerprintsStructuredErrorsHandler extends HandlerWrapper
{
    /**
     * Context keys promoted to tags, in the order they should appear. Restricted to short, bounded identifiers -
     * tags are capped at 200 characters and a high-cardinality free-text tag is worse than useless.
     *
     * Notably absent: `rawLine`. It is long, and it is the field carrying player names and GUIDs, so it stays in the
     * context where it is readable during triage but never indexed.
     */
    private const array TAG_CONTEXT_KEYS = [
        'runId',
        'seasonId',
        'combatLogVersion',
        'lineNumber',
        'exceptionClass',
    ];

    /** Context key holding the class of the exception the record describes. */
    private const string EXCEPTION_CLASS_KEY = 'exceptionClass';

    /** Context key holding the failure's message, whose digit-stripped shape is the second fingerprint component. */
    private const string MESSAGE_KEY = 'message';

    /** Hard cap the error tracker applies to tag values; a longer value is dropped rather than truncated. */
    private const int MAX_TAG_LENGTH = 200;

    /**
     * How much of the normalised message contributes to the fingerprint. Long enough to tell failure shapes apart,
     * short enough that a message with an entire combat log line appended cannot make every event unique.
     */
    private const int MAX_FINGERPRINT_MESSAGE_LENGTH = 120;

    #[Override]
    public function handle(LogRecord $record): bool
    {
        return parent::handle($this->fingerprint($this->promoteTags($record)));
    }

    /**
     * Records are forwarded in batches when a buffering handler sits in front of this one, which would otherwise
     * bypass the enrichment entirely.
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
     * Copies the recognised identifiers into the `tags` context key, which the SDK's handler reads out and applies to
     * the scope. Values that cannot be expressed as a short string are skipped rather than truncated - a silently
     * cut-off identifier is a wrong identifier.
     */
    private function promoteTags(LogRecord $record): LogRecord
    {
        $tags = [];
        foreach (self::TAG_CONTEXT_KEYS as $key) {
            $value = $record->context[$key] ?? null;

            if ($value === null || is_array($value) || is_object($value)) {
                continue;
            }

            $value = (string)$value;
            if ($value === '' || strlen($value) > self::MAX_TAG_LENGTH) {
                continue;
            }

            $tags[$key] = $value;
        }

        if ($tags === []) {
            return $record;
        }

        // Merge rather than replace - a caller that set its own tags keeps them, and wins on a key collision
        return $record->with(context: array_merge($record->context, [
            'tags' => array_merge($tags, is_array($record->context['tags'] ?? null) ? $record->context['tags'] : []),
        ]));
    }

    /**
     * Splits a log site's single issue into one issue per root cause.
     *
     * The log site itself stays the first component, so two different log sites that happen to hit the same exception
     * class never merge. A record carrying neither an exception class nor a message is left alone: there is nothing to
     * distinguish causes by, and the error tracker's default message grouping is already the right answer.
     *
     * A fingerprint set by another handler is never overwritten - {@see SkipsExceptionMirrorsHandler} sets its own for
     * uncaught-exception mirrors, and it has better information about those than this handler does.
     */
    private function fingerprint(LogRecord $record): LogRecord
    {
        if (isset($record->context['fingerprint'])) {
            return $record;
        }

        $exceptionClass = $record->context[self::EXCEPTION_CLASS_KEY] ?? null;
        $message        = $record->context[self::MESSAGE_KEY] ?? null;

        $components = array_values(array_filter([
            is_string($exceptionClass) ? $exceptionClass : null,
            is_string($message) ? self::normalizeMessage($message) : null,
        ]));

        if ($components === []) {
            return $record;
        }

        return $record->with(context: array_merge($record->context, [
            'fingerprint' => [$record->message, ...$components],
        ]));
    }

    /**
     * Reduces a failure message to its stable shape, so "Unable to find combat log version 21" and "... version 22"
     * group together.
     *
     * Collapsing digit runs alone is not enough here. Parsing exceptions routinely interpolate the offending combat
     * log line into their message - `CombatLogStringParser` throws "Unbalanced quotes in string <line>", and
     * `CombatLogService` wraps that as the message - and such a line carries character names and hex GUIDs. Left
     * alone those make every single failing line its own fingerprint, which would defeat both the grouping and the
     * throttle that keys on it. Quoted segments and GUID-shaped tokens are therefore flattened first, and the result
     * is capped so a very long line cannot reintroduce uniqueness past the cap.
     *
     * Known limitation, inherited from the manual clustering this replaces: two genuinely different root causes that
     * happen to share a normalised shape merge into one issue. That is acceptable for a triage feed - re-cluster by
     * hand once the dominant cause is fixed.
     */
    private static function normalizeMessage(string $message): string
    {
        // Quoted segments in a combat log line are names and free text; the failure shape does not depend on them
        $normalized = (string)preg_replace('/"[^"]*"/', '""', $message);

        // An unbalanced quote - the single most common parse failure - leaves a dangling `"Somebody-Realm` that the
        // pass above cannot match, and that trailing name is per-player. Flatten it the same way.
        $normalized = (string)preg_replace('/"[^"]*$/', '""', $normalized);

        // WoW GUIDs (Player-1234-0A1B2C3D, Creature-0-3886-2291-16093-208862-00003EDB78) are per-entity by design
        $normalized = (string)preg_replace('/\b[A-Za-z]+(?:-[0-9A-Fa-f]+)+\b/', '#', $normalized);

        $normalized = (string)preg_replace('/\d+/', '#', $normalized);
        $normalized = (string)preg_replace('/\s+/', ' ', $normalized);

        return mb_substr(trim($normalized), 0, self::MAX_FINGERPRINT_MESSAGE_LENGTH);
    }
}
