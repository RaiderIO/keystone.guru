<?php

namespace App\Logging\Handlers;

use Monolog\Handler\HandlerWrapper;
use Monolog\LogRecord;
use Override;
use Throwable;

/**
 * Keeps the error tracker's view of uncaught exceptions usable.
 *
 * `Handler::report()` logs every uncaught exception through `HandlerLogging` *and* lets the SDK report the exception
 * itself. Two problems follow, and this handler fixes both without discarding anything the SDK never saw:
 *
 * 1. When the SDK did report it, the log record is a strictly worse duplicate - no stack trace - so it is dropped.
 * 2. When the SDK did not report it (an HttpException subclass, say - `Handler::$dontReport` matches on the exact
 *    class while `shouldReport()` matches with instanceof, so the two disagree), the record is the only trace of the
 *    failure and must be kept. But `HandlerLogging::uncaughtException` logs under a constant message, and Sentry
 *    groups message events by their message, so every such exception would collapse into one issue. Those records
 *    get a fingerprint derived from the exception class instead, which SentryHandler reads out of the context.
 *
 * Whether the SDK reported it is not inferred here - `Handler::report()` passes `shouldReport()`'s own answer along
 * in the log context, so this stays correct when the suppression rules change.
 *
 * 3. A record carrying a Throwable under the `exception` context key is dropped by the SDK's handler outright
 *    (`report_exceptions` is off). That is right for the framework's own log of an exception it reports natively -
 *    recognisable by its message being the exception's message - but every other such record is a caught exception
 *    the SDK never saw. Those have the Throwable flattened into the `exceptionClass`/`message` keys, so they arrive as
 *    message events that {@see FingerprintsStructuredErrorsHandler} can fingerprint and tag.
 */
class SkipsExceptionMirrorsHandler extends HandlerWrapper
{
    /** Context key carrying whether the exception handler reported this exception natively. */
    private const string REPORTED_KEY = 'reportedByErrorTracker';

    /** Context key holding the class of the exception the record describes. */
    private const string EXCEPTION_CLASS_KEY = 'exceptionClass';

    /** Context key holding the failure's message. */
    private const string MESSAGE_KEY = 'message';

    /** Context key under which the SDK's handler looks for a Throwable to report - and, with reporting off, drops. */
    private const string EXCEPTION_KEY = 'exception';

    #[Override]
    public function handle(LogRecord $record): bool
    {
        if (($record->context[self::REPORTED_KEY] ?? false) === true) {
            // Returning false rather than true so the record still bubbles to the other handlers of a stack
            return false;
        }

        $exception = $record->context[self::EXCEPTION_KEY] ?? null;
        if ($exception instanceof Throwable) {
            if ($record->message === $exception->getMessage()) {
                return false;
            }

            $record = $this->flattenException($record, $exception);
        }

        return parent::handle($this->fingerprintByExceptionClass($record));
    }

    /**
     * Records are forwarded in batches when a buffering handler sits in front of this one, which would otherwise
     * bypass the filter entirely.
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
     * Keys the record already carries win, so a log site that passes its own exceptionClass/message keeps them.
     */
    private function flattenException(LogRecord $record, Throwable $exception): LogRecord
    {
        $context = $record->context;
        unset($context[self::EXCEPTION_KEY]);

        return $record->with(context: $context + [
            self::EXCEPTION_CLASS_KEY => $exception::class,
            self::MESSAGE_KEY         => $exception->getMessage(),
        ]);
    }

    /**
     * Splits the constant `HandlerLogging::uncaughtException` message into one group per exception class.
     */
    private function fingerprintByExceptionClass(LogRecord $record): LogRecord
    {
        $exceptionClass = $record->context[self::EXCEPTION_CLASS_KEY] ?? null;

        if (!array_key_exists(self::REPORTED_KEY, $record->context) || !is_string($exceptionClass)) {
            return $record;
        }

        return $record->with(context: array_merge($record->context, [
            'fingerprint' => [$record->message, $exceptionClass],
        ]));
    }
}
