<?php

namespace Tests\Feature\App\Service\Dungeon\DungeonService;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\User;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\Dungeon\DungeonService;
use App\Service\Dungeon\Logging\DungeonServiceLoggingInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonService')]
#[Group('GetDungeonContext')]
final class GetDungeonContextTest extends PublicTestCase
{
    /**
     * Builds a DungeonService with setDungeonContext no-oped so tests can focus
     * purely on what getDungeonContext returns without dealing with its save side-effects.
     */
    private function buildService(
        ?GameVersionServiceInterface    $gameVersionService = null,
        ?SeasonServiceInterface         $seasonService = null,
        ?CookieServiceInterface         $cookieService = null,
        ?DungeonServiceLoggingInterface $log = null,
    ): MockObject&DungeonService {
        if ($seasonService === null) {
            $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
            $seasonService->method('getCurrentSeason')->willReturn(null);
        }

        /** @var MockObject&DungeonService $service */
        $service = $this->getMockBuilderPublic(DungeonService::class)
            ->setConstructorArgs([
                $cookieService ?? $this->createMockPublic(CookieServiceInterface::class),
                $seasonService,
                $log ?? $this->createMockPublic(DungeonServiceLoggingInterface::class),
                $gameVersionService ?? $this->createMockPublic(GameVersionServiceInterface::class),
            ])
            ->onlyMethods(['setDungeonContext'])
            ->getMock();

        return $service;
    }

    #[Test]
    public function getDungeonContext_givenGuestWithNoCookies_returnsDungeonFromDefaultGameVersion(): void
    {
        // Arrange
        unset($_COOKIE['dungeon_context']);

        $retailGameVersion  = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $gameVersionService = $this->createMockPublic(GameVersionServiceInterface::class);
        $gameVersionService->method('getGameVersion')->with(null)->willReturn($retailGameVersion);

        $service = $this->buildService(gameVersionService: $gameVersionService);

        // Act
        $dungeon = $service->getDungeonContext(null);

        // Assert
        $this->assertEquals($retailGameVersion->expansion_id, $dungeon->expansion_id);
    }

    #[Test]
    public function getDungeonContext_givenGuestWithClassicGameVersionAndNoDungeonContextCookie_returnsClassicDungeon(): void
    {
        // Arrange — regression: game_version=classic must be respected for guests when no dungeon context cookie is set
        unset($_COOKIE['dungeon_context']);

        $classicGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $gameVersionService = $this->createMockPublic(GameVersionServiceInterface::class);
        $gameVersionService->method('getGameVersion')->with(null)->willReturn($classicGameVersion);

        $service = $this->buildService(gameVersionService: $gameVersionService);

        // Act
        $dungeon = $service->getDungeonContext(null);

        // Assert
        $this->assertEquals(
            $classicGameVersion->expansion_id,
            $dungeon->expansion_id,
            'getDungeonContext must respect the game version for guests, not always default to retail',
        );
    }

    #[Test]
    public function getDungeonContext_givenGuestWithValidDungeonContextCookie_returnsCookieDungeon(): void
    {
        // Arrange
        $expectedDungeon = Dungeon::active()
            ->whereHas('expansion', static fn($q) => $q->where('shortname', 'classic'))
            ->first();
        $this->assertNotNull($expectedDungeon, 'Need at least one active Classic dungeon in the DB');

        $_COOKIE['dungeon_context'] = $expectedDungeon->key;

        try {
            // getGameVersion must NOT be called — the cookie short-circuits the fallback
            $gameVersionService = $this->createMockPublic(GameVersionServiceInterface::class);
            $gameVersionService->expects($this->never())->method('getGameVersion');

            $service = $this->buildService(gameVersionService: $gameVersionService);

            // Act
            $dungeon = $service->getDungeonContext(null);

            // Assert
            $this->assertEquals($expectedDungeon->id, $dungeon->id);
        } finally {
            unset($_COOKIE['dungeon_context']);
        }
    }

    #[Test]
    public function getDungeonContext_givenLoggedInUserWithDungeon_returnsUserDungeon(): void
    {
        // Arrange
        $expectedDungeon = Dungeon::active()->first();

        /** @var MockObject&User $user */
        $user = $this->createPartialMockPublic(User::class, ['getAttribute']);
        $user->method('getAttribute')->with('dungeon')->willReturn($expectedDungeon);

        // getGameVersion must NOT be called — user already has a dungeon
        $gameVersionService = $this->createMockPublic(GameVersionServiceInterface::class);
        $gameVersionService->expects($this->never())->method('getGameVersion');

        $service = $this->buildService(gameVersionService: $gameVersionService);

        // Act
        $dungeon = $service->getDungeonContext($user);

        // Assert
        $this->assertEquals($expectedDungeon->id, $dungeon->id);
    }

    #[Test]
    public function getDungeonContext_givenLoggedInUserWithNoDungeon_returnsDungeonFromUserGameVersion(): void
    {
        // Arrange
        $classicGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);

        /** @var MockObject&User $user */
        $user = $this->createPartialMockPublic(User::class, ['getAttribute']);
        $user->method('getAttribute')->willReturnCallback(
            static fn(string $key) => $key === 'dungeon' ? null : null,
        );

        $gameVersionService = $this->createMockPublic(GameVersionServiceInterface::class);
        $gameVersionService->method('getGameVersion')->with($user)->willReturn($classicGameVersion);

        $service = $this->buildService(gameVersionService: $gameVersionService);

        // Act
        $dungeon = $service->getDungeonContext($user);

        // Assert
        $this->assertEquals(
            $classicGameVersion->expansion_id,
            $dungeon->expansion_id,
            'getDungeonContext must use the logged-in user\'s game version as fallback',
        );
    }
}
