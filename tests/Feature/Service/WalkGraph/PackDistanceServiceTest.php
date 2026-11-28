<?php

namespace Tests\Feature\Service\WalkGraph;

use App\Logic\Structs\LatLng;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\WalkGraph\EnemyPackFloorSwitchWalkDistance;
use App\Models\WalkGraph\EnemyPackWalkDistance;
use App\Models\WalkGraph\FloorSwitchMarkerWalkDistance;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\WalkGraph\PackDistanceServiceInterface;
use Database\Factories\FloorFactory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('WalkGraph')]
#[Group('PackDistanceService')]
final class PackDistanceServiceTest extends PublicTestCase
{
    use CreatesDungeon;
    use ProvidesDungeon;

    private MappingVersion $mappingVersion;

    /** @var array<int, Floor> */
    private array $floors = [];

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

    #[Test]
    public function getNeighbours_givenTwoStaircases_picksTheShorterWalkAndListsItsMarker(): void
    {
        // Arrange - the first staircase is next to the pack in a straight line, but the walk to it is the longer one
        $this->createFloors(2);
        $pack                           = $this->createPack(0, -100.0, 100.0);
        $otherPack                      = $this->createPack(1, -100.0, 120.0);
        [$nearStairs, $nearStairsBelow] = $this->createLinkedMarkers(0, 1, -100.0, 101.0);
        [$farStairs, $farStairsBelow]   = $this->createLinkedMarkers(0, 1, -200.0, 300.0);
        $this->createPackMarkerWalk($pack, $nearStairs, 200.0);
        $this->createPackMarkerWalk($pack, $farStairs, 50.0);
        $this->createPackMarkerWalk($otherPack, $nearStairsBelow, 10.0);
        $this->createPackMarkerWalk($otherPack, $farStairsBelow, 20.0);
        config(['keystoneguru.walk_graph.floor_switch_cost' => 0.0]);

        // Act
        $neighbours = $this->getNeighbours();

        // Assert
        $this->assertSame([[
            'enemy_pack_id'           => $otherPack->id,
            'distance'                => 70.0,
            'unverified'              => false,
            'floor_switch_marker_ids' => [$farStairs->id],
        ]], $neighbours[$pack->id]);
        $this->assertSame([[
            'enemy_pack_id'           => $pack->id,
            'distance'                => 70.0,
            'unverified'              => false,
            'floor_switch_marker_ids' => [$farStairsBelow->id],
        ]], $neighbours[$otherPack->id]);
    }

    #[Test]
    public function getNeighbours_givenFloorSwitchCost_addsItToTheWalk(): void
    {
        // Arrange
        $this->createFloors(2);
        $pack                   = $this->createPack(0, -100.0, 100.0);
        $otherPack              = $this->createPack(1, -100.0, 120.0);
        [$stairs, $stairsBelow] = $this->createLinkedMarkers(0, 1, -100.0, 110.0);
        $this->createPackMarkerWalk($pack, $stairs, 50.0);
        $this->createPackMarkerWalk($otherPack, $stairsBelow, 20.0);
        config(['keystoneguru.walk_graph.floor_switch_cost' => 100.0]);

        // Act
        $neighbours = $this->getNeighbours();

        // Assert
        $this->assertSame(170.0, $neighbours[$pack->id][0]['distance']);
    }

    #[Test]
    public function getNeighbours_givenOneWayMarker_listsNoNeighbourAgainstItsDirection(): void
    {
        // Arrange - the drop down has no way back up
        $this->createFloors(2);
        $pack               = $this->createPack(0, -100.0, 100.0);
        $otherPack          = $this->createPack(1, -100.0, 120.0);
        [$drop, $dropBelow] = $this->createLinkedMarkers(0, 1, -100.0, 110.0);
        $dropBelow->update(['linked_dungeon_floor_switch_marker_id' => null]);
        $this->createPackMarkerWalk($pack, $drop, 50.0);
        $this->createPackMarkerWalk($otherPack, $dropBelow, 20.0);

        // Act
        $neighbours = $this->getNeighbours();

        // Assert
        $this->assertSame([$otherPack->id], array_column($neighbours[$pack->id], 'enemy_pack_id'), 'The drop down is taken in its own direction');
        $this->assertSame([], $neighbours[$otherPack->id]);
    }

