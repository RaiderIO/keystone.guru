<?php

namespace Tests\Unit\App\Logging;

use Illuminate\Support\Facades\Context;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use RuntimeException;
use Tests\Fixtures\LoggingFixtures;
use Tests\TestCases\PublicTestCase;

#[Group('Logging')]
#[Group('StructuredLogging')]
class StructuredLoggingTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function start_GivenStartCalled_ShouldKeepContextPersistent(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'local']);

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
    public function start_GivenNestedStartCalled_ShouldKeepContextPersistent(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'local']);

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
    public function start_GivenStartAndEndCalled_ShouldLogElapsedTime(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'local']);

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

    /**
     * @throws Exception
     */
    #[Test]
    public function start_GivenStartCalled_ShouldMirrorContextIntoLaravelContext(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'local']);

        $logger            = LoggingFixtures::createLogManager($this);
        $log               = new TestableStructuredLogging($logger);
        $persistentContext = ['test' => 'test'];

        // Act & Assert
        $log->start('firstStart', $persistentContext);

        self::assertSame($persistentContext, Context::get('structured:first'));

        $log->end('firstEnd');

        self::assertFalse(Context::has('structured:first'));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function log_GivenNonLocalAppType_ShouldEmitDepthAsContextFieldInsteadOfMessagePrefix(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'production']);

        $logger            = LoggingFixtures::createLogManager($this);
        $log               = new TestableStructuredLogging($logger);
        $persistentContext = ['test' => 'test'];

        $logger
            ->expects($this->exactly(4))
            ->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context = []) {
                // The message must stay stable (no padding/dash prefixes) so production logs remain grep/parse-able
                switch ($message) {
                    case 'firstStart':
                    case 'log':
                    case 'firstEnd':
                        self::assertSame(1, $context['depth']);
                        break;
                    case 'afterEndLog':
                        self::assertSame(0, $context['depth']);
                        break;
                    default:
                        self::fail(sprintf('Unexpected log message: %s', $message));
                }
            });

        // Act
        $log->start('firstStart', $persistentContext);
        $log->debug('log');
        $log->end('firstEnd');
        $log->debug('afterEndLog');

        // Assert
        // Already checked in the callback
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function wrapLog_GivenCallback_ShouldLogStartAndEndAndReturnCallbackResult(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'local']);

        $logger   = LoggingFixtures::createLogManager($this);
        $log      = new TestableStructuredLogging($logger);
        $callback = static fn() => 42;

        $logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context = []) {
                // The callback that get_defined_vars() picks up at the call site must never end up in the context
                self::assertArrayNotHasKey('callback', $context);

                switch (trim($message)) {
                    case '- wrappedStart':
                        self::assertArrayHasKey('npcId', $context);
                        break;
                    case '- wrappedEnd':
                        self::assertArrayHasKey('elapsedMS', $context);
                        break;
                    default:
                        self::fail(sprintf('Unexpected log message: %s', $message));
                }
            });

        // Act
        $result = $log->wrapLog('wrapped', ['npcId' => 123, 'callback' => $callback], $callback);

        // Assert
        self::assertSame(42, $result);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function wrapLog_GivenThrowingCallback_ShouldStillLogEnd(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.type' => 'local']);

        $logger = LoggingFixtures::createLogManager($this);
        $log    = new TestableStructuredLogging($logger);

        // The exactly(2) expectation is verified after the test body, so it proves the end was still logged
        $logger
            ->expects($this->exactly(2))
            ->method('log')
            ->willReturnCallback(function (string $level, string $message, array $context = []) {
                if (!in_array(trim($message), ['- wrappedStart', '- wrappedEnd'], true)) {
                    self::fail(sprintf('Unexpected log message: %s', $message));
                }
            });

        // Assert
        $this->expectException(RuntimeException::class);

        // Act
        $log->wrapLog('wrapped', [], static function (): void {
            throw new RuntimeException('boom');
        });
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function start_GivenFunctionNameNotEndingInStart_ShouldThrowLogicException(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.debug' => true]);

        $logger = LoggingFixtures::createLogManager($this);
        $log    = new TestableStructuredLogging($logger);

        // Assert
        $this->expectException(LogicException::class);

        // Act
        // Ends in the literal "start" but not in the conventional "Start" suffix, so it would mispair (restart -> re)
        $log->start('restart');
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function end_GivenFunctionNameNotEndingInEnd_ShouldThrowLogicException(): void
    {
        // Arrange
        config(['app.log_level' => 'debug', 'app.debug' => true]);

        $logger = LoggingFixtures::createLogManager($this);
        $log    = new TestableStructuredLogging($logger);

        // Assert
        $this->expectException(LogicException::class);

        // Act
        $log->end('suspend');
    }
}
