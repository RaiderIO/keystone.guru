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
     * Scoped to this dungeon rather than every dungeon: Temple of Sethraliss had the identical shape and
     * is already being fixed by the separate, unmerged #3734, whose mapping version for that dungeon
     * only exists in that branch's database state - asserting the invariant across all dungeons here
     * would fail against master's still-unpatched seeder data until that PR lands.
     */
    #[Test]
    public function facadeFloorUnions_givenThroneOfTidesCurrentMappingVersion_requiresFacadeEnabled(): void
    {
        // Arrange
        $dungeon = Dungeon::with(['floors', 'mappingVersions'])
            ->where('key', Dungeon::DUNGEON_THRONE_OF_THE_TIDES)
            ->firstOrFail();

        $facadeFloorIds = $dungeon->floors->where('facade', true)->pluck('id');
        $this->assertNotEmpty($facadeFloorIds, 'Expected Throne of the Tides to have a facade floor');

        // Act
        /** @var \Illuminate\Support\Collection<int, MappingVersion> $currentMappingVersionsPerGameVersion */
        $currentMappingVersionsPerGameVersion = $dungeon->mappingVersions
            ->groupBy('game_version_id')
            ->map(static fn($mappingVersions) => $mappingVersions->sortByDesc('version')->first());

        $failures = [];
        foreach ($currentMappingVersionsPerGameVersion as $mappingVersion) {
            $hasFacadeFloorUnions = FloorUnion::query()
                ->where('mapping_version_id', $mappingVersion->id)
                ->whereIn('floor_id', $facadeFloorIds)
                ->exists();

            if ($hasFacadeFloorUnions && !$mappingVersion->facade_enabled) {
                $failures[] = sprintf('mapping version %d has floor unions on a facade floor but facade_enabled is false', $mappingVersion->id);
            }
        }

        // Assert
        $this->assertEmpty($failures, implode("\n", $failures));
    }
}
