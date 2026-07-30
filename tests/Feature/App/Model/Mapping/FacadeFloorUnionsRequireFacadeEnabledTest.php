<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Dungeon;
use App\Models\Floor\FloorUnion;
use App\Models\Mapping\MappingVersion;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
final class FacadeFloorUnionsRequireFacadeEnabledTest extends PublicTestCase
{
    /**
     * MDTDungeon::getClonesAsEnemies() only redistributes clones off a facade floor onto their real
     * floors via CoordinatesService::convertFacadeMapLocationToMapLocation(), which is a no-op when the
     * mapping version's facade is disabled. A dungeon that has floor unions on a facade floor but keeps
     * facade_enabled false therefore has no code path that can place a reimport's enemies correctly - see
     * #3742, where this silently existed for Throne of the Tides until a reimport would have stranded (or
     * misplaced) every one of its enemies.
     *
     * Scoped to the current (highest `version`) mapping version per dungeon per game version, not every
     * MappingVersion row: reimports only ever write into the current mapping version, so a historical one
     * carrying this exact shape is inert rather than a live bug. Temple of Sethraliss is a real example -
     * its superseded version 1 (mapping_version_id 19) has facade floor unions with facade_enabled false in
     * the seeder data to this day, but its current version 2 already has facade_enabled true. This mirrors
     * the "current mapping version per game version" scope MDTNpcMappingCoverageTest already uses for the
     * same reason.
     *
     * Deliberately one-directional: this only checks "floor unions present without facade_enabled", the
     * shape that stranded Throne of the Tides' enemies. The inverse (facade_enabled true but no facade
     * floor, or a facade floor with no floor unions) is a different, pre-existing gotcha that several
     * legacy dungeons (e.g. Cathedral of Eternal Night, Halls/Upper/Lower Karazhan, Maw of Souls, Vault of
     * the Wardens) already have in seeded data - fixing those is out of scope here. That shape is instead
     * covered going forward by FacadeNotConfiguredException, which getClonesAsEnemies() throws for it (see
     * #3739) - a loud runtime failure the moment anyone reimports one of those dungeons.
     */
    #[Test]
    public function facadeFloorUnions_givenAllDungeonsCurrentMappingVersions_requiresFacadeEnabled(): void
    {
        // Arrange
        $dungeons = Dungeon::with(['floors', 'mappingVersions'])->get();

        $failures = [];

        // Act
        foreach ($dungeons as $dungeon) {
            $facadeFloorIds = $dungeon->floors->where('facade', true)->pluck('id');

            if ($facadeFloorIds->isEmpty()) {
                continue;
            }

            /** @var \Illuminate\Support\Collection<int, MappingVersion> $currentMappingVersionsPerGameVersion */
            $currentMappingVersionsPerGameVersion = $dungeon->mappingVersions
                ->groupBy('game_version_id')
                ->map(static fn($mappingVersions) => $mappingVersions->sortByDesc('version')->first());

            foreach ($currentMappingVersionsPerGameVersion as $mappingVersion) {
                $hasFacadeFloorUnions = FloorUnion::query()
                    ->where('mapping_version_id', $mappingVersion->id)
                    ->whereIn('floor_id', $facadeFloorIds)
                    ->exists();

                if ($hasFacadeFloorUnions && !$mappingVersion->facade_enabled) {
                    $failures[] = sprintf(
                        '%s mapping version %d (game_version_id %d) has floor unions on a facade floor but facade_enabled is false',
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
