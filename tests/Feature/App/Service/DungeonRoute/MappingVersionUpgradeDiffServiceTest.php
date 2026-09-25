<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\MapIcon;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
use App\Service\DungeonRoute\Dtos\MappingVersionUpgradeDiffEnemy;
use App\Service\DungeonRoute\Dtos\MappingVersionUpgradeDiffMovedEnemy;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use App\Service\DungeonRoute\MappingVersionUpgradeDiffServiceInterface;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('DungeonRoute')]
final class MappingVersionUpgradeDiffServiceTest extends DungeonRouteSaveServiceTestCase
{
    /**
     * Every model created by a test, torn down newest first.
     *
     * @var array<int, Model>
     */
    private array $cleanup = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

    private function service(): MappingVersionUpgradeDiffServiceInterface
    {
        return app(MappingVersionUpgradeDiffServiceInterface::class);
    }

    /**
     * Two empty mapping versions of the same dungeon, so every test states exactly the mapping it diffs.
     *
     * @return array{0: Dungeon, 1: MappingVersion, 2: MappingVersion, 3: Floor}
     */
    private function createMappingVersionPair(): array
    {
        [$dungeon, $mappingVersion, $floor] = $this->findDungeon(
            facadeEnabled: false,
            resolve:       static fn(Dungeon $dungeon): ?Floor => $dungeon->floors()
                ->where('facade', false)
                ->where('active', true)
                ->whereNotNull('ingame_min_y')
                ->whereRaw('ingame_max_y != ingame_min_y')
                ->whereRaw('ingame_max_x != ingame_min_x')
                ->first(),
        );

        $oldMappingVersion = $this->createNewerMappingVersion($dungeon, $mappingVersion);
        array_unshift($this->cleanup, $oldMappingVersion);
        $this->emptyMappingVersion($oldMappingVersion);

        $newMappingVersion = $this->createNewerMappingVersion($dungeon, $oldMappingVersion);
        array_unshift($this->cleanup, $newMappingVersion);
        $this->emptyMappingVersion($newMappingVersion);

        return [$dungeon, $oldMappingVersion, $newMappingVersion, $floor];
    }

    /**
     * Strips the mapping a new mapping version clones off its predecessor, so each test states the entire mapping
     * it diffs rather than asserting against whichever dungeon the pick happened to land on.
     */
    private function emptyMappingVersion(MappingVersion $mappingVersion): void
    {
        Enemy::query()->where('mapping_version_id', $mappingVersion->id)->delete();
        EnemyPack::query()->where('mapping_version_id', $mappingVersion->id)->delete();
        EnemyPatrol::query()->where('mapping_version_id', $mappingVersion->id)->delete();
        NpcEnemyForces::query()->where('mapping_version_id', $mappingVersion->id)->delete();
        MapIcon::query()->where('mapping_version_id', $mappingVersion->id)->delete();
    }

