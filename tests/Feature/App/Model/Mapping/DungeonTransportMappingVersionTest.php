<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Logic\Structs\LatLng;
use App\Models\DungeonTransport;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Mapping\MappingServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
#[Group('DungeonTransport')]
final class DungeonTransportMappingVersionTest extends PublicTestCase
{
    use ProvidesDungeon;

    /** Sentinel lat that doesn't occur in the seeded mapping, so the clones can be found unambiguously */
    private const float SENTINEL_LAT = 111.11;

    #[Test]
    public function create_givenMappingVersionWithLinkedTransports_clonesThemLinkedToEachOthersClones(): void
    {
        // Arrange
        $existingMappingVersion = $this->getMappingVersionThatWillBeCloned();
        [$transportA, $transportB, $oneWay] = $this->createLinkedTransports($existingMappingVersion);
        $newMappingVersion = null;

        try {
            // Act
            $newMappingVersion = $this->createNextMappingVersion($existingMappingVersion);

            // Assert
            $clones = $this->findSentinelTransports($newMappingVersion);
            $this->assertCount(3, $clones);
            $cloneA      = $clones->firstWhere('lng', 10.0);
            $cloneB      = $clones->firstWhere('lng', 20.0);
            $cloneOneWay = $clones->firstWhere('lng', 30.0);

            $this->assertNotSame($transportA->id, $cloneA->id, 'The clone must be a new row.');
            $this->assertSame($cloneB->id, $cloneA->linked_dungeon_transport_id);
            $this->assertSame($cloneA->id, $cloneB->linked_dungeon_transport_id);
            $this->assertSame($cloneA->id, $cloneOneWay->linked_dungeon_transport_id);
            $this->assertSame('boat-a-b', $cloneA->link_key);
            $this->assertSame($transportA->target_dungeon_id, $cloneA->target_dungeon_id);
        } finally {
            $newMappingVersion?->delete();
            DungeonTransport::query()->whereKey([$transportA->id, $transportB->id, $oneWay->id])->delete();
        }
    }

    #[Test]
    public function copyMappingVersionContentsToDungeon_givenLinkedTransports_copiesThemLinkedToEachOthersCopies(): void
    {
        // Arrange
        $mappingService       = $this->app->make(MappingServiceInterface::class);
        $sourceMappingVersion = $this->getMappingVersionThatWillBeCloned();
        [$transportA, $transportB, $oneWay] = $this->createLinkedTransports($sourceMappingVersion);
        $targetMappingVersion = null;

        try {
            // Act
            $targetMappingVersion = $mappingService->copyMappingVersionToDungeon($sourceMappingVersion, $sourceMappingVersion->dungeon);
            DungeonTransport::query()->where('mapping_version_id', $targetMappingVersion->id)->delete();
            $mappingService->copyMappingVersionContentsToDungeon($sourceMappingVersion, $targetMappingVersion);

            // Assert
            $copies = $this->findSentinelTransports($targetMappingVersion);
            $this->assertCount(3, $copies);
            $copyA      = $copies->firstWhere('lng', 10.0);
            $copyB      = $copies->firstWhere('lng', 20.0);
            $copyOneWay = $copies->firstWhere('lng', 30.0);

            $this->assertSame($copyB->id, $copyA->linked_dungeon_transport_id);
            $this->assertSame($copyA->id, $copyB->linked_dungeon_transport_id);
            $this->assertSame($copyA->id, $copyOneWay->linked_dungeon_transport_id);
        } finally {
            $targetMappingVersion?->delete();
            DungeonTransport::query()->whereKey([$transportA->id, $transportB->id, $oneWay->id])->delete();
        }
    }

    #[Test]
    public function delete_givenMappingVersionWithTransports_deletesItsTransports(): void
    {
        // Arrange
        $existingMappingVersion = $this->getMappingVersionThatWillBeCloned();
        [$transportA, $transportB, $oneWay] = $this->createLinkedTransports($existingMappingVersion);
        $newMappingVersion = $this->createNextMappingVersion($existingMappingVersion);

        try {
            $this->assertTrue($newMappingVersion->dungeonTransports()->exists(), 'Precondition: the new MappingVersion has cloned transports.');

            // Act
            $newMappingVersion->delete();

            // Assert
            $this->assertFalse(DungeonTransport::query()->where('mapping_version_id', $newMappingVersion->id)->exists());
            $this->assertTrue(DungeonTransport::query()->whereKey($transportA->id)->exists(), 'The source mapping version keeps its transports.');
        } finally {
            DungeonTransport::query()->where('mapping_version_id', $newMappingVersion->id)->delete();
            DungeonTransport::query()->whereKey([$transportA->id, $transportB->id, $oneWay->id])->delete();
        }
    }

