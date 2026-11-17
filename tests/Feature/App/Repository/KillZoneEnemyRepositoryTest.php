<?php

namespace Tests\Feature\App\Repository;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Repositories\Database\KillZone\KillZoneEnemyRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('KillZoneEnemyRepository')]
final class KillZoneEnemyRepositoryTest extends PublicTestCase
{
    private const int NPC_ID_A = 999_000_001;
    private const int NPC_ID_B = 999_000_002;
    private const int NPC_ID_C = 999_000_003;

    private KillZoneEnemyRepository $repository;

    /** @var array<int> */
    private array $createdDungeonRouteIds = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new KillZoneEnemyRepository();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            KillZoneEnemy::query()->whereIn('kill_zone_id', KillZone::query()->whereIn('dungeon_route_id', $this->createdDungeonRouteIds)->select('id'))->delete();
            KillZone::query()->whereIn('dungeon_route_id', $this->createdDungeonRouteIds)->delete();
            DungeonRoute::query()->whereIn('id', $this->createdDungeonRouteIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function getNpcIdsInPullsPerDungeonRoute_givenRoutesWithPulls_returnsTheRequestedNpcIdsPerRoute(): void
    {
        // Arrange - npc A sits in two pulls of the first route, which must still count once
        $first  = $this->createRouteWithPulls([[self::NPC_ID_A, self::NPC_ID_C], [self::NPC_ID_A]]);
        $second = $this->createRouteWithPulls([[self::NPC_ID_B]]);

        // Act
        $result = $this->repository->getNpcIdsInPullsPerDungeonRoute(
            [$first->id, $second->id],
            [self::NPC_ID_A, self::NPC_ID_B],
        );

        // Assert
        $this->assertSame([self::NPC_ID_A], $result->get($first->id));
        $this->assertSame([self::NPC_ID_B], $result->get($second->id));
    }

    #[Test]
    public function getNpcIdsInPullsPerDungeonRoute_givenRouteWithoutTheRequestedNpcs_leavesItOut(): void
    {
        // Arrange
        $dungeonRoute = $this->createRouteWithPulls([[self::NPC_ID_C]]);

        // Act
        $result = $this->repository->getNpcIdsInPullsPerDungeonRoute([$dungeonRoute->id], [self::NPC_ID_A]);

        // Assert
        $this->assertFalse($result->has($dungeonRoute->id));
    }

    #[Test]
    public function getNpcIdsInPullsPerDungeonRoute_givenRouteThatWasNotAskedFor_leavesItOut(): void
    {
        // Arrange
        $asked    = $this->createRouteWithPulls([[self::NPC_ID_A]]);
        $notAsked = $this->createRouteWithPulls([[self::NPC_ID_A]]);

        // Act
        $result = $this->repository->getNpcIdsInPullsPerDungeonRoute([$asked->id], [self::NPC_ID_A]);

        // Assert
        $this->assertTrue($result->has($asked->id));
        $this->assertFalse($result->has($notAsked->id));
    }

    /**
     * @param array<int, array<int, int>> $pulls the npc_ids of each pull
     */
    private function createRouteWithPulls(array $pulls): DungeonRoute
    {
        $dungeonRoute                   = DungeonRoute::factory()->create();
        $this->createdDungeonRouteIds[] = $dungeonRoute->id;

        foreach ($pulls as $index => $npcIds) {
            $killZone = KillZone::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'color'            => '#ff0000',
                'index'            => $index + 1,
            ]);

            foreach ($npcIds as $npcId) {
                KillZoneEnemy::create([
                    'kill_zone_id' => $killZone->id,
                    'npc_id'       => $npcId,
                    'mdt_id'       => 1,
                ]);
            }
        }

        return $dungeonRoute;
    }
}
