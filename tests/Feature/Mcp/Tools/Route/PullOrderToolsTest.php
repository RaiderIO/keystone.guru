<?php

namespace Tests\Feature\Mcp\Tools\Route;

use App\Mcp\Servers\KeystoneGuruServer;
use App\Mcp\Tools\Route\RemovePullTool;
use App\Mcp\Tools\Route\ReorderPullsTool;
use App\Models\KillZone\KillZone;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Mcp\McpTestCase;

/**
 * reorder_pulls and remove_pull: both only ever touch the caller's own route, and keep pull numbers 1..n.
 */
#[Group('Mcp')]
#[Group('McpPullOrder')]
final class PullOrderToolsTest extends McpTestCase
{
    #[Test]
    public function reorder_givenEveryPullId_savesTheNewOrder(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $packs                      = $this->getPacksWithEnemies($mappingVersion, 3);
        $pulls                      = $packs->values()->map(fn($pack, int $i) => $this->createPull($dungeonRoute, $i + 1, $pack->enemies->pluck('id')->all()));

        // Act
        $response = KeystoneGuruServer::tool(ReorderPullsTool::class, [
            'route'    => $dungeonRoute->public_key,
            'pull_ids' => [$pulls[2]->id, $pulls[0]->id, $pulls[1]->id],
        ]);

        // Assert
        $response->assertHasNoErrors();
        $this->assertSame(1, $pulls[2]->fresh()->index);
        $this->assertSame(2, $pulls[0]->fresh()->index);
        $this->assertSame(3, $pulls[1]->fresh()->index);
        $this->assertSame([$pulls[2]->id, $pulls[0]->id, $pulls[1]->id], array_column($this->structuredContent($response)['pulls'], 'id'));
    }

    #[Test]
    public function reorder_givenRouteAtItsPullLimit_savesTheNewOrder(): void
    {
        // Arrange - reordering creates no pull, so the route's pull cap must not refuse it
        config(['keystoneguru.dungeon_route_limits.kill_zones' => 2]);
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$firstPack, $secondPack]   = $this->getPacksWithEnemies($mappingVersion, 2);
        $firstPull                  = $this->createPull($dungeonRoute, 1, $firstPack->enemies->pluck('id')->all());
        $secondPull                 = $this->createPull($dungeonRoute, 2, $secondPack->enemies->pluck('id')->all());

        // Act
        $response = KeystoneGuruServer::tool(ReorderPullsTool::class, [
            'route'    => $dungeonRoute->public_key,
            'pull_ids' => [$secondPull->id, $firstPull->id],
        ]);

        // Assert
        $response->assertHasNoErrors();
        $this->assertSame(1, $secondPull->fresh()->index);
        $this->assertSame(2, $firstPull->fresh()->index);
    }

    #[Test]
    public function reorder_givenMissingPullId_refusesAndKeepsTheOrder(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$firstPack, $secondPack]   = $this->getPacksWithEnemies($mappingVersion, 2);
        $firstPull                  = $this->createPull($dungeonRoute, 1, $firstPack->enemies->pluck('id')->all());
        $secondPull                 = $this->createPull($dungeonRoute, 2, $secondPack->enemies->pluck('id')->all());

        // Act
        $response = KeystoneGuruServer::tool(ReorderPullsTool::class, [
            'route'    => $dungeonRoute->public_key,
            'pull_ids' => [$secondPull->id],
        ]);

        // Assert
        $response->assertHasErrors(['every pull id']);
        $this->assertSame(1, $firstPull->fresh()->index);
        $this->assertSame(2, $secondPull->fresh()->index);
    }

    #[Test]
    public function reorder_givenSomeoneElsesRoute_returnsRouteNotFound(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $dungeonRoute               = $this->createDungeonRoute($this->createUser(), $dungeon, $mappingVersion);
        [$firstPack, $secondPack]   = $this->getPacksWithEnemies($mappingVersion, 2);
        $firstPull                  = $this->createPull($dungeonRoute, 1, $firstPack->enemies->pluck('id')->all());
        $secondPull                 = $this->createPull($dungeonRoute, 2, $secondPack->enemies->pluck('id')->all());
        $this->actingAsMcpUser($this->createUser());

        // Act
        $response = KeystoneGuruServer::tool(ReorderPullsTool::class, [
            'route'    => $dungeonRoute->public_key,
            'pull_ids' => [$secondPull->id, $firstPull->id],
        ]);

        // Assert
        $response->assertHasErrors([__('mcp.error.route_not_found', ['route' => $dungeonRoute->public_key])]);
        $this->assertSame(1, $firstPull->fresh()->index);
    }

    #[Test]
    public function remove_givenMiddlePull_removesItAndRenumbersTheRest(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $packs                      = $this->getPacksWithEnemies($mappingVersion, 3);
        $pulls                      = $packs->values()->map(fn($pack, int $i) => $this->createPull($dungeonRoute, $i + 1, $pack->enemies->pluck('id')->all()));

        // Act
        $response = KeystoneGuruServer::tool(RemovePullTool::class, [
            'route'   => $dungeonRoute->public_key,
            'pull_id' => $pulls[1]->id,
        ]);

        // Assert
        $response->assertHasNoErrors();
        $this->assertNull(KillZone::find($pulls[1]->id));
        $this->assertSame(1, $pulls[0]->fresh()->index);
        $this->assertSame(2, $pulls[2]->fresh()->index);
        $this->assertSame(2, $this->structuredContent($response)['pull_count']);
    }

    #[Test]
    public function remove_givenPullOfSomeoneElsesRoute_removesNothing(): void
    {
        // Arrange - the caller owns a route too, so the pull id is the only thing that points elsewhere
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $ownRoute                   = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $otherRoute                 = $this->createDungeonRoute($this->createUser(), $dungeon, $mappingVersion);
        [$pack]                     = $this->getPacksWithEnemies($mappingVersion, 1);
        $otherPull                  = $this->createPull($otherRoute, 1, $pack->enemies->pluck('id')->all());

        // Act
        $response = KeystoneGuruServer::tool(RemovePullTool::class, [
            'route'   => $ownRoute->public_key,
            'pull_id' => $otherPull->id,
        ]);

        // Assert
        $response->assertHasErrors([__('mcp.error.pull_not_found', ['pull' => $otherPull->id])]);
        $this->assertNotNull(KillZone::find($otherPull->id));
    }
}
