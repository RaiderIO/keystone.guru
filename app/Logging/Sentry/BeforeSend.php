<?php

namespace App\Logging\Sentry;

use Sentry\Event;
use Sentry\EventHint;

/**
 * Composes the individual `before_send` callbacks into the single callable `config/sentry.php`'s
 * `before_send` option accepts.
 */
class BeforeSend
{
    public static function apply(Event $event, ?EventHint $hint): ?Event
    {
        $event = ScheduledCommandFingerprint::apply($event, $hint);

        if ($event === null) {
            return null;
        }

        return ExceptionLevelOverride::apply($event, $hint);
    }
}