    #[Test]
    public function getNeighbours_givenPackTwoFloorsAway_walksAcrossTheFloorInBetween(): void
    {
        // Arrange
        $this->createFloors(3);
        $pack                             = $this->createPack(0, -100.0, 100.0);
        $otherPack                        = $this->createPack(2, -100.0, 120.0);
        [$stairs, $stairsBelow]           = $this->createLinkedMarkers(0, 1, -100.0, 110.0);
        [$lowerStairs, $lowerStairsBelow] = $this->createLinkedMarkers(1, 2, -150.0, 200.0);
        $this->createPackMarkerWalk($pack, $stairs, 50.0);
        $this->createMarkerWalk($stairsBelow, $lowerStairs, 30.0);
        $this->createPackMarkerWalk($otherPack, $lowerStairsBelow, 5.0);
        config(['keystoneguru.walk_graph.floor_switch_cost' => 0.0]);

        // Act
        $neighbours = $this->getNeighbours();

        // Assert
        $this->assertSame([[
            'enemy_pack_id'           => $otherPack->id,
            'distance'                => 85.0,
            'unverified'              => false,
            'floor_switch_marker_ids' => [$stairs->id, $lowerStairs->id],
        ]], $neighbours[$pack->id]);
    }

    #[Test]
    public function getNeighbours_givenNoStoredCrossFloorWalks_fallsBackToStraightLinesAndIsUnverified(): void
    {
        // Arrange
        $this->createFloors(2);
        $pack                   = $this->createPack(0, -100.0, 100.0);
        $otherPack              = $this->createPack(1, -120.0, 130.0);
        [$stairs, $stairsBelow] = $this->createLinkedMarkers(0, 1, -110.0, 110.0);
        $coordinatesService     = app(CoordinatesServiceInterface::class);
        $ingameXY               = fn(float $lat, float $lng, int $floorIndex) => $coordinatesService->calculateIngameLocationForMapLocation(
            new LatLng($lat, $lng, $this->floors[$floorIndex]),
        );
        $expectedDistance = $coordinatesService->distanceIngameXY($ingameXY(-100.0, 100.0, 0), $ingameXY(-110.0, 110.0, 0)) +
            $coordinatesService->distanceIngameXY($ingameXY(-110.0, 110.0, 1), $ingameXY(-120.0, 130.0, 1));

        // Act
        $neighbours = $this->getNeighbours();

        // Assert
        $this->assertCount(1, $neighbours[$pack->id]);
        $this->assertSame($otherPack->id, $neighbours[$pack->id][0]['enemy_pack_id']);
        $this->assertEqualsWithDelta($expectedDistance, $neighbours[$pack->id][0]['distance'], 0.1);
        $this->assertTrue($neighbours[$pack->id][0]['unverified']);
        $this->assertSame([$stairs->id], $neighbours[$pack->id][0]['floor_switch_marker_ids']);
    }

    /**
     * @return array<int, list<array{enemy_pack_id: int, distance: float, unverified: bool, floor_switch_marker_ids: list<int>}>>
     */
    private function getNeighbours(): array
    {
        $enemyPacks = $this->mappingVersion->enemyPacks()->with(['enemies', 'floor'])->orderBy('id')->get();

        return app(PackDistanceServiceInterface::class)->getNeighbours($this->mappingVersion, $enemyPacks, 10);
    }

