<?php

namespace App\Logging\Sentry;

use Sentry\Event;
use Sentry\EventHint;

/**
 * Sentry `before_send` callback that gives each failing scheduled command its own issue.
 *
 * Laravel's ScheduleRunCommand throws a plain Exception with an identical message shape and stack trace (inside vendor
 * code) for every failing scheduled command, so Sentry's default trace-based grouping aggregates failures from
 * unrelated commands (combatlog:detectstaledata, patreon:refreshmembers, ...) into one issue (#3902). Fingerprinting
 * on the command splits them back apart.
 *
 * This lives in a class rather than inline in config/sentry.php because `php artisan config:cache` var_exports every
 * config value and a Closure is not var_export-able - see the note in that file.
 */
class ScheduledCommandFingerprint
{
    /**
     * Fingerprint a scheduled-command failure by the command that failed, leaving every other event untouched.
     */
    public static function apply(Event $event, ?EventHint $hint): ?Event
    {
        $exception = $hint?->exception;

        if ($exception !== null
            && preg_match('/^Scheduled command \[(.+)] failed with exit code \[\d+]\.$/', $exception->getMessage(), $matches) === 1) {
            $command = $matches[1];

            // $command is Illuminate\Console\Application::formatCommandString()'s output: the php
            // binary and artisan binary, each individually shell-escaped (single-quoted) via
            // Illuminate\Support\ProcessUtils::escapeArgument(), followed by the actual artisan
            // command and its arguments. Strip the two quoted binary tokens so the fingerprint (and
            // the resulting issue title) reflects the command that actually failed rather than an
            // environment-dependent interpreter path - without hardcoding 'artisan' as a literal,
            // since ARTISAN_BINARY can override it.
            if (preg_match("/^'[^']*'\\s+'[^']*'\\s+(.+)$/", $command, $commandMatches) === 1) {
                $command = $commandMatches[1];
            }

            $event->setFingerprint(['schedule-run-command-failed', $command]);
        }

        return $event;
    }
}
