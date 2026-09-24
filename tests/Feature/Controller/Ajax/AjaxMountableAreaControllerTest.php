<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\MountableArea;
use App\Models\Polyline;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('MountableArea')]
final class AjaxMountableAreaControllerTest extends AjaxPublicTestCase
{
    use ProvidesDungeon;

    private const VERTICES_JSON = '[{"lat":-100.5,"lng":100.5},{"lat":-120.5,"lng":120.5},{"lat":-110.25,"lng":130.75}]';

    private MappingVersion $mappingVersion;

    private Floor $floor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // The endpoints under test are admin-only
        $this->be(User::findOrFail(1));

        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, challengeMode: true, minEnemies: 1);

        $this->mappingVersion = $mappingVersion;

        /** @var Floor $floor */
        $floor       = $dungeon->floors()->where('facade', false)->firstOrFail();
        $this->floor = $floor;
    }

    #[Test]
    public function store_givenNewMountableArea_createsTheAreaWithItsOwnPolylineAndMirrorsItsVertices(): void
    {
        // Arrange
        $mountableAreaId = null;

        try {
            // Act
            $response = $this->post($this->createUrl(), $this->payload(150, self::VERTICES_JSON));

            // Assert
            $response->assertCreated();
            $responseArr     = json_decode($response->content(), true);
            $mountableAreaId = $responseArr['id'];
            $this->assertSame(150, $responseArr['speed']);
            $this->assertSame(MountableArea::DEFAULT_COLOR, $responseArr['polyline']['color']);
            $this->assertSame(self::VERTICES_JSON, $responseArr['polyline']['vertices_json']);
            $this->assertArrayNotHasKey('vertices_json', $responseArr);

            /** @var MountableArea $storedMountableArea */
            $storedMountableArea = MountableArea::query()->findOrFail($mountableAreaId);
            $this->assertSame($this->floor->id, $storedMountableArea->floor_id);
            $this->assertSame(self::VERTICES_JSON, $storedMountableArea->getRawOriginal('vertices_json'));

            /** @var Polyline $storedPolyline */
            $storedPolyline = Polyline::query()->findOrFail($storedMountableArea->polyline_id);
            $this->assertSame(MountableArea::class, $storedPolyline->model_class);
            $this->assertSame($mountableAreaId, $storedPolyline->model_id);
            $this->assertSame(MountableArea::DEFAULT_COLOR, $storedPolyline->color);
            $this->assertSame(MountableArea::DEFAULT_WEIGHT, $storedPolyline->weight);
            $this->assertSame(self::VERTICES_JSON, $storedPolyline->vertices_json);
        } finally {
            $this->deleteMountableArea($mountableAreaId);
        }
    }

    #[Test]
    public function store_givenExistingMountableArea_updatesItsPolylineInPlaceAndMirrorsItsVertices(): void
    {
        // Arrange
        $mountableAreaId = json_decode($this->post($this->createUrl(), $this->payload(null, self::VERTICES_JSON))->content(), true)['id'];
        /** @var MountableArea $mountableArea */
        $mountableArea = MountableArea::query()->findOrFail($mountableAreaId);
        $polylineId    = $mountableArea->polyline_id;

        $newVerticesJson = '[{"lat":-10,"lng":10},{"lat":-20,"lng":20},{"lat":-15,"lng":30}]';

        try {
            // Act
            $response = $this->put(
                route('ajax.admin.mountablearea.update', ['mappingVersion' => $this->mappingVersion, 'mountableArea' => $mountableArea]),
                $this->payload(200, $newVerticesJson),
            );

            // Assert
            $response->assertOk();

            /** @var MountableArea $storedMountableArea */
            $storedMountableArea = MountableArea::query()->findOrFail($mountableAreaId);
            $this->assertSame(200, $storedMountableArea->speed);
            $this->assertSame($polylineId, $storedMountableArea->polyline_id);
            $this->assertSame(1, Polyline::query()->where('model_class', MountableArea::class)->where('model_id', $mountableAreaId)->count());
            $this->assertSame($newVerticesJson, Polyline::query()->findOrFail($polylineId)->vertices_json);
            $this->assertSame($newVerticesJson, $storedMountableArea->getRawOriginal('vertices_json'));
        } finally {
            $this->deleteMountableArea($mountableAreaId);
        }
    }

    #[Test]
    public function store_givenFewerThanTwoVertices_returnsValidationError(): void
    {
        // Arrange
        $mountableAreaCount = MountableArea::query()->count();

        // Act
        $response = $this->postJson($this->createUrl(), $this->payload(null, '[{"lat":-10,"lng":10}]'));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['polyline.vertices_json']);
        $this->assertSame($mountableAreaCount, MountableArea::query()->count());
    }

    #[Test]
    public function store_givenNoPolyline_returnsValidationError(): void
    {
        // Arrange
        $mountableAreaCount = MountableArea::query()->count();
        $payload            = $this->payload(null, self::VERTICES_JSON);
        unset($payload['polyline']);

        // Act
        $response = $this->postJson($this->createUrl(), $payload);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['polyline.vertices_json']);
        $this->assertSame($mountableAreaCount, MountableArea::query()->count());
    }

    #[Test]
    public function delete_givenAnExistingMountableArea_deletesItAndItsPolyline(): void
    {
        // Arrange
        $mountableAreaId = json_decode($this->post($this->createUrl(), $this->payload(null, self::VERTICES_JSON))->content(), true)['id'];
        /** @var MountableArea $mountableArea */
        $mountableArea = MountableArea::query()->findOrFail($mountableAreaId);
        $polylineId    = $mountableArea->polyline_id;

        try {
            // Act
            $response = $this->delete(route('ajax.admin.mountablearea.delete', [
                'mappingVersion' => $this->mappingVersion,
                'mountableArea'  => $mountableArea,
            ]));

            // Assert
            $response->assertNoContent();
            $this->assertNull(MountableArea::find($mountableAreaId));
            $this->assertNull(Polyline::find($polylineId));
        } finally {
            $this->deleteMountableArea($mountableAreaId);
        }
    }

    private function createUrl(): string
    {
        return route('ajax.admin.mountablearea.create', ['mappingVersion' => $this->mappingVersion]);
    }

    /** @return array<string, mixed> */
    private function payload(?int $speed, string $verticesJson): array
    {
        return [
            'id'                 => 0,
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $this->floor->id,
            'speed'              => $speed,
            'polyline'           => [
                'color'          => MountableArea::DEFAULT_COLOR,
                'color_animated' => null,
                'weight'         => MountableArea::DEFAULT_WEIGHT,
                'vertices_json'  => $verticesJson,
            ],
        ];
    }

    private function deleteMountableArea(?int $mountableAreaId): void
    {
        if ($mountableAreaId === null) {
            return;
        }

        Polyline::query()->where('model_class', MountableArea::class)->where('model_id', $mountableAreaId)->delete();
        MountableArea::query()->whereKey($mountableAreaId)->delete();
    }
}
