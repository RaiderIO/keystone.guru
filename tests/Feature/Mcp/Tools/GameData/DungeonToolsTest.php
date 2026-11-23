<?php

namespace Tests\Feature\Mcp\Tools\GameData;

use App\Logic\Structs\LatLng;
use App\Mcp\Servers\KeystoneGuruServer;
use App\Mcp\Tools\GameData\GetDungeonPacksTool;
use App\Mcp\Tools\GameData\ListDungeonsTool;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\WalkGraph\EnemyPackWalkDistance;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use App\Service\User\ApiTokenScope;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Mcp\McpTestCase;

#[Group('Mcp')]
#[Group('McpDungeonTools')]
final class DungeonToolsTest extends McpTestCase
{
    #[Test]
    public function listDungeons_givenDefault_listsOnlyCurrentSeasonDungeons(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $currentSeason = app(SeasonServiceInterface::class)->getCurrentSeason();
        $this->assertNotNull($currentSeason, 'The seed has no current season');
        $expectedKeys = $currentSeason->dungeons()->where('dungeons.active', true)->pluck('key')->sort()->values()->all();

        // Act
        $response = KeystoneGuruServer::tool(ListDungeonsTool::class);

        // Assert
        $response->assertHasNoErrors();
        $dungeons = $this->structuredContent($response)['dungeons'];
        $keys     = array_column($dungeons, 'key');
        sort($keys);
        $this->assertSame($expectedKeys, $keys);
        $this->assertNotContains(false, array_column($dungeons, 'in_current_season'));
    }

    #[Test]
    public function listDungeons_givenIncludeAllActive_alsoListsDungeonsOutsideTheSeason(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);

        // Act
        $response = KeystoneGuruServer::tool(ListDungeonsTool::class, ['include_all_active' => true]);

