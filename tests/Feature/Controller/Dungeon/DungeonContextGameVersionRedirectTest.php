<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\Dungeon\DungeonServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * The game version entry routes of explore, heatmap and route search open a dungeon of that game version, never the
 * dungeon selector, whatever dungeon context was saved under another game version.
 */
#[Group('Controller')]
#[Group('DungeonContext')]
final class DungeonContextGameVersionRedirectTest extends PublicTestCase
{
    use CreatesDungeon;

    #[\Override]
    protected function tearDown(): void
    {
        try {
            unset($_COOKIE['game_version'], $_COOKIE['dungeon_context']);
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function exploreGetByGameVersion_givenContextDungeonNotMappedForTheGameVersion_redirectsToTheGameVersionsFirstDungeon(): void
    {
        // Arrange
        $retailGameVersion = $this->getGameVersion(GameVersion::GAME_VERSION_RETAIL);
        $expectedDungeon   = $this->getFirstDungeon($retailGameVersion);
        $this->useContext(GameVersion::GAME_VERSION_RETAIL, $this->createClassicEraOnlyDungeon());

        // Act
        $response = $this->get(route('dungeon.explore.gameversion', ['gameVersion' => $retailGameVersion]));

        // Assert
        $response->assertRedirect(route('dungeon.explore.gameversion.view', [
            'gameVersion' => $retailGameVersion,
            'dungeon'     => $expectedDungeon,
        ]));
    }

    #[Test]
    public function exploreGetByGameVersion_givenAnotherGameVersionThanTheUsers_redirectsToTheUsersGameVersion(): void
    {
        // Arrange
        $retailGameVersion = $this->getGameVersion(GameVersion::GAME_VERSION_RETAIL);
        $this->useContext(GameVersion::GAME_VERSION_RETAIL, $this->getFirstDungeon($retailGameVersion));

        // Act
        $response = $this->get(route('dungeon.explore.gameversion', [
            'gameVersion' => $this->getGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA),
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.explore.gameversion', ['gameVersion' => $retailGameVersion]));
    }

    #[Test]
    public function heatmapGetByGameVersion_givenContextDungeonWithoutAHeatmap_redirectsToTheGameVersionsFirstHeatmapDungeon(): void
    {
        // Arrange - mapped for retail, so a fine context everywhere but on the heatmap
        $retailGameVersion = $this->getGameVersion(GameVersion::GAME_VERSION_RETAIL);
        $seasonService     = app(SeasonServiceInterface::class);
        $expectedDungeon   = app(DungeonServiceInterface::class)->getDungeonsForGameVersion($retailGameVersion)->first(
            static fn(Dungeon $dungeon): bool => $dungeon->heatmap_enabled && $seasonService->getActiveSeasonForDungeon($dungeon) !== null,
        );
        $this->assertNotNull($expectedDungeon, 'Need a seeded retail dungeon with a heatmap');
        $this->useContext(GameVersion::GAME_VERSION_RETAIL, $this->createDungeon(['active' => true, 'heatmap_enabled' => false]));

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion', ['gameVersion' => $retailGameVersion]));

        // Assert
        $response->assertRedirect(route('dungeon.heatmap.gameversion.view', [
            'gameVersion' => $retailGameVersion,
            'dungeon'     => $expectedDungeon,
        ]));
    }

    #[Test]
    public function heatmapGetByGameVersion_givenAnotherGameVersionThanTheUsers_redirectsToTheUsersGameVersion(): void
    {
        // Arrange
        $retailGameVersion = $this->getGameVersion(GameVersion::GAME_VERSION_RETAIL);
        $this->useContext(GameVersion::GAME_VERSION_RETAIL, $this->getFirstDungeon($retailGameVersion));

        // Act
        $response = $this->get(route('dungeon.heatmap.gameversion', [
            'gameVersion' => $this->getGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA),
        ]));

        // Assert
        $response->assertRedirect(route('dungeon.heatmap.gameversion', ['gameVersion' => $retailGameVersion]));
    }

    #[Test]
    public function routeSearchByGameVersion_givenContextDungeonNotMappedForTheGameVersion_redirectsToTheGameVersionsFirstDungeon(): void
    {
        // Arrange
        $retailGameVersion = $this->getGameVersion(GameVersion::GAME_VERSION_RETAIL);
        $expectedDungeon   = $this->getFirstDungeon($retailGameVersion);
        $this->useContext(GameVersion::GAME_VERSION_RETAIL, $this->createClassicEraOnlyDungeon());

        // Act
        $response = $this->get(route('dungeon.dungeonroute.search.gameversion', ['gameVersion' => $retailGameVersion]));

        // Assert
        $response->assertRedirect(route('dungeon.dungeonroute.search.gameversion.dungeon', [
            'gameVersion' => $retailGameVersion,
            'dungeon'     => $expectedDungeon,
        ]));
    }

    private function getGameVersion(string $key): GameVersion
    {
        return GameVersion::firstWhere('key', $key);
    }

    private function getFirstDungeon(GameVersion $gameVersion): Dungeon
    {
        $dungeon = app(DungeonServiceInterface::class)->getDungeonsForGameVersion($gameVersion)->first();
        $this->assertNotNull($dungeon, sprintf('Need a seeded %s dungeon', $gameVersion->key));

        return $dungeon;
    }

    private function createClassicEraOnlyDungeon(): Dungeon
    {
        return $this->createDungeon(['active' => true], mappingVersionAttributes: [
            'game_version_id' => $this->getGameVersion(GameVersion::GAME_VERSION_CLASSIC_ERA)->id,
        ]);
    }

    private function useContext(string $gameVersionKey, Dungeon $dungeon): void
    {
        $_COOKIE['game_version']    = $gameVersionKey;
        $_COOKIE['dungeon_context'] = $dungeon->key;
    }
}
