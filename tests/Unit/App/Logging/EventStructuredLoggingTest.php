<?php

namespace Tests\Unit\App\Logging;

use BadMethodCallException;
use Illuminate\Log\LogManager;
use Monolog\Level;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Fixtures\LoggingFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('Logging')]
#[Group('StructuredLogging')]
#[Group('EventStructuredLogging')]
class EventStructuredLoggingTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function call_givenNamedArguments_logsThemAsContextUnderTheClassAndEventName(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'production', 'app.debug' => true]);
        [$logger, $log] = $this->createLog();

        $logger
            ->expects($this->once())
            ->method('log')
            ->with(Level::Error->getName(), 'TestableEventLogging::somethingBroke', $this->callback(
                static fn(array $context) => ($context['itemId'] ?? null) === 5 && ($context['exceptionClass'] ?? null) === 'RuntimeException',
            ));

        // Act
        $log->somethingBroke(itemId: 5, exceptionClass: 'RuntimeException');

        // Assert
        // Checked by the expectation above
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider('call_givenEventWithLevel_logsAtThatLevel_DataProvider')]
    public function call_givenEventWithLevel_logsAtThatLevel(string $event, Level $expectedLevel): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'production', 'app.debug' => true]);
        [$logger, $log] = $this->createLog();

        $logger
            ->expects($this->once())
            ->method('log')
            ->with($expectedLevel->getName(), sprintf('TestableEventLogging::%s', $event));

        // Act
        $log->{$event}(itemId: 1);

        // Assert
        // Checked by the expectation above
    }

    /**
     * @return array<string, array{string, Level}>
     */
    public static function call_givenEventWithLevel_logsAtThatLevel_DataProvider(): array
    {
        return [
            'debug'     => ['somethingDebugged', Level::Debug],
            'notice'    => ['somethingNoticed', Level::Notice],
            'info'      => ['somethingInformed', Level::Info],
            'warning'   => ['somethingWarned', Level::Warning],
            'error'     => ['somethingBroke', Level::Error],
            'critical'  => ['somethingCritical', Level::Critical],
            'alert'     => ['somethingAlerted', Level::Alert],
            'emergency' => ['somethingEmergency', Level::Emergency],
        ];
    }

    /**
     * An error-level event must reach the real log pipeline at error level, because that is the level the discord
     * and sentry channels are configured to pick up - while a warning from the same class must not.
     *
     * @throws Exception
     */
    #[Test]
    public function call_givenErrorEvent_reachesTheAlertingThresholdAndAWarningDoesNot(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'production', 'app.debug' => true]);
        [$logger, $log] = $this->createLog();

        $alertLevels = [];
        $logger
            ->method('log')
            ->willReturnCallback(function (string $level) use (&$alertLevels) {
                if (Level::fromName(ucfirst(strtolower($level)))->isHigherThan(Level::Warning)) {
                    $alertLevels[] = $level;
                }
            });

        // Act
        $log->somethingWarned(itemId: 1);
        $log->somethingBroke(itemId: 2, exceptionClass: 'RuntimeException');

        // Assert
        $this->assertSame([Level::Error->getName()], $alertLevels);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function call_givenStartAndEndEvents_groupsContextOverEventsInBetween(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'production', 'app.debug' => true]);
        [$logger, $log] = $this->createLog();

        $logged = [];
        $logger
            ->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context) use (&$logged) {
                $logged[$message] = $context;
            });

        // Act
        $log->jobStart(jobName: 'import');
        $log->somethingInformed(itemId: 3);
        $log->jobEnd();
        $log->somethingInformed(itemId: 4);

        // Assert
        $this->assertSame('import', $logged['TestableEventLogging::jobStart']['jobName'] ?? null);
        $this->assertArrayHasKey('elapsedMS', $logged['TestableEventLogging::jobEnd']);

        // Persistent context only lives between start and end - the last somethingInformed overwrote the first
        $this->assertArrayNotHasKey('jobName', $logged['TestableEventLogging::somethingInformed']);
        $this->assertSame(4, $logged['TestableEventLogging::somethingInformed']['itemId']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function call_givenStartEvent_keepsContextOnEventsInBetween(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'production', 'app.debug' => true]);
        [$logger, $log] = $this->createLog();

        $inBetweenContext = null;
        $logger
            ->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context) use (&$inBetweenContext) {
                if ($message === 'TestableEventLogging::somethingInformed') {
                    $inBetweenContext = $context;
                }
            });

        // Act
        $log->jobStart(jobName: 'import');
        $log->somethingInformed(itemId: 3);

        // Assert
        $this->assertSame('import', $inBetweenContext['jobName'] ?? null);
        $this->assertSame(3, $inBetweenContext['itemId'] ?? null);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function call_givenUnknownEvent_throwsBadMethodCallException(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'production', 'app.debug' => true]);
        [, $log] = $this->createLog();

        // Assert
        $this->expectException(BadMethodCallException::class);

        // Act
        $log->somethingThatWasNeverDeclared(itemId: 1); // @phpstan-ignore method.notFound
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function call_givenPositionalArgumentsInDebug_throwsBadMethodCallException(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'production', 'app.debug' => true]);
        [, $log] = $this->createLog();

        // Assert
        $this->expectException(BadMethodCallException::class);

        // Act
        $log->somethingBroke(5, 'RuntimeException');
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function call_givenPositionalArgumentsOutsideDebug_stillLogsWithNumberedKeys(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'production', 'app.debug' => false]);
        [$logger, $log] = $this->createLog();

        $logger
            ->expects($this->once())
            ->method('log')
            ->with(Level::Error->getName(), 'TestableEventLogging::somethingBroke', $this->callback(
                static fn(array $context) => ($context['argument0'] ?? null) === 5 && ($context['argument1'] ?? null) === 'RuntimeException',
            ));

        // Act
        $log->somethingBroke(5, 'RuntimeException');

        // Assert
        // Checked by the expectation above
    }

    /**
     * @return array{MockObject&LogManager, TestableEventLogging}
     *
     * @throws Exception
     */
    private function createLog(): array
    {
        $logger = LoggingFixtures::createLogManager($this);

        return [$logger, new TestableEventLogging($logger)];
    }
}
