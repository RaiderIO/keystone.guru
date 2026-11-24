<?php

namespace Tests\Feature\Service\WalkGraph;

use App\Models\EnemyPack;
use App\Models\Mapping\MappingVersion;
use App\Models\WalkGraph\EnemyPackWalkDistance;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\WalkGraph\PackDistanceServiceInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('WalkGraph')]
#[Group('PackDistanceService')]
final class PackDistanceServiceTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function getNeighbours_givenWalkDistanceForOnePair_usesItAndFallsBackToUnverifiedStraightLines(): void
    {
        // Arrange - three packs on one floor; only the first pair has a measured walking distance
        [, $mappingVersion, $floorId] = $this->findDungeon(
            challengeMode: true,
            resolve: static fn($dungeon, MappingVersion $mappingVersion) => $mappingVersion->enemyPacks()
                ->whereHas('enemies')
                ->selectRaw('floor_id, count(*) as pack_count')
                ->groupBy('floor_id')
                ->havingRaw('count(*) >= 3')
                ->value('floor_id'),
        );
        $enemyPacks               = $this->getFloorPacks($mappingVersion, (int)$floorId);
        [$first, $second, $third] = $enemyPacks->values()->all();

        // Set aside whatever the seed holds for the two pairs under test, and put it back afterwards
        $pairQuery = static fn() => EnemyPackWalkDistance::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->where(static fn($query) => $query
                ->where(static fn($pair) => $pair->where('enemy_pack_id', min($first->id, $second->id))->where('other_enemy_pack_id', max($first->id, $second->id)))
                ->orWhere(static fn($pair) => $pair->where('enemy_pack_id', min($first->id, $third->id))->where('other_enemy_pack_id', max($first->id, $third->id))));
        $seededRows = $pairQuery()->get()->map(static fn(EnemyPackWalkDistance $row) => $row->getAttributes())->all();
        $pairQuery()->delete();

        $walkDistance = EnemyPackWalkDistance::factory()->create([
            'mapping_version_id'  => $mappingVersion->id,
            'floor_id'            => $floorId,
            'enemy_pack_id'       => min($first->id, $second->id),
            'other_enemy_pack_id' => max($first->id, $second->id),
            'straight_distance'   => 1.0,
            'walk_distance'       => 12345.6,
        ]);

        /** @var PackDistanceServiceInterface $service */
        $service   = app(PackDistanceServiceInterface::class);
        $centroids = $service->getPackCentroids($enemyPacks);

        try {
            // Act
            $neighbours = $service->getNeighbours($mappingVersion, $enemyPacks, $enemyPacks->count());

            // Assert
            $firstToSecond = collect($neighbours[$first->id])->firstWhere('enemy_pack_id', $second->id);
            $this->assertSame(12345.6, $firstToSecond['distance']);
            $this->assertFalse($firstToSecond['unverified']);

            $secondToFirst = collect($neighbours[$second->id])->firstWhere('enemy_pack_id', $first->id);
            $this->assertSame(12345.6, $secondToFirst['distance'], 'A pair is stored once and must read from both ends');

            $firstToThird = collect($neighbours[$first->id])->firstWhere('enemy_pack_id', $third->id);
            $this->assertTrue($firstToThird['unverified']);
            $this->assertEqualsWithDelta(
                app(CoordinatesServiceInterface::class)->distanceIngameXY($centroids[$first->id], $centroids[$third->id]),
                $firstToThird['distance'],
                0.1,
            );

            $distances = array_column($neighbours[$first->id], 'distance');
            $sorted    = $distances;
            sort($sorted);
            $this->assertSame($sorted, $distances, 'Neighbours are listed nearest first');
        } finally {
            $walkDistance->delete();
            if ($seededRows !== []) {
                EnemyPackWalkDistance::query()->insert($seededRows);
            }
        }
    }

    #[Test]
    public function getNeighbours_givenNeighbourCount_limitsEachPacksNeighbours(): void
    {
        // Arrange
        [, $mappingVersion, $floorId] = $this->findDungeon(
            challengeMode: true,
            resolve: static fn($dungeon, MappingVersion $mappingVersion) => $mappingVersion->enemyPacks()
                ->whereHas('enemies')
                ->selectRaw('floor_id, count(*) as pack_count')
                ->groupBy('floor_id')
                ->havingRaw('count(*) >= 3')
                ->value('floor_id'),
        );
        $enemyPacks = $this->getFloorPacks($mappingVersion, (int)$floorId);

        // Act
        $neighbours = app(PackDistanceServiceInterface::class)->getNeighbours($mappingVersion, $enemyPacks, 1);

        // Assert
        $this->assertCount($enemyPacks->count(), $neighbours);
        foreach ($neighbours as $packNeighbours) {
            $this->assertCount(1, $packNeighbours);
        }
    }

    /**
     * @return EloquentCollection<int, EnemyPack>
     */
    private function getFloorPacks(MappingVersion $mappingVersion, int $floorId): EloquentCollection
    {
        return $mappingVersion->enemyPacks()
            ->where('floor_id', $floorId)
            ->whereHas('enemies')
            ->with(['enemies', 'floor'])
            ->orderBy('id')
            ->get();
    }
}
