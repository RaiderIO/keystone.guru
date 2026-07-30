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
     * Six retail mapping versions had this shape in seeded data and are fixed in #3739: Cathedral of
     * Eternal Night and Maw of Souls dropped their retail mapping version entirely (both were empty stubs
     * that predate the Legion Remix mappings holding all the real data), while Halls of Valor, Lower
     * Karazhan, Upper Karazhan and Vault of the Wardens copied the floor unions from their newest Legion
     * Remix mapping version.
     *
     * Scoped to the current (highest `version`) mapping version per dungeon per game version, matching
     * FacadeFloorUnionsRequireFacadeEnabledTest: a superseded mapping version in this shape can still be
     * hit by an old route, but fixing history is a data migration rather than something seeded data can
     * assert, and every dungeon's current mapping version is what a reimport writes into.
     */
    #[Test]
    public function facadeEnabled_givenAllDungeonsCurrentMappingVersions_requiresFloorUnionsOnAFacadeFloor(): void
    {
        // Arrange
        $dungeons = Dungeon::with(['floors', 'mappingVersions'])->get();

        $failures = [];

        // Act
        foreach ($dungeons as $dungeon) {
            $facadeFloorIds = $dungeon->floors->where('facade', true)->where('active', true)->pluck('id');

            /** @var Collection<int, MappingVersion> $currentMappingVersionsPerGameVersion */
            $currentMappingVersionsPerGameVersion = $dungeon->mappingVersions
                ->groupBy('game_version_id')
                ->map(static fn($mappingVersions) => $mappingVersions->sortByDesc('version')->first());

            foreach ($currentMappingVersionsPerGameVersion as $mappingVersion) {
                if (!$mappingVersion->facade_enabled) {
                    continue;
                }

                if ($facadeFloorIds->isEmpty()) {
                    $failures[] = sprintf(
                        '%s mapping version %d (game_version_id %d) has facade_enabled but the dungeon has no active facade floor',
                        $dungeon->key,
                        $mappingVersion->id,
                        $mappingVersion->game_version_id,
                    );

                    continue;
                }

                $hasFacadeFloorUnions = FloorUnion::query()
                    ->where('mapping_version_id', $mappingVersion->id)
                    ->whereIn('floor_id', $facadeFloorIds)
                    ->exists();

                if (!$hasFacadeFloorUnions) {
                    $failures[] = sprintf(
                        '%s mapping version %d (game_version_id %d) has facade_enabled but no floor unions on a facade floor',
                        $dungeon->key,
                        $mappingVersion->id,
                        $mappingVersion->game_version_id,
                    );
                }
            }
        }

        // Assert
        $this->assertEmpty($failures, implode("\n", $failures));
    }
}
