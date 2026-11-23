<?php

namespace Tests\Feature\Mcp\Tools\Route;

use App\Mcp\Servers\KeystoneGuruServer;
use App\Mcp\Tools\Route\AddPullTool;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\KillZone\KillZone;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $looseEnemy = $mappingVersion->enemies()->where(self::excludeMdtPlaceholders(...))->whereNull('enemy_pack_id')->first()
            ?? $mappingVersion->enemies()->where(self::excludeMdtPlaceholders(...))->whereNotIn('enemy_pack_id', [$firstPack->id, $secondPack->id])->firstOrFail();

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
    public function handle_givenMdtPlaceholderEnemy_returnsUnknownEnemyIds(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$pack]                     = $this->getPacksWithEnemies($mappingVersion, 1);
        /** @var Enemy $placeholder */
        $placeholder          = $pack->enemies->first();
        $originalSeasonalType = $placeholder->seasonal_type;
        $placeholder->update(['seasonal_type' => Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER]);

        try {
            // Act
            $response = KeystoneGuruServer::tool(AddPullTool::class, [
                'route'     => $dungeonRoute->public_key,
                'enemy_ids' => [$placeholder->id],
            ]);

            // Assert
            $response->assertHasErrors([__('mcp.error.unknown_enemy_ids', ['ids' => $placeholder->id])]);
            $this->assertSame(0, $dungeonRoute->killZones()->count());
        } finally {
            Enemy::query()->whereKey($placeholder->id)->update(['seasonal_type' => $originalSeasonalType]);
        }
    }

    #[Test]
    public function handle_givenPackWithMdtPlaceholder_pullsOnlyThePullableEnemies(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        $pullable                   = static fn($query) => $query->where(self::excludeMdtPlaceholders(...));
        /** @var EnemyPack $pack */
        $pack = $mappingVersion->enemyPacks()
            ->whereHas('enemies', $pullable, '>=', 2)
            ->with(['enemies' => $pullable])
            ->firstOrFail();
        /** @var Enemy $placeholder */
        $placeholder          = $pack->enemies->first();
        $originalSeasonalType = $placeholder->seasonal_type;
        $placeholder->update(['seasonal_type' => Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER]);

        try {
            // Act
            $response = KeystoneGuruServer::tool(AddPullTool::class, [
                'route'    => $dungeonRoute->public_key,
                'pack_ids' => [$pack->id],
            ]);

            // Assert
            $response->assertHasNoErrors();
            $pulledEnemyIds = $this->structuredContent($response)['pull']['enemy_ids'];
            $this->assertSame($pack->enemies->where('id', '<>', $placeholder->id)->pluck('id')->sort()->values()->all(), $pulledEnemyIds);
        } finally {
            Enemy::query()->whereKey($placeholder->id)->update(['seasonal_type' => $originalSeasonalType]);
        }
    }

    #[Test]
    public function handle_givenPackOfOnlyMdtPlaceholders_returnsUnknownPackIds(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$pack]                     = $this->getPacksWithEnemies($mappingVersion, 1);
        $originalSeasonalTypes      = $pack->enemies->pluck('seasonal_type', 'id')->all();
        Enemy::query()->whereIn('id', array_keys($originalSeasonalTypes))->update(['seasonal_type' => Enemy::SEASONAL_TYPE_MDT_PLACEHOLDER]);

        try {
            // Act
            $response = KeystoneGuruServer::tool(AddPullTool::class, [
                'route'    => $dungeonRoute->public_key,
                'pack_ids' => [$pack->id],
            ]);

            // Assert
            $response->assertHasErrors([__('mcp.error.unknown_pack_ids', ['ids' => $pack->id])]);
            $this->assertSame(0, $dungeonRoute->killZones()->count());
        } finally {
            foreach ($originalSeasonalTypes as $enemyId => $seasonalType) {
                Enemy::query()->whereKey($enemyId)->update(['seasonal_type' => $seasonalType]);
            }
        }
    }

    #[Test]
    #[DataProvider('previousPullColorProvider')]
    public function handle_givenNoColor_picksAColorOfTheOtherShadeThanThePreviousPull(string $previousPullColor, bool $expectedDark): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findPackDungeon();
        $user                       = $this->actingAsMcpUser($this->createUser());
        $dungeonRoute               = $this->createDungeonRoute($user, $dungeon, $mappingVersion);
        [$firstPack, $secondPack]   = $this->getPacksWithEnemies($mappingVersion, 2);
        $this->createPull($dungeonRoute, 1, $firstPack->enemies->pluck('id')->all(), $previousPullColor);

        // Act
        $response = KeystoneGuruServer::tool(AddPullTool::class, [
            'route'    => $dungeonRoute->public_key,
            'pack_ids' => [$secondPack->id],
        ]);

        // Assert
        $response->assertHasNoErrors();
        $color = $this->structuredContent($response)['pull']['color'];
        $this->assertSame($expectedDark, isHexColorDark($color), sprintf('%s after %s', $color, $previousPullColor));
    }

    /** @return array<string, array{0: string, 1: bool}> */
    public static function previousPullColorProvider(): array
    {
        return [
            'after a dark pull'  => ['#000000', false],
            'after a light pull' => ['#ffffff', true],
        ];
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