    private function createFloors(int $count): void
    {
        $dungeon = $this->createDungeon();
        $this->beforeApplicationDestroyed(fn() => $this->deleteWalkDistances());
        /** @var MappingVersion $mappingVersion */
        $mappingVersion       = $dungeon->mappingVersions()->firstOrFail();
        $this->mappingVersion = $mappingVersion;
        $this->floors[]       = $dungeon->floors()->firstOrFail();

        for ($i = 1; $i < $count; $i++) {
            $this->floors[] = FloorFactory::new()->create([
                'dungeon_id'   => $dungeon->id,
                'index'        => $i + 1,
                'name'         => sprintf('Test Floor %d', $i + 1),
                'default'      => false,
                'ingame_min_x' => 0,
                'ingame_max_x' => 1000,
                'ingame_min_y' => 0,
                'ingame_max_y' => 1000,
            ]);
        }
        $this->floors[0]->update(['ingame_min_x' => 0, 'ingame_max_x' => 1000, 'ingame_min_y' => 0, 'ingame_max_y' => 1000]);
    }

    private function deleteWalkDistances(): void
    {
        EnemyPackFloorSwitchWalkDistance::query()->where('mapping_version_id', $this->mappingVersion->id)->delete();
        FloorSwitchMarkerWalkDistance::query()->where('mapping_version_id', $this->mappingVersion->id)->delete();
    }

    private function createPack(int $floorIndex, float $lat, float $lng): EnemyPack
    {
        $enemyPack = EnemyPack::create([
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $this->floors[$floorIndex]->id,
            'group'              => 1,
            'label'              => 'Pack distance test pack',
            'teeming'            => null,
            'faction'            => 'any',
            'vertices_json'      => '[]',
        ]);

        Enemy::create([
            'mapping_version_id' => $this->mappingVersion->id,
            'enemy_pack_id'      => $enemyPack->id,
            'floor_id'           => $this->floors[$floorIndex]->id,
            'npc_id'             => 0,
            'teeming'            => null,
            'faction'            => Faction::FACTION_ANY,
            'required'           => true,
            'lat'                => $lat,
            'lng'                => $lng,
        ]);

        return $enemyPack;
    }

    /**
     * @return array{DungeonFloorSwitchMarker, DungeonFloorSwitchMarker} the marker, and the one it leads to
     */
    private function createLinkedMarkers(int $floorIndex, int $targetFloorIndex, float $lat, float $lng): array
    {
        $create = fn(int $from, int $to) => DungeonFloorSwitchMarker::create([
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $this->floors[$from]->id,
            'source_floor_id'    => $this->floors[$from]->id,
            'target_floor_id'    => $this->floors[$to]->id,
            'direction'          => null,
            'hidden_in_facade'   => false,
            'lat'                => $lat,
            'lng'                => $lng,
        ]);

        $marker       = $create($floorIndex, $targetFloorIndex);
        $linkedMarker = $create($targetFloorIndex, $floorIndex);
        $marker->update(['linked_dungeon_floor_switch_marker_id' => $linkedMarker->id]);
        $linkedMarker->update(['linked_dungeon_floor_switch_marker_id' => $marker->id]);

        return [$marker, $linkedMarker];
    }

    private function createPackMarkerWalk(EnemyPack $enemyPack, DungeonFloorSwitchMarker $marker, float $walkDistance): void
    {
        EnemyPackFloorSwitchWalkDistance::create([
            'mapping_version_id'             => $this->mappingVersion->id,
            'floor_id'                       => $enemyPack->floor_id,
            'enemy_pack_id'                  => $enemyPack->id,
            'dungeon_floor_switch_marker_id' => $marker->id,
            'straight_distance'              => 1.0,
            'walk_distance'                  => $walkDistance,
        ]);
    }

    private function createMarkerWalk(DungeonFloorSwitchMarker $marker, DungeonFloorSwitchMarker $otherMarker, float $walkDistance): void
    {
        FloorSwitchMarkerWalkDistance::create([
            'mapping_version_id'                   => $this->mappingVersion->id,
            'floor_id'                             => $marker->floor_id,
            'dungeon_floor_switch_marker_id'       => min($marker->id, $otherMarker->id),
            'other_dungeon_floor_switch_marker_id' => max($marker->id, $otherMarker->id),
            'straight_distance'                    => 1.0,
            'walk_distance'                        => $walkDistance,
        ]);
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
