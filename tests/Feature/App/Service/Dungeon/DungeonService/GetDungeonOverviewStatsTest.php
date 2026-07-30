<?php

namespace Tests\Feature\App\Service\Dungeon\DungeonService;

use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\Faction;
use App\Models\Mapping\MappingVersion;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\Dungeon\DungeonService;
use App\Service\Dungeon\Logging\DungeonServiceLoggingInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Closure;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonService')]
#[Group('GetDungeonOverviewStats')]
final class GetDungeonOverviewStatsTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function getDungeonOverviewStats_givenUnpackedEnemies_excludesThemFromAverage(): void
    {
        // Arrange - an isolated mapping version so the counts below aren't polluted by (or don't
        // pollute) the dungeon's real seeded mapping.
        $dungeon                = $this->getDungeonWithNonFacadeFloor();
        $existingMappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($existingMappingVersion, 'Need a dungeon with a current mapping version');

        $mappingVersion = $this->createIsolatedMappingVersion($dungeon, $existingMappingVersion);
        $floorId        = $dungeon->floors()->where('facade', false)->value('id');

        try {
            $packOne = $this->createEnemyPack($mappingVersion->id, $floorId);
            $packTwo = $this->createEnemyPack($mappingVersion->id, $floorId);

            // 3 packed enemies across 2 packs
            $this->createEnemy($mappingVersion->id, $floorId, $packOne->id);
            $this->createEnemy($mappingVersion->id, $floorId, $packOne->id);
            $this->createEnemy($mappingVersion->id, $floorId, $packTwo->id);

            // Unpacked enemies (no enemy_pack_id) must not inflate the average - these are e.g.
            // bosses or lone enemies placed outside of any pack.
            $this->createEnemy($mappingVersion->id, $floorId, null);
            $this->createEnemy($mappingVersion->id, $floorId, null);

            // getCurrentMappingVersion() caches per-instance, so re-fetch the dungeon fresh to pick
            // up the mapping version created above instead of the one resolved during Arrange.
            $freshDungeon = Dungeon::findOrFail($dungeon->id);

            $service = $this->buildService();

            // Act
            $stats = $service->getDungeonOverviewStats($freshDungeon, $mappingVersion->gameVersion);

            // Assert
            $this->assertEquals(2, $stats['pull_count']);
            $this->assertEquals(1.5, $stats['avg_enemies_per_pull'], 'Unpacked enemies must not be counted towards the average');
        } finally {
            Enemy::where('mapping_version_id', $mappingVersion->id)->delete();
            EnemyPack::where('mapping_version_id', $mappingVersion->id)->delete();
            $mappingVersion->delete();
        }
    }

    #[Test]
    public function getDungeonOverviewStats_givenNoPulls_returnsZeroAverage(): void
    {
        // Arrange - an isolated mapping version with no enemy packs at all.
        $dungeon                = $this->getDungeonWithNonFacadeFloor();
        $existingMappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($existingMappingVersion, 'Need a dungeon with a current mapping version');

        $mappingVersion = $this->createIsolatedMappingVersion($dungeon, $existingMappingVersion);

        try {
            Enemy::where('mapping_version_id', $mappingVersion->id)->delete();
            EnemyPack::where('mapping_version_id', $mappingVersion->id)->delete();

            $freshDungeon = Dungeon::findOrFail($dungeon->id);

            $service = $this->buildService();

            // Act
            $stats = $service->getDungeonOverviewStats($freshDungeon, $mappingVersion->gameVersion);

            // Assert
            $this->assertEquals(0, $stats['pull_count']);
            $this->assertEquals(0.0, $stats['avg_enemies_per_pull']);
        } finally {
            Enemy::where('mapping_version_id', $mappingVersion->id)->delete();
            EnemyPack::where('mapping_version_id', $mappingVersion->id)->delete();
            $mappingVersion->delete();
        }
    }

    /**
     * Builds a DungeonService with the cache service mocked to always call through to the closure,
     * so the computed stats can be asserted directly without depending on (or polluting) real redis.
     */
    private function buildService(): DungeonService
    {
        $cacheService = $this->createMockPublic(CacheServiceInterface::class);
        $cacheService->method('remember')->willReturnCallback(
            static fn(string $key, mixed $value, mixed $ttl = null) => $value instanceof Closure ? $value() : $value,
        );

        return new DungeonService(
            $this->createMockPublic(CookieServiceInterface::class),
            $this->createMockPublic(SeasonServiceInterface::class),
            $this->createMockPublic(DungeonServiceLoggingInterface::class),
            $this->createMockPublic(GameVersionServiceInterface::class),
            $cacheService,
        );
    }

    /**
     * Creates a newer, isolated mapping version for the dungeon so its seeded mapping version becomes
     * "non-current" (getCurrentMappingVersion() resolves to the newer one), then strips whatever
     * enemies/packs MappingVersion::boot() auto-cloned onto it so the fixture starts from a clean slate.
     */
    private function createIsolatedMappingVersion(Dungeon $dungeon, MappingVersion $existing): MappingVersion
    {
        $mappingVersion = MappingVersion::create([
            'game_version_id'                 => $existing->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $existing->version + 1000,
            'enemy_forces_required'           => $existing->enemy_forces_required,
            'enemy_forces_required_teeming'   => $existing->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $existing->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $existing->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $existing->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);

        Enemy::where('mapping_version_id', $mappingVersion->id)->delete();
        EnemyPack::where('mapping_version_id', $mappingVersion->id)->delete();

        return $mappingVersion;
    }

    private function createEnemyPack(int $mappingVersionId, int $floorId): EnemyPack
    {
        return EnemyPack::create([
            'mapping_version_id' => $mappingVersionId,
            'floor_id'           => $floorId,
            'group'              => null,
            'teeming'            => null,
            'faction'            => Faction::FACTION_ANY,
            'color'              => null,
            'color_animated'     => null,
            'label'              => 'Enemy pack',
            'vertices_json'      => '[{"lat":-50,"lng":50},{"lat":-51,"lng":50},{"lat":-51,"lng":51},{"lat":-50,"lng":51}]',
        ]);
    }

    private function createEnemy(int $mappingVersionId, int $floorId, ?int $enemyPackId): Enemy
    {
        return Enemy::create([
            'mapping_version_id' => $mappingVersionId,
            'floor_id'           => $floorId,
            'enemy_pack_id'      => $enemyPackId,
            'npc_id'             => 1,
            'faction'            => Faction::FACTION_ANY,
            'lat'                => -100.0,
            'lng'                => 100.0,
        ]);
    }
}
