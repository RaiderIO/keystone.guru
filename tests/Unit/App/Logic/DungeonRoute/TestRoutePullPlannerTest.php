<?php

namespace Tests\Unit\App\Logic\DungeonRoute;

use App\Logic\DungeonRoute\TestRoutePullPlanner;
use App\Models\Enemy;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('DungeonRoute')]
#[Group('TestRoutePullPlanner')]
final class TestRoutePullPlannerTest extends TestCase
{
    /** A pull closes once it holds at least 7 enemies, so a pack of 3 can push it to 9. */
    private const int MAX_PULL_OVERSHOOT = 9;

    private int $nextEnemyId = 1;

    /** @var Collection<string, int> */
    private Collection $enemyForcesByKey;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->nextEnemyId      = 1;
        $this->enemyForcesByKey = collect();
    }

    #[Test]
    public function plan_givenSingleFloor_pullsThatFloorUntilTheTargetAndKillsTheBoss(): void
    {
        // Arrange
        $boss       = $this->makeEnemy(floorId: 1, forces: 0, boss: true);
        $candidates = $this->makePacks(floorId: 1, packCount: 20)->push(collect([$boss]));
        $planner    = new TestRoutePullPlanner($this->enemyForcesByKey);

        // Act
        $pulls = $planner->plan($candidates, collect([1 => 1]), 20);

        // Assert
        $this->assertGreaterThanOrEqual(20, $planner->getForces());
        $this->assertLessThan(20 + self::MAX_PULL_OVERSHOOT, $planner->getForces(), 'The planner must stop once the target is met');
        $this->assertContains($boss->id, $this->pulledEnemyIds($pulls), 'Every boss must be pulled');
        $this->assertPullsAreValid($pulls, collect([1 => 1]));
    }

    #[Test]
    public function plan_givenSeveralFloors_visitsFloorsInIndexOrderAndMeetsEachFloorsShare(): void
    {
        // Arrange
        $floorIndexByFloorId = collect([30 => 1, 20 => 2, 10 => 3]);
        $candidates          = collect();
        $bossIds             = [];
        foreach ($floorIndexByFloorId->keys() as $floorId) {
            $boss       = $this->makeEnemy(floorId: $floorId, forces: 0, boss: true);
            $bossIds[]  = $boss->id;
            $candidates = $candidates->concat($this->makePacks(floorId: $floorId, packCount: 20))->push(collect([$boss]));
        }
        $planner = new TestRoutePullPlanner($this->enemyForcesByKey);

        // Act
        $pulls = $planner->plan($candidates->shuffle(), $floorIndexByFloorId, 90);

        // Assert
        $this->assertGreaterThanOrEqual(90, $planner->getForces());
        $this->assertLessThan(90 + self::MAX_PULL_OVERSHOOT, $planner->getForces(), 'Overshoot must not pile up across floors');
        $forcesSoFar = 0;
        foreach ($floorIndexByFloorId as $floorId => $floorIndex) {
            $floorForces = $this->forcesOnFloor($pulls, $floorId);
            $forcesSoFar += $floorForces;
            $this->assertGreaterThanOrEqual(30 * $floorIndex, $forcesSoFar, sprintf('Floor %d must catch the route up with its share', $floorId));
            $this->assertLessThan(30 + self::MAX_PULL_OVERSHOOT, $floorForces, sprintf('Floor %d must be left once its share is met', $floorId));
        }
        $this->assertEmpty(array_diff($bossIds, $this->pulledEnemyIds($pulls)), 'Every boss must be pulled');
        $this->assertPullsAreValid($pulls, $floorIndexByFloorId);
    }

    #[Test]
    public function plan_givenFloorWithTooFewForces_carriesTheShortfallToTheNextFloor(): void
    {
        // Arrange
        $floorIndexByFloorId = collect([1 => 1, 2 => 2]);
        $smallFloor          = $this->makePacks(floorId: 1, packCount: 2);
        $candidates          = $smallFloor->concat($this->makePacks(floorId: 2, packCount: 40));
        $planner             = new TestRoutePullPlanner($this->enemyForcesByKey);

        // Act
        $pulls = $planner->plan($candidates, $floorIndexByFloorId, 60);

        // Assert
        $this->assertEmpty(
            array_diff($smallFloor->flatten(1)->pluck('id')->all(), $this->pulledEnemyIds($pulls)),
            'A floor short of its share must be pulled clean',
        );
        $this->assertSame(6, $this->forcesOnFloor($pulls, 1));
        $this->assertGreaterThanOrEqual(60 - 6, $this->forcesOnFloor($pulls, 2), 'The next floor must make up the shortfall');
        $this->assertGreaterThanOrEqual(60, $planner->getForces());
        $this->assertPullsAreValid($pulls, $floorIndexByFloorId);
    }

    #[Test]
    public function plan_givenLastFloorTooSmallForItsShare_movesTheRestToEarlierFloors(): void
    {
        // Arrange
        $floorIndexByFloorId = collect([1 => 1, 2 => 2]);
        $candidates          = $this->makePacks(floorId: 1, packCount: 40)->concat($this->makePacks(floorId: 2, packCount: 1));
        $planner             = new TestRoutePullPlanner($this->enemyForcesByKey);

        // Act
        $pulls = $planner->plan($candidates, $floorIndexByFloorId, 100);

        // Assert
        $this->assertGreaterThanOrEqual(100, $planner->getForces());
        $this->assertLessThan(100 + self::MAX_PULL_OVERSHOOT, $planner->getForces());
        $this->assertGreaterThanOrEqual(97, $this->forcesOnFloor($pulls, 1), 'The earlier floor must take over the share the last floor cannot deliver');
        $this->assertLessThanOrEqual(3, $this->forcesOnFloor($pulls, 2));
        $this->assertPullsAreValid($pulls, $floorIndexByFloorId);
    }

    #[Test]
    public function plan_givenCandidatesShortOfTheTarget_pullsEverythingOnce(): void
    {
        // Arrange
        $floorIndexByFloorId = collect([1 => 1, 2 => 2]);
        $boss                = $this->makeEnemy(floorId: 2, forces: 0, boss: true);
        $candidates          = $this->makePacks(floorId: 1, packCount: 2)
            ->concat($this->makePacks(floorId: 2, packCount: 2))
            ->push(collect([$boss]));
        $planner = new TestRoutePullPlanner($this->enemyForcesByKey);

        // Act
        $pulls = $planner->plan($candidates, $floorIndexByFloorId, 100);

        // Assert
        $this->assertSame(12, $planner->getForces());
        $this->assertEqualsCanonicalizing($candidates->flatten(1)->pluck('id')->all(), $this->pulledEnemyIds($pulls));
        $this->assertPullsAreValid($pulls, $floorIndexByFloorId);
    }

    #[Test]
    public function plan_givenNoCandidates_returnsNoPulls(): void
    {
        // Arrange
        $planner = new TestRoutePullPlanner($this->enemyForcesByKey);

        // Act
        $pulls = $planner->plan(collect(), collect([1 => 1]), 100);

        // Assert
        $this->assertTrue($pulls->isEmpty());
        $this->assertSame(0, $planner->getForces());
    }

    #[Test]
    public function plan_givenDuplicatedKeyWorthForces_countsTheKeyOnceAtItsAverageForces(): void
    {
        // Arrange
        $duplicates = collect([
            $this->makeKeyedEnemy(id: 1, floorId: 1, npcId: 500, mdtId: 7, forcesOverride: 4),
            $this->makeKeyedEnemy(id: 2, floorId: 1, npcId: 500, mdtId: 7, forcesOverride: 6),
            $this->makeKeyedEnemy(id: 3, floorId: 1, npcId: 500, mdtId: 7, forcesOverride: 8),
        ]);
        $enemyForcesByKey = TestRoutePullPlanner::getEnemyForcesByKey($duplicates, collect());
        $planner          = new TestRoutePullPlanner($enemyForcesByKey);

        // Act
        $planner->plan(collect([$duplicates]), collect([1 => 1]), 100);

        // Assert
        $this->assertSame(6, $planner->getForces(), 'A key shared by three enemies must add their average forces once, not their sum');
    }

    #[Test]
    public function getEnemyForcesByKey_givenDuplicatedKey_returnsTheRoundedAverageForcesOfThatKey(): void
    {
        // Arrange
        $enemies = collect([
            $this->makeKeyedEnemy(id: 1, floorId: 1, npcId: 500, mdtId: 7),
            $this->makeKeyedEnemy(id: 2, floorId: 1, npcId: 500, mdtId: 7),
            $this->makeKeyedEnemy(id: 3, floorId: 1, npcId: 500, mdtId: 7, forcesOverride: 6),
            $this->makeKeyedEnemy(id: 4, floorId: 1, npcId: 600, mdtId: 7, mdtNpcId: 500),
        ]);

        // Act
        $enemyForcesByKey = TestRoutePullPlanner::getEnemyForcesByKey($enemies, collect([500 => 3]));

        // Assert
        $this->assertSame(['500-7' => 4], $enemyForcesByKey->all(), 'The MDT npc id keys the enemy and picks its forces; (3 + 3 + 6 + 3) / 4 rounds to 4');
    }

    #[Test]
    public function getEnemyForcesByKey_givenUniqueKeys_returnsEachEnemysForces(): void
    {
        // Arrange
        $enemies = collect([
            $this->makeKeyedEnemy(id: 1, floorId: 1, npcId: 500, mdtId: 1),
            $this->makeKeyedEnemy(id: 2, floorId: 1, npcId: 500, mdtId: 2, forcesOverride: 9),
            $this->makeKeyedEnemy(id: 3, floorId: 1, npcId: 700, mdtId: 3),
        ]);

        // Act
        $enemyForcesByKey = TestRoutePullPlanner::getEnemyForcesByKey($enemies, collect([500 => 3]));

        // Assert
        $this->assertSame(['500-1' => 3, '500-2' => 9, '700-3' => 0], $enemyForcesByKey->all());
    }

    #[Test]
    public function getEnemyForcesByKey_givenEnemyWithoutMdtId_returnsNoKeyForIt(): void
    {
        // Arrange
        $enemies = collect([$this->makeKeyedEnemy(id: 1, floorId: 1, npcId: 500, mdtId: null)]);

        // Act
        $enemyForcesByKey = TestRoutePullPlanner::getEnemyForcesByKey($enemies, collect([500 => 3]));

        // Assert
        $this->assertTrue($enemyForcesByKey->isEmpty());
    }

    /**
     * Every pull stays on one floor, the floors are visited in index order, and no enemy is pulled twice.
     *
     * @param Collection<int, Collection<int, Enemy>> $pulls
     * @param Collection<int, int>                    $floorIndexByFloorId
     */
    private function assertPullsAreValid(Collection $pulls, Collection $floorIndexByFloorId): void
    {
        $previousFloorIndex = PHP_INT_MIN;
        foreach ($pulls as $pull) {
            $this->assertCount(1, $pull->pluck('floor_id')->unique(), 'A pull must stay on one floor');

            $floorIndex = $floorIndexByFloorId->get($pull->first()->floor_id);
            $this->assertGreaterThanOrEqual($previousFloorIndex, $floorIndex, 'Floors must be visited in index order');
            $previousFloorIndex = $floorIndex;
        }

        $pulledEnemyIds = $this->pulledEnemyIds($pulls);
        $this->assertCount(count(array_unique($pulledEnemyIds)), $pulledEnemyIds, 'No enemy may be pulled twice');
    }

    /**
     * @param  Collection<int, Collection<int, Enemy>> $pulls
     * @return array<int, int>
     */
    private function pulledEnemyIds(Collection $pulls): array
    {
        return $pulls->flatten(1)->pluck('id')->all();
    }

    /**
     * @param Collection<int, Collection<int, Enemy>> $pulls
     */
    private function forcesOnFloor(Collection $pulls, int $floorId): int
    {
        return (int)$pulls->flatten(1)
            ->filter(static fn(Enemy $enemy) => $enemy->floor_id === $floorId)
            ->sum(fn(Enemy $enemy) => $this->enemyForcesByKey->get(TestRoutePullPlanner::getEnemyKey($enemy), 0));
    }

    /**
     * Packs of three enemies worth one enemy force each.
     *
     * @return Collection<int, Collection<int, Enemy>>
     */
    private function makePacks(int $floorId, int $packCount): Collection
    {
        $packs = collect();
        for ($i = 0; $i < $packCount; $i++) {
            $packs->push(collect([
                $this->makeEnemy($floorId, 1),
                $this->makeEnemy($floorId, 1),
                $this->makeEnemy($floorId, 1),
            ]));
        }

        return $packs;
    }

    private function makeKeyedEnemy(
        int  $id,
        int  $floorId,
        int  $npcId,
        ?int $mdtId,
        ?int $forcesOverride = null,
        ?int $mdtNpcId = null,
    ): Enemy {
        $enemy = new Enemy();
        $enemy->forceFill([
            'id'                    => $id,
            'floor_id'              => $floorId,
            'npc_id'                => $npcId,
            'mdt_npc_id'            => $mdtNpcId,
            'mdt_id'                => $mdtId,
            'enemy_forces_override' => $forcesOverride,
        ]);
        $enemy->setRelation('npc', null);

        return $enemy;
    }

    private function makeEnemy(int $floorId, int $forces, bool $boss = false): Enemy
    {
        $id = $this->nextEnemyId++;

        $npc = new Npc();
        $npc->forceFill([
            'id'                => 100000 + $id,
            'classification_id' => NpcClassification::ALL[$boss ? NpcClassification::NPC_CLASSIFICATION_BOSS : NpcClassification::NPC_CLASSIFICATION_NORMAL],
        ]);

        $enemy = new Enemy();
        $enemy->forceFill([
            'id'       => $id,
            'floor_id' => $floorId,
            'npc_id'   => $npc->id,
            'mdt_id'   => $id,
        ]);
        $enemy->setRelation('npc', $npc);

        $this->enemyForcesByKey->put(TestRoutePullPlanner::getEnemyKey($enemy), $forces);

        return $enemy;
    }
}
