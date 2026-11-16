<?php

namespace Tests\Feature\Controller\Ajax;

use App\Models\DungeonFloorSwitchMarker;
use App\Models\DungeonStart;
use App\Models\DungeonTransport;
use App\Models\Enemy;
use App\Models\EnemyForcesCheckpoint;
use App\Models\EnemyPack;
use App\Models\EnemyPatrol;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use App\Models\MapIcon;
use App\Models\Mapping\MappingVersion;
use App\Models\MountableArea;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\AjaxPublicTestCase;

#[Group('Controller')]
#[Group('MappingVersion')]
final class AjaxMappingVersionChildScopeTest extends AjaxPublicTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // The endpoints under test are admin-only
        $this->be(User::findOrFail(1));
    }

    /**
     * @param class-string<Model> $modelClass
     */
    #[Test]
    #[DataProvider('mappingModelProvider')]
    public function update_givenAMappingObjectOfItsOwnMappingVersion_reachesValidation(string $urlSegment, string $modelClass): void
    {
        // Arrange
        $model = $this->findSeededModel($modelClass);

        // Act
        $response = $this->putJson($this->childUrl($model->getAttribute('mapping_version_id'), $urlSegment, $model->getKey()));

        // Assert
        $response->assertUnprocessable();
    }

    /**
     * @param class-string<Model> $modelClass
     */
    #[Test]
    #[DataProvider('mappingModelProvider')]
    public function update_givenAMappingObjectOfAnotherMappingVersion_returnsNotFound(string $urlSegment, string $modelClass): void
    {
        // Arrange
        $model               = $this->findSeededModel($modelClass);
        $ownMappingVersionId = $model->getAttribute('mapping_version_id');
        $otherMappingVersion = $this->findOtherMappingVersion($ownMappingVersionId);

        // Act
        $response = $this->putJson($this->childUrl($otherMappingVersion->id, $urlSegment, $model->getKey()));

        // Assert
        $response->assertNotFound();
        $this->assertSame($ownMappingVersionId, $modelClass::query()->findOrFail($model->getKey())->getAttribute('mapping_version_id'));
    }

    /**
     * @param class-string<Model> $modelClass
     */
    #[Test]
    #[DataProvider('mappingModelProvider')]
    public function delete_givenAMappingObjectOfAnotherMappingVersion_returnsNotFoundAndKeepsIt(string $urlSegment, string $modelClass): void
    {
        // Arrange
        $model               = $this->replicateSeededModel($modelClass);
        $otherMappingVersion = $this->findOtherMappingVersion($model->getAttribute('mapping_version_id'));

        try {
            // Act
            $response = $this->deleteJson($this->childUrl($otherMappingVersion->id, $urlSegment, $model->getKey()));

            // Assert
            $response->assertNotFound();
            $this->assertTrue($modelClass::query()->whereKey($model->getKey())->exists());
        } finally {
            $modelClass::query()->whereKey($model->getKey())->delete();
        }
    }

    /**
     * @return array<string, array{0: string, 1: class-string<Model>}>
     */
    public static function mappingModelProvider(): array
    {
        return [
            'enemy'                   => ['enemy', Enemy::class],
            'enemy pack'              => ['enemypack', EnemyPack::class],
            'enemy patrol'            => ['enemypatrol', EnemyPatrol::class],
            'dungeon floor switch'    => ['dungeonfloorswitchmarker', DungeonFloorSwitchMarker::class],
            'dungeon start'           => ['dungeonstart', DungeonStart::class],
            'dungeon transport'       => ['dungeontransport', DungeonTransport::class],
            'map icon'                => ['mapicon', MapIcon::class],
            'mountable area'          => ['mountablearea', MountableArea::class],
            'enemy forces checkpoint' => ['enemyforcescheckpoint', EnemyForcesCheckpoint::class],
            'floor union'             => ['floorunion', FloorUnion::class],
            'floor union area'        => ['floorunionarea', FloorUnionArea::class],
        ];
    }

    #[Test]
    public function update_givenAMapIconOfADungeonRoute_returnsNotFound(): void
    {
        // Arrange
        $mapIcon        = $this->createDungeonRouteMapIcon();
        $mappingVersion = MappingVersion::query()->firstOrFail();

        try {
            // Act
            $response = $this->putJson($this->childUrl($mappingVersion->id, 'mapicon', $mapIcon->id));

            // Assert
            $response->assertNotFound();
            $this->assertNull(MapIcon::query()->findOrFail($mapIcon->id)->mapping_version_id);
        } finally {
            MapIcon::query()->whereKey($mapIcon->id)->delete();
        }
    }

    #[Test]
    public function delete_givenAMapIconOfADungeonRoute_returnsNotFoundAndKeepsIt(): void
    {
        // Arrange
        $mapIcon        = $this->createDungeonRouteMapIcon();
        $mappingVersion = MappingVersion::query()->firstOrFail();

        try {
            // Act
            $response = $this->deleteJson($this->childUrl($mappingVersion->id, 'mapicon', $mapIcon->id));

            // Assert
            $response->assertNotFound();
            $this->assertTrue(MapIcon::query()->whereKey($mapIcon->id)->exists());
        } finally {
            MapIcon::query()->whereKey($mapIcon->id)->delete();
        }
    }

    private function childUrl(int $mappingVersionId, string $urlSegment, int $childId): string
    {
        return sprintf('/ajax/admin/mappingVersion/%d/%s/%d', $mappingVersionId, $urlSegment, $childId);
    }

    /**
     * @param class-string<Model> $modelClass
     */
    private function findSeededModel(string $modelClass): Model
    {
        return $modelClass::query()->whereNotNull('mapping_version_id')->orderBy('id')->firstOrFail();
    }

    /**
     * A throwaway copy of a seeded row in the same mapping version, so a delete that wrongly goes
     * through cannot remove seeded mapping data. It owns no polyline, so the polyline models' deleting
     * hooks cannot remove the seeded row's polyline either.
     *
     * @param class-string<Model> $modelClass
     */
    private function replicateSeededModel(string $modelClass): Model
    {
        $model = $this->findSeededModel($modelClass)->replicate();

        if ($model->getAttribute('polyline_id') !== null) {
            $model->setAttribute('polyline_id', null);
        }

        $model->saveQuietly();

        return $model;
    }

    private function findOtherMappingVersion(int $mappingVersionId): MappingVersion
    {
        return MappingVersion::query()->whereKeyNot($mappingVersionId)->orderBy('id')->firstOrFail();
    }

    private function createDungeonRouteMapIcon(): MapIcon
    {
        /** @var Floor $floor */
        $floor = Floor::query()->firstOrFail();

        return MapIcon::factory()->create([
            'mapping_version_id' => null,
            'floor_id'           => $floor->id,
        ]);
    }
}
