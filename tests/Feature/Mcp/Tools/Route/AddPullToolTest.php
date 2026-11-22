<?php

namespace Tests\Feature\Mcp\Tools\Route;

use App\Mcp\Servers\KeystoneGuruServer;
use App\Mcp\Tools\Route\AddPullTool;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Mcp\McpTestCase;

#[Group('Mcp')]
#[Group('McpAddPull')]
final class AddPullToolTest extends McpTestCase
{
    #[Test]
    public function handle_givenPackAndEnemy_addsPullWithEveryPackEnemyAtTheEnd(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$firstPack, $secondPack]   = $this->getPacksWithEnemies($mappingVersion, 2);
        $this->createPull($dungeonRoute, 1, $firstPack->enemies->pluck('id')->all());
        $looseEnemy = $mappingVersion->enemies()->whereNull('enemy_pack_id')->first()
            ?? $mappingVersion->enemies()->whereNotIn('enemy_pack_id', [$firstPack->id, $secondPack->id])->firstOrFail();

        // Act
        $response = KeystoneGuruServer::tool(AddPullTool::class, [
            'route'     => $dungeonRoute->public_key,
            'pack_ids'  => [$secondPack->id],
            'enemy_ids' => [$looseEnemy->id],
            'color'     => '#123456',
        ]);

        // Assert
        $response->assertHasNoErrors();
        $expectedEnemyIds = $secondPack->enemies->pluck('id')->push($looseEnemy->id)->sort()->values()->all();

        /** @var KillZone $killZone */
        $killZone = $dungeonRoute->killZones()->where('index', 2)->firstOrFail();
        $this->assertSame('#123456', $killZone->color);
        $this->assertEqualsCanonicalizing($expectedEnemyIds, $killZone->killZoneEnemies()->pluck('enemy_id')->map(static fn($id) => (int)$id)->all());

        $pull = $this->structuredContent($response)['pull'];
        $this->assertSame($killZone->id, $pull['id']);
        $this->assertSame(2, $pull['index']);
        $this->assertSame($expectedEnemyIds, $pull['enemy_ids']);
    }

    #[Test]
    public function handle_givenSomeoneElsesRoute_returnsRouteNotFoundAndAddsNothing(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $owner                      = $this->createUser();
        $dungeonRoute               = $this->createDungeonRoute($owner, $dungeon, $mappingVersion);
        [$pack]                     = $this->getPacksWithEnemies($mappingVersion, 1);
        $this->actingAsMcpUser($this->createUser());

        // Act
        $response = KeystoneGuruServer::tool(AddPullTool::class, [
            'route'    => $dungeonRoute->public_key,
            'pack_ids' => [$pack->id],
        ]);

        // Assert
        $response->assertHasErrors([__('mcp.error.route_not_found', ['route' => $dungeonRoute->public_key])]);
        $this->assertSame(0, $dungeonRoute->killZones()->count());
    }

    #[Test]
    public function handle_givenPackOfAnotherMapping_returnsUnknownPackIds(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $foreignEnemy               = Enemy::query()
            ->where('mapping_version_id', '<>', $mappingVersion->id)
            ->whereNotNull('enemy_pack_id')
            ->firstOrFail();

        // Act
        $response = KeystoneGuruServer::tool(AddPullTool::class, [
            'route'    => $dungeonRoute->public_key,
            'pack_ids' => [$foreignEnemy->enemy_pack_id],
        ]);

        // Assert
        $response->assertHasErrors([__('mcp.error.unknown_pack_ids', ['ids' => $foreignEnemy->enemy_pack_id])]);
        $this->assertSame(0, $dungeonRoute->killZones()->count());
    }

    #[Test]
    public function handle_givenEnemyOfAnotherMapping_returnsUnknownEnemyIds(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $foreignEnemy               = Enemy::query()->where('mapping_version_id', '<>', $mappingVersion->id)->firstOrFail();

        // Act
        $response = KeystoneGuruServer::tool(AddPullTool::class, [
            'route'     => $dungeonRoute->public_key,
            'enemy_ids' => [$foreignEnemy->id],
        ]);

        // Assert
        $response->assertHasErrors([__('mcp.error.unknown_enemy_ids', ['ids' => $foreignEnemy->id])]);
        $this->assertSame(0, $dungeonRoute->killZones()->count());
    }

    #[Test]
    public function handle_givenEnemyAlreadyInAnotherPull_refusesThePull(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$pack]                     = $this->getPacksWithEnemies($mappingVersion, 1);
        $pulledEnemy                = $pack->enemies->first();
        $this->createPull($dungeonRoute, 1, [$pulledEnemy->id]);

        // Act
        $response = KeystoneGuruServer::tool(AddPullTool::class, [
            'route'    => $dungeonRoute->public_key,
            'pack_ids' => [$pack->id],
        ]);

        // Assert
        $response->assertHasErrors([sprintf('%d => 1', $pulledEnemy->id)]);
        $this->assertSame(1, $dungeonRoute->killZones()->count());
    }

    #[Test]
    public function handle_givenRateLimitReached_refusesThePull(): void
    {
        // Arrange
        config(['keystoneguru.api.mcp.edit_pull_per_hour' => 1]);
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$firstPack, $secondPack]   = $this->getPacksWithEnemies($mappingVersion, 2);
        KeystoneGuruServer::tool(AddPullTool::class, ['route' => $dungeonRoute->public_key, 'pack_ids' => [$firstPack->id]])
            ->assertHasNoErrors();

        // Act
        $response = KeystoneGuruServer::tool(AddPullTool::class, ['route' => $dungeonRoute->public_key, 'pack_ids' => [$secondPack->id]]);

        // Assert
        $response->assertHasErrors(['Too many requests']);
        $this->assertSame(1, $dungeonRoute->killZones()->count());
    }
}
