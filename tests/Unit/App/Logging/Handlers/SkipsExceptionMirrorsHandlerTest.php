<?php

namespace Tests\Unit\App\Logging\Handlers;

use App\Logging\Handlers\SkipsExceptionMirrorsHandler;
use DateTimeImmutable;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCases\PublicTestCase;

#[Group('Logging')]
#[Group('SkipsExceptionMirrorsHandler')]
class SkipsExceptionMirrorsHandlerTest extends PublicTestCase
{
    #[Test]
    public function handle_GivenExceptionAlreadyReportedNatively_ShouldNotForwardRecord(): void
    {
        // Arrange
        $testHandler = new TestHandler();
        $handler     = new SkipsExceptionMirrorsHandler($testHandler);

        // Act
        $handler->handle($this->createUncaughtExceptionRecord(RuntimeException::class, true));

        // Assert
        self::assertCount(0, $testHandler->getRecords());
    }

    /**
     * Handler::$dontReport matches on the exact class while shouldReport() matches with instanceof, so an
     * HttpException subclass is logged without ever being reported natively. Dropping those would make the failure
     * invisible rather than merely deduplicated.
     */
    #[Test]
    public function handle_GivenExceptionNotReportedNatively_ShouldForwardRecord(): void
    {
        // Arrange
        $testHandler = new TestHandler();
        $handler     = new SkipsExceptionMirrorsHandler($testHandler);

        // Act
        $handler->handle($this->createUncaughtExceptionRecord(ConflictHttpException::class, false));

        // Assert
        self::assertCount(1, $testHandler->getRecords());
    }

    /**
     * HandlerLogging::uncaughtException logs under a constant message, and Sentry groups message events by their
     * message - without a fingerprint every kept exception would collapse into a single issue.
     */
    #[Test]
    public function handle_GivenExceptionNotReportedNatively_ShouldFingerprintByExceptionClass(): void
    {
        // Arrange
        $testHandler = new TestHandler();
        $handler     = new SkipsExceptionMirrorsHandler($testHandler);

        // Act
        $handler->handle($this->createUncaughtExceptionRecord(ConflictHttpException::class, false));

        // Assert
        $records = $testHandler->getRecords();
        self::assertSame(
            ['HandlerLogging::uncaughtException', ConflictHttpException::class],
            $records[0]->context['fingerprint'] ?? null,
        );
    }

    #[Test]
    public function handle_GivenOrdinaryStructuredRecord_ShouldForwardRecordUnchanged(): void
    {
        // Arrange
        $testHandler = new TestHandler();
        $handler     = new SkipsExceptionMirrorsHandler($testHandler);

        $record = new LogRecord(
            new DateTimeImmutable(),
            'testing',
            Level::Error,
            'ProcessCombatLogSegmentsLogging::handleSegmentsNotAvailable',
            ['runId' => 42015954],
        );

        // Act
        $handler->handle($record);

        // Assert
        $records = $testHandler->getRecords();
        self::assertCount(1, $records);
        self::assertArrayNotHasKey('fingerprint', $records[0]->context, 'Only exception records get a fingerprint.');
    }

    /**
     * A buffering handler in front of this one forwards through handleBatch(), which would bypass the filter unless it
     * is overridden.
     */
    #[Test]
    public function handleBatch_GivenExceptionAlreadyReportedNatively_ShouldNotForwardRecord(): void
    {
        // Arrange
        $testHandler = new TestHandler();
        $handler     = new SkipsExceptionMirrorsHandler($testHandler);

        // Act
        $handler->handleBatch([
            $this->createUncaughtExceptionRecord(RuntimeException::class, true),
            $this->createUncaughtExceptionRecord(ConflictHttpException::class, false),
        ]);

        // Assert
        self::assertCount(1, $testHandler->getRecords());
    }

    /**
     * Returning false keeps the record bubbling to the rest of a stack's handlers - returning true would end Monolog's
     * handler loop and silently swallow every channel listed after this one.
     */
    #[Test]
    public function handle_GivenDroppedRecord_ShouldNotStopBubbling(): void
    {
        // Arrange
        $handler = new SkipsExceptionMirrorsHandler(new TestHandler());

        // Act
        $handled = $handler->handle($this->createUncaughtExceptionRecord(RuntimeException::class, true));

        // Assert
        self::assertFalse($handled);
    }

    private function createUncaughtExceptionRecord(string $exceptionClass, bool $reportedByErrorTracker): LogRecord
    {
        return new LogRecord(
            new DateTimeImmutable(),
            'testing',
            Level::Error,
            'HandlerLogging::uncaughtException',
            [
                'exceptionClass'         => $exceptionClass,
                'message'                => 'Something broke',
                'reportedByErrorTracker' => $reportedByErrorTracker,
            ],
        );
    }
}
