<?php

namespace App\Logging\Sentry;

use App\Exceptions\Handler;
use Psr\Log\LogLevel;
use ReflectionProperty;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\Severity;

/**
 * Sentry `before_send` callback that gives a reported exception the severity `bootstrap/app.php`'s
 * `$exceptions->level()` mapped for it. `Sentry\Laravel\Integration::handles()` captures every
 * reportable exception through its own path (`captureException()`), independently of Laravel's log
 * level handling, so a `$exceptions->level()` call on its own never reaches Sentry - this reads the
 * same mapping back off the bound exception handler and applies it to the outgoing event.
 */
class ExceptionLevelOverride
{
    public static function apply(Event $event, ?EventHint $hint): ?Event
    {
        $exception = $hint?->exception;

        if ($exception === null) {
            return $event;
        }

        $handler = app(Handler::class);

        foreach (new ReflectionProperty($handler::class, 'levels')->getValue($handler) as $type => $level) {
            if ($exception instanceof $type) {
                $event->setLevel(self::toSentrySeverity($level));

                break;
            }
        }

        return $event;
    }

    private static function toSentrySeverity(string $psrLevel): Severity
    {
        return match ($psrLevel) {
            LogLevel::DEBUG                                          => Severity::debug(),
            LogLevel::INFO, LogLevel::NOTICE                         => Severity::info(),
            LogLevel::WARNING                                        => Severity::warning(),
            LogLevel::CRITICAL, LogLevel::ALERT, LogLevel::EMERGENCY => Severity::fatal(),
            default                                                  => Severity::error(),
        };
    }
}
