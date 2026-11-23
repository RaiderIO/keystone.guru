<?php

namespace Tests\Feature\Mcp\Tools\Route;

use App\Logic\Structs\LatLng;
use App\Mcp\Servers\KeystoneGuruServer;
use App\Mcp\Tools\Route\GetRouteTool;
use App\Mcp\Tools\Route\ListMyRoutesTool;
use App\Mcp\Tools\Route\ValidateRouteTool;
use App\Models\Enemy;
use App\Models\Faction;
use App\Models\Npc\NpcClassification;
use App\Models\PublishedState;
use App\Service\Coordinates\CoordinatesServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Mcp\McpTestCase;

/**
 * get_route, list_my_routes and validate_route only ever show the caller's own routes.
 */
#[Group('Mcp')]
#[Group('McpRouteRead')]
final class RouteReadToolsTest extends McpTestCase
{
    #[Test]
    public function getRoute_givenOwnRoute_returnsPullsWithKillAreaInIngameCoordinates(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$pack]                     = $this->getPacksWithEnemies($mappingVersion, 1);
        /** @var Enemy $enemy */
        $enemy    = $pack->enemies->first()->load('floor');
        $killZone = $this->createPull($dungeonRoute, 1, [$enemy->id], '#abcdef');
        $killZone->update(['floor_id' => $enemy->floor_id, 'lat' => $enemy->lat, 'lng' => $enemy->lng]);
        $expectedXY = app(CoordinatesServiceInterface::class)->calculateIngameLocationForMapLocation(
            new LatLng($enemy->lat, $enemy->lng, $enemy->floor),
        );

        // Act
        $response = KeystoneGuruServer::tool(GetRouteTool::class, ['route' => $dungeonRoute->public_key]);

