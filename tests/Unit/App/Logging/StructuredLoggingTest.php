<?php

namespace Tests\Unit\App\Logging;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\Fixtures\LoggingFixtures;
use Tests\TestCases\PublicTestCase;

class StructuredLoggingTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    #[Group('StructuredLogging')]
    public function start_GivenStartCalled_ShouldKeepContextPersistent(): void
    {
        // Arrange
        config(['app.log_level' => 'debug']);

        $logger            = LoggingFixtures::createLogManager($this);
        $log               = new TestableStructuredLogging($logger);
        $persistentContext = ['test' => 'test'];
        $context           = ['test2' => 'test2'];

        $logger
            ->expects($this->exactly(3))
            ->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context = []) {
                self::assertArrayHasKey('test', $context);

                // The message is prefixed with padding and one dash per open context group
                match (trim($message)) {
                    '- firstStart' => self::assertArrayNotHasKey('test2', $context),
                    '- log'        => self::assertArrayHasKey('test2', $context),
                    '- firstEnd'   => self::assertArrayNotHasKey('test2', $context),
                    default        => self::fail(sprintf('Unexpected log message: %s', $message)),
                };
            });

        // Act
        $log->start('firstStart', $persistentContext);
        $log->debug('log', $context);
        $log->end('firstEnd');

        // Assert
        // Already checked in the callback
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('StructuredLogging')]
    public function start_GivenNestedStartCalled_ShouldKeepContextPersistent(): void
    {
        // Arrange
        config(['app.log_level' => 'debug']);

        $logger                  = LoggingFixtures::createLogManager($this);
        $log                     = new TestableStructuredLogging($logger);
        $persistentContext       = ['test' => 'test'];
        $persistentNestedContext = ['nested' => 'nested'];
        $context                 = ['test2' => 'test2'];

        $logger
            ->expects($this->exactly(5))
            ->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context = []) {
                self::assertArrayHasKey('test', $context);

                // The message is prefixed with padding and one dash per open context group
                switch (trim($message)) {
                    case '- firstStart':
                        self::assertArrayNotHasKey('nested', $context);
                        self::assertArrayNotHasKey('test2', $context);
                        break;
                    case '-- nestedStart':
                        self::assertArrayHasKey('nested', $context);
                        self::assertArrayNotHasKey('test2', $context);
                        break;
                    case '-- log':
                        self::assertArrayHasKey('nested', $context);
                        self::assertArrayHasKey('test2', $context);
                        break;
                    case '-- nestedEnd':
                        self::assertArrayHasKey('nested', $context);
                        self::assertArrayNotHasKey('test2', $context);
                        break;
                    case '- firstEnd':
                        self::assertArrayNotHasKey('nested', $context);
                        self::assertArrayNotHasKey('test2', $context);
                        break;
                    default:
                        self::fail(sprintf('Unexpected log message: %s', $message));
                }
            });

        // Act
        $log->start('firstStart', $persistentContext);
        $log->start('nestedStart', $persistentNestedContext);
        $log->debug('log', $context);
        $log->end('nestedEnd');
        $log->end('firstEnd');

        // Assert
        // Already checked in the callback
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[Group('StructuredLogging')]
    public function start_GivenStartAndEndCalled_ShouldLogElapsedTime(): void
    {
        // Arrange
        config(['app.log_level' => 'debug']);

        $logger            = LoggingFixtures::createLogManager($this);
        $log               = new TestableStructuredLogging($logger);
        $persistentContext = ['test' => 'test'];
        $context           = ['test2' => 'test2'];
        $endLogged         = false;

        $logger
            ->expects($this->exactly(3))
            ->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context = []) use (&$endLogged) {
                if (trim($message) === '- myLogEnd') {
                    self::assertArrayHasKey('elapsedMS', $context);
                    $endLogged = true;
                }
            });

        // Act
        $log->start('myLogStart', $persistentContext);
        $log->debug('myLogLog', $context);
        $log->end('myLogEnd');

        // Assert
        self::assertTrue($endLogged);
    }
}
