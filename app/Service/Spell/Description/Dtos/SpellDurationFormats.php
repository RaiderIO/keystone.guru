<?php

namespace App\Service\Spell\Description\Dtos;

/**
 * How the game client writes a duration, in one locale.
 *
 * These are the client's own `GlobalStrings` entries (`SPELL_DURATION_SEC` and friends) rather than
 * strings of ours: a description's sentence comes from the client, so the unit inside it has to read
 * the way the client would have written it. They carry a printf placeholder for the number and may
 * carry the client's `|4singular:plural;` macro, both of which the parser fills in.
 */
class SpellDurationFormats
{
    /** The `GlobalStrings` tag holding each of these, in the order the constructor takes them. */
    public const array GLOBAL_STRING_TAGS = [
        'seconds'        => 'SPELL_DURATION_SEC',
        'minutes'        => 'SPELL_DURATION_MIN',
        'hours'          => 'SPELL_DURATION_HOURS',
        'days'           => 'SPELL_DURATION_DAYS',
        'untilCancelled' => 'SPELL_DURATION_UNTIL_CANCELLED',
    ];

    public function __construct(
        public readonly string $seconds = '%.1f sec',
        public readonly string $minutes = '%.1f min',
        public readonly string $hours = '%.1f |4hour:hrs;',
        public readonly string $days = '%.1f |4day:days;',
        public readonly string $untilCancelled = 'until canceled',
    ) {
    }

    /**
     * Build the set from the client's `GlobalStrings` text, keyed by tag. A tag the build does not carry
     * keeps its English default - an odd unit reads better than none at all.
     *
     * @param array<string, string> $globalStrings
     */
    public static function fromGlobalStrings(array $globalStrings): self
    {
        $defaults = new self();

        $resolve = static fn(string $property, string $tag): string => trim($globalStrings[$tag] ?? '') === ''
            ? $defaults->{$property}
            : $globalStrings[$tag];

        return new self(
            seconds: $resolve('seconds', self::GLOBAL_STRING_TAGS['seconds']),
            minutes: $resolve('minutes', self::GLOBAL_STRING_TAGS['minutes']),
            hours: $resolve('hours', self::GLOBAL_STRING_TAGS['hours']),
            days: $resolve('days', self::GLOBAL_STRING_TAGS['days']),
            untilCancelled: $resolve('untilCancelled', self::GLOBAL_STRING_TAGS['untilCancelled']),
        );
    }
}