    private function createRoute(Dungeon $dungeon, MappingVersion $mappingVersion, bool $teeming = false): DungeonRoute
    {
        $route = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'teeming'            => $teeming,
            'expires_at'         => null,
        ]);
        array_unshift($this->cleanup, $route);

        return $route;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createEnemy(MappingVersion $mappingVersion, Floor $floor, array $attributes = []): Enemy
    {
        $enemy = Enemy::create(array_merge([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'npc_id'             => null,
            'mdt_id'             => null,
            'mdt_npc_id'         => null,
            'teeming'            => null,
            'required'           => false,
            'lat'                => -100.0,
            'lng'                => 100.0,
        ], $attributes));
        array_unshift($this->cleanup, $enemy);

        return $enemy;
    }

    private function createKillZone(DungeonRoute $dungeonRoute, int $index): KillZone
    {
        $killZone = KillZone::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => null,
            'color'            => '#00ff00',
            'index'            => $index,
        ]);
        array_unshift($this->cleanup, $killZone);

        return $killZone;
    }

    private function pullEnemy(KillZone $killZone, Enemy $enemy): KillZoneEnemy
    {
        $killZoneEnemy = KillZoneEnemy::create([
            'kill_zone_id' => $killZone->id,
            'npc_id'       => $enemy->mdt_npc_id ?? $enemy->npc_id,
            'mdt_id'       => $enemy->mdt_id,
            'enemy_id'     => $enemy->id,
        ]);
        array_unshift($this->cleanup, $killZoneEnemy);

        return $killZoneEnemy;
    }

    /**
     * How many lat units one ingame yard is worth on this floor. Lat/lng is display-only, so every distance a
     * test states has to be expressed in yards and converted, never assumed.
     */
    private function latPerYard(Floor $floor): float
    {
        return 256 / abs($floor->ingame_max_y - $floor->ingame_min_y);
    }

    private function tearDownCleanup(): void
    {
        foreach ($this->cleanup as $model) {
            // fresh() rather than refresh(): the upgrade parity test lets the real upgrade delete rows out from
            // under this list, and a torn down row is exactly the outcome that test asserts
            $model->fresh()?->delete();
        }
        $this->cleanup = [];
    }

    // ------------------------------------------------------------------ removed / emptied

    #[Test]
    public function diff_givenPullEnemyMissingInNewMappingVersion_reportsRemovedPullEnemyWithItsPull(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            $route = $this->createRoute($dungeon, $oldMappingVersion);

            $keptEnemy    = $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 1, 'mdt_id' => 1]);
            $removedEnemy = $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 2, 'mdt_id' => 2]);
            $this->createEnemy($newMappingVersion, $floor, ['npc_id' => 1, 'mdt_id' => 1]);

            $killZone = $this->createKillZone($route, 3);
            $this->pullEnemy($killZone, $keptEnemy);
            $this->pullEnemy($killZone, $removedEnemy);

            // Act
            $diff = $this->service()->diff($route, $newMappingVersion);

            // Assert
            $this->assertCount(1, $diff->removedPullEnemies);
            /** @var MappingVersionUpgradeDiffEnemy $removed */
            $removed = $diff->removedPullEnemies->first();
            $this->assertEquals($removedEnemy->id, $removed->enemyId);
            $this->assertEquals(2, $removed->npcId);
            $this->assertEquals(3, $removed->pull?->index);
            $this->assertEquals('#00ff00', $removed->pull?->color);
            $this->assertCount(0, $diff->emptiedPulls);
            $this->assertTrue($diff->hasRouteImpact());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function diff_givenEveryPullEnemyMissingInNewMappingVersion_reportsEmptiedPull(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            $route = $this->createRoute($dungeon, $oldMappingVersion);

            $enemy    = $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 2, 'mdt_id' => 2]);
            $killZone = $this->createKillZone($route, 1);
            $this->pullEnemy($killZone, $enemy);

            // Act
            $diff = $this->service()->diff($route, $newMappingVersion);

            // Assert
            $this->assertCount(1, $diff->emptiedPulls);
            $this->assertEquals(1, $diff->emptiedPulls->first()?->index);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function diff_givenPullEnemyWithoutMdtId_reportsItAsRemovedJustLikeTheUpgradeDropsIt(): void
    {
        // Arrange - the upgrade joins mdt_id with a plain `=`, so a null on either side matches nothing at all
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            $route = $this->createRoute($dungeon, $oldMappingVersion);

            $enemy = $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 5, 'mdt_id' => null]);
            $this->createEnemy($newMappingVersion, $floor, ['npc_id' => 5, 'mdt_id' => null]);

            $killZone = $this->createKillZone($route, 1);
            $this->pullEnemy($killZone, $enemy);

            // Act
            $diff = $this->service()->diff($route, $newMappingVersion);

            // Assert
            $this->assertCount(1, $diff->removedPullEnemies);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function diff_givenMdtNpcIdMatching_matchesExactlyWhatTheUpgradeKeeps(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            $route = $this->createRoute($dungeon, $oldMappingVersion);

            // MDT knows this enemy as npc 77, we know its real npc as 88 - the upgrade matches on MDT's
            $keptOld = $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 88, 'mdt_npc_id' => 77, 'mdt_id' => 4]);
            $keptNew = $this->createEnemy($newMappingVersion, $floor, ['npc_id' => 99, 'mdt_npc_id' => 77, 'mdt_id' => 4]);
            $goneOld = $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 12, 'mdt_id' => 9]);

            $killZone = $this->createKillZone($route, 1);
            $this->pullEnemy($killZone, $keptOld);
            $this->pullEnemy($killZone, $goneOld);

            $diff = $this->service()->diff($route, $newMappingVersion);

            // Act - let the upgrade itself run over the very same fixture
            app(DungeonRouteServiceInterface::class)->upgradeMappingVersion($route);

            // Assert
            $survivingEnemyIds = KillZoneEnemy::query()
                ->where('kill_zone_id', $killZone->id)
                ->pluck('enemy_id')
                ->all();

            $this->assertEquals([$keptNew->id], $survivingEnemyIds);
            $this->assertCount(1, $diff->removedPullEnemies);
            $this->assertEquals($goneOld->id, $diff->removedPullEnemies->first()?->enemyId);
        } finally {
            $this->tearDownCleanup();
        }
    }

    // ------------------------------------------------------------------ moved

    #[Test]
    public function diff_givenEnemyMovedBeyondTheThreshold_reportsMovedPullEnemyWithItsDistance(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            config(['keystoneguru.mapping_version_upgrade_diff.enemy_moved_min_distance_yd' => 10]);

            $route = $this->createRoute($dungeon, $oldMappingVersion);

            $oldEnemy = $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 1, 'mdt_id' => 1, 'lat' => -100.0]);
            $this->createEnemy($newMappingVersion, $floor, [
                'npc_id' => 1,
                'mdt_id' => 1,
                'lat'    => -100.0 + (30 * $this->latPerYard($floor)),
            ]);

            $killZone = $this->createKillZone($route, 2);
            $this->pullEnemy($killZone, $oldEnemy);

            // Act
            $diff = $this->service()->diff($route, $newMappingVersion);

            // Assert
            $this->assertCount(1, $diff->movedPullEnemies);
            /** @var MappingVersionUpgradeDiffMovedEnemy $moved */
            $moved = $diff->movedPullEnemies->first();
            $this->assertEquals(2, $moved->pull->index);
            $this->assertFalse($moved->hasChangedFloor());
            $this->assertEqualsWithDelta(30.0, $moved->distance, 0.5);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function diff_givenEnemyMovedWithinTheThreshold_reportsNoMovedPullEnemy(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            config(['keystoneguru.mapping_version_upgrade_diff.enemy_moved_min_distance_yd' => 10]);

            $route = $this->createRoute($dungeon, $oldMappingVersion);

            $oldEnemy = $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 1, 'mdt_id' => 1, 'lat' => -100.0]);
            $this->createEnemy($newMappingVersion, $floor, [
                'npc_id' => 1,
                'mdt_id' => 1,
                'lat'    => -100.0 + (2 * $this->latPerYard($floor)),
            ]);

            $killZone = $this->createKillZone($route, 1);
            $this->pullEnemy($killZone, $oldEnemy);

            // Act
            $diff = $this->service()->diff($route, $newMappingVersion);

            // Assert
            $this->assertCount(0, $diff->movedPullEnemies);
            $this->assertFalse($diff->hasRouteImpact());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function diff_givenEnemyOnAnotherFloor_reportsMovedPullEnemyWithoutADistance(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            $otherFloor = $dungeon->floors()
                ->where('facade', false)
                ->where('id', '!=', $floor->id)
                ->first();

            if ($otherFloor === null) {
                $this->markTestSkipped('The chosen dungeon has only one non-facade floor');
            }

            $route = $this->createRoute($dungeon, $oldMappingVersion);

            $oldEnemy = $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 1, 'mdt_id' => 1]);
            $this->createEnemy($newMappingVersion, $otherFloor, ['npc_id' => 1, 'mdt_id' => 1]);

            $killZone = $this->createKillZone($route, 1);
            $this->pullEnemy($killZone, $oldEnemy);

            // Act
            $diff = $this->service()->diff($route, $newMappingVersion);

            // Assert
            $this->assertCount(1, $diff->movedPullEnemies);
            $this->assertTrue($diff->movedPullEnemies->first()->hasChangedFloor());
            $this->assertNull($diff->movedPullEnemies->first()->distance);
        } finally {
            $this->tearDownCleanup();
        }
    }

    // ------------------------------------------------------------------ required enemies

    #[Test]
    public function diff_givenNewlyRequiredEnemyTheRouteDoesNotKill_reportsItAsNewlyRequired(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            $route = $this->createRoute($dungeon, $oldMappingVersion);

            $requiredEnemy = $this->createEnemy($newMappingVersion, $floor, [
                'npc_id'   => 3,
                'mdt_id'   => 3,
                'required' => true,
            ]);

            // Act
            $diff = $this->service()->diff($route, $newMappingVersion);

            // Assert
            $this->assertCount(1, $diff->unkilledRequiredEnemies);
            $this->assertEquals($requiredEnemy->id, $diff->unkilledRequiredEnemies->first()?->enemyId);
            $this->assertTrue($diff->unkilledRequiredEnemies->first()?->newlyRequired);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function diff_givenRequiredEnemyTheRouteKills_reportsNoUnkilledRequiredEnemy(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            $route = $this->createRoute($dungeon, $oldMappingVersion);

            $oldEnemy = $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 3, 'mdt_id' => 3, 'required' => true]);
            $this->createEnemy($newMappingVersion, $floor, ['npc_id' => 3, 'mdt_id' => 3, 'required' => true]);

            $killZone = $this->createKillZone($route, 1);
            $this->pullEnemy($killZone, $oldEnemy);

            // Act
            $diff = $this->service()->diff($route, $newMappingVersion);

            // Assert
            $this->assertCount(0, $diff->unkilledRequiredEnemies);
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function diff_givenRequiredEnemyHiddenByTheRoutesTeemingSetting_reportsNoUnkilledRequiredEnemy(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            $route = $this->createRoute($dungeon, $oldMappingVersion, teeming: false);

            $this->createEnemy($newMappingVersion, $floor, [
                'npc_id'   => 3,
                'mdt_id'   => 3,
                'required' => true,
                'teeming'  => Enemy::TEEMING_VISIBLE,
            ]);

            // Act
            $diff = $this->service()->diff($route, $newMappingVersion);

            // Assert
            $this->assertCount(0, $diff->unkilledRequiredEnemies);
        } finally {
            $this->tearDownCleanup();
        }
    }

    // ------------------------------------------------------------------ enemy forces

    #[Test]
    public function diff_givenChangedNpcEnemyForces_reportsTheChangeForThePulledNpc(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            $route = $this->createRoute($dungeon, $oldMappingVersion);

            $enemy = $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 61, 'mdt_id' => 1]);
            $this->createEnemy($newMappingVersion, $floor, ['npc_id' => 61, 'mdt_id' => 1]);

            $killZone = $this->createKillZone($route, 1);
            $this->pullEnemy($killZone, $enemy);

            array_unshift($this->cleanup, NpcEnemyForces::create([
                'mapping_version_id' => $oldMappingVersion->id,
                'npc_id'             => 61,
                'enemy_forces'       => 4,
            ]));
            array_unshift($this->cleanup, NpcEnemyForces::create([
                'mapping_version_id' => $newMappingVersion->id,
                'npc_id'             => 61,
                'enemy_forces'       => 7,
            ]));

            // Act
            $diff = $this->service()->diff($route, $newMappingVersion);

            // Assert
            $this->assertCount(1, $diff->npcEnemyForcesChanges);
            $this->assertEquals(61, $diff->npcEnemyForcesChanges->first()?->npcId);
            $this->assertEquals(4, $diff->npcEnemyForcesChanges->first()?->oldEnemyForces);
            $this->assertEquals(7, $diff->npcEnemyForcesChanges->first()?->newEnemyForces);
            $this->assertTrue($diff->hasEnemyForcesImpact());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function diff_givenNoUpgradedRoute_leavesTheNewEnemyForcesTotalUnknown(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion] = $this->createMappingVersionPair();

        try {
            $route = $this->createRoute($dungeon, $oldMappingVersion);

            // Act
            $diff = $this->service()->diff($route, $newMappingVersion);

            // Assert
            $this->assertNull($diff->newEnemyForces);
            $this->assertEquals($route->enemy_forces, $diff->oldEnemyForces);
        } finally {
            $this->tearDownCleanup();
        }
    }

    // ------------------------------------------------------------------ dungeon wide, empty diff

    #[Test]
    public function diff_givenAddedAndRemovedEnemies_countsThemDungeonWide(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            $route = $this->createRoute($dungeon, $oldMappingVersion);

            $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 1, 'mdt_id' => 1]);
            $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 2, 'mdt_id' => 2]);
            $this->createEnemy($newMappingVersion, $floor, ['npc_id' => 1, 'mdt_id' => 1]);
            $this->createEnemy($newMappingVersion, $floor, ['npc_id' => 3, 'mdt_id' => 3]);
            $this->createEnemy($newMappingVersion, $floor, ['npc_id' => 4, 'mdt_id' => 4]);

            // Act
            $diff = $this->service()->diff($route, $newMappingVersion);

            // Assert
            $this->assertEquals(2, $diff->addedEnemyCount);
            $this->assertEquals(1, $diff->removedEnemyCount);
            $this->assertTrue($diff->hasMappingChanges());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function diff_givenIdenticalMappingVersions_reportsNoImpactAtAll(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            $route = $this->createRoute($dungeon, $oldMappingVersion);

            $oldEnemy = $this->createEnemy($oldMappingVersion, $floor, ['npc_id' => 1, 'mdt_id' => 1]);
            $this->createEnemy($newMappingVersion, $floor, ['npc_id' => 1, 'mdt_id' => 1]);

            $killZone = $this->createKillZone($route, 1);
            $this->pullEnemy($killZone, $oldEnemy);

            // Act
            $diff = $this->service()->diff($route, $newMappingVersion);

            // Assert
            $this->assertFalse($diff->hasRouteImpact());
            $this->assertFalse($diff->hasMappingChanges());
        } finally {
            $this->tearDownCleanup();
        }
    }

    // ------------------------------------------------------------------ diffForUpgradeDraft

    #[Test]
    public function diffForUpgradeDraft_givenADraft_readsTheNewEnemyForcesTotalOffTheDraft(): void
    {
        // Arrange
        [$dungeon, $oldMappingVersion, $newMappingVersion, $floor] = $this->createMappingVersionPair();

        try {
            $route = $this->createRoute($dungeon, $oldMappingVersion);
            $route->update(['enemy_forces' => 120]);

            $draft = $this->createRoute($dungeon, $newMappingVersion);
            $draft->update([
                'upgrade_of_dungeon_route_id' => $route->id,
                'enemy_forces'                => 90,
            ]);

            // Act
            $diff = $this->service()->diffForUpgradeDraft($draft->fresh());

            // Assert
            $this->assertNotNull($diff);
            $this->assertEquals(120, $diff->oldEnemyForces);
            $this->assertEquals(90, $diff->newEnemyForces);
            $this->assertTrue($diff->hasEnemyForcesImpact());
        } finally {
            $this->tearDownCleanup();
        }
    }

    #[Test]
    public function diffForUpgradeDraft_givenADraftWhoseOriginalIsGone_returnsNull(): void
    {
        // Arrange
        [$dungeon, , $newMappingVersion] = $this->createMappingVersionPair();

        try {
            $draft = $this->createRoute($dungeon, $newMappingVersion);
            $draft->update(['upgrade_of_dungeon_route_id' => 0]);

            // Act
            $diff = $this->service()->diffForUpgradeDraft($draft->fresh());

            // Assert
            $this->assertNull($diff);
        } finally {
            $this->tearDownCleanup();
        }
    }
}
