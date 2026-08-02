<?php

namespace Tests\Unit\App\Logging\Handlers;

use App\Logging\Handlers\DeduplicateHandlers;
use Illuminate\Log\Logger;
use Monolog\Handler\DeduplicationHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

#[Group('Logging')]
#[Group('DeduplicateHandlers')]
class DeduplicateHandlersTest extends PublicTestCase
{
    /**
     * The deduplication store is keyed on level + message only, so two channels sharing one would suppress each
     * other's records - an error already sent to Discord would silently never reach Sentry, and vice versa.
     */
    #[Test]
    public function invoke_GivenDifferentStores_ShouldUseSeparateDeduplicationStores(): void
    {
        // Arrange
        $tap = new DeduplicateHandlers();

        $discordLogger = $this->createLogger();
        $sentryLogger  = $this->createLogger();

        // Act
        $tap($discordLogger);
        $tap($sentryLogger, 'sentry');

        // Assert
        self::assertNotSame(
            $this->getDeduplicationStore($discordLogger),
            $this->getDeduplicationStore($sentryLogger),
            'Each channel must deduplicate against its own store.',
        );
    }

    #[Test]
    public function invoke_GivenNoStore_ShouldDefaultToTheDiscordStore(): void
    {
        // Arrange
        $tap    = new DeduplicateHandlers();
        $logger = $this->createLogger();

        // Act
        $tap($logger);

        // Assert
        self::assertSame(
            storage_path('logs/discord-deduplication.log'),
            $this->getDeduplicationStore($logger),
            'The default must stay the discord store, or the discord channel silently changes behaviour.',
        );
    }

    #[Test]
    public function invoke_GivenStore_ShouldUseThatStore(): void
    {
        // Arrange
        $tap    = new DeduplicateHandlers();
        $logger = $this->createLogger();

        // Act
        $tap($logger, 'sentry');

        // Assert
        self::assertSame(storage_path('logs/sentry-deduplication.log'), $this->getDeduplicationStore($logger));
    }

    private function createLogger(): Logger
    {
        return new Logger(new MonologLogger('test', [new TestHandler()]));
    }

    private function getDeduplicationStore(Logger $logger): string
    {
        $monolog = $logger->getLogger();
        self::assertInstanceOf(MonologLogger::class, $monolog);

        $handlers = $monolog->getHandlers();

        self::assertInstanceOf(DeduplicationHandler::class, $handlers[0]);

        return (new ReflectionProperty(DeduplicationHandler::class, 'deduplicationStore'))->getValue($handlers[0]);
    }
}
