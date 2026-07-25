<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Enemy;
use App\Models\Floor\FloorUnion;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
final class MappingVersionFloorEnemyForcesAnchorsTest extends PublicTestCase
{
    #[Test]
    public function mapContextFloorEnemyForcesAnchors_givenSplitFloorsLayout_returnsNoAnchors(): void
    {
        // Arrange
        $mappingVersion = $this->getFacadeMappingVersionWithFloorUnions();

        // Act
        $anchors = $mappingVersion->mapContextFloorEnemyForcesAnchors($this->getCoordinatesService(), false);

        // Assert - only the facade draws every floor at once, so there is nothing to anchor otherwise
        $this->assertCount(0, $anchors);
    }

    #[Test]
    public function mapContextFloorEnemyForcesAnchors_givenFacadeWithFloorUnions_anchorsEveryFloorThatHasEnemies(): void
    {
        // Arrange
        $mappingVersion = $this->getFacadeMappingVersionWithFloorUnions();
        $floorIds       = $this->getFloorIdsWithEnemies($mappingVersion);

        // Act
        $anchors = $mappingVersion->mapContextFloorEnemyForcesAnchors($this->getCoordinatesService(), true);

        // Assert - a floor whose centroid falls in a gap between its union areas must still be anchored
        // (via the largest-cluster fallback) rather than silently dropped
        $this->assertNotEmpty($floorIds, 'The chosen mapping version should have enemies to anchor.');
        $this->assertEqualsCanonicalizing(
            $floorIds,
            $anchors->pluck('floor_id')->all(),
            'Every floor that has enemies on it should get exactly one anchor.',
        );
    }

    #[Test]
    public function mapContextFloorEnemyForcesAnchors_givenFacadeWithFloorUnions_convertsAnchorsOntoTheFacadeFloor(): void
    {
        // Arrange
        $mappingVersion = $this->getFacadeMappingVersionWithFloorUnions();

        $enemiesByFloorId = $mappingVersion->enemies()->get()->groupBy('floor_id');

        // Act
        $anchors = $mappingVersion->mapContextFloorEnemyForcesAnchors($this->getCoordinatesService(), true);

        // Assert - the anchor is the floor-space centroid pushed through the floor union, so it must not
        // simply be the average of the floor's raw coordinates
        $this->assertGreaterThan(0, $anchors->count());

        foreach ($anchors as $anchor) {
            /** @var EloquentCollection<int, Enemy> $enemies */
            $enemies = $enemiesByFloorId->get($anchor['floor_id']);

            // Compare on distance rather than on lat or lng alone - a floor union can translate almost
            // purely along one axis, which would leave the other looking untouched.
            $distance = $this->getCoordinatesService()->distanceBetweenPoints(
                (float)$enemies->avg('lng'),
                $anchor['lng'],
                (float)$enemies->avg('lat'),
                $anchor['lat'],
            );

            $this->assertGreaterThan(
                0.001,
                $distance,
                sprintf('Anchor for floor %d was not translated onto the facade image.', $anchor['floor_id']),
            );
        }
    }

    #[Test]
    public function mapContextFloorEnemyForcesAnchors_givenFacadeWithoutFloorUnions_returnsThePlainCentroid(): void
    {
        // Arrange - without floor unions the floors are drawn onto the combined image untouched, exactly
        // like their enemies are, so no translation should happen at all
        $mappingVersion = MappingVersion::query()
            ->where('facade_enabled', true)
            ->whereDoesntHave('floorUnions')
            ->whereHas('enemies')
            ->firstOrFail();

        $enemiesByFloorId = $mappingVersion->enemies()->get()->groupBy('floor_id');

        // Act
        $anchors = $mappingVersion->mapContextFloorEnemyForcesAnchors($this->getCoordinatesService(), true);

        // Assert
        $this->assertCount($enemiesByFloorId->count(), $anchors);

        foreach ($anchors as $anchor) {
            /** @var EloquentCollection<int, Enemy> $enemies */
            $enemies = $enemiesByFloorId->get($anchor['floor_id']);

            $this->assertEqualsWithDelta((float)$enemies->avg('lat'), $anchor['lat'], 0.001);
            $this->assertEqualsWithDelta((float)$enemies->avg('lng'), $anchor['lng'], 0.001);
        }
    }

    private function getCoordinatesService(): CoordinatesServiceInterface
    {
        return app(CoordinatesServiceInterface::class);
    }

    /**
     * The mapping version whose facade carves a single floor into the most floor unions - the case where
     * a floor's centroid is most likely to land in a gap that no union can translate.
     */
    private function getFacadeMappingVersionWithFloorUnions(): MappingVersion
    {
        $mostCarvedUpFloor = FloorUnion::query()
            ->selectRaw('mapping_version_id, count(*) as floor_union_count')
            // A mapping version that doesn't draw a facade, or has no enemies, would produce no anchors
            // at all and let every assertion below pass vacuously.
            ->whereHas('mappingVersion', static fn(Builder $query) => $query
                ->where('facade_enabled', true)
                ->whereHas('enemies'))
            ->groupBy('mapping_version_id', 'target_floor_id')
            ->havingRaw('count(*) > 1')
            ->orderByDesc('floor_union_count')
            ->firstOrFail();

        return MappingVersion::query()->findOrFail($mostCarvedUpFloor->mapping_version_id);
    }

    /**
     * @return array<int, int>
     */
    private function getFloorIdsWithEnemies(MappingVersion $mappingVersion): array
    {
        return $mappingVersion->enemies()
            ->distinct()
            ->pluck('floor_id')
            ->all();
    }
}
