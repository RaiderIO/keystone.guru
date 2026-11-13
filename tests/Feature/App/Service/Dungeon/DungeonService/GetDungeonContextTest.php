<?php

namespace Tests\Feature\App\Service\Dungeon\DungeonService;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\PageViewCountRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\Dungeon\DungeonService;
use App\Service\Dungeon\DungeonServiceInterface;
use App\Service\Dungeon\Logging\DungeonServiceLoggingInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonService')]
#[Group('GetDungeonContext')]
final class GetDungeonContextTest extends PublicTestCase
{
    use CreatesDungeon;

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
                app(DungeonRepositoryInterface::class),
                $this->createMockPublic(PageViewCountRepositoryInterface::class),
                $this->createMockPublic(CacheServiceInterface::class),
            ])
            ->onlyMethods(['setDungeonContext'])
            ->getMock();

        return $service;
    }

    #[Test]
    public function getDungeonContext_givenGuestWithNoCookies_returnsDungeonUsableInDefaultGameVersion(): void
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
        $this->assertTrue(app(DungeonServiceInterface::class)->isUsableInGameVersion($dungeon, $retailGameVersion));
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
        $expectedDungeon = app(DungeonServiceInterface::class)->getDungeonsForGameVersion(GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA))->first();
        $this->assertNotNull($expectedDungeon, 'Need at least one Classic Era dungeon in the DB');

        $_COOKIE['dungeon_context'] = $expectedDungeon->key;

        try {
            $gameVersionService = $this->createMockPublic(GameVersionServiceInterface::class);
            $gameVersionService->method('getGameVersion')->with(null)->willReturn(GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA));

            $service = $this->buildService(gameVersionService: $gameVersionService);
            $service->expects($this->never())->method('setDungeonContext');

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
        $retailGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $expectedDungeon   = app(DungeonServiceInterface::class)->getDungeonsForGameVersion($retailGameVersion)->first();

        /** @var MockObject&User $user */
        $user = $this->createPartialMockPublic(User::class, ['getAttribute']);
        $user->method('getAttribute')->with('dungeon')->willReturn($expectedDungeon);

        $gameVersionService = $this->createMockPublic(GameVersionServiceInterface::class);
        $gameVersionService->method('getGameVersion')->with($user)->willReturn($retailGameVersion);

        $service = $this->buildService(gameVersionService: $gameVersionService);
        $service->expects($this->never())->method('setDungeonContext');

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

    #[Test]
    public function getDungeonContext_givenGuestWithNoCookiesAndACurrentSeason_returnsAndStoresTheSeasonsFirstDungeon(): void
    {
        // Arrange
        unset($_COOKIE['dungeon_context']);
        $retailGameVersion  = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $gameVersionService = $this->createMockPublic(GameVersionServiceInterface::class);
        $gameVersionService->method('getGameVersion')->with(null)->willReturn($retailGameVersion);

        // Shadowlands S4 holds none of the retail expansion's own dungeons, so the expansion fallback cannot pass
        $currentSeason   = Season::with('dungeons')->findOrFail(Season::SEASON_SL_S4);
        $expectedDungeon = $currentSeason->dungeons->first();
        $this->assertNotNull($expectedDungeon);
        $this->assertNotSame($retailGameVersion->expansion_id, $expectedDungeon->expansion_id);
        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn($currentSeason);

        $service = $this->buildService(gameVersionService: $gameVersionService, seasonService: $seasonService);
        $service->expects($this->once())
            ->method('setDungeonContext')
            ->with($this->callback(static fn(Dungeon $dungeon): bool => $dungeon->id === $expectedDungeon->id), null);

        // Act
        $dungeon = $service->getDungeonContext(null);

        // Assert
        $this->assertSame($expectedDungeon->id, $dungeon->id);
    }

    #[Test]
    public function getDungeonContext_givenGuestCookieDungeonNotMappedForTheGameVersion_returnsTheGameVersionsFirstDungeon(): void
    {
        // Arrange - mapped for retail only, while the guest is on Classic Era
        $retailOnlyDungeon          = $this->createDungeon(['active' => true]);
        $classicEraGameVersion      = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $expectedDungeon            = app(DungeonServiceInterface::class)->getDungeonsForGameVersion($classicEraGameVersion)->first();
        $_COOKIE['game_version']    = GameVersion::GAME_VERSION_CLASSIC_ERA;
        $_COOKIE['dungeon_context'] = $retailOnlyDungeon->key;

        try {
            // Act
            $dungeon = app(DungeonServiceInterface::class)->getDungeonContext(null);

            // Assert
            $this->assertSame($expectedDungeon->id, $dungeon->id);
        } finally {
            unset($_COOKIE['game_version'], $_COOKIE['dungeon_context']);
        }
    }

    #[Test]
    public function getDungeonContext_givenUserDungeonNotMappedForTheGameVersion_storesTheGameVersionsFirstDungeon(): void
    {
        // Arrange
        $retailOnlyDungeon     = $this->createDungeon(['active' => true]);
        $classicEraGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $expectedDungeon       = app(DungeonServiceInterface::class)->getDungeonsForGameVersion($classicEraGameVersion)->first();
        $user                  = null;

        try {
            $user = User::factory()->create([
                'game_version_id' => $classicEraGameVersion->id,
                'dungeon_id'      => $retailOnlyDungeon->id,
            ]);

            // Act
            $dungeon = app(DungeonServiceInterface::class)->getDungeonContext($user);

            // Assert
            $this->assertSame($expectedDungeon->id, $dungeon->id);
            $this->assertSame($expectedDungeon->id, $user->fresh()->dungeon_id);
        } finally {
            $user?->delete();
        }
    }

    #[Test]
    public function getDungeonContext_givenInactiveCookieDungeon_returnsTheGameVersionsFirstDungeon(): void
    {
        // Arrange - mapped for retail, but inactive
        $inactiveDungeon            = $this->createDungeon(['active' => false]);
        $expectedDungeon            = app(DungeonServiceInterface::class)->getDungeonsForGameVersion(GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL))->first();
        $_COOKIE['game_version']    = GameVersion::GAME_VERSION_RETAIL;
        $_COOKIE['dungeon_context'] = $inactiveDungeon->key;

        try {
            // Act
            $dungeon = app(DungeonServiceInterface::class)->getDungeonContext(null);

            // Assert
            $this->assertSame($expectedDungeon->id, $dungeon->id);
        } finally {
            unset($_COOKIE['game_version'], $_COOKIE['dungeon_context']);
        }
    }

    #[Test]
    public function getDungeonContextForGameVersion_givenAFilterRejectingTheContextDungeon_returnsTheFirstDungeonPassingIt(): void
    {
        // Arrange
        $retailGameVersion          = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $retailDungeons             = app(DungeonServiceInterface::class)->getDungeonsForGameVersion($retailGameVersion);
        $contextDungeon             = $retailDungeons->get(0);
        $expectedDungeon            = $retailDungeons->get(1);
        $_COOKIE['game_version']    = GameVersion::GAME_VERSION_RETAIL;
        $_COOKIE['dungeon_context'] = $contextDungeon->key;

        try {
            // Act
            $dungeon = app(DungeonServiceInterface::class)->getDungeonContextForGameVersion(
                $retailGameVersion,
                null,
                static fn(Dungeon $dungeon): bool => $dungeon->id !== $contextDungeon->id,
            );

            // Assert
            $this->assertSame($expectedDungeon->id, $dungeon?->id);
        } finally {
            unset($_COOKIE['game_version'], $_COOKIE['dungeon_context']);
        }
    }

    #[Test]
    public function getDungeonContextForGameVersion_givenAnotherGameVersionThanTheUsers_returnsThatGameVersionsFirstDungeon(): void
    {
        // Arrange - the guest is on retail with a retail dungeon, but asks for Classic Era
        $retailDungeon              = app(DungeonServiceInterface::class)->getDungeonsForGameVersion(GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL))->first();
        $classicEraGameVersion      = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
        $expectedDungeon            = app(DungeonServiceInterface::class)->getDungeonsForGameVersion($classicEraGameVersion)->first();
        $_COOKIE['game_version']    = GameVersion::GAME_VERSION_RETAIL;
        $_COOKIE['dungeon_context'] = $retailDungeon->key;
        $this->assertFalse(app(DungeonServiceInterface::class)->isUsableInGameVersion($retailDungeon, $classicEraGameVersion));

        try {
            // Act
            $dungeon = app(DungeonServiceInterface::class)->getDungeonContextForGameVersion($classicEraGameVersion);

            // Assert
            $this->assertSame($expectedDungeon->id, $dungeon?->id);
        } finally {
            unset($_COOKIE['game_version'], $_COOKIE['dungeon_context']);
        }
    }

    #[Test]
    public function getDungeonContextForGameVersion_givenAFilterRejectingEveryDungeon_returnsNull(): void
    {
        // Arrange
        $retailGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);

        // Act
        $dungeon = app(DungeonServiceInterface::class)->getDungeonContextForGameVersion(
            $retailGameVersion,
            null,
            static fn(Dungeon $dungeon): bool => false,
        );

        // Assert
        $this->assertNull($dungeon);
    }

    #[Test]
    public function getDungeonContext_givenGuestCookieDungeonInTheCurrentSeason_queriesNoMappingVersions(): void
    {
        // Arrange
        $retailGameVersion          = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $service                    = app(DungeonServiceInterface::class);
        $expectedDungeon            = $service->getDungeonsForGameVersion($retailGameVersion)->first();
        $_COOKIE['game_version']    = GameVersion::GAME_VERSION_RETAIL;
        $_COOKIE['dungeon_context'] = $expectedDungeon->key;
        $mappingVersionQueries      = 0;
        DB::listen(static function (QueryExecuted $query) use (&$mappingVersionQueries): void {
            if (str_contains($query->sql, 'mapping_versions')) {
                $mappingVersionQueries++;
            }
        });

        try {
            // Act
            $dungeon = $service->getDungeonContext(null);

            // Assert
            $this->assertSame($expectedDungeon->id, $dungeon->id);
            $this->assertSame(0, $mappingVersionQueries);
        } finally {
            unset($_COOKIE['game_version'], $_COOKIE['dungeon_context']);
        }
    }
}
