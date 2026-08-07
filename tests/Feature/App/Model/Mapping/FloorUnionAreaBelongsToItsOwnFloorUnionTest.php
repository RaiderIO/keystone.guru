<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
final class FloorUnionAreaBelongsToItsOwnFloorUnionTest extends PublicTestCase
{
    /**
     * A floor union area is the region of the facade that projects onto one specific floor union, so it is
     * only meaningful together with that union: it has to exist, and it has to belong to the same mapping
     * version and floor. There are no foreign keys in this schema and the seeder inserts `floor_union_id`
     * verbatim, so nothing else catches a clone or a hand-edit that writes the wrong id.
     *
     * Dawn of the Infinite: Galakrond's Fall had exactly that in mapping version 287: its four areas carried
     * the *area* ids of the version they were cloned from (86-89) in `floor_union_id` instead of that
     * version's own floor unions (126-129), which resolved to nothing at all. Found while copying floor
     * unions between mapping versions in #3739, where getting this remap wrong would have seeded cleanly
     * and been silently wrong in the same way.
     */
    #[Test]
    public function floorUnionAreas_givenSeededDatabase_eachPointsAtAFloorUnionOnItsOwnMappingVersionAndFloor(): void
    {
        // Arrange
        $floorUnionsById = FloorUnion::query()->get()->keyBy('id');

        $failures               = [];
        $checkedFloorUnionAreas = 0;

        // Act
        foreach (FloorUnionArea::query()->get() as $floorUnionArea) {
            $checkedFloorUnionAreas++;

            /** @var FloorUnion|null $floorUnion */
            $floorUnion = $floorUnionsById->get($floorUnionArea->floor_union_id);

            if ($floorUnion === null) {
                $failures[] = sprintf(
                    'floor union area %d (mapping version %d) points at floor union %d, which does not exist',
                    $floorUnionArea->id,
                    $floorUnionArea->mapping_version_id,
                    $floorUnionArea->floor_union_id,
                );

                continue;
            }

            if ($floorUnion->mapping_version_id !== $floorUnionArea->mapping_version_id) {
                $failures[] = sprintf(
                    'floor union area %d (mapping version %d) points at floor union %d, which is on mapping version %d',
                    $floorUnionArea->id,
                    $floorUnionArea->mapping_version_id,
                    $floorUnion->id,
                    $floorUnion->mapping_version_id,
                );
            }

            if ($floorUnion->floor_id !== $floorUnionArea->floor_id) {
                $failures[] = sprintf(
                    'floor union area %d (floor %d) points at floor union %d, which is on floor %d',
                    $floorUnionArea->id,
                    $floorUnionArea->floor_id,
                    $floorUnion->id,
                    $floorUnion->floor_id,
                );
            }
        }

        // Assert
        $this->assertEmpty($failures, implode("\n", $failures));
        $this->assertGreaterThan(
            0,
            $checkedFloorUnionAreas,
            'No floor union area was examined - the assertion above proved nothing.',
        );
    }
}
