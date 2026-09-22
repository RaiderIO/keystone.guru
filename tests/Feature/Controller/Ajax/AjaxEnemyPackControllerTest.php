<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\EnemyPack;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Polyline;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('EnemyPack')]
final class AjaxEnemyPackControllerTest extends AjaxPublicTestCase
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
    public function store_givenNewEnemyPack_createsThePackWithItsOwnPolyline(): void
    {
        // Arrange
        $enemyPackId = null;

        try {
            // Act
            $response = $this->post($this->createUrl(), $this->payload('#ff002b', self::VERTICES_JSON));

            // Assert
            $response->assertCreated();
            $responseArr = json_decode($response->content(), true);
            $enemyPackId = $responseArr['id'];
            $this->assertSame('#ff002b', $responseArr['polyline']['color']);
            $this->assertSame(self::VERTICES_JSON, $responseArr['polyline']['vertices_json']);
            $this->assertArrayNotHasKey('vertices_json', $responseArr);

            /** @var EnemyPack $storedEnemyPack */
            $storedEnemyPack = EnemyPack::query()->findOrFail($enemyPackId);
            $this->assertSame($this->floor->id, $storedEnemyPack->floor_id);
            $this->assertNull($storedEnemyPack->getRawOriginal('vertices_json'));

            /** @var Polyline $storedPolyline */
            $storedPolyline = Polyline::query()->findOrFail($storedEnemyPack->polyline_id);
            $this->assertSame(EnemyPack::class, $storedPolyline->model_class);
            $this->assertSame($enemyPackId, $storedPolyline->model_id);
            $this->assertSame('#ff002b', $storedPolyline->color);
            $this->assertSame(EnemyPack::DEFAULT_WEIGHT, $storedPolyline->weight);
            $this->assertSame(self::VERTICES_JSON, $storedPolyline->vertices_json);
        } finally {
            $this->deleteEnemyPack($enemyPackId);
        }
    }

    #[Test]
    public function store_givenExistingEnemyPack_updatesItsPolylineInPlace(): void
    {
        // Arrange
        $enemyPackId = json_decode($this->post($this->createUrl(), $this->payload('#ff002b', self::VERTICES_JSON))->content(), true)['id'];
        /** @var EnemyPack $enemyPack */
        $enemyPack  = EnemyPack::query()->findOrFail($enemyPackId);
        $polylineId = $enemyPack->polyline_id;

        $newVerticesJson = '[{"lat":-10,"lng":10},{"lat":-20,"lng":20},{"lat":-15,"lng":30}]';

        try {
            // Act
            $response = $this->put(
                route('ajax.admin.enemypack.update', ['mappingVersion' => $this->mappingVersion, 'enemyPack' => $enemyPack]),
                $this->payload('#25e433', $newVerticesJson),
            );

            // Assert
            $response->assertOk();

            /** @var EnemyPack $storedEnemyPack */
            $storedEnemyPack = EnemyPack::query()->findOrFail($enemyPackId);
            $this->assertSame($polylineId, $storedEnemyPack->polyline_id);
            $this->assertSame(1, Polyline::query()->where('model_class', EnemyPack::class)->where('model_id', $enemyPackId)->count());

            /** @var Polyline $storedPolyline */
            $storedPolyline = Polyline::query()->findOrFail($polylineId);
            $this->assertSame('#25e433', $storedPolyline->color);
            $this->assertSame($newVerticesJson, $storedPolyline->vertices_json);
        } finally {
            $this->deleteEnemyPack($enemyPackId);
        }
    }

    #[Test]
    public function store_givenFewerThanTwoVertices_returnsValidationError(): void
    {
        // Arrange
        $enemyPackCount = EnemyPack::query()->count();

        // Act
        $response = $this->postJson($this->createUrl(), $this->payload('#ff002b', '[{"lat":-10,"lng":10}]'));

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['polyline.vertices_json']);
        $this->assertSame($enemyPackCount, EnemyPack::query()->count());
    }

    #[Test]
    public function store_givenNoPolyline_returnsValidationError(): void
    {
        // Arrange
        $enemyPackCount = EnemyPack::query()->count();
        $payload        = $this->payload('#ff002b', self::VERTICES_JSON);
        unset($payload['polyline']);

        // Act
        $response = $this->postJson($this->createUrl(), $payload);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['polyline.vertices_json']);
        $this->assertSame($enemyPackCount, EnemyPack::query()->count());
    }

    #[Test]
    public function delete_givenAnExistingEnemyPack_deletesItAndItsPolyline(): void
    {
        // Arrange
        $enemyPackId = json_decode($this->post($this->createUrl(), $this->payload('#ff002b', self::VERTICES_JSON))->content(), true)['id'];
        /** @var EnemyPack $enemyPack */
        $enemyPack  = EnemyPack::query()->findOrFail($enemyPackId);
        $polylineId = $enemyPack->polyline_id;

        try {
            // Act
            $response = $this->delete(route('ajax.admin.enemypack.delete', [
                'mappingVersion' => $this->mappingVersion,
                'enemyPack'      => $enemyPack,
            ]));

            // Assert
            $response->assertNoContent();
            $this->assertNull(EnemyPack::find($enemyPackId));
            $this->assertNull(Polyline::find($polylineId));
        } finally {
            $this->deleteEnemyPack($enemyPackId);
        }
    }

    private function createUrl(): string
    {
        return route('ajax.admin.enemypack.create', ['mappingVersion' => $this->mappingVersion]);
    }

    /** @return array<string, mixed> */
    private function payload(string $color, string $verticesJson): array
    {
        return [
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $this->floor->id,
            'group'              => null,
            'teeming'            => null,
            'faction'            => 'any',
            'label'              => 'Enemy pack',
            'polyline'           => [
                'color'          => $color,
                'color_animated' => null,
                'weight'         => EnemyPack::DEFAULT_WEIGHT,
                'vertices_json'  => $verticesJson,
            ],
        ];
    }

    private function deleteEnemyPack(?int $enemyPackId): void
    {
        if ($enemyPackId === null) {
            return;
        }

        Polyline::query()->where('model_class', EnemyPack::class)->where('model_id', $enemyPackId)->delete();
        EnemyPack::query()->whereKey($enemyPackId)->delete();
    }
}
