<?php

namespace Tests\Feature\App\Service\MDT\Export;

use App\Logic\MDT\Exception\ImportWarning;
use App\Models\DungeonKey;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Faction;
use App\Models\KillZone\KillZone;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Service\MDT\Export\PullExporter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\App\Service\MDT\MDTExportStringServiceTestBase;

#[Group('MDT')]
#[Group('PullExporter')]
final class PullExporterTest extends MDTExportStringServiceTestBase
{
    #[Test]
    public function export_givenKillZoneWithAnMdtEnemy_returnsThatEnemysCloneInThePull(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();

            /** @var Enemy $enemy */
            $enemy = $this->getSafeMdtEnemies($dungeonRoute)->first();
            KillZone::factory()
                ->withEnemies($enemy)
                ->create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'index'            => 1,
                    'description'      => null,
                    'floor_id'         => null,
                    'lat'              => null,
                    'lng'              => null,
                ]);

            $warnings = collect();

            // Act
            $pulls = app(PullExporter::class)->export($dungeonRoute, $dungeonRoute->mappingVersion, $warnings);

            // Assert - Lua is 1 based, so the first pull sits at index 1
            $this->assertEmpty($warnings);
            $this->assertCount(1, $pulls);

            $pullNpcIndices = array_diff_key($pulls[1], ['color' => null]);
            $this->assertCount(1, $pullNpcIndices);
            $this->assertSame([$enemy->mdt_id], array_values($pullNpcIndices)[0]);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function export_givenKillZoneColorWithLeadingHash_returnsColorWithoutIt(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();

            /** @var Enemy $enemy */
            $enemy = $this->getSafeMdtEnemies($dungeonRoute)->first();
            KillZone::factory()
                ->withEnemies($enemy)
                ->create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'index'            => 1,
                    'color'            => '#ABCDEF',
                    'description'      => null,
                    'floor_id'         => null,
                    'lat'              => null,
                    'lng'              => null,
                ]);

            // Act
            $pulls = app(PullExporter::class)->export($dungeonRoute, $dungeonRoute->mappingVersion, collect());

            // Assert
            $this->assertSame('ABCDEF', $pulls[1]['color']);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function export_givenTolDagorEnemyOfOffsetMdtNpcIndex_returnsMdtCloneIndex(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange - Tol Dagor's second MDT npc index for npc 131112 starts at mdt_id 3 for its clone 1
            $dungeonRoute = $this->createDungeonRouteForCurrentMappingVersion(DungeonKey::TOL_DAGOR);

            /** @var Enemy $enemy */
            $enemy = $dungeonRoute->mappingVersion->enemies()
                ->where('npc_id', 131112)
                ->where('mdt_id', 3)
                ->sole();
            KillZone::factory()
                ->withEnemies($enemy)
                ->create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'index'            => 1,
                    'description'      => null,
                    'floor_id'         => null,
                    'lat'              => null,
                    'lng'              => null,
                ]);

            $warnings = collect();

            // Act
            $pulls = app(PullExporter::class)->export($dungeonRoute, $dungeonRoute->mappingVersion, $warnings);

            // Assert
            $this->assertEmpty($warnings);
            $this->assertSame([11 => [1]], array_diff_key($pulls[1], ['color' => null]));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function export_givenEnemyWhoseMdtIdExistsOnTwoFloors_returnsCloneOnItsOwnFloor(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange - MDT numbers clones per floor, so npc 131112's mdt_id 2 exists on both of Tol Dagor's
            // floors: npc index 11 on the first floor, npc index 7 on the second, where this enemy is
            $dungeonRoute = $this->createDungeonRouteForCurrentMappingVersion(DungeonKey::TOL_DAGOR);

            /** @var Enemy $enemy */
            $enemy = $dungeonRoute->mappingVersion->enemies()
                ->where('npc_id', 131112)
                ->where('mdt_id', 2)
                ->sole();
            KillZone::factory()
                ->withEnemies($enemy)
                ->create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'index'            => 1,
                    'description'      => null,
                    'floor_id'         => null,
                    'lat'              => null,
                    'lng'              => null,
                ]);

            $warnings = collect();

            // Act
            $pulls = app(PullExporter::class)->export($dungeonRoute, $dungeonRoute->mappingVersion, $warnings);

            // Assert
            $this->assertEmpty($warnings);
            $this->assertSame([7 => [2]], array_diff_key($pulls[1], ['color' => null]));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function export_givenPullOfOnlyAnEnemyUnknownToMdt_warnsAndLeavesThePullOut(): void
    {
        $dungeonRoute = null;
        $unknownEnemy = null;

        try {
            // Arrange
            $isNotABoss   = static fn(Enemy $enemy): bool => !$enemy->npc->isBoss();
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies(enemyFilter: $isNotABoss);

            /** @var Enemy $knownEnemy */
            $knownEnemy   = $this->getSafeMdtEnemies($dungeonRoute, enemyFilter: $isNotABoss)->first();
            $unknownEnemy = $this->createEnemyUnknownToMdt($dungeonRoute, $knownEnemy->npc_id, $knownEnemy->floor_id);

            $this->createKillZone($dungeonRoute, 1, $unknownEnemy);
            $this->createKillZone($dungeonRoute, 2, $knownEnemy);

            $warnings = collect();

            // Act
            $pulls = app(PullExporter::class)->export($dungeonRoute, $dungeonRoute->mappingVersion, $warnings);

            // Assert - the empty pull is left out, so the next pull takes its index
            $this->assertSame([
                [sprintf(__('services.mdt.io.export_string.category.pull'), 1), sprintf(
                    __('services.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_enemy'),
                    __($knownEnemy->npc->name),
                    $unknownEnemy->id,
                    $knownEnemy->npc_id,
                )],
                [sprintf(__('services.mdt.io.export_string.category.pull'), 1), __('services.mdt.io.export_string.unable_to_find_mdt_enemy_for_kg_caused_empty_pull')],
            ], $warnings->map(static fn(ImportWarning $warning): array => [$warning->getCategory(), $warning->getMessage()])->all());
            $this->assertCount(1, $pulls);
            $this->assertSame([[$knownEnemy->mdt_id]], array_values(array_diff_key($pulls[1], ['color' => null])));
        } finally {
            $dungeonRoute?->delete();
            $unknownEnemy?->delete();
        }
    }

    #[Test]
    public function export_givenBossUnknownToMdtInAPull_leavesItOutWithoutAWarning(): void
    {
        $dungeonRoute = null;
        $unknownBoss  = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();

            /** @var Enemy $knownEnemy */
            $knownEnemy = $this->getSafeMdtEnemies($dungeonRoute)->first();

            /** @var Npc $bossNpc */
            $bossNpc = Npc::query()
                ->where('classification_id', NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS])
                ->firstOrFail();
            $unknownBoss = $this->createEnemyUnknownToMdt($dungeonRoute, $bossNpc->id, $knownEnemy->floor_id);

            $this->createKillZone($dungeonRoute, 1, $knownEnemy, $unknownBoss);

            $warnings = collect();

            // Act
            $pulls = app(PullExporter::class)->export($dungeonRoute, $dungeonRoute->mappingVersion, $warnings);

            // Assert
            $this->assertEmpty($warnings);
            $this->assertCount(1, $pulls);
            $this->assertSame([[$knownEnemy->mdt_id]], array_values(array_diff_key($pulls[1], ['color' => null])));
        } finally {
            $dungeonRoute?->delete();
            $unknownBoss?->delete();
        }
    }

    #[Test]
    public function export_givenRouteWithoutKillZones_returnsNoPulls(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();

            $warnings = collect();

            // Act
            $pulls = app(PullExporter::class)->export($dungeonRoute, $dungeonRoute->mappingVersion, $warnings);

            // Assert
            $this->assertEmpty($warnings);
            $this->assertEmpty($pulls);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    /**
     * An enemy of the route's mapping version without an MDT clone index, which MDT therefore knows nothing about.
     */
    private function createEnemyUnknownToMdt(DungeonRoute $dungeonRoute, int $npcId, int $floorId): Enemy
    {
        return Enemy::create([
            'mapping_version_id' => $dungeonRoute->mapping_version_id,
            'floor_id'           => $floorId,
            'npc_id'             => $npcId,
            'mdt_id'             => null,
            'faction'            => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            'lat'                => -100.0,
            'lng'                => 100.0,
        ]);
    }

    private function createKillZone(DungeonRoute $dungeonRoute, int $index, Enemy ...$enemies): KillZone
    {
        return KillZone::factory()
            ->withEnemies(...$enemies)
            ->create([
                'dungeon_route_id' => $dungeonRoute->id,
                'index'            => $index,
                'description'      => null,
                'floor_id'         => null,
                'lat'              => null,
                'lng'              => null,
            ]);
    }
}
