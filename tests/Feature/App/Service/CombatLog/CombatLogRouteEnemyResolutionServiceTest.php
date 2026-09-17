<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\CombatLogRouteEnemyResolutionServiceInterface;
use App\Service\CombatLog\Enums\EnemyResolutionHeatmapMetric;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CombatLogRouteEnemyResolutionService')]
final class CombatLogRouteEnemyResolutionServiceTest extends PublicTestCase
{
    use CreatesDungeon;

    private CombatLogRouteEnemyResolutionServiceInterface $service;

    private Dungeon $dungeon;

    private Floor $floor;

    private MappingVersion $mappingVersion;

    /** @var int[] */
    private array $createdResolutionIds = [];

    /** @var int[] */
    private array $createdDungeonRouteIds = [];

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->createdResolutionIds   = [];
        $this->createdDungeonRouteIds = [];

        $this->service = app(CombatLogRouteEnemyResolutionServiceInterface::class);

        $this->dungeon        = $this->createDungeon();
        $this->floor          = $this->dungeon->floors()->firstOrFail();
        $this->mappingVersion = $this->dungeon->getCurrentMappingVersion();
    }

    #[Override]
    protected function tearDown(): void
    {
        try {
            if ($this->createdResolutionIds !== []) {
                CombatLogRouteEnemyResolution::query()->whereKey($this->createdResolutionIds)->delete();
            }

            foreach (DungeonRoute::query()->whereKey($this->createdDungeonRouteIds)->get() as $dungeonRoute) {
                $dungeonRoute->delete();
            }
        } finally {
            parent::tearDown();
        }
    }

    /**
     * The point of the heatmap is how far off the matches in one spot were, so a cell's weight is their average
     * distance - not how many of them there were, the way the failure heatmap counts.
     */
    #[Test]
    public function getResolutionHeatmapData_givenTwoRecordsInSameGridCell_weighsTheCellByTheirAverageDistance(): void
    {
        // Arrange - lat=-100.0/-100.5 and lng=192.0/192.5 both hash to gridX=117, gridY=100
        config(['keystoneguru.enemy_resolution.heatmap_min_samples' => 1]);
        $this->createResolution(-100.0, 192.0, 60.0);
        $this->createResolution(-100.5, 192.5, 40.0);

        // Act
        $array = $this->service
            ->getResolutionHeatmapData($this->dungeon, $this->mappingVersion, null, EnemyResolutionHeatmapMetric::Average)
            ->setUseFacade(false)
            ->toArray();

        // Assert
        $latLngs = $this->latLngsOnFloor($array);
        $this->assertCount(1, $latLngs);
        $this->assertEqualsWithDelta(50.0, $latLngs[0]['weight'], 0.01);
        $this->assertSame(2, $latLngs[0]['count']);
        $this->assertEqualsWithDelta(50.0, $array['weight_max'], 0.01);
        $this->assertSame(2, $array['resolution_count']);
        $this->assertSame(2, $array['drawn_count']);
    }

    #[Test]
    public function getResolutionHeatmapData_givenMaxMetric_weighsTheCellByItsWorstMatch(): void
    {
        // Arrange
        config(['keystoneguru.enemy_resolution.heatmap_min_samples' => 1]);
        $this->createResolution(-100.0, 192.0, 60.0);
        $this->createResolution(-100.5, 192.5, 40.0);

        // Act
        $array = $this->service
            ->getResolutionHeatmapData($this->dungeon, $this->mappingVersion, null, EnemyResolutionHeatmapMetric::Max)
            ->setUseFacade(false)
            ->toArray();

        // Assert
        $latLngs = $this->latLngsOnFloor($array);
        $this->assertCount(1, $latLngs);
        $this->assertEqualsWithDelta(60.0, $latLngs[0]['weight'], 0.01);
        $this->assertEqualsWithDelta(60.0, $array['weight_max'], 0.01);
    }

    /**
     * A single enemy that got body pulled across the room leaves one far match anywhere on the map. Only a spot that
     * is off again and again says something about the mapping, so a cell has to carry enough matches to be drawn.
     */
    #[Test]
    public function getResolutionHeatmapData_givenCellWithFewerMatchesThanMinimumSamples_doesNotDrawIt(): void
    {
        // Arrange
        config(['keystoneguru.enemy_resolution.heatmap_min_samples' => 3]);
        $this->createResolution(-100.0, 192.0, 60.0);
        $this->createResolution(-100.5, 192.5, 40.0);

        // Act
        $array = $this->service
            ->getResolutionHeatmapData($this->dungeon, $this->mappingVersion, null, EnemyResolutionHeatmapMetric::Average)
            ->setUseFacade(false)
            ->toArray();

        // Assert - the matches are still counted, they are just not drawn
        $this->assertCount(0, $this->latLngsOnFloor($array));
        $this->assertSame(2, $array['resolution_count']);
        $this->assertSame(0, $array['drawn_count']);
        $this->assertEqualsWithDelta(0.0, $array['weight_max'], 0.01);
    }

    #[Test]
    public function getResolutionHeatmapData_givenMinDistance_ignoresMatchesCloserThanThat(): void
    {
        // Arrange
        config(['keystoneguru.enemy_resolution.heatmap_min_samples' => 1]);
        $this->createResolution(-100.0, 192.0, 80.0);
        $this->createResolution(-100.5, 192.5, 40.0);

        // Act
        $array = $this->service
            ->getResolutionHeatmapData($this->dungeon, $this->mappingVersion, null, EnemyResolutionHeatmapMetric::Average, 50.0)
            ->setUseFacade(false)
            ->toArray();

        // Assert - only the 80 yard match is left, so it is the cell's average as well as its worst
        $latLngs = $this->latLngsOnFloor($array);
        $this->assertCount(1, $latLngs);
        $this->assertEqualsWithDelta(80.0, $latLngs[0]['weight'], 0.01);
        $this->assertSame(1, $latLngs[0]['count']);
        $this->assertSame(1, $array['resolution_count']);
    }

    /**
     * The filter runs on the distance the matcher judged on, not the raw one - a long line that kill priority
     * explains must not come back when filtering for the far ones.
     */
    #[Test]
    public function getResolutionHeatmapData_givenMinDistance_filtersOnTheWeightedDistance(): void
    {
        // Arrange
        config(['keystoneguru.enemy_resolution.heatmap_min_samples' => 1]);
        $this->createResolution(-100.0, 192.0, weightedDistance: 30.0, distance: 90.0);

        // Act
        $array = $this->service
            ->getResolutionHeatmapData($this->dungeon, $this->mappingVersion, null, EnemyResolutionHeatmapMetric::Average, 50.0)
            ->setUseFacade(false)
            ->toArray();

        // Assert
        $this->assertCount(0, $this->latLngsOnFloor($array));
        $this->assertSame(0, $array['resolution_count']);
    }

    #[Test]
    public function getResolutionCountsPerNpc_givenResolutionsOfSeveralNpcs_returnsCountsMostFirst(): void
    {
        // Arrange
        $this->createResolution(-100.0, 192.0, 60.0, npcId: 991001);
        $this->createResolution(-100.5, 192.5, 60.0, npcId: 991002);
        $this->createResolution(-101.0, 193.0, 60.0, npcId: 991002);

        // Act
        $counts = $this->service->getResolutionCountsPerNpc($this->dungeon, $this->mappingVersion);

        // Assert
        $this->assertSame([991002 => 2, 991001 => 1], $counts->all());
    }

    #[Test]
    public function getResolutionCountsPerMappingVersion_givenResolutionsOfOneVersion_countsOnlyThatVersion(): void
    {
        // Arrange
        $this->createResolution(-100.0, 192.0, 60.0);

        // Act
        $counts = $this->service->getResolutionCountsPerMappingVersion($this->dungeon);

        // Assert
        $this->assertSame(1, $counts->get($this->mappingVersion->id));
    }

    /**
     * The shortlist is there to open the routes behind a hot spot, so it has to name several of them. Taking the worst
     * rows and reducing them to routes afterwards would let one route that is off in several places fill the list and
     * hide every other route with the same problem.
     */
    #[Test]
    public function getResolutionHeatmapData_givenOneRouteHoldingTheWorstMatches_stillListsTheOtherRoutes(): void
    {
        // Arrange - the first route is off five times, the other two once each, so a per record limit of 5 would
        // return the first route alone
        config(['keystoneguru.enemy_resolution.heatmap_min_samples' => 1]);

        $worstRoute   = $this->createDungeonRoute();
        $middleRoute  = $this->createDungeonRoute();
        $closestRoute = $this->createDungeonRoute();

        foreach ([95.0, 94.0, 93.0, 92.0, 91.0] as $distance) {
            $this->createResolution(-100.0, 192.0, $distance, npcId: 991010, dungeonRouteId: $worstRoute->id);
        }
        $this->createResolution(-100.0, 192.0, 80.0, npcId: 991010, dungeonRouteId: $middleRoute->id);
        $this->createResolution(-100.0, 192.0, 60.0, npcId: 991010, dungeonRouteId: $closestRoute->id);

        // Act
        $array = $this->service
            ->getResolutionHeatmapData($this->dungeon, $this->mappingVersion, [991010], EnemyResolutionHeatmapMetric::Average)
            ->setUseFacade(false)
            ->toArray();

        // Assert - every route is listed, worst first
        $publicKeys = array_column($array['dungeon_routes'], 'public_key');
        $this->assertSame([
            $worstRoute->public_key,
            $middleRoute->public_key,
            $closestRoute->public_key,
        ], $publicKeys);
    }

    private function createDungeonRoute(): DungeonRoute
    {
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $this->dungeon->id,
            'mapping_version_id' => $this->mappingVersion->id,
        ]);

        $this->createdDungeonRouteIds[] = $dungeonRoute->id;

        return $dungeonRoute;
    }

    private function createResolution(
        float  $lat,
        float  $lng,
        float  $weightedDistance,
        ?float $distance = null,
        ?int   $npcId = null,
        ?int   $dungeonRouteId = null,
    ): CombatLogRouteEnemyResolution {
        $resolution = CombatLogRouteEnemyResolution::create([
            'dungeon_id'         => $this->dungeon->id,
            'floor_id'           => $this->floor->id,
            'mapping_version_id' => $this->mappingVersion->id,
            'npc_id'             => $npcId,
            'dungeon_route_id'   => $dungeonRouteId,
            'enemy_id'           => 1,
            'lat'                => $lat,
            'lng'                => $lng,
            'enemy_lat'          => $lat + 1,
            'enemy_lng'          => $lng + 1,
            'distance'           => $distance ?? $weightedDistance,
            'weighted_distance'  => $weightedDistance,
        ]);

        $this->createdResolutionIds[] = $resolution->id;

        return $resolution;
    }

    /**
     * @param  array<string, mixed>             $array
     * @return array<int, array<string, mixed>>
     */
    private function latLngsOnFloor(array $array): array
    {
        /** @var array<int, array<string, mixed>> $data */
        $data      = $array['data'];
        $floorData = collect($data)->firstWhere('floor_id', $this->floor->id);

        return $floorData === null ? [] : $floorData['lat_lngs'];
    }
}
