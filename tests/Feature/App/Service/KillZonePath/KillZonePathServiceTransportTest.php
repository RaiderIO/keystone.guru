<?php

namespace Tests\Feature\App\Service\KillZonePath;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Logic\Structs\PathStep;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonTransport;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\KillZonePath\KillZonePathServiceInterface;
use Database\Factories\FloorFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;
use Throwable;

#[Group('KillZonePath')]
final class KillZonePathServiceTransportTest extends PublicTestCase
{
    use CreatesDungeon;

    private Floor $floor;

    private MappingVersion $mappingVersion;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default'                                       => 'null',
            'keystoneguru.character.default_movement_speed_yards_second' => 7,
            'keystoneguru.character.mounted_movement_speed_yards_second' => 14,
        ]);

        $dungeon = $this->createDungeon();
        /** @var Floor $floor */
        $floor                = $dungeon->floors()->firstOrFail();
        $this->floor          = $floor;
        $this->mappingVersion = $dungeon->mappingVersions()->firstOrFail();
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            DungeonTransport::query()->where('mapping_version_id', $this->mappingVersion->id)->delete();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * Walking 300 yards straight takes 300 yards; walking to the transport, riding it at twice the walking speed and
     * walking to the pull takes 2 * $offsetYards + 150, so the transport is the way while $offsetYards is under 75.
     */
    #[Test]
    #[DataProvider('transportOffsetProvider')]
    public function findPathsToKillZones_givenTwoWayTransportPair_takesTheTransportOnlyWhenRidingItIsQuicker(
        float $offsetYards,
        bool  $expectTransport,
    ): void {
        // Arrange
        $dungeonRoute = $this->createDungeonRoute();

        try {
            $this->createTransportPair($this->floor, 0, $offsetYards, $this->floor, 300, $offsetYards);
            $this->createKillZone($dungeonRoute, $this->floor, 0, 0, 1);
            $killZone = $this->createKillZone($dungeonRoute, $this->floor, 300, 0, 2);

            // Act
            $result = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);

            // Assert
            $this->assertSame(
                $expectTransport ? [false, false, true, false] : [false, false],
                $this->getArrivedByTransport($result[$killZone->id]),
            );
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @return array<string, array{float, bool}>
     */
    public static function transportOffsetProvider(): array
    {
        return [
            'transport 60 yards off the line: 270 yards' => [60, true],
            'transport 90 yards off the line: 330 yards' => [90, false],
        ];
    }

    #[Test]
    public function findPathsToKillZones_givenOneWayTransport_takesItInItsDirectionOnly(): void
    {
        // Arrange - the transport only goes from the far side back to the start
        $dungeonRoute = $this->createDungeonRoute();

        try {
            $start = $this->createTransport($this->floor, 0, 10);
            $this->createTransport($this->floor, 300, 10, $start->id);
            $this->createKillZone($dungeonRoute, $this->floor, 0, 0, 1);
            $farKillZone  = $this->createKillZone($dungeonRoute, $this->floor, 300, 0, 2);
            $backKillZone = $this->createKillZone($dungeonRoute, $this->floor, 0, 20, 3);

            // Act
            $result = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);

            // Assert
            $this->assertSame([false, false], $this->getArrivedByTransport($result[$farKillZone->id]));
            $this->assertSame([false, false, true, false], $this->getArrivedByTransport($result[$backKillZone->id]));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function findPathsToKillZones_givenTransportBetweenFloors_returnsPathAcrossFloors(): void
    {
        // Arrange - no floor switch connects the floors, only the transport
        $otherFloor   = $this->createOtherFloor();
        $dungeonRoute = $this->createDungeonRoute();

        try {
            $this->createTransportPair($this->floor, 0, 10, $otherFloor, 0, 10);
            $this->createKillZone($dungeonRoute, $this->floor, 0, 0, 1);
            $killZone = $this->createKillZone($dungeonRoute, $otherFloor, 0, 0, 2);

            // Act
            $result = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);

            // Assert
            $this->assertSame([false, false, true, false], $this->getArrivedByTransport($result[$killZone->id]));
            $this->assertSame($otherFloor->id, $result[$killZone->id][2]->latLng->getFloor()->id);
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @param string $routeFaction     The key of the route's faction
     * @param string $transportFaction The faction of both ends of the transport
     */
    #[Test]
    #[DataProvider('factionProvider')]
    public function findPathsToKillZones_givenTransportFaction_takesOnlyTransportsTheRouteFactionCanUse(
        string $routeFaction,
        string $transportFaction,
        bool   $expectTransport,
    ): void {
        // Arrange
        $dungeonRoute = $this->createDungeonRoute($routeFaction);

        try {
            $this->createTransportPair($this->floor, 0, 10, $this->floor, 300, 10, $transportFaction);
            $this->createKillZone($dungeonRoute, $this->floor, 0, 0, 1);
            $killZone = $this->createKillZone($dungeonRoute, $this->floor, 300, 0, 2);

            // Act
            $result = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);

            // Assert
            $this->assertSame(
                $expectTransport ? [false, false, true, false] : [false, false],
                $this->getArrivedByTransport($result[$killZone->id]),
            );
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function factionProvider(): array
    {
        return [
            'horde route, alliance transport'       => [Faction::FACTION_HORDE, Faction::FACTION_ALLIANCE, false],
            'horde route, horde transport'          => [Faction::FACTION_HORDE, Faction::FACTION_HORDE, true],
            'horde route, any faction transport'    => [Faction::FACTION_HORDE, Faction::FACTION_ANY, true],
            'unspecified route, alliance transport' => [Faction::FACTION_UNSPECIFIED, Faction::FACTION_ALLIANCE, true],
        ];
    }

    #[Test]
    public function findPathsToKillZones_givenCrossDungeonTransport_doesNotTakeIt(): void
    {
        // Arrange
        $dungeonRoute = $this->createDungeonRoute();

        try {
            [$transport] = $this->createTransportPair($this->floor, 0, 10, $this->floor, 300, 10);
            $transport->update(['target_dungeon_id' => $this->createDungeon()->id, 'link_key' => 'test']);
            $this->createKillZone($dungeonRoute, $this->floor, 0, 0, 1);
            $killZone = $this->createKillZone($dungeonRoute, $this->floor, 300, 0, 2);

            // Act
            $result = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);

            // Assert
            $this->assertSame([false, false], $this->getArrivedByTransport($result[$killZone->id]));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function findPathsToKillZones_givenTransportOnFacadeFloor_leavesItOutInsteadOfThrowing(): void
    {
        // Arrange
        $facadeFloor  = $this->createOtherFloor(['facade' => true]);
        $dungeonRoute = $this->createDungeonRoute();

        try {
            $facadeTransport = $this->createTransport($facadeFloor, 0, 10);
            $transport       = $this->createTransport($this->floor, 300, 10, $facadeTransport->id);
            $facadeTransport->update(['linked_dungeon_transport_id' => $transport->id]);
            $this->createKillZone($dungeonRoute, $this->floor, 0, 0, 1);
            $killZone = $this->createKillZone($dungeonRoute, $this->floor, 300, 0, 2);

            // Act
            try {
                $result = app(KillZonePathServiceInterface::class)->findPathsToKillZones($dungeonRoute);
            } catch (Throwable $throwable) {
                $this->fail(sprintf('A transport on the facade floor took part in the path: %s', $throwable->getMessage()));
            }

            // Assert
            $this->assertSame([false, false], $this->getArrivedByTransport($result[$killZone->id]));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function calculateForRoute_givenTransport_splitsTheSegmentWhereTheTransportIsTaken(): void
    {
        // Arrange
        $dungeonRoute = $this->createDungeonRoute();

        try {
            $this->createTransportPair($this->floor, 0, 10, $this->floor, 300, 10);
            $this->createKillZone($dungeonRoute, $this->floor, 0, 0, 1);
            $this->createKillZone($dungeonRoute, $this->floor, 300, 0, 2);

            // Act
            $result = app(KillZonePathServiceInterface::class)->calculateForRoute($dungeonRoute, false);

            // Assert - one segment walks to the transport, the next walks on from where it arrives
            $this->assertCount(2, $result);
            $this->assertCount(2, $result[0]);
            $this->assertCount(2, $result[1]);
            $this->assertEqualsWithDelta($this->latLngAt($this->floor, 0, 10)->getLat(), $result[0][1]['lat'], 0.0001);
            $this->assertEqualsWithDelta($this->latLngAt($this->floor, 300, 10)->getLat(), $result[1][0]['lat'], 0.0001);
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createOtherFloor(array $attributes = []): Floor
    {
        return FloorFactory::new()->create(array_merge([
            'dungeon_id'   => $this->floor->dungeon_id,
            'index'        => 2,
            'name'         => 'Test Floor 2',
            'default'      => false,
            'ingame_min_x' => $this->floor->ingame_min_x,
            'ingame_max_x' => $this->floor->ingame_max_x,
            'ingame_min_y' => $this->floor->ingame_min_y,
            'ingame_max_y' => $this->floor->ingame_max_y,
        ], $attributes));
    }

    private function createDungeonRoute(string $factionKey = Faction::FACTION_UNSPECIFIED): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'dungeon_id'         => $this->floor->dungeon_id,
            'mapping_version_id' => $this->mappingVersion->id,
            'faction_id'         => Faction::ALL[$factionKey],
        ]);
    }

    private function createKillZone(DungeonRoute $dungeonRoute, Floor $floor, float $xYards, float $yYards, int $index): KillZone
    {
        $latLng = $this->latLngAt($floor, $xYards, $yYards);

        return KillZone::factory()->create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'lat'              => $latLng->getLat(),
            'lng'              => $latLng->getLng(),
            'index'            => $index,
        ]);
    }

    /**
     * @return array{DungeonTransport, DungeonTransport}
     */
    private function createTransportPair(
        Floor  $floor,
        float  $xYards,
        float  $yYards,
        Floor  $otherFloor,
        float  $otherXYards,
        float  $otherYYards,
        string $faction = Faction::FACTION_ANY,
    ): array {
        $transport      = $this->createTransport($floor, $xYards, $yYards, null, $faction);
        $otherTransport = $this->createTransport($otherFloor, $otherXYards, $otherYYards, $transport->id, $faction);
        $transport->update(['linked_dungeon_transport_id' => $otherTransport->id]);

        return [$transport, $otherTransport];
    }

    private function createTransport(
        Floor  $floor,
        float  $xYards,
        float  $yYards,
        ?int   $linkedDungeonTransportId = null,
        string $faction = Faction::FACTION_ANY,
    ): DungeonTransport {
        // A facade floor has no in-game coordinates of its own, so place its end where it would be on the zone floor
        $latLng = $this->latLngAt($floor->facade ? $this->floor : $floor, $xYards, $yYards);

        return DungeonTransport::factory()->create([
            'mapping_version_id'          => $this->mappingVersion->id,
            'floor_id'                    => $floor->id,
            'linked_dungeon_transport_id' => $linkedDungeonTransportId,
            'faction'                     => $faction,
            'lat'                         => $latLng->getLat(),
            'lng'                         => $latLng->getLng(),
        ]);
    }

    /**
     * @return LatLng The point $xYards east and $yYards north of the floor's centre
     */
    private function latLngAt(Floor $floor, float $xYards, float $yYards): LatLng
    {
        return app(CoordinatesServiceInterface::class)->calculateMapLocationForIngameLocation(new IngameXY(
            ($floor->ingame_min_x + $floor->ingame_max_x) / 2 + $xYards,
            ($floor->ingame_min_y + $floor->ingame_max_y) / 2 + $yYards,
            $floor,
        ));
    }

    /**
     * @param list<PathStep> $path
     *
     * @return list<bool>
     */
    private function getArrivedByTransport(array $path): array
    {
        return array_map(static fn(PathStep $pathStep): bool => $pathStep->arrivedByTransport, $path);
    }
}
