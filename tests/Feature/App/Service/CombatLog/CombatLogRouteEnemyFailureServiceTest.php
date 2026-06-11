<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\CombatLogRouteEnemyFailureService;
use App\Service\CombatLog\CombatLogRouteEnemyFailureServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CombatLogRouteEnemyFailureService')]
final class CombatLogRouteEnemyFailureServiceTest extends PublicTestCase
{
    private CombatLogRouteEnemyFailureServiceInterface $service;

    private Dungeon $dungeon;

    private Floor $floor;

    private MappingVersion $mappingVersion;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CombatLogRouteEnemyFailureService();

        /** @var Dungeon $dungeon */
        $dungeon       = Dungeon::inRandomOrder()->first();
        $this->dungeon = $dungeon;

        /** @var Floor $floor */
        $floor       = $this->dungeon->floors()->where('facade', 0)->first();
        $this->floor = $floor;

        $this->mappingVersion = $this->dungeon->getCurrentMappingVersion();
    }

    #[Test]
    public function getEnemyFailureHeatmapData_givenTwoRecordsInSameGridCell_returnsSingleEntryWithWeightTwo(): void
    {
        $created = [];

        try {
            // Arrange — lat=-100.0/-100.5 and lng=192.0/192.5 both hash to gridX=117, gridY=100
            // (floor(100/256*300)=117, floor(192/384*200)=100)
            $record1 = CombatLogRouteEnemyFailure::create([
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => null,
                'lat'                => -100.0,
                'lng'                => 192.0,
            ]);
            $created[] = $record1->id;

            $record2 = CombatLogRouteEnemyFailure::create([
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => null,
                'lat'                => -100.5,
                'lng'                => 192.5,
            ]);
            $created[] = $record2->id;

            // Act
            $result = $this->service->getEnemyFailureHeatmapData($this->dungeon, null);
            $array  = $result->toArray();

            // Assert
            $floorData = collect($array['data'])->firstWhere('floor_id', $this->floor->id);
            $this->assertNotNull($floorData);
            $this->assertCount(1, $floorData['lat_lngs']);
            $this->assertEquals(2, $floorData['lat_lngs'][0]['weight']);
            $this->assertEquals(2, $array['weight_max']);
            $this->assertEquals(2, $array['failure_count']);
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();
        }
    }

    #[Test]
    public function getEnemyFailureHeatmapData_givenTwoRecordsInDifferentGridCells_returnsTwoEntriesEachWithWeightOne(): void
    {
        $created = [];

        try {
            // Arrange — far-apart coordinates guaranteed to fall in different grid cells
            $record1 = CombatLogRouteEnemyFailure::create([
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => null,
                'lat'                => -50.0,
                'lng'                => 100.0,
            ]);
            $created[] = $record1->id;

            $record2 = CombatLogRouteEnemyFailure::create([
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => null,
                'lat'                => -200.0,
                'lng'                => 300.0,
            ]);
            $created[] = $record2->id;

            // Act
            $result = $this->service->getEnemyFailureHeatmapData($this->dungeon, null);
            $array  = $result->toArray();

            // Assert
            $floorData = collect($array['data'])->firstWhere('floor_id', $this->floor->id);
            $this->assertNotNull($floorData);
            $this->assertCount(2, $floorData['lat_lngs']);

            foreach ($floorData['lat_lngs'] as $latLng) {
                $this->assertEquals(1, $latLng['weight']);
            }

            $this->assertEquals(1, $array['weight_max']);
            $this->assertEquals(2, $array['failure_count']);
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();
        }
    }

    #[Test]
    public function getEnemyFailureHeatmapData_givenNpcIdFilter_returnsOnlyMatchingRecords(): void
    {
        $created = [];

        $targetNpcId = 99801;
        $otherNpcId  = 99802;

        try {
            // Arrange
            $matching = CombatLogRouteEnemyFailure::create([
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => $targetNpcId,
                'lat'                => -50.0,
                'lng'                => 100.0,
            ]);
            $created[] = $matching->id;

            $excluded = CombatLogRouteEnemyFailure::create([
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => $otherNpcId,
                'lat'                => -200.0,
                'lng'                => 300.0,
            ]);
            $created[] = $excluded->id;

            // Act
            $result = $this->service->getEnemyFailureHeatmapData($this->dungeon, [$targetNpcId]);
            $array  = $result->toArray();

            // Assert
            $allLatLngs = collect($array['data'])->flatMap(fn(array $entry) => $entry['lat_lngs']);
            $this->assertCount(1, $allLatLngs);
            $this->assertEquals(1, $allLatLngs->first()['weight']);
            $this->assertEquals(1, $array['failure_count']);
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();
        }
    }

    #[Test]
    public function getEnemyFailureHeatmapData_givenNoRecords_returnsEmptyData(): void
    {
        // Act — use a dungeon that should have no combat log failures for a non-existent npc
        $result = $this->service->getEnemyFailureHeatmapData($this->dungeon, [999999]);
        $array  = $result->toArray();

        // Assert
        $this->assertEmpty($array['data']);
        $this->assertEquals(0, $array['weight_max']);
        $this->assertEquals(0, $array['failure_count']);
    }
}
