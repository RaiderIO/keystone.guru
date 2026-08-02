<?php

namespace App\Logging\Handlers;

use Monolog\Handler\HandlerWrapper;
use Monolog\LogRecord;
use Override;

/**
 * Drops the structured log records that merely mirror an exception the Sentry SDK already captured itself.
 *
 * `Handler::report()` reports every uncaught exception to Sentry (through `Integration::handles()` in
 * bootstrap/app.php) *and* logs it at error level through `HandlerLogging`. Left alone, both arrive: the real
 * event with a stack trace, plus a strictly worse log-derived twin.
 *
 * The twin is not merely redundant. `HandlerLogging::uncaughtException()` logs `get_defined_vars()` under a
 * constant message, and Sentry groups message events by their message - so every uncaught exception in the
 * application would collapse into a single issue titled `HandlerLogging::uncaughtException`.
 */
class SkipsExceptionMirrorsHandler extends HandlerWrapper
{
    /**
     * @var array<int, string> Structured messages whose exception the SDK reports natively.
     *                         `HandlerLogging::tooManyRequests` is included for the same reason: it too runs
     *                         alongside the SDK's own capture of the ThrottleRequestsException.
     */
    private const array MIRRORED_MESSAGES = [
        'HandlerLogging::uncaughtException',
        'HandlerLogging::tooManyRequests',
    ];

    #[Override]
    public function handle(LogRecord $record): bool
    {
        return !$this->isExceptionMirror($record) && parent::handle($record);
    }

    /**
     * Whether the record mirrors an exception that is reported natively.
     */
    private function isExceptionMirror(LogRecord $record): bool
    {
        foreach (self::MIRRORED_MESSAGES as $mirroredMessage) {
            // Not a strict comparison: under APP_TYPE=local, StructuredLogging pads the message and prefixes it
            // with one '-' per open start() group, so the method name is a substring rather than the whole message
            if (str_contains($record->message, $mirroredMessage)) {
                return true;
            }
        }

        return false;
    }
}
