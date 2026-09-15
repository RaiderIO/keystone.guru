<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Mapping\MappingVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
#[Group('MappingVersionFloorUnions')]
final class MappingVersionFloorUnionsTest extends PublicTestCase
{
    #[Test]
    public function getFloorUnionsForFloor_givenEveryFloorOfAMappingVersionWithFloorUnions_returnsEachFloorsUnionsFromOneQuery(): void
    {
        // Arrange
        $mappingVersion = $this->findMappingVersionWithFloorUnions();
        $floors         = $mappingVersion->dungeon->floors()->get();

        $floorUnionQueries = 0;
        DB::listen(static function (QueryExecuted $query) use (&$floorUnionQueries): void {
            if (str_contains($query->sql, 'from `floor_unions`')) {
                $floorUnionQueries++;
            }
        });

        // Act - CI runs with the model cache on, which would answer these queries without reaching the database
        $floorUnionIdsForFloor = app('model-cache')->runDisabled(static fn() => $floors->mapWithKeys(
            static fn(Floor $floor) => [$floor->id => $mappingVersion->getFloorUnionsForFloor($floor)->pluck('id')->sort()->values()->all()],
        )->all());
        $floorUnionIdsOnFloor = app('model-cache')->runDisabled(static fn() => $floors->mapWithKeys(
            static fn(Floor $floor) => [$floor->id => $mappingVersion->getFloorUnionsOnFloor($floor->id)->pluck('id')->sort()->values()->all()],
        )->all());

        // Assert
        $this->assertSame(1, $floorUnionQueries);

        $floorUnions = FloorUnion::query()->where('mapping_version_id', $mappingVersion->id)->get();
        foreach ($floors as $floor) {
            $this->assertSame(
                $floorUnions->where('target_floor_id', $floor->id)->pluck('id')->sort()->values()->all(),
                $floorUnionIdsForFloor[$floor->id],
                sprintf('Floor unions targeting floor %d', $floor->id),
            );
            $this->assertSame(
                $floorUnions->where('floor_id', $floor->id)->pluck('id')->sort()->values()->all(),
                $floorUnionIdsOnFloor[$floor->id],
                sprintf('Floor unions on floor %d', $floor->id),
            );
        }
    }

    private function findMappingVersionWithFloorUnions(): MappingVersion
    {
        /** @var FloorUnion $floorUnion */
        $floorUnion = FloorUnion::query()->inRandomOrder()->firstOrFail();

        return MappingVersion::with('dungeon')->findOrFail($floorUnion->mapping_version_id);
    }
}
