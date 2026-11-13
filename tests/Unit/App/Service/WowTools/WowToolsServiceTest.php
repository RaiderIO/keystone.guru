<?php

namespace Tests\Unit\App\Service\WowTools;

use App\Service\WowTools\Logging\WowToolsServiceLoggingInterface;
use App\Service\WowTools\WowToolsService;
use Closure;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('WowTools')]
final class WowToolsServiceTest extends PublicTestCase
{
    private const int NPC_ID = 12345;

    #[Test]
    public function getDisplayId_givenAResponseWithADisplayId_logsTheRequestStartWithTheNpcIdAndTheRequestEnd(): void
    {
        // Arrange
        $log = $this->createMock(WowToolsServiceLoggingInterface::class);
        $log->expects($this->once())->method('getDisplayIdRequestStart')->with(self::NPC_ID);
        $log->expects($this->once())->method('getDisplayIdRequestResult')->with(678);
        $log->expects($this->once())->method('getDisplayIdRequestEnd');
        $service = $this->createService($log, static fn(): array => ['CreatureDisplayInfoID[0]' => '678']);

        // Act
        $result = $service->getDisplayId(self::NPC_ID);

        // Assert
        $this->assertSame(678, $result);
    }

    #[Test]
    public function getDisplayId_givenARequestThatThrows_stillLogsTheRequestEnd(): void
    {
        // Arrange
        $log = $this->createMock(WowToolsServiceLoggingInterface::class);
        $log->expects($this->once())->method('getDisplayIdRequestStart')->with(self::NPC_ID);
        $log->expects($this->once())->method('getDisplayIdRequestEnd');
        $service = $this->createService($log, static function (): array {
            throw new RuntimeException('boom');
        });

        // Assert
        $this->expectException(RuntimeException::class);

        // Act
        $service->getDisplayId(self::NPC_ID);
    }

    /**
     * @param Closure(int): array<string, mixed> $requestCreature
     */
    private function createService(WowToolsServiceLoggingInterface $log, Closure $requestCreature): WowToolsService
    {
        return new class($log, $requestCreature) extends WowToolsService {
            /**
             * @param Closure(int): array<string, mixed> $requestCreature
             */
            public function __construct(WowToolsServiceLoggingInterface $log, private readonly Closure $requestCreature)
            {
                parent::__construct($log);
            }

            #[\Override]
            protected function requestCreature(int $npcId): array
            {
                return ($this->requestCreature)($npcId);
            }
        };
    }
}