    #[Test]
    public function mapContextDungeonTransports_givenFacade_returnsTheTransportOnTheFacade(): void
    {
        // Arrange
        /** @var CoordinatesServiceInterface $coordinatesService */
        $coordinatesService = app(CoordinatesServiceInterface::class);
        /** @var array{0: MappingVersion, 1: Floor, 2: LatLng, 3: LatLng} $location */
        $location = $this->findDungeon(
            facadeEnabled: true,
            resolve:       static fn($dungeon, MappingVersion $mappingVersion) => self::findConvertibleFacadeLocation($coordinatesService, $mappingVersion),
        )[2];
        [$mappingVersion, $facadeFloor, $facadeLatLng, $floorLatLng] = $location;

        $dungeonTransport = DungeonTransport::factory()->create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floorLatLng->getFloor()->id,
            'lat'                => $floorLatLng->getLat(),
            'lng'                => $floorLatLng->getLng(),
        ]);

        try {
            // Act
            $facadeTransports = $mappingVersion->mapContextDungeonTransports($coordinatesService, true);
            $floorTransports  = $mappingVersion->mapContextDungeonTransports($coordinatesService, false);

            // Assert
            /** @var DungeonTransport $onFacade */
            $onFacade = $facadeTransports->firstWhere('id', $dungeonTransport->id);
            $this->assertSame($facadeFloor->id, $onFacade->floor_id);
            $this->assertEqualsWithDelta($facadeLatLng->getLat(), $onFacade->lat, 0.01);
            $this->assertEqualsWithDelta($facadeLatLng->getLng(), $onFacade->lng, 0.01);

            /** @var DungeonTransport $onFloor */
            $onFloor = $floorTransports->firstWhere('id', $dungeonTransport->id);
            $this->assertSame($floorLatLng->getFloor()->id, $onFloor->floor_id);
            $this->assertEqualsWithDelta($floorLatLng->getLat(), $onFloor->lat, 0.0001);
        } finally {
            $dungeonTransport->delete();
        }
    }

    /**
     * @return array{0: MappingVersion, 1: Floor, 2: LatLng, 3: LatLng}|null
     */
    private static function findConvertibleFacadeLocation(CoordinatesServiceInterface $coordinatesService, MappingVersion $mappingVersion): ?array
    {
        $mappingVersion->load(['floorUnions.floor', 'floorUnions.floorUnionAreas']);

        /** @var FloorUnion $floorUnion */
        foreach ($mappingVersion->floorUnions as $floorUnion) {
            /** @var FloorUnionArea $floorUnionArea */
            foreach ($floorUnion->floorUnionAreas as $floorUnionArea) {
                /** @var Collection<int, LatLng> $vertices */
                $vertices = $floorUnionArea->getDecodedLatLngs();
                if ($vertices->isEmpty()) {
                    continue;
                }

                $facadeLatLng = new LatLng(
                    (float)$vertices->avg(static fn(LatLng $latLng) => $latLng->getLat()),
                    (float)$vertices->avg(static fn(LatLng $latLng) => $latLng->getLng()),
                    $floorUnion->floor,
                );

                $floorLatLng = $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $facadeLatLng);

                // A floor union can overlay its target floor 1:1, changing the floor but not the coordinates
                if ($floorLatLng->getFloor()?->id !== $floorUnion->floor_id
                    && abs($floorLatLng->getLat() - $facadeLatLng->getLat()) > 0.0001) {
                    return [$mappingVersion, $floorUnion->floor, $facadeLatLng, $floorLatLng];
                }
            }
        }

        return null;
    }

    /**
     * Creates a two-way pair (A <-> B) and a one-way transport pointing at A.
     *
     * @return array{DungeonTransport, DungeonTransport, DungeonTransport}
     */
    private function createLinkedTransports(MappingVersion $mappingVersion): array
    {
        $floors          = $mappingVersion->dungeon->floors;
        $targetDungeonId = $mappingVersion->dungeon_id;

        $transportA = DungeonTransport::factory()->create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floors->first()->id,
            'target_dungeon_id'  => $targetDungeonId,
            'link_key'           => 'boat-a-b',
            'lat'                => self::SENTINEL_LAT,
            'lng'                => 10.0,
        ]);
        $transportB = DungeonTransport::factory()->create([
            'mapping_version_id'          => $mappingVersion->id,
            'floor_id'                    => $floors->last()->id,
            'linked_dungeon_transport_id' => $transportA->id,
            'lat'                         => self::SENTINEL_LAT,
            'lng'                         => 20.0,
        ]);
        $transportA->update(['linked_dungeon_transport_id' => $transportB->id]);
        $oneWay = DungeonTransport::factory()->create([
            'mapping_version_id'          => $mappingVersion->id,
            'floor_id'                    => $floors->first()->id,
            'linked_dungeon_transport_id' => $transportA->id,
            'lat'                         => self::SENTINEL_LAT,
            'lng'                         => 30.0,
        ]);

        return [$transportA, $transportB, $oneWay];
    }

    /**
     * @return Collection<int, DungeonTransport>
     */
    private function findSentinelTransports(MappingVersion $mappingVersion): Collection
    {
        return DungeonTransport::query()
            ->where('mapping_version_id', $mappingVersion->id)
            ->where('lat', self::SENTINEL_LAT)
            ->get();
    }

    private function getMappingVersionThatWillBeCloned(): MappingVersion
    {
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $this->findDungeon(challengeMode: true)[1];

        // The created hook clones the highest version of the dungeon's game version
        /** @var MappingVersion $latestMappingVersion */
        $latestMappingVersion = $mappingVersion->dungeon->mappingVersions()
            ->where('game_version_id', $mappingVersion->game_version_id)
            ->with('dungeon.floors')
            ->firstOrFail();

        return $latestMappingVersion;
    }

    private function createNextMappingVersion(MappingVersion $existingMappingVersion): MappingVersion
    {
        return MappingVersion::create([
            'game_version_id'                 => $existingMappingVersion->game_version_id,
            'dungeon_id'                      => $existingMappingVersion->dungeon_id,
            'version'                         => $existingMappingVersion->version + 1000,
            'enemy_forces_required'           => $existingMappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming'   => $existingMappingVersion->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $existingMappingVersion->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $existingMappingVersion->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $existingMappingVersion->timer_max_seconds,
            'facade_enabled'                  => false,
        ]);
    }
}
