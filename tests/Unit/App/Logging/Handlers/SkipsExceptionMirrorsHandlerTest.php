<?php

namespace Tests\Unit\App\Logging\Handlers;

use App\Exceptions\Logging\HandlerLogging;
use App\Logging\Handlers\SkipsExceptionMirrorsHandler;
use DateTimeImmutable;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCases\PublicTestCase;

#[Group('Logging')]
#[Group('SkipsExceptionMirrorsHandler')]
class SkipsExceptionMirrorsHandlerTest extends PublicTestCase
{
    #[Test]
    public function handle_GivenUncaughtExceptionMirror_ShouldNotForwardRecord(): void
    {
        // Arrange
        $testHandler = new TestHandler();
        $handler     = new SkipsExceptionMirrorsHandler($testHandler);

        // Act
        $handler->handle($this->createLogRecord($this->structuredMessageFor('uncaughtException')));

        // Assert
        self::assertCount(0, $testHandler->getRecords());
    }

    #[Test]
    public function handle_GivenTooManyRequestsMirror_ShouldNotForwardRecord(): void
    {
        // Arrange
        $testHandler = new TestHandler();
        $handler     = new SkipsExceptionMirrorsHandler($testHandler);

        // Act
        $handler->handle($this->createLogRecord($this->structuredMessageFor('tooManyRequests')));

        // Assert
        self::assertCount(0, $testHandler->getRecords());
    }

    /**
     * Under APP_TYPE=local, StructuredLogging pads the message and prefixes one '-' per open start() group, so the
     * filter cannot compare the message strictly.
     */
    #[Test]
    public function handle_GivenPrettyPrintedMirror_ShouldNotForwardRecord(): void
    {
        // Arrange
        $testHandler = new TestHandler();
        $handler     = new SkipsExceptionMirrorsHandler($testHandler);

        // Act
        $handler->handle($this->createLogRecord(sprintf('  --%s', $this->structuredMessageFor('uncaughtException'))));

        // Assert
        self::assertCount(0, $testHandler->getRecords());
    }

    #[Test]
    public function handle_GivenOrdinaryStructuredRecord_ShouldForwardRecord(): void
    {
        // Arrange
        $testHandler = new TestHandler();
        $handler     = new SkipsExceptionMirrorsHandler($testHandler);

        // Act
        $handler->handle($this->createLogRecord('ProcessCombatLogSegmentsLogging::handleSegmentsNotAvailable'));

        // Assert
        self::assertCount(1, $testHandler->getRecords());
    }

    /**
     * The filter matches on the message StructuredLogging writes, which is the short class name plus the method name.
     * Deriving it here rather than hardcoding the string means renaming either side fails this test instead of
     * silently turning the filter into a no-op - at which point every uncaught exception would collapse into a single
     * Sentry issue.
     */
    private function structuredMessageFor(string $methodName): string
    {
        $handlerLogging = new ReflectionClass(HandlerLogging::class);

        self::assertTrue(
            $handlerLogging->hasMethod($methodName),
            sprintf('%s::%s no longer exists; the mirror filter needs updating.', $handlerLogging->getName(), $methodName),
        );

        return sprintf('%s::%s', $handlerLogging->getShortName(), $methodName);
    }

    private function createLogRecord(string $message): LogRecord
    {
        return new LogRecord(new DateTimeImmutable(), 'test', Level::Error, $message);
    }
}
