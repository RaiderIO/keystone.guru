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
use Sentry\Laravel\SentryHandler;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
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
     * Parse error log sites carry an exceptionClass of their own; their fingerprint belongs to
     * FingerprintsStructuredErrorsHandler, which never overwrites one that is already set.
     */
    #[Test]
    public function handle_givenStructuredRecordCarryingAnExceptionClass_forwardsItWithoutAFingerprint(): void
    {
        // Arrange
        $testHandler = new TestHandler();
        $handler     = new SkipsExceptionMirrorsHandler($testHandler);

        $record = new LogRecord(
            new DateTimeImmutable(),
            'testing',
            Level::Error,
            'ProcessCombatLogSegmentsLogging::handleParseError',
            ['exceptionClass' => RuntimeException::class, 'message' => 'Invalid parameter count'],
        );

        // Act
        $handler->handle($record);

        // Assert
        $records = $testHandler->getRecords();
        self::assertCount(1, $records);
        self::assertArrayNotHasKey('fingerprint', $records[0]->context);
    }

    /**
     * The SDK's handler runs with report_exceptions off and drops any record holding a Throwable under `exception`,
     * so a caught exception logged through StructuredLogging only reaches the error tracker once it is flattened.
     */
    #[Test]
    public function handle_givenStructuredRecordCarryingAThrowable_forwardsItWithTheThrowableFlattened(): void
    {
        // Arrange
        $testHandler = new TestHandler();
        $handler     = new SkipsExceptionMirrorsHandler($testHandler);

        $record = $this->createStructuredRecordWithThrowable(['rawEvent' => '12: SPELL_CAST_SUCCESS']);

        // Act
        $handler->handle($record);

        // Assert
        $records = $testHandler->getRecords();
        self::assertCount(1, $records);
        self::assertArrayNotHasKey('exception', $records[0]->context);
        self::assertSame(RuntimeException::class, $records[0]->context['exceptionClass'] ?? null);
        self::assertSame('Invalid parameter count', $records[0]->context['message'] ?? null);
        self::assertSame('12: SPELL_CAST_SUCCESS', $records[0]->context['rawEvent'] ?? null);
    }

    #[Test]
    public function handle_givenStructuredRecordCarryingAThrowableAndItsOwnKeys_keepsTheRecordsKeys(): void
    {
        // Arrange
        $testHandler = new TestHandler();
        $handler     = new SkipsExceptionMirrorsHandler($testHandler);

        $record = $this->createStructuredRecordWithThrowable([
            'exceptionClass' => 'App\\Exceptions\\SomeWrapperException',
            'message'        => 'Wrapped failure',
        ]);

        // Act
        $handler->handle($record);

        // Assert
        $records = $testHandler->getRecords();
        self::assertCount(1, $records);
        self::assertSame('App\\Exceptions\\SomeWrapperException', $records[0]->context['exceptionClass'] ?? null);
        self::assertSame('Wrapped failure', $records[0]->context['message'] ?? null);
    }

    /**
     * Laravel's exception handler logs every exception it reports under the exception's own message, alongside the
     * SDK capturing that same exception natively - this record is the duplicate.
     */
    #[Test]
    public function handle_givenFrameworkLogOfAReportedException_doesNotForwardRecord(): void
    {
        // Arrange
        $testHandler = new TestHandler();
        $handler     = new SkipsExceptionMirrorsHandler($testHandler);

        $exception = new RuntimeException('Something broke');
        $record    = new LogRecord(
            new DateTimeImmutable(),
            'testing',
            Level::Error,
            $exception->getMessage(),
            ['exception' => $exception],
        );

        // Act
        $handled = $handler->handle($record);

        // Assert
        self::assertCount(0, $testHandler->getRecords());
        self::assertFalse($handled);
    }

    #[Test]
    public function handle_givenStructuredRecordCarryingAThrowableInFrontOfTheSentryHandler_capturesAMessage(): void
    {
        // Arrange
        $hub = $this->createMock(HubInterface::class);
        $hub->method('withScope')->willReturnCallback(static fn(callable $callback) => $callback(new Scope()));
        $hub->expects($this->never())->method('captureException');
        $hub->expects($this->once())
            ->method('captureMessage')
            ->with('CombatLogServiceLogging::parseCombatLogParseEventsException');

        $handler = new SkipsExceptionMirrorsHandler(new SentryHandler($hub, Level::Error->value, true, false));

        // Act
        $handler->handle($this->createStructuredRecordWithThrowable([]));
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

    /** @param array<string, mixed> $context */
    private function createStructuredRecordWithThrowable(array $context): LogRecord
    {
        return new LogRecord(
            new DateTimeImmutable(),
            'testing',
            Level::Error,
            'CombatLogServiceLogging::parseCombatLogParseEventsException',
            array_merge($context, ['exception' => new RuntimeException('Invalid parameter count')]),
        );
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
