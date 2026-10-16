<?php

namespace Tests\Feature\Controller\Ajax;

use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonTransport;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Closure;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('DungeonTransport')]
#[Group('AjaxDungeonTransportController')]
final class AjaxDungeonTransportControllerTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function store_givenValidDungeonTransport_createsIt(): void
    {
        // Arrange
        $mappingVersion     = $this->getNonFacadeMappingVersion();
        $floorId            = $mappingVersion->dungeon->floors->first()->id;
        $partner            = $this->createTransport($mappingVersion);
        $targetDungeon      = Dungeon::query()->whereKeyNot($mappingVersion->dungeon_id)->firstOrFail();
        $dungeonTransportId = null;

        try {
            // Act
            $response = $this->post(route('ajax.admin.dungeontransport.create', ['mappingVersion' => $mappingVersion]), [
                'mapping_version_id'          => $mappingVersion->id,
                'floor_id'                    => $floorId,
                'map_icon_type_id'            => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_PORTAL_GREEN],
                'linked_dungeon_transport_id' => $partner->id,
                'target_dungeon_id'           => $targetDungeon->id,
                'link_key'                    => 'boat-ratchet-booty-bay',
                'lat'                         => -100.5,
                'lng'                         => 150.5,
                'comment'                     => 'To the docks',
            ]);

            // Assert
            $response->assertCreated();
            $dungeonTransportId = $response->json('id');

            /** @var DungeonTransport $dungeonTransport */
            $dungeonTransport = DungeonTransport::query()->findOrFail($dungeonTransportId);
            $this->assertSame($mappingVersion->id, $dungeonTransport->mapping_version_id);
            $this->assertSame($floorId, $dungeonTransport->floor_id);
            $this->assertSame(MapIconType::ALL[MapIconType::MAP_ICON_TYPE_PORTAL_GREEN], $dungeonTransport->map_icon_type_id);
            $this->assertSame($partner->id, $dungeonTransport->linked_dungeon_transport_id);
            $this->assertSame($targetDungeon->id, $dungeonTransport->target_dungeon_id);
            $this->assertSame('boat-ratchet-booty-bay', $dungeonTransport->link_key);
            $this->assertEqualsWithDelta(-100.5, $dungeonTransport->lat, 0.001);
            $this->assertEqualsWithDelta(150.5, $dungeonTransport->lng, 0.001);
            $this->assertSame('To the docks', $dungeonTransport->comment);
        } finally {
            if ($dungeonTransportId !== null) {
                DungeonTransport::query()->whereKey($dungeonTransportId)->delete();
            }

            $partner->delete();
        }
    }

    #[Test]
    public function store_givenThePlaceholderIdOfANewMapObject_createsItWithAGeneratedId(): void
    {
        // Arrange
        $mappingVersion     = $this->getNonFacadeMappingVersion();
        $dungeonTransportId = null;

        try {
            // Act
            $response = $this->postJson(route('ajax.admin.dungeontransport.create', ['mappingVersion' => $mappingVersion]), [
                ...$this->validAttributes($mappingVersion),
                'id' => -1,
            ]);

            // Assert
            $response->assertCreated();
            $dungeonTransportId = $response->json('id');
            $this->assertGreaterThan(0, $dungeonTransportId);
            $this->assertTrue(DungeonTransport::query()->whereKey($dungeonTransportId)->exists());
        } finally {
            if ($dungeonTransportId !== null) {
                DungeonTransport::query()->whereKey($dungeonTransportId)->delete();
            }
        }
    }

    #[Test]
    public function store_givenNoneSelected_clearsTheLinkTargetDungeonAndLinkKey(): void
    {
        // Arrange
        $mappingVersion   = $this->getNonFacadeMappingVersion();
        $partner          = $this->createTransport($mappingVersion);
        $dungeonTransport = $this->createTransport($mappingVersion, [
            'linked_dungeon_transport_id' => $partner->id,
            'target_dungeon_id'           => Dungeon::query()->whereKeyNot($mappingVersion->dungeon_id)->value('id'),
            'link_key'                    => 'boat-ratchet-booty-bay',
        ]);

        try {
            // Act
            $response = $this->put($this->updateRoute($mappingVersion, $dungeonTransport), [
                'mapping_version_id'          => $mappingVersion->id,
                'floor_id'                    => $dungeonTransport->floor_id,
                'map_icon_type_id'            => $dungeonTransport->map_icon_type_id,
                'linked_dungeon_transport_id' => -1,
                'target_dungeon_id'           => -1,
                'link_key'                    => '',
                'lat'                         => -110.0,
                'lng'                         => 160.0,
            ]);

            // Assert
            $response->assertOk();
            $dungeonTransport->refresh();
            $this->assertNull($dungeonTransport->linked_dungeon_transport_id);
            $this->assertNull($dungeonTransport->target_dungeon_id);
            $this->assertNull($dungeonTransport->link_key);
            $this->assertEqualsWithDelta(-110.0, $dungeonTransport->lat, 0.001);
        } finally {
            $dungeonTransport->delete();
            $partner->delete();
        }
    }

    #[Test]
    public function store_givenUnknownMapIconType_returnsValidationError(): void
    {
        // Arrange
        $mappingVersion = $this->getNonFacadeMappingVersion();
        $countBefore    = DungeonTransport::query()->count();

        // Act
        $response = $this->postJson(route('ajax.admin.dungeontransport.create', ['mappingVersion' => $mappingVersion]), [
            ...$this->validAttributes($mappingVersion),
            'map_icon_type_id' => (int)MapIconType::query()->max('id') + 1,
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('map_icon_type_id');
        $this->assertSame($countBefore, DungeonTransport::query()->count());
    }

    #[Test]
    public function store_givenLinkedTransportOfAnotherMappingVersion_returnsValidationError(): void
    {
        // Arrange
        $mappingVersion      = $this->getNonFacadeMappingVersion();
        $otherMappingVersion = MappingVersion::query()->whereKeyNot($mappingVersion->id)->with('dungeon.floors')->firstOrFail();
        $foreignTransport    = $this->createTransport($otherMappingVersion);
        $countBefore         = DungeonTransport::query()->count();

        try {
            // Act
            $response = $this->postJson(route('ajax.admin.dungeontransport.create', ['mappingVersion' => $mappingVersion]), [
                ...$this->validAttributes($mappingVersion),
                'linked_dungeon_transport_id' => $foreignTransport->id,
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('linked_dungeon_transport_id');
            $this->assertSame($countBefore, DungeonTransport::query()->count());
        } finally {
            $foreignTransport->delete();
        }
    }

    #[Test]
    public function store_givenBodyMappingVersionOfTheLinkedTransportButAnotherRouteMappingVersion_returnsValidationError(): void
    {
        // Arrange - the controller stores into the route's mapping version whatever the body says
        $mappingVersion      = $this->getNonFacadeMappingVersion();
        $otherMappingVersion = MappingVersion::query()->whereKeyNot($mappingVersion->id)->with('dungeon.floors')->firstOrFail();
        $foreignTransport    = $this->createTransport($otherMappingVersion);
        $countBefore         = DungeonTransport::query()->count();

        try {
            // Act
            $response = $this->postJson(route('ajax.admin.dungeontransport.create', ['mappingVersion' => $mappingVersion]), [
                ...$this->validAttributes($mappingVersion),
                'mapping_version_id'          => $otherMappingVersion->id,
                'linked_dungeon_transport_id' => $foreignTransport->id,
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('linked_dungeon_transport_id');
            $this->assertSame($countBefore, DungeonTransport::query()->count());
        } finally {
            $foreignTransport->delete();
        }
    }

    #[Test]
    public function store_givenLinkToItself_returnsValidationError(): void
    {
        // Arrange
        $mappingVersion   = $this->getNonFacadeMappingVersion();
        $dungeonTransport = $this->createTransport($mappingVersion);

        try {
            // Act
            $response = $this->putJson($this->updateRoute($mappingVersion, $dungeonTransport), [
                ...$this->validAttributes($mappingVersion),
                'id'                          => $dungeonTransport->id,
                'linked_dungeon_transport_id' => $dungeonTransport->id,
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('linked_dungeon_transport_id');
            $this->assertNull($dungeonTransport->refresh()->linked_dungeon_transport_id);
        } finally {
            $dungeonTransport->delete();
        }
    }

    #[Test]
    public function store_givenUnknownTargetDungeon_returnsValidationError(): void
    {
        // Arrange
        $mappingVersion = $this->getNonFacadeMappingVersion();
        $countBefore    = DungeonTransport::query()->count();

        // Act
        $response = $this->postJson(route('ajax.admin.dungeontransport.create', ['mappingVersion' => $mappingVersion]), [
            ...$this->validAttributes($mappingVersion),
            'target_dungeon_id' => (int)Dungeon::query()->max('id') + 1,
        ]);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('target_dungeon_id');
        $this->assertSame($countBefore, DungeonTransport::query()->count());
    }

    #[Test]
    public function store_givenPathWithoutVertexFloors_stampsThePostedFloorOnEveryVertex(): void
    {
        // Arrange
        $mappingVersion   = $this->getNonFacadeMappingVersion();
        $dungeonTransport = $this->createTransport($mappingVersion);

        try {
            // Act
            $response = $this->putJson($this->updateRoute($mappingVersion, $dungeonTransport), [
                ...$this->validAttributes($mappingVersion),
                'path_vertices_json' => json_encode([
                    ['lat' => -10.5, 'lng' => 20.5],
                    ['lat' => -30.5, 'lng' => 40.5],
                ]),
            ]);

            // Assert
            $response->assertOk();
            $floorId = $mappingVersion->dungeon->floors->first()->id;
            $this->assertSame([
                ['lat' => -10.5, 'lng' => 20.5, 'floor_id' => $floorId],
                ['lat' => -30.5, 'lng' => 40.5, 'floor_id' => $floorId],
            ], $dungeonTransport->refresh()->getPathVertices());
        } finally {
            $dungeonTransport->delete();
        }
    }

    #[Test]
    public function store_givenPathOnSeveralFloors_keepsEachVertexOnItsOwnFloor(): void
    {
        // Arrange
        [, $mappingVersion] = $this->findDungeon(facadeEnabled: false, minActiveFloors: 2);
        $mappingVersion->load('dungeon.floors');
        [$floorA, $floorB]  = $mappingVersion->dungeon->floors->take(2)->values()->all();
        $dungeonTransport   = $this->createTransport($mappingVersion);
        $pathVertices       = [
            ['lat' => -10.5, 'lng' => 20.5, 'floor_id' => $floorA->id],
            ['lat' => -30.5, 'lng' => 40.5, 'floor_id' => $floorB->id],
            ['lat' => -50.5, 'lng' => 60.5, 'floor_id' => $floorB->id],
        ];

        try {
            // Act
            $response = $this->putJson($this->updateRoute($mappingVersion, $dungeonTransport), [
                ...$this->validAttributes($mappingVersion),
                'path_vertices_json' => json_encode($pathVertices),
            ]);

            // Assert
            $response->assertOk();
            $this->assertSame($pathVertices, $dungeonTransport->refresh()->getPathVertices());
        } finally {
            $dungeonTransport->delete();
        }
    }

    #[Test]
    public function store_givenEmptyPath_clearsThePath(): void
    {
        // Arrange
        $mappingVersion   = $this->getNonFacadeMappingVersion();
        $dungeonTransport = $this->createTransport($mappingVersion, [
            'path_vertices_json' => $this->pathOnFirstFloor($mappingVersion),
        ]);

        try {
            // Act
            $response = $this->putJson($this->updateRoute($mappingVersion, $dungeonTransport), [
                ...$this->validAttributes($mappingVersion),
                'path_vertices_json' => '[]',
            ]);

            // Assert
            $response->assertOk();
            $this->assertNull($dungeonTransport->refresh()->path_vertices_json);
        } finally {
            $dungeonTransport->delete();
        }
    }

    #[Test]
    public function store_givenNoPath_keepsTheStoredPath(): void
    {
        // Arrange
        $mappingVersion   = $this->getNonFacadeMappingVersion();
        $pathVerticesJson = $this->pathOnFirstFloor($mappingVersion);
        $dungeonTransport = $this->createTransport($mappingVersion, [
            'path_vertices_json' => $pathVerticesJson,
        ]);

        try {
            // Act
            $response = $this->putJson($this->updateRoute($mappingVersion, $dungeonTransport), [
                ...$this->validAttributes($mappingVersion),
                'comment' => 'Renamed',
            ]);

            // Assert
            $response->assertOk();
            $dungeonTransport->refresh();
            $this->assertSame('Renamed', $dungeonTransport->comment);
            $this->assertSame($pathVerticesJson, $dungeonTransport->path_vertices_json);
        } finally {
            $dungeonTransport->delete();
        }
    }

    /**
     * @param Closure(MappingVersion): mixed $pathVerticesJson
     */
    #[Test]
    #[DataProvider('invalidPathProvider')]
    public function store_givenInvalidPath_returnsValidationError(Closure $pathVerticesJson, string $expectedErrorKey): void
    {
        // Arrange
        $mappingVersion   = $this->getNonFacadeMappingVersion();
        $dungeonTransport = $this->createTransport($mappingVersion);

        try {
            // Act
            $response = $this->putJson($this->updateRoute($mappingVersion, $dungeonTransport), [
                ...$this->validAttributes($mappingVersion),
                'path_vertices_json' => $pathVerticesJson($mappingVersion),
            ]);

            // Assert
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors($expectedErrorKey);
            $this->assertNull($dungeonTransport->refresh()->path_vertices_json);
        } finally {
            $dungeonTransport->delete();
        }
    }

    /**
     * @return array<string, array{Closure(MappingVersion): mixed, string}>
     */
    public static function invalidPathProvider(): array
    {
        $floorIdOfAnotherDungeon = static fn(MappingVersion $mappingVersion) => Floor::query()
            ->where('dungeon_id', '!=', $mappingVersion->dungeon_id)
            ->value('id');

        return [
            'not json' => [
                static fn() => 'not json',
                'path_vertices_json',
            ],
            'an object instead of a list' => [
                static fn() => json_encode(['a' => ['lat' => 1, 'lng' => 2], 'b' => ['lat' => 3, 'lng' => 4]]),
                'path_vertices_json',
            ],
            'a single vertex' => [
                static fn() => json_encode([['lat' => -10.5, 'lng' => 20.5]]),
                'path_vertices_json',
            ],
            'too many vertices' => [
                static fn() => json_encode(array_fill(0, DungeonTransport::PATH_VERTICES_MAX_COUNT + 1, ['lat' => -10.5, 'lng' => 20.5])),
                'path_vertices_json',
            ],
            'a vertex without lng' => [
                static fn() => json_encode([['lat' => -10.5, 'lng' => 20.5], ['lat' => -30.5]]),
                'path_vertices_json.1.lng',
            ],
            'a non-numeric lat' => [
                static fn() => json_encode([['lat' => -10.5, 'lng' => 20.5], ['lat' => 'north', 'lng' => 40.5]]),
                'path_vertices_json.1.lat',
            ],
            'an unknown vertex key' => [
                static fn() => json_encode([['lat' => -10.5, 'lng' => 20.5], ['lat' => -30.5, 'lng' => 40.5, 'z' => 1]]),
                'path_vertices_json.1',
            ],
            'a vertex on another dungeon\'s floor' => [
                static fn(MappingVersion $mappingVersion) => json_encode([
                    ['lat' => -10.5, 'lng' => 20.5],
                    ['lat' => -30.5, 'lng' => 40.5, 'floor_id' => $floorIdOfAnotherDungeon($mappingVersion)],
                ]),
                'path_vertices_json.1.floor_id',
            ],
        ];
    }

    #[Test]
    public function store_givenPathOnTheFacade_storesEachVertexOnItsRealFloorAndEchoesTheFacadeVertices(): void
    {
        // Arrange
        /** @var CoordinatesServiceInterface $coordinatesService */
        $coordinatesService = app(CoordinatesServiceInterface::class);
        /** @var array{0: Floor, 1: LatLng, 2: LatLng} $location */
        [, $mappingVersion, $location]           = $this->findDungeon(
            facadeEnabled: true,
            resolve:       static fn($dungeon, MappingVersion $mappingVersion) => self::findConvertibleFacadeLocation($coordinatesService, $mappingVersion),
        );
        [$facadeFloor, $facadeLatLng, $floorLatLng] = $location;
        $dungeonTransport                         = $this->createTransport($mappingVersion);
        $facadeVertex                             = [
            'lat'      => $facadeLatLng->getLat(DungeonTransport::PATH_VERTEX_PRECISION),
            'lng'      => $facadeLatLng->getLng(DungeonTransport::PATH_VERTEX_PRECISION),
            'floor_id' => $facadeFloor->id,
        ];

        try {
            // Act
            $response = $this->putJson($this->updateRoute($mappingVersion, $dungeonTransport), [
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $facadeFloor->id,
                'map_icon_type_id'   => $dungeonTransport->map_icon_type_id,
                'lat'                => $facadeLatLng->getLat(),
                'lng'                => $facadeLatLng->getLng(),
                'path_vertices_json' => json_encode([$facadeVertex, $facadeVertex]),
            ]);

            // Assert
            $response->assertOk();
            $echoedVertices = json_decode($response->json('path_vertices_json'), true);
            $this->assertCount(2, $echoedVertices);
            $this->assertSame($facadeFloor->id, $echoedVertices[0]['floor_id']);
            $this->assertEqualsWithDelta($facadeVertex['lat'], $echoedVertices[0]['lat'], 0.0001);
            $this->assertEqualsWithDelta($facadeVertex['lng'], $echoedVertices[0]['lng'], 0.0001);

            $storedVertices = $dungeonTransport->refresh()->getPathVertices();
            $this->assertCount(2, $storedVertices);
            $this->assertSame($floorLatLng->getFloor()->id, $storedVertices[0]['floor_id']);
            $this->assertEqualsWithDelta($floorLatLng->getLat(), $storedVertices[0]['lat'], 0.0001);
            $this->assertEqualsWithDelta($floorLatLng->getLng(), $storedVertices[0]['lng'], 0.0001);
        } finally {
            $dungeonTransport->delete();
        }
    }

    #[Test]
    public function delete_givenLinkedTransport_deletesItAndUnlinksItsPartners(): void
    {
        // Arrange
        $mappingVersion   = $this->getNonFacadeMappingVersion();
        $dungeonTransport = $this->createTransport($mappingVersion);
        $partner          = $this->createTransport($mappingVersion, ['linked_dungeon_transport_id' => $dungeonTransport->id]);
        $bystanderTarget  = $this->createTransport($mappingVersion);
        $bystander        = $this->createTransport($mappingVersion, ['linked_dungeon_transport_id' => $bystanderTarget->id]);

        try {
            // Act
            $response = $this->delete(route('ajax.admin.dungeontransport.delete', [
                'mappingVersion'   => $mappingVersion,
                'dungeonTransport' => $dungeonTransport,
            ]));

            // Assert
            $response->assertNoContent();
            $this->assertNull(DungeonTransport::query()->find($dungeonTransport->id));
            $this->assertNull($partner->refresh()->linked_dungeon_transport_id);
            $this->assertSame($bystanderTarget->id, $bystander->refresh()->linked_dungeon_transport_id);
        } finally {
            DungeonTransport::query()
                ->whereKey([$dungeonTransport->id, $partner->id, $bystanderTarget->id, $bystander->id])
                ->delete();
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createTransport(MappingVersion $mappingVersion, array $attributes = []): DungeonTransport
    {
        return DungeonTransport::factory()->create([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $mappingVersion->dungeon->floors->first()->id,
            ...$attributes,
        ]);
    }

    private function pathOnFirstFloor(MappingVersion $mappingVersion): string
    {
        $floorId = $mappingVersion->dungeon->floors->first()->id;

        return json_encode([
            ['lat' => -10.5, 'lng' => 20.5, 'floor_id' => $floorId],
            ['lat' => -30.5, 'lng' => 40.5, 'floor_id' => $floorId],
        ]);
    }

    /**
     * @return array{0: Floor, 1: LatLng, 2: LatLng}|null
     */
    private static function findConvertibleFacadeLocation(CoordinatesServiceInterface $coordinatesService, MappingVersion $mappingVersion): ?array
    {
        $mappingVersion->load(['floorUnions.floor', 'floorUnions.floorUnionAreas', 'dungeon.floors']);

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
                    return [$floorUnion->floor, $facadeLatLng, $floorLatLng];
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function validAttributes(MappingVersion $mappingVersion): array
    {
        return [
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $mappingVersion->dungeon->floors->first()->id,
            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_PORTAL_BLUE],
            'lat'                => -100.5,
            'lng'                => 150.5,
        ];
    }

    private function updateRoute(MappingVersion $mappingVersion, DungeonTransport $dungeonTransport): string
    {
        return route('ajax.admin.dungeontransport.update', [
            'mappingVersion'   => $mappingVersion,
            'dungeonTransport' => $dungeonTransport,
        ]);
    }

    private function getNonFacadeMappingVersion(): MappingVersion
    {
        /** @var MappingVersion|null $mappingVersion */
        $mappingVersion = MappingVersion::query()
            ->where('facade_enabled', false)
            ->whereHas('dungeon', static fn($query) => $query->where('active', true))
            ->with('dungeon.floors')
            ->orderByDesc('id')
            ->first();

        if ($mappingVersion === null || !$mappingVersion->dungeon instanceof Dungeon) {
            $this->fail('No non-facade mapping version of an active dungeon found for testing.');
        }

        return $mappingVersion;
    }
}
