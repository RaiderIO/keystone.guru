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
     */
    #[Test]
    public function facadeFloorUnions_givenCurrentMappingVersionOfEveryDungeon_requireFacadeEnabled(): void
    {
        // Arrange
        $failures = [];

        $dungeons = Dungeon::with(['floors', 'mappingVersions'])->get();

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
                        '%s (mapping version %d) has floor unions on a facade floor but facade_enabled is false',
                        $dungeon->key,
                        $mappingVersion->id,
                    );
                }
            }
        }

        // Assert
        $this->assertEmpty($failures, implode("\n", $failures));
    }
}
