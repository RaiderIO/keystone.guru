<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcEnemyForces;
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

    /** @var int[] */
    private array $createdNpcEnemyForcesIds = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->createdNpcEnemyForcesIds = [];

        $this->service = app(CombatLogRouteEnemyFailureServiceInterface::class);

        // CombatLogRouteEnemyFailure is on a separate DB connection, so fetch existing dungeon IDs first.
        $dungeonIdsWithData = CombatLogRouteEnemyFailure::query()
            ->distinct()
            ->pluck('dungeon_id')
            ->all();

        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()
            ->when(!empty($dungeonIdsWithData), fn($q) => $q->whereNotIn('id', $dungeonIdsWithData))
            ->inRandomOrder()
            ->first();
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
            $result = $this->service->getEnemyFailureHeatmapData($this->dungeon, $this->mappingVersion, null);
            $array  = $result->setUseFacade(false)->toArray();

            // Assert
            /** @var array<int, array<string, mixed>> $data */
            $data      = $array['data'];
            $floorData = collect($data)->firstWhere('floor_id', $this->floor->id);
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
            $result = $this->service->getEnemyFailureHeatmapData($this->dungeon, $this->mappingVersion, null);
            $array  = $result->setUseFacade(false)->toArray();

            // Assert
            /** @var array<int, array<string, mixed>> $data */
            $data      = $array['data'];
            $floorData = collect($data)->firstWhere('floor_id', $this->floor->id);
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
            $this->createEnemyForces($targetNpcId);
            $this->createEnemyForces($otherNpcId);

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
            $result = $this->service->getEnemyFailureHeatmapData($this->dungeon, $this->mappingVersion, [$targetNpcId]);
            $array  = $result->setUseFacade(false)->toArray();

            // Assert
            /** @var array<int, array<string, mixed>> $data */
            $data       = $array['data'];
            $allLatLngs = collect($data)->flatMap(fn(array $entry): array => $entry['lat_lngs']);
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
        // Act — PHP_INT_MAX as npc_id is guaranteed to not exist
        $result = $this->service->getEnemyFailureHeatmapData($this->dungeon, $this->mappingVersion, [PHP_INT_MAX]);
        $array  = $result->setUseFacade(false)->toArray();

        // Assert
        $this->assertEmpty($array['data']);
        $this->assertEquals(0, $array['weight_max']);
        $this->assertEquals(0, $array['failure_count']);
    }

    #[Test]
    public function getEnemyFailureHeatmapData_givenMatchingDungeonRoutes_returnsDungeonRoutes(): void
    {
        $createdFailures = [];
        $createdRouteIds = [];

        try {
            // Arrange
            $route = DungeonRoute::factory()->create([
                'dungeon_id'         => $this->dungeon->id,
                'mapping_version_id' => $this->mappingVersion->id,
            ]);
            $createdRouteIds[] = $route->id;

            $targetNpcId = 99901;
            $this->createEnemyForces($targetNpcId);

            $failure = CombatLogRouteEnemyFailure::create([
                'dungeon_route_id'   => $route->id,
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => $targetNpcId,
                'lat'                => -50.0,
                'lng'                => 100.0,
            ]);
            $createdFailures[] = $failure->id;

            // Act
            $result = $this->service->getEnemyFailureHeatmapData($this->dungeon, $this->mappingVersion, [$targetNpcId]);
            $array  = $result->toArray();

            // Assert
            $this->assertArrayHasKey('dungeon_routes', $array);
            $this->assertCount(1, $array['dungeon_routes']);
            $this->assertEquals($route->public_key, $array['dungeon_routes'][0]['public_key']);
            $this->assertEquals($route->title, $array['dungeon_routes'][0]['title']);
            $this->assertNotEmpty($array['dungeon_routes'][0]['url']);
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $createdFailures)->delete();
            DungeonRoute::whereIn('id', $createdRouteIds)->delete();
        }
    }

    #[Test]
    public function getEnemyFailureHeatmapData_givenMoreThanFiveRoutes_returnsMaxFiveRoutes(): void
    {
        $createdFailures = [];
        $createdRouteIds = [];

        try {
            // Arrange — create 6 routes each with a failure for the same NPC
            $targetNpcId = 99902;
            $this->createEnemyForces($targetNpcId);

            for ($i = 0; $i < 6; $i++) {
                $route = DungeonRoute::factory()->create([
                    'dungeon_id'         => $this->dungeon->id,
                    'mapping_version_id' => $this->mappingVersion->id,
                ]);
                $createdRouteIds[] = $route->id;

                $failure = CombatLogRouteEnemyFailure::create([
                    'dungeon_route_id'   => $route->id,
                    'dungeon_id'         => $this->dungeon->id,
                    'floor_id'           => $this->floor->id,
                    'mapping_version_id' => $this->mappingVersion->id,
                    'npc_id'             => $targetNpcId,
                    'lat'                => -50.0,
                    'lng'                => 100.0,
                ]);
                $createdFailures[] = $failure->id;
            }

            // Act
            $result = $this->service->getEnemyFailureHeatmapData($this->dungeon, $this->mappingVersion, [$targetNpcId]);
            $array  = $result->toArray();

            // Assert
            $this->assertArrayHasKey('dungeon_routes', $array);
            $this->assertCount(5, $array['dungeon_routes']);
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $createdFailures)->delete();
            DungeonRoute::whereIn('id', $createdRouteIds)->delete();
        }
    }

    #[Test]
    public function getEnemyFailureHeatmapData_givenNoNpcFilter_returnsDungeonRoutesEmpty(): void
    {
        $createdFailures = [];
        $createdRouteIds = [];

        try {
            // Arrange
            $route = DungeonRoute::factory()->create([
                'dungeon_id'         => $this->dungeon->id,
                'mapping_version_id' => $this->mappingVersion->id,
            ]);
            $createdRouteIds[] = $route->id;

            $failure = CombatLogRouteEnemyFailure::create([
                'dungeon_route_id'   => $route->id,
                'dungeon_id'         => $this->dungeon->id,
                'floor_id'           => $this->floor->id,
                'mapping_version_id' => $this->mappingVersion->id,
                'npc_id'             => 99903,
                'lat'                => -50.0,
                'lng'                => 100.0,
            ]);
            $createdFailures[] = $failure->id;

            // Act — no NPC filter
            $result = $this->service->getEnemyFailureHeatmapData($this->dungeon, $this->mappingVersion, null);
            $array  = $result->toArray();

            // Assert — routes should be empty when no NPC filter is active
            $this->assertArrayHasKey('dungeon_routes', $array);
            $this->assertEmpty($array['dungeon_routes']);
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $createdFailures)->delete();
            DungeonRoute::whereIn('id', $createdRouteIds)->delete();
        }
    }

    #[Test]
    public function getEnemyFailureHeatmapData_givenMappingVersionFilter_returnsOnlyMatchingRows(): void
    {
        $created = [];

        try {
            // Arrange — one failure in the selected mapping version, one in another (fake) one
            $created[] = $this->createFailure(['mapping_version_id' => $this->mappingVersion->id, 'lat' => -50.0, 'lng' => 100.0])->id;
            $created[] = $this->createFailure(['mapping_version_id' => PHP_INT_MAX, 'lat' => -200.0, 'lng' => 300.0])->id;

            // Act
            $array = $this->service->getEnemyFailureHeatmapData($this->dungeon, $this->mappingVersion, null)
                ->setUseFacade(false)
                ->toArray();

            // Assert
            $this->assertEquals(1, $array['failure_count']);
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();
        }
    }

    #[Test]
    public function getEnemyFailureHeatmapData_givenNpcsWithoutEnemyForces_excludesTheirRows(): void
    {
        $created          = [];
        $zeroForcesNpcId  = 99910;
        $noForcesRowNpcId = 99911;
        $worthForcesNpcId = 99912;

        try {
            // Arrange — worth 0 explicitly, no enemy forces row at all, and worth enemy forces
            $this->createEnemyForces($zeroForcesNpcId, 0);
            $this->createEnemyForces($worthForcesNpcId);

            $created[] = $this->createFailure(['npc_id' => $zeroForcesNpcId, 'lat' => -50.0, 'lng' => 100.0])->id;
            $created[] = $this->createFailure(['npc_id' => $noForcesRowNpcId, 'lat' => -200.0, 'lng' => 300.0])->id;
            $created[] = $this->createFailure(['npc_id' => $worthForcesNpcId, 'lat' => -150.0, 'lng' => 250.0])->id;
            $created[] = $this->createFailure(['npc_id' => null, 'lat' => -100.0, 'lng' => 200.0])->id;

            // Act
            $array = $this->service->getEnemyFailureHeatmapData($this->dungeon, $this->mappingVersion, null)
                ->setUseFacade(false)
                ->toArray();

            // Assert — both npcs not worth enemy forces are gone, the npc worth them and the npc-less row stay
            $this->assertEquals(2, $array['failure_count']);
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();
        }
    }

    /**
     * A mapping version with no enemy forces tuned at all is a dungeon nobody has gotten to yet, not a dungeon whose
     * every failure is noise - filtering on an empty set would blank the view exactly when a mapper needs it.
     */
    #[Test]
    public function getEnemyFailureHeatmapData_givenMappingVersionWithoutAnyEnemyForces_keepsEveryRow(): void
    {
        $created             = [];
        $emptyMappingVersion = null;

        try {
            // Arrange — a mapping version on a game version this dungeon has none on, so nothing is cloned onto it
            $gameVersionId = GameVersion::query()
                ->whereNotIn('id', $this->dungeon->mappingVersions->pluck('game_version_id')->all())
                ->value('id');
            $this->assertNotNull($gameVersionId, 'Expected a game version the dungeon has no mapping version on.');

            $emptyMappingVersion = MappingVersion::create([
                'dungeon_id'            => $this->dungeon->id,
                'game_version_id'       => $gameVersionId,
                'version'               => 1,
                'enemy_forces_required' => 0,
                'timer_max_seconds'     => 0,
            ]);
            $this->assertSame([], $this->service->getNonZeroEnemyForcesNpcIds($emptyMappingVersion));

            $created[] = $this->createFailure(['npc_id' => 99913, 'mapping_version_id' => $emptyMappingVersion->id, 'lat' => -50.0, 'lng' => 100.0])->id;
            $created[] = $this->createFailure(['npc_id' => null, 'mapping_version_id' => $emptyMappingVersion->id, 'lat' => -200.0, 'lng' => 300.0])->id;

            // Act
            $array = $this->service->getEnemyFailureHeatmapData($this->dungeon, $emptyMappingVersion, null)
                ->setUseFacade(false)
                ->toArray();

            // Assert
            $this->assertEquals(2, $array['failure_count']);
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();

            if ($emptyMappingVersion !== null) {
                $emptyMappingVersion->delete();
            }
        }
    }

    #[Test]
    public function getFailureCountsPerNpc_givenRowsAcrossMappingVersions_returnsOnlySelectedMappingVersionMostFailuresFirst(): void
    {
        $created = [];

        try {
            // Arrange — npc A twice and npc B once in the selected mapping version, npc B again in another one
            $this->createEnemyForces(99920);
            $this->createEnemyForces(99921);

            $created[] = $this->createFailure(['npc_id' => 99920])->id;
            $created[] = $this->createFailure(['npc_id' => 99920])->id;
            $created[] = $this->createFailure(['npc_id' => 99921])->id;
            $created[] = $this->createFailure(['npc_id' => 99921, 'mapping_version_id' => PHP_INT_MAX])->id;
            $created[] = $this->createFailure(['npc_id' => null])->id;

            // Act
            $counts = $this->service->getFailureCountsPerNpc($this->dungeon, $this->mappingVersion);

            // Assert
            $this->assertSame([99920 => 2, 99921 => 1], $counts->all());
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();
        }
    }

    #[Test]
    public function getFailureCountsPerMappingVersion_givenRows_returnsCountForEveryMappingVersionOfTheDungeon(): void
    {
        $created = [];

        try {
            // Arrange
            $created[] = $this->createFailure()->id;
            $created[] = $this->createFailure()->id;

            // Act
            $counts = $this->service->getFailureCountsPerMappingVersion($this->dungeon);

            // Assert — every mapping version is present, the current one carries our two rows
            $this->assertEqualsCanonicalizing($this->dungeon->mappingVersions->pluck('id')->all(), $counts->keys()->all());
            $this->assertSame(2, $counts->get($this->mappingVersion->id));
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();
        }
    }

    #[Test]
    public function getFailureCountsPerMappingVersion_givenNpcsWithoutEnemyForces_excludesTheirRowsLikeTheHeatmapDoes(): void
    {
        $created          = [];
        $zeroForcesNpcId  = 99940;
        $worthForcesNpcId = 99942;

        try {
            // Arrange — two rows worth 0 enemy forces, one for an npc without any row, one worth forces, one without npc
            $this->createEnemyForces($zeroForcesNpcId, 0);
            $this->createEnemyForces($worthForcesNpcId);

            $created[] = $this->createFailure(['npc_id' => $zeroForcesNpcId])->id;
            $created[] = $this->createFailure(['npc_id' => $zeroForcesNpcId])->id;
            $created[] = $this->createFailure(['npc_id' => 99941])->id;
            $created[] = $this->createFailure(['npc_id' => $worthForcesNpcId])->id;
            $created[] = $this->createFailure(['npc_id' => null])->id;

            // Act
            $counts = $this->service->getFailureCountsPerMappingVersion($this->dungeon);

            // Assert — matches the heatmap's failure_count for the same mapping version
            $this->assertSame(2, $counts->get($this->mappingVersion->id));
            $heatmap = $this->service->getEnemyFailureHeatmapData($this->dungeon, $this->mappingVersion, null)->setUseFacade(false)->toArray();
            $this->assertSame($heatmap['failure_count'], $counts->get($this->mappingVersion->id));
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();
        }
    }

    /**
     * The coverage page explains why a route missed 100% enemy forces, so an npc worth no enemy forces cannot be part
     * of the explanation there either.
     */
    #[Test]
    public function getFailureCountsPerDungeonRoute_givenNpcsWithoutEnemyForces_countsOnlyTheRestPerRoute(): void
    {
        $created          = [];
        $createdRouteIds  = [];
        $worthForcesNpcId = 99970;
        $noForcesNpcId    = 99971;

        try {
            // Arrange — two routes on the same mapping version, one failure of each kind on the first
            $this->createEnemyForces($worthForcesNpcId);

            foreach ([0, 1] as $unused) {
                $createdRouteIds[] = DungeonRoute::factory()->create([
                    'dungeon_id'         => $this->dungeon->id,
                    'mapping_version_id' => $this->mappingVersion->id,
                ])->id;
            }

            $created[] = $this->createFailure(['dungeon_route_id' => $createdRouteIds[0], 'npc_id' => $worthForcesNpcId])->id;
            $created[] = $this->createFailure(['dungeon_route_id' => $createdRouteIds[0], 'npc_id' => $noForcesNpcId])->id;
            $created[] = $this->createFailure(['dungeon_route_id' => $createdRouteIds[0], 'npc_id' => null])->id;
            $created[] = $this->createFailure(['dungeon_route_id' => $createdRouteIds[1], 'npc_id' => $noForcesNpcId])->id;

            $dungeonRoutes = DungeonRoute::query()->whereIn('id', $createdRouteIds)->get()->keyBy('id');

            // Act
            $counts = $this->service->getFailureCountsPerDungeonRoute($dungeonRoutes);

            // Assert — the npc worth forces and the npc-less row count, the npc without forces does not, and a route
            // left with nothing to explain is absent rather than present with 0
            $this->assertSame(2, $counts->get($createdRouteIds[0]));
            $this->assertNull($counts->get($createdRouteIds[1]));
        } finally {
            CombatLogRouteEnemyFailure::whereIn('id', $created)->delete();
            DungeonRoute::whereIn('id', $createdRouteIds)->delete();
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        if ($this->createdNpcEnemyForcesIds !== []) {
            NpcEnemyForces::query()->whereKey($this->createdNpcEnemyForcesIds)->delete();
            new NpcEnemyForces()->flushCache();
            $this->createdNpcEnemyForcesIds = [];
        }

        parent::tearDown();
    }

    /**
     * Only npcs worth enemy forces have their failures kept, so a test npc that should survive the filter needs a row.
     */
    private function createEnemyForces(int $npcId, int $enemyForces = 10, ?int $mappingVersionId = null): int
    {
        $id = NpcEnemyForces::query()->create([
            'mapping_version_id' => $mappingVersionId ?? $this->mappingVersion->id,
            'npc_id'             => $npcId,
            'enemy_forces'       => $enemyForces,
        ])->id;

        $this->createdNpcEnemyForcesIds[] = $id;

        return $id;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createFailure(array $attributes = []): CombatLogRouteEnemyFailure
    {
        return CombatLogRouteEnemyFailure::create(array_merge([
            'dungeon_id'         => $this->dungeon->id,
            'floor_id'           => $this->floor->id,
            'mapping_version_id' => $this->mappingVersion->id,
            'npc_id'             => null,
            'lat'                => -50.0,
            'lng'                => 100.0,
        ], $attributes));
    }
}
