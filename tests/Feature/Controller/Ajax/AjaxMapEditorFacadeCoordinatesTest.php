<?php

namespace Tests\Feature\Controller\Ajax;

use App\Logic\Structs\LatLng;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\EnemyPatrol;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Path;
use App\Models\Polyline;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Fixtures\PolylineFixtures;
use Tests\Feature\Traits\GeneratesDungeonRoutes;
use Tests\TestCases\AjaxPublicTestCase;

/**
 * The map editor posts facade coordinates whenever the user drew on the facade floor - the floor id
 * it sends is the facade floor's. AjaxMappingModelBaseController::storeModel() converts those onto
 * the floor they actually belong to before writing, and echoes the facade coordinates back so the
 * client that drew them can still place what it gets in return.
 */
#[Group('Controller')]
#[Group('MapIcon')]
final class AjaxMapEditorFacadeCoordinatesTest extends AjaxPublicTestCase
{
    use GeneratesDungeonRoutes;

    #[Test]
    public function store_givenMapIconOnFacadeFloor_savesOnRealFloorAndEchoesFacadeCoordinates(): void
    {
        // Arrange
        [$mappingVersion, $facadeFloor, $facadeLatLng, $expectedFloor] = $this->findConvertibleFacadeLocation();

        $dungeonRoute = $this->createFacadeDungeonRoute($mappingVersion);

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/mapicon', $dungeonRoute->getRouteKey()), $this->mapIconPayload($facadeFloor, $facadeLatLng));

            // Assert - the client gets back exactly what it drew, on the floor it drew it on
            $response->assertCreated();
            $responseArr = json_decode($response->content(), true);
            $this->assertEquals($facadeFloor->id, $responseArr['floor_id']);
            $this->assertEqualsWithDelta($facadeLatLng->getLat(), $responseArr['lat'], 0.0001);
            $this->assertEqualsWithDelta($facadeLatLng->getLng(), $responseArr['lng'], 0.0001);

            // ... while what was stored sits on the real floor, at converted coordinates
            /** @var MapIcon $storedMapIcon */
            $storedMapIcon = MapIcon::query()->findOrFail($responseArr['id']);
            $this->assertEquals($expectedFloor->id, $storedMapIcon->floor_id);
            $this->assertNotEqualsWithDelta($facadeLatLng->getLat(), $storedMapIcon->lat, 0.0001);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function store_givenMapIconOnRealFloor_storesCoordinatesVerbatim(): void
    {
        // Arrange
        [$mappingVersion, , , $expectedFloor] = $this->findConvertibleFacadeLocation();

        $dungeonRoute = $this->createFacadeDungeonRoute($mappingVersion);
        $latLng       = new LatLng(-100.5, 100.5, $expectedFloor);

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/mapicon', $dungeonRoute->getRouteKey()), $this->mapIconPayload($expectedFloor, $latLng));

            // Assert
            $response->assertCreated();
            $responseArr = json_decode($response->content(), true);

            /** @var MapIcon $storedMapIcon */
            $storedMapIcon = MapIcon::query()->findOrFail($responseArr['id']);
            $this->assertEquals($expectedFloor->id, $storedMapIcon->floor_id);
            $this->assertEqualsWithDelta($latLng->getLat(), $storedMapIcon->lat, 0.0001);
            $this->assertEqualsWithDelta($latLng->getLng(), $storedMapIcon->lng, 0.0001);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function store_givenPathOnFacadeFloor_savesVerticesOnRealFloorAndEchoesFacadeVertices(): void
    {
        // Arrange
        [$mappingVersion, $facadeFloor, $facadeLatLng, $expectedFloor] = $this->findConvertibleFacadeLocation();

        $dungeonRoute = $this->createFacadeDungeonRoute($mappingVersion);
        $polyline     = PolylineFixtures::createPolyline($facadeFloor, collect([$facadeLatLng, $facadeLatLng]));

        try {
            // Act
            $response = $this->post(route('ajax.dungeonroute.path.create', ['dungeonRoute' => $dungeonRoute]), [
                'floor_id' => $facadeFloor->id,
                'polyline' => $polyline,
            ]);

            // Assert - the client gets its own facade vertices back on the facade floor
            $response->assertCreated();
            $responseArr = json_decode($response->content(), true);
            $this->assertEquals($facadeFloor->id, $responseArr['floor_id']);
            $this->assertEquals($polyline['vertices_json'], $responseArr['polyline']['vertices_json']);

            // ... while the stored path and its vertices sit on the real floor
            /** @var Path $storedPath */
            $storedPath = Path::query()->findOrFail($responseArr['id']);
            $this->assertEquals($expectedFloor->id, $storedPath->floor_id);

            /** @var Polyline $storedPolyline */
            $storedPolyline = Polyline::query()->findOrFail($storedPath->polyline_id);
            $this->assertNotEquals($polyline['vertices_json'], $storedPolyline->vertices_json);
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * Whether the coordinates need converting is decided by the floor they came in on, not by the
     * acting user's map facade style preference. A client that drew on the facade floor while its
     * user preference says split floors used to have its facade coordinates stored verbatim, which
     * put the object nowhere near where it was drawn.
     */
    #[Test]
    public function store_givenFacadeFloorAndSplitFloorsUserPreference_stillConvertsToTheRealFloor(): void
    {
        // Arrange
        [$mappingVersion, $facadeFloor, $facadeLatLng, $expectedFloor] = $this->findConvertibleFacadeLocation();

        $dungeonRoute = $this->createFacadeDungeonRoute($mappingVersion);

        // A persisted preference rather than User::forceMapFacadeStyle(), which the
        // ResetsMapFacadeStyleOverride middleware clears at the start of the request under test
        $user             = User::findOrFail(1);
        $originalMapStyle = $user->map_facade_style;
        $user->update(['map_facade_style' => User::MAP_FACADE_STYLE_SPLIT_FLOORS]);
        // The authenticated instance is what getCurrentUserMapFacadeStyle() reads, so re-auth it
        $this->actingAs($user->fresh());

        try {
            // Act
            $response = $this->post(sprintf('/ajax/%s/mapicon', $dungeonRoute->getRouteKey()), $this->mapIconPayload($facadeFloor, $facadeLatLng));

            // Assert
            $response->assertCreated();

            /** @var MapIcon $storedMapIcon */
            $storedMapIcon = MapIcon::query()->findOrFail(json_decode($response->content(), true)['id']);
            $this->assertEquals($expectedFloor->id, $storedMapIcon->floor_id);
        } finally {
            $user->update(['map_facade_style' => $originalMapStyle]);

            $dungeonRoute->delete();
        }
    }

    /**
     * The admin map editor never renders the facade, so it posts real floors even for a mapping
     * version that has one. Those coordinates must be stored exactly as they came in - the endpoint
     * used to force the mapping version's facade_enabled to false to guarantee that.
     */
    #[Test]
    public function store_givenEnemyPatrolOnRealFloorOfFacadeMappingVersion_storesVerticesVerbatim(): void
    {
        // Arrange
        [$mappingVersion, , , $realFloor] = $this->findConvertibleFacadeLocation();

        $verticesJson  = json_encode([['lat' => -100.5, 'lng' => 100.5], ['lat' => -120.5, 'lng' => 120.5]]);
        $enemyPatrolId = null;

        try {
            // Act
            $response = $this->post(route('ajax.admin.enemypatrol.create', ['mappingVersion' => $mappingVersion]), [
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $realFloor->id,
                'teeming'            => null,
                'faction'            => 'any',
                'polyline'           => [
                    'color'          => '#f00000',
                    'color_animated' => null,
                    'weight'         => 2,
                    'vertices_json'  => $verticesJson,
                ],
            ]);

            // Assert
            $response->assertCreated();
            $enemyPatrolId = json_decode($response->content(), true)['id'];

            /** @var EnemyPatrol $storedEnemyPatrol */
            $storedEnemyPatrol = EnemyPatrol::query()->findOrFail($enemyPatrolId);
            $this->assertEquals($realFloor->id, $storedEnemyPatrol->floor_id);

            /** @var Polyline $storedPolyline */
            $storedPolyline = Polyline::query()->findOrFail($storedEnemyPatrol->polyline_id);
            $this->assertEquals($verticesJson, $storedPolyline->vertices_json);
        } finally {
            if ($enemyPatrolId !== null) {
                Polyline::query()->where('model_id', $enemyPatrolId)->where('model_class', EnemyPatrol::class)->delete();
                EnemyPatrol::query()->whereKey($enemyPatrolId)->delete();
            }
        }
    }

    /**
     * A floor union positions a floor onto the facade floor, so its own floor_id IS the facade floor
     * and its lat/lng are facade coordinates. Converting them would move every floor it places.
     */
    #[Test]
    public function store_givenFloorUnionOnFacadeFloor_storesCoordinatesVerbatim(): void
    {
        // Arrange
        [$mappingVersion, $facadeFloor, $facadeLatLng, $expectedFloor] = $this->findConvertibleFacadeLocation();

        $floorUnionId = null;

        try {
            // Act
            $response = $this->post(sprintf('/ajax/admin/mappingVersion/%d/floorunion', $mappingVersion->id), [
                'id'                 => -1,
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $facadeFloor->id,
                'target_floor_id'    => $expectedFloor->id,
                'lat'                => $facadeLatLng->getLat(),
                'lng'                => $facadeLatLng->getLng(),
                'size'               => 100,
                'rotation'           => 0,
            ]);

            // Assert
            $response->assertCreated();
            $floorUnionId = json_decode($response->content(), true)['id'];

            /** @var FloorUnion $storedFloorUnion */
            $storedFloorUnion = FloorUnion::query()->findOrFail($floorUnionId);
            $this->assertEquals($facadeFloor->id, $storedFloorUnion->floor_id);
            // The column itself only holds 2 decimals - a conversion would move it far further than that
            $this->assertEqualsWithDelta($facadeLatLng->getLat(), $storedFloorUnion->lat, 0.01);
            $this->assertEqualsWithDelta($facadeLatLng->getLng(), $storedFloorUnion->lng, 0.01);
        } finally {
            if ($floorUnionId !== null) {
                FloorUnion::query()->whereKey($floorUnionId)->delete();
            }
        }
    }

    /**
     * Finds a seeded facade mapping version together with a facade lat/lng that genuinely converts
     * onto another floor. A facade point outside every floor union area converts to itself, which
     * would make these tests assert nothing.
     *
     * @return array{0: MappingVersion, 1: Floor, 2: LatLng, 3: Floor}
     */
    private function findConvertibleFacadeLocation(): array
    {
        /** @var CoordinatesServiceInterface $coordinatesService */
        $coordinatesService = app(CoordinatesServiceInterface::class);

        return $this->findDungeon(
            facadeEnabled: true,
            resolve:       static function ($dungeon, MappingVersion $mappingVersion) use ($coordinatesService) {
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

                        // The centroid of the area is the point most likely to sit inside it
                        $facadeLatLng = new LatLng(
                            (float)$vertices->avg(static fn(LatLng $latLng) => $latLng->getLat()),
                            (float)$vertices->avg(static fn(LatLng $latLng) => $latLng->getLng()),
                            $floorUnion->floor,
                        );

                        $converted = $coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $facadeLatLng);

                        if ($converted->getFloor()?->id !== $floorUnion->floor_id) {
                            return [$mappingVersion, $floorUnion->floor, $facadeLatLng, $converted->getFloor()];
                        }
                    }
                }

                return null;
            },
        )[2];
    }

    private function createFacadeDungeonRoute(MappingVersion $mappingVersion): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function mapIconPayload(Floor $floor, LatLng $latLng): array
    {
        return [
            'floor_id'                   => $floor->id,
            'lat'                        => $latLng->getLat(),
            'lng'                        => $latLng->getLng(),
            'map_icon_type_id'           => MapIconType::query()->firstOrFail()->id,
            'team_id'                    => null,
            'linked_awakened_obelisk_id' => null,
            'comment'                    => 'Facade coordinate test',
            'permanent_tooltip'          => false,
            'seasonal_index'             => null,
        ];
    }
}
