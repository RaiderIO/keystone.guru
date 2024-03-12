<?php

namespace Tests\Unit\App\Logging;

use Monolog\Level;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCases\PublicTestCase;
use Tests\Unit\Fixtures\LoggingFixtures;

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
        $logger            = LoggingFixtures::createLogManager($this);
        $log               = new TestableStructuredLogging($logger);
        $persistentContext = ['test' => 'test'];
        $context           = ['test2' => 'test2'];

        $logger
            ->expects($this->exactly(3))
            ->method('log')
            ->willReturnCallback(function (string $level, string $methodName, array $context = []) {
                self::assertArrayHasKey('test', $context);

                if ($methodName === 'startStart') {
                    self::assertArrayNotHasKey('test2', $context);
                } else if ($methodName === 'log') {
                    self::assertArrayHasKey('test2', $context);
                }
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
    #[Group('StructuredLogging2')]
    public function start_GivenNestedStartCalled_ShouldKeepContextPersistent(): void
    {
        // Arrange
        $logger                  = LoggingFixtures::createLogManager($this);
        $log                     = new TestableStructuredLogging($logger);
        $persistentContext       = ['test' => 'test'];
        $persistentNestedContext = ['nested' => 'nested'];
        $context                 = ['test2' => 'test2'];

        $logger
            ->expects($this->exactly(5))
            ->method('log')
            ->willReturnCallback(function (string $level, string $methodName, array $context = []) {
                self::assertArrayHasKey('test', $context);

                if ($methodName === 'firstStart') {
                    self::assertArrayNotHasKey('nested', $context);
                    self::assertArrayNotHasKey('test2', $context);
                } else if ($methodName === 'nestedStart') {
                    self::assertArrayHasKey('nested', $context);
                    self::assertArrayNotHasKey('test2', $context);
                } else if ($methodName === 'log') {
                    self::assertArrayHasKey('nested', $context);
                    self::assertArrayHasKey('test2', $context);
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
        $logger            = LoggingFixtures::createLogManager($this);
        $log               = new TestableStructuredLogging($logger);
        $persistentContext = ['test' => 'test'];
        $context           = ['test2' => 'test2'];

        $logger
            ->expects($this->exactly(3))
            ->method('log')
            ->willReturnCallback(function (string $level, string $methodName, array $context = []) {
                if ($methodName === 'myLogEnd') {
                    self::assertArrayHasKey('elapsed', $context);
                }
            });


        // Act
        $log->start('myLogStart', $persistentContext);
        $log->debug('myLogLog', $context);
        $log->end('myLogEnd');

        // Assert
        // Already checked in the callback
    }
}
