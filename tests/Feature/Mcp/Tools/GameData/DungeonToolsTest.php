<?php

namespace Tests\Feature\Mcp\Tools\GameData;

use App\Logic\Structs\LatLng;
use App\Mcp\Servers\KeystoneGuruServer;
use App\Mcp\Tools\GameData\GetDungeonPacksTool;
use App\Mcp\Tools\GameData\ListDungeonsTool;
use App\Models\Enemy;
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
    public function getDungeonPacks_givenUnknownDungeon_returnsDungeonNotFound(): void
    {
        // Arrange
        $this->actingAsMcpUser($this->createUser(), [ApiTokenScope::RoutesRead]);

        // Act
        $response = KeystoneGuruServer::tool(GetDungeonPacksTool::class, ['dungeon' => 'nope']);

        // Assert
        $response->assertHasErrors([__('mcp.error.dungeon_not_found', ['dungeon' => 'nope'])]);
    }
}