        // Assert
        $response->assertHasNoErrors();
        $this->assertContains(false, array_column($this->structuredContent($response)['dungeons'], 'in_current_season'));
    }

    #[Test]
    public function listDungeons_givenDungeon_returnsTranslatedAbbreviation(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $currentSeason = app(SeasonServiceInterface::class)->getCurrentSeason();
        $this->assertNotNull($currentSeason, 'The seed has no current season');
        /** @var Dungeon $dungeon */
        $dungeon = $currentSeason->dungeons()->where('dungeons.active', true)->whereNotNull('abbreviation')->firstOrFail();
        $this->assertNotSame($dungeon->abbreviation, __($dungeon->abbreviation), 'The dungeon\'s abbreviation has no translation');

        // Act
        $response = KeystoneGuruServer::tool(ListDungeonsTool::class);

        // Assert
        $response->assertHasNoErrors();
        $dungeonOutput = collect(self::getList($this->structuredContent($response), 'dungeons'))->firstWhere('key', $dungeon->key);
        $this->assertNotNull($dungeonOutput);
        $this->assertSame(__($dungeon->abbreviation), $dungeonOutput['abbreviation']);
    }

    #[Test]
    public function listDungeons_givenIncludeAllActiveWithoutExpansion_leavesFloorsOut(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);

        // Act
        $response = KeystoneGuruServer::tool(ListDungeonsTool::class, ['include_all_active' => true]);

        // Assert
        $response->assertHasNoErrors();
        /** @var array<int, array<string, mixed>> $dungeons */
        $dungeons = $this->structuredContent($response)['dungeons'];
        $this->assertNotEmpty($dungeons);
        foreach ($dungeons as $dungeonOutput) {
            $this->assertArrayNotHasKey('floors', $dungeonOutput);
        }
    }

    #[Test]
    public function listDungeons_givenIncludeAllActiveAndExpansion_listsOnlyThatExpansionWithFloors(): void
    {
        // Arrange
        [$dungeon] = $this->findPackDungeon();
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $expansionKey = $dungeon->expansion->key;
        $expectedKeys = Dungeon::query()
            ->where('expansion_id', $dungeon->expansion_id)
            ->where('active', true)
            ->pluck('key')
            ->sort()
            ->values()
            ->all();

        // Act
        $response = KeystoneGuruServer::tool(ListDungeonsTool::class, [
            'include_all_active' => true,
            'expansion'          => $expansionKey,
        ]);

        // Assert
        $response->assertHasNoErrors();
        /** @var array<int, array<string, mixed>> $dungeons */
        $dungeons = $this->structuredContent($response)['dungeons'];
        $keys     = array_column($dungeons, 'key');
        sort($keys);
        $this->assertSame($expectedKeys, $keys);
        $dungeonOutput = collect($dungeons)->firstWhere('key', $dungeon->key);
        $this->assertNotEmpty($dungeonOutput['floors']);
    }

    #[Test]
    public function getDungeonPacks_givenDungeon_returnsPacksInIngameCoordinatesWithNeighbours(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        [$pack] = $this->getPacksWithEnemies($mappingVersion, 1);
        /** @var Enemy $enemy */
        $enemy      = $pack->enemies->first()->load('floor');
        $expectedXY = app(CoordinatesServiceInterface::class)->calculateIngameLocationForMapLocation(
            new LatLng($enemy->lat, $enemy->lng, $enemy->floor),
        );

        // Act
        $response = KeystoneGuruServer::tool(GetDungeonPacksTool::class, ['dungeon' => $dungeon->key]);

        // Assert
        $response->assertHasNoErrors();
        $content = $this->structuredContent($response);
        $this->assertSame($mappingVersion->enemy_forces_required, $content['dungeon']['enemy_forces_required']);

        /** @var array<int, array<string, mixed>> $packs */
        $packs      = $content['packs'];
        $packOutput = collect($packs)->firstWhere('id', $pack->id);
        $this->assertNotNull($packOutput);
        $this->assertNotEmpty($packOutput['neighbours']);

        /** @var array<int, array<string, mixed>> $packEnemies */
        $packEnemies = $packOutput['enemies'];
        $enemyOutput = collect($packEnemies)->firstWhere('id', $enemy->id);
        $this->assertEqualsWithDelta($expectedXY->getX(), $enemyOutput['x'], 0.1);
        $this->assertEqualsWithDelta($expectedXY->getY(), $enemyOutput['y'], 0.1);
        $this->assertArrayNotHasKey('lat', $enemyOutput);
        $this->assertArrayNotHasKey('lng', $enemyOutput);
        $this->assertArrayHasKey((string)$enemy->npc_id, $content['npcs']);
    }

    #[Test]
    public function getDungeonPacks_givenPackEnemies_leavesOutWhatThePackAlreadySays(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        [$pack] = $this->getPacksWithEnemies($mappingVersion, 1);

        // Act
        $response = KeystoneGuruServer::tool(GetDungeonPacksTool::class, ['dungeon' => $dungeon->key]);

        // Assert
        $response->assertHasNoErrors();
        $packOutput = collect(self::getList($this->structuredContent($response), 'packs'))->firstWhere('id', $pack->id);
        $this->assertNotNull($packOutput);
        $this->assertSame($pack->floor_id, $packOutput['floor_id']);
        /** @var array<string, mixed> $enemyOutput */
        $enemyOutput = $packOutput['enemies'][0];
        $this->assertArrayHasKey('id', $enemyOutput);
        $this->assertArrayNotHasKey('floor_id', $enemyOutput);
        $this->assertArrayNotHasKey('enemy_pack_id', $enemyOutput);
    }

    #[Test]
    public function getDungeonPacks_givenEqualTeemingForces_leavesTeemingForcesOut(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        [$pack] = $this->getPacksWithEnemies($mappingVersion, 1);
        /** @var Enemy $enemy */
        $enemy                      = $pack->enemies->first();
        $originalEnemyForces        = $enemy->enemy_forces_override;
        $originalEnemyForcesTeeming = $enemy->enemy_forces_override_teeming;
        $enemy->update(['enemy_forces_override' => 3, 'enemy_forces_override_teeming' => 3]);

        try {
            // Act
            $response = KeystoneGuruServer::tool(GetDungeonPacksTool::class, ['dungeon' => $dungeon->key]);

            // Assert
            $response->assertHasNoErrors();
            $packOutput  = collect(self::getList($this->structuredContent($response), 'packs'))->firstWhere('id', $pack->id);
            $enemyOutput = collect(self::getList($packOutput, 'enemies'))->firstWhere('id', $enemy->id);
            $this->assertSame(3, $enemyOutput['enemy_forces']);
            $this->assertArrayNotHasKey('enemy_forces_teeming', $enemyOutput);
        } finally {
            Enemy::query()->whereKey($enemy->id)->update([
                'enemy_forces_override'         => $originalEnemyForces,
                'enemy_forces_override_teeming' => $originalEnemyForcesTeeming,
            ]);
        }
    }

    #[Test]
    public function getDungeonPacks_givenEnemyWithOtherTeemingForces_returnsTeemingForces(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        [$pack] = $this->getPacksWithEnemies($mappingVersion, 1);
        /** @var Enemy $enemy */
        $enemy                      = $pack->enemies->first();
        $originalEnemyForces        = $enemy->enemy_forces_override;
        $originalEnemyForcesTeeming = $enemy->enemy_forces_override_teeming;
        $enemy->update(['enemy_forces_override' => 3, 'enemy_forces_override_teeming' => 5]);

        try {
            // Act
            $response = KeystoneGuruServer::tool(GetDungeonPacksTool::class, ['dungeon' => $dungeon->key]);

            // Assert
            $response->assertHasNoErrors();
            $packOutput  = collect(self::getList($this->structuredContent($response), 'packs'))->firstWhere('id', $pack->id);
            $enemyOutput = collect(self::getList($packOutput, 'enemies'))->firstWhere('id', $enemy->id);
            $this->assertSame(3, $enemyOutput['enemy_forces']);
            $this->assertSame(5, $enemyOutput['enemy_forces_teeming']);
        } finally {
            Enemy::query()->whereKey($enemy->id)->update([
                'enemy_forces_override'         => $originalEnemyForces,
                'enemy_forces_override_teeming' => $originalEnemyForcesTeeming,
            ]);
        }
    }

    #[Test]
    public function getDungeonPacks_givenFloor_returnsOnlyThatFloor(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(
            minActiveFloors: 2,
            challengeMode: true,
            dungeonActive: true,
            constraint: static fn($query) => $query->whereHas('expansion', static fn($expansion) => $expansion->where('active', true)),
            resolve: static fn($dungeon, $mappingVersion) => $mappingVersion->enemyPacks()
                ->whereHas('enemies', static fn($query) => $query->where(self::excludeMdtPlaceholders(...)))
                ->distinct()
                ->count('floor_id') >= 2 ? true : null,
        );
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        [$pack]  = $this->getPacksWithEnemies($mappingVersion, 1);
        $floorId = $pack->floor_id;

        // Act
        $response = KeystoneGuruServer::tool(GetDungeonPacksTool::class, ['dungeon' => $dungeon->key, 'floor' => $floorId]);

        // Assert
        $response->assertHasNoErrors();
        $content = $this->structuredContent($response);
        /** @var array<int, array<string, mixed>> $packs */
        $packs = $content['packs'];
        $this->assertContains($pack->id, array_column($packs, 'id'));
        $this->assertSame([$floorId], array_values(array_unique(array_column($packs, 'floor_id'))));
        /** @var array<int, array<string, mixed>> $looseEnemies */
        $looseEnemies = $content['loose_enemies'];
        foreach ($looseEnemies as $looseEnemy) {
            $this->assertSame($floorId, $looseEnemy['floor_id']);
        }
    }

    #[Test]
    public function getDungeonPacks_givenFloorOfAnotherDungeon_returnsUnknownDungeonFloor(): void
    {
        // Arrange
        [$dungeon] = $this->findPackDungeon();
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        $foreignFloor = Floor::query()->where('dungeon_id', '<>', $dungeon->id)->firstOrFail();

        // Act
        $response = KeystoneGuruServer::tool(GetDungeonPacksTool::class, ['dungeon' => $dungeon->key, 'floor' => $foreignFloor->id]);

        // Assert
        $response->assertHasErrors([__('mcp.error.unknown_dungeon_floor', ['floor' => $foreignFloor->id])]);
    }

    #[Test]
    public function getDungeonPacks_givenMdtPlaceholder_leavesItOut(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);
        [$pack] = $this->getPacksWithEnemies($mappingVersion, 1);
        /** @var Enemy $placeholder */
        $placeholder          = $pack->enemies->first();
        $originalSeasonalType = $placeholder->seasonal_type;
        $placeholder->update(['seasonal_type' => Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER]);

        try {
            // Act
            $response = KeystoneGuruServer::tool(GetDungeonPacksTool::class, ['dungeon' => $dungeon->key]);

            // Assert
            $response->assertHasNoErrors();
            $content  = $this->structuredContent($response);
            $enemyIds = collect(self::getList($content, 'packs'))->pluck('enemies')->flatten(1)->pluck('id')
                ->merge(array_column($content['loose_enemies'], 'id'));
            $this->assertNotEmpty($enemyIds);
            $this->assertNotContains($placeholder->id, $enemyIds->all());
        } finally {
            Enemy::query()->whereKey($placeholder->id)->update(['seasonal_type' => $originalSeasonalType]);
        }
    }

    #[Test]
    public function getDungeonPacks_givenNoMeasuredDistance_marksTheDungeonInsteadOfEveryNeighbour(): void
    {
        // Arrange
        [$dungeon] = $this->findDungeon(
            challengeMode: true,
            dungeonActive: true,
            minEnemyPacks: 3,
            constraint: static fn($query) => $query->whereHas('expansion', static fn($expansion) => $expansion->where('active', true)),
            resolve: static fn($dungeon, $mappingVersion) => EnemyPackWalkDistance::query()
                ->whereIn('enemy_pack_id', $mappingVersion->enemyPacks()->pluck('id'))
                ->whereNotNull('walk_distance')
                ->exists() ? null : true,
        );
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);

        // Act
        $response = KeystoneGuruServer::tool(GetDungeonPacksTool::class, ['dungeon' => $dungeon->key]);

        // Assert
        $response->assertHasNoErrors();
        $content = $this->structuredContent($response);
        $this->assertFalse($content['dungeon']['distances_measured']);
        $neighbours = collect(self::getList($content, 'packs'))->pluck('neighbours')->flatten(1);
        $this->assertNotEmpty($neighbours);
        $this->assertCount(0, $neighbours->filter(static fn(array $neighbour) => array_key_exists('unverified', $neighbour)));
        $this->assertArrayHasKey('id', $neighbours->first());
    }

    #[Test]
    public function getDungeonPacks_givenUnknownDungeon_returnsDungeonNotFound(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);

        // Act
        $response = KeystoneGuruServer::tool(GetDungeonPacksTool::class, ['dungeon' => 'nope']);

        // Assert
        $response->assertHasErrors([__('mcp.error.dungeon_not_found', ['dungeon' => 'nope'])]);
    }

    /**
     * @param  array<string, mixed>             $output
     * @return array<int, array<string, mixed>>
     */
    private static function getList(array $output, string $key): array
    {
        /** @var array<int, array<string, mixed>> $list */
        $list = $output[$key];

        return $list;
    }
}