        // Assert
        $response->assertHasNoErrors();
        $pull = $this->structuredContent($response)['pulls'][0];
        $this->assertSame($killZone->id, $pull['id']);
        $this->assertSame('#abcdef', $pull['color']);
        $this->assertSame([$enemy->id], $pull['enemy_ids']);
        $this->assertSame($enemy->floor_id, $pull['kill_area']['floor_id']);
        $this->assertEqualsWithDelta($expectedXY->getX(), $pull['kill_area']['x'], 0.1);
        $this->assertEqualsWithDelta($expectedXY->getY(), $pull['kill_area']['y'], 0.1);
    }

    #[Test]
    public function getRoute_givenFactionRoute_returnsTheFactionKey(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $dungeonRoute->update(['faction_id' => Faction::ALL[Faction::FACTION_HORDE]]);

        // Act
        $response = KeystoneGuruServer::tool(GetRouteTool::class, ['route' => $dungeonRoute->public_key]);

        // Assert
        $response->assertHasNoErrors();
        $content = $this->structuredContent($response);
        $this->assertSame(Faction::FACTION_HORDE, $content['faction']);
        $this->assertArrayNotHasKey('faction_id', $content);
    }

    #[Test]
    public function getRoute_givenSomeoneElsesPublishedRoute_returnsRouteNotFound(): void
    {
        // Arrange - published to the world, so the view policy alone would let anyone read it
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $dungeonRoute               = $this->createDungeonRoute($this->createUser(), $dungeon, $mappingVersion);
        $dungeonRoute->update(['published_state_id' => PublishedState::ALL[PublishedState::WORLD]]);
        $this->actingAsMcpUser($this->createUser());

        // Act
        $response = KeystoneGuruServer::tool(GetRouteTool::class, ['route' => $dungeonRoute->public_key]);

        // Assert
        $response->assertHasErrors([__('mcp.error.route_not_found', ['route' => $dungeonRoute->public_key])]);
        $response->assertDontSee($dungeonRoute->title);
    }

    #[Test]
    public function getRoute_givenOwnRouteOnATeam_returnsRouteNotFound(): void
    {
        // Arrange - teammates can edit a team route's free text, so the tools leave team routes out entirely
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $dungeonRoute->update(['team_id' => 999999]);

        // Act
        $response = KeystoneGuruServer::tool(GetRouteTool::class, ['route' => $dungeonRoute->public_key]);

        // Assert
        $response->assertHasErrors([__('mcp.error.route_not_found', ['route' => $dungeonRoute->public_key])]);
    }

    #[Test]
    public function listMyRoutes_givenOwnRouteOnATeam_leavesItOut(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $ownRoute                   = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $teamRoute                  = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $teamRoute->update(['team_id' => 999999]);

        // Act
        $response = KeystoneGuruServer::tool(ListMyRoutesTool::class);

        // Assert
        $response->assertHasNoErrors();
        $this->assertSame([$ownRoute->public_key], array_column($this->structuredContent($response)['routes'], 'public_key'));
    }

    #[Test]
    public function listMyRoutes_givenOwnAndOthersRoutes_listsOnlyOwnRoutes(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $ownRoute                   = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $otherRoute                 = $this->createDungeonRoute($this->createUser(), $dungeon, $mappingVersion);

        // Act
        $response = KeystoneGuruServer::tool(ListMyRoutesTool::class);

        // Assert
        $response->assertHasNoErrors();
        $content = $this->structuredContent($response);
        $this->assertSame(1, $content['total']);
        $this->assertSame([$ownRoute->public_key], array_column($content['routes'], 'public_key'));
        $this->assertNotContains($otherRoute->public_key, array_column($content['routes'], 'public_key'));
    }

    #[Test]
    public function validateRoute_givenRouteMissingForcesAndBosses_reportsEveryProblem(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(
            challengeMode: true,
            dungeonActive: true,
            minEnemyPacks: 2,
            resolve: static fn($dungeon, $mappingVersion) => $mappingVersion->enemies()
                ->where(self::excludeMdtPlaceholders(...))
                ->whereNull('seasonal_index')
                ->whereNull('dungeon_difficulty')
                ->whereNull('teeming')
                ->whereHas('npc', static fn($npc) => $npc->where('classification_id', NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]))
                ->exists() ? true : null,
        );
        $user         = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $dungeonRoute->update(['teeming' => false, 'seasonal_index' => 0, 'dungeon_difficulty' => null]);
        [$pack]     = $this->getPacksWithEnemies($mappingVersion, 1);
        $enemyId    = $pack->enemies->first()->id;
        $firstPull  = $this->createPull($dungeonRoute, 1, [$enemyId]);
        $secondPull = $this->createPull($dungeonRoute, 2, [$enemyId]);
        $emptyPull  = $this->createPull($dungeonRoute, 3, []);
        $boss       = $mappingVersion->enemies()
            ->where(self::excludeMdtPlaceholders(...))
            ->whereNull('seasonal_index')
            ->whereNull('dungeon_difficulty')
            ->whereNull('teeming')
            ->whereHas('npc', static fn($npc) => $npc->where('classification_id', NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]))
            ->firstOrFail();

        // Act
        $response = KeystoneGuruServer::tool(ValidateRouteTool::class, ['route' => $dungeonRoute->public_key]);

        // Assert
        $response->assertHasNoErrors();
        $content = $this->structuredContent($response);
        $this->assertFalse($content['valid']);
        $this->assertGreaterThan(0, $content['enemy_forces_missing']);
        $this->assertContains($boss->id, array_column($content['missing_bosses'], 'enemy_id'));
        $this->assertSame([['pull_id' => $emptyPull->id, 'index' => 3]], $content['empty_pulls']);
        $this->assertSame([['enemy_id' => $enemyId, 'pull_indices' => [$firstPull->index, $secondPull->index]]], $content['overlapping_enemies']);
    }

    #[Test]
    public function validateRoute_givenSomeoneElsesRoute_returnsRouteNotFound(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $dungeonRoute               = $this->createDungeonRoute($this->createUser(), $dungeon, $mappingVersion);
        $this->actingAsMcpUser($this->createUser());

        // Act
        $response = KeystoneGuruServer::tool(ValidateRouteTool::class, ['route' => $dungeonRoute->public_key]);

        // Assert
        $response->assertHasErrors([__('mcp.error.route_not_found', ['route' => $dungeonRoute->public_key])]);
    }
}
