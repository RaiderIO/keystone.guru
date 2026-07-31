<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Dungeon;
use App\Models\Floor\FloorUnion;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
final class FacadeEnabledRequiresFacadeFloorUnionsTest extends PublicTestCase
{
    /**
     * The inverse of FacadeFloorUnionsRequireFacadeEnabledTest, and the exact precondition
     * MDTDungeon::getClonesAsEnemies() now throws FacadeNotConfiguredException for: facade_enabled promises
     * both a facade floor and floor unions on it to redistribute MDT's single-sublevel coordinates through,
     * and there is no code path that can place enemies when either is missing.
     *
     * That exception is not reachable from mdt:importmapping alone - MDTExportStringService (exporting a
     * route as an MDT string) and RaidMarkerImporter/PullImporter (importing pulls/raid markers) all call
     * getClonesAsEnemies() with the route's own mapping version, so a mapping version in this shape breaks
     * regular player actions on every existing route that uses it, not just an admin reimport.
     *
     * Fifteen retail mapping versions had this shape in seeded data and are fixed in #3739. Six were the
     * current version for their dungeon: Cathedral of Eternal Night and Maw of Souls dropped their retail
     * mapping version entirely (both were empty stubs that predate the Legion Remix mappings holding all
     * the real data), while Halls of Valor, Lower Karazhan, Upper Karazhan and Vault of the Wardens copied
     * the floor unions from their newest Legion Remix mapping version. The other nine were superseded
     * versions that copied theirs from their own dungeon's current version.
     *
     * Deliberately NOT scoped to the current mapping version per game version, unlike
     * FacadeFloorUnionsRequireFacadeEnabledTest. That scope is right for the inverse direction, which only
     * misplaces a reimport's enemies and so is inert on a version nothing reimports into. This direction is
     * not: getClonesAsEnemies() is called with the *route's own* mapping version, so a superseded version
     * in this shape throws on every export and pull import of every route still pinned to it - 690 of them
     * across Black Rook Hold, Waycrest Manor and Algeth'ar Academy alone.
     *
     * The facade floors are filtered to active ones because that is what mdt:importmapping passes in
     * (`$dungeon->floors()->active()->get()`), which is the strictest of the callers - the player-facing
     * ones pass every floor.
     */
    #[Test]
    public function facadeEnabled_givenEveryMappingVersion_requiresFloorUnionsOnAFacadeFloor(): void
    {
        // Arrange
        $dungeons = Dungeon::with(['floors', 'mappingVersions'])->get();

        $failures               = [];
        $checkedMappingVersions = 0;

        // Act
        foreach ($dungeons as $dungeon) {
            $facadeFloorIds = $dungeon->floors->where('facade', true)->where('active', true)->pluck('id');

            /** @var Collection<int, MappingVersion> $mappingVersions */
            $mappingVersions = $dungeon->mappingVersions;

            foreach ($mappingVersions as $mappingVersion) {
                if (!$mappingVersion->facade_enabled) {
                    continue;
                }

                $checkedMappingVersions++;

                if ($facadeFloorIds->isEmpty()) {
                    $failures[] = sprintf(
                        '%s mapping version %d (game_version_id %d) has facade_enabled but the dungeon has no active facade floor',
                        $dungeon->key,
                        $mappingVersion->id,
                        $mappingVersion->game_version_id,
                    );

                    continue;
                }

                /** @var Collection<int, FloorUnion> $facadeFloorUnions */
                $facadeFloorUnions = FloorUnion::query()
                    ->withCount('floorUnionAreas')
                    ->where('mapping_version_id', $mappingVersion->id)
                    ->whereIn('floor_id', $facadeFloorIds)
                    ->get();

                if ($facadeFloorUnions->isEmpty()) {
                    $failures[] = sprintf(
                        '%s mapping version %d (game_version_id %d) has facade_enabled but no floor unions on a facade floor',
                        $dungeon->key,
                        $mappingVersion->id,
                        $mappingVersion->game_version_id,
                    );

                    continue;
                }

                // A floor union with no areas is skipped by CoordinatesService::convertFacadeMapLocationToMapLocation()
                // just as surely as a missing one - it iterates the union's areas looking for the one containing the
                // point, so with none it never resolves a target floor and leaves the coordinate on the facade plane.
                foreach ($facadeFloorUnions->where('floor_union_areas_count', 0) as $floorUnion) {
                    $failures[] = sprintf(
                        '%s mapping version %d (game_version_id %d) has facade floor union %d with no floor union areas',
                        $dungeon->key,
                        $mappingVersion->id,
                        $mappingVersion->game_version_id,
                        $floorUnion->id,
                    );
                }
            }
        }

        // Assert
        $this->assertEmpty($failures, implode("\n", $failures));
        $this->assertGreaterThan(
            0,
            $checkedMappingVersions,
            'No facade-enabled mapping version was examined - the assertion above proved nothing.',
        );
    }
}
