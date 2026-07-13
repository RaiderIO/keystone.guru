<?php

namespace Tests\Feature\View;

use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Service\Expansion\ExpansionServiceInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\View\RequestViewContext;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCases\PublicTestCase;

#[Group('ViewComposers')]
final class RequestViewContextTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function getCurrentExpansion_givenMultipleCalls_resolvesUnderlyingServiceOnce(): void
    {
        // Arrange
        $expansion        = new Expansion();
        $expansionService = $this->createMock(ExpansionServiceInterface::class);
        $expansionService->expects($this->once())
            ->method('getCurrentExpansion')
            ->willReturn($expansion);
        $gameVersionService = $this->createMock(GameVersionServiceInterface::class);
        $requestViewContext = new RequestViewContext($expansionService, $gameVersionService);

        // Act
        $first  = $requestViewContext->getCurrentExpansion();
        $second = $requestViewContext->getCurrentExpansion();

        // Assert
        $this->assertSame($expansion, $first);
        $this->assertSame($first, $second);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getCurrentUserGameVersion_givenMultipleCalls_resolvesUnderlyingServiceOnce(): void
    {
        // Arrange
        $gameVersion        = new GameVersion();
        $gameVersionService = $this->createMock(GameVersionServiceInterface::class);
        $gameVersionService->expects($this->once())
            ->method('getGameVersion')
            ->willReturn($gameVersion);
        $expansionService   = $this->createMock(ExpansionServiceInterface::class);
        $requestViewContext = new RequestViewContext($expansionService, $gameVersionService);

        // Act
        $first  = $requestViewContext->getCurrentUserGameVersion();
        $second = $requestViewContext->getCurrentUserGameVersion();

        // Assert
        $this->assertSame($gameVersion, $first);
        $this->assertSame($first, $second);
    }

    #[Test]
    public function scoped_givenSeparateContainerScopes_returnsDistinctInstances(): void
    {
        // Arrange
        $first = app(\App\Service\View\RequestViewContextInterface::class);

        // Act - forget the scoped instances as Octane does between requests
        $this->app->forgetScopedInstances();
        $second = app(\App\Service\View\RequestViewContextInterface::class);

        // Assert
        $this->assertNotSame($first, $second);
    }
}
