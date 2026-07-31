<?php

namespace Tests\Feature\App\Service\Mapping;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\User;
use App\Service\Mapping\MappingServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #3757 cold-review follow-up: MappingService::createNewMappingVersionFromMDTMapping()'s null-predecessor path
 * (a dungeon's genuinely first-ever import for a game version) used to resolve its facade geometry source via
 * `$currentMappingVersion ?? $dungeon->getCurrentMappingVersion()` - reintroducing, one layer down, the exact
 * ambient/unrelated-game-version resolution this whole issue was about fixing at the layer above.
 * `Dungeon::getCurrentMappingVersion()` ultimately falls back through
 * `GameVersion::getUserOrDefaultGameVersion()`, which depends on the ACTING USER's own `game_version_id` when
 * one is authenticated - context the pipeline's real caller (the `mdt:importmapping` CLI command) never has,
 * but that any other caller of this public method could. `facade_enabled` is now derived from
 * `Dungeon::getFacadeFloor() !== null` (a physical fact about the dungeon, not tied to any mapping version),
 * and the FloorUnion clone source is pinned explicitly and deterministically to the newest facade-enabled
 * mapping version of ANY game version - never to whichever one happens to be "ambiently current".
 *
 * Calls createNewMappingVersionFromMDTMapping() directly (no Lua, no MDT string) so it doesn't touch any of
 * the shared seeded NPC/dungeon rows the real importMappingVersionFromMDT() pipeline rewrites - see
 * MDTMappingImportGameVersionScopingTest, which is deliberately scoped away from this behaviour for exactly
 * that reason.
 */
#[Group('MDT')]
#[Group('MappingVersion')]
final class MappingServiceCreateNewMappingVersionFromMDTMappingTest extends PublicTestCase
{
    #[Test]
    public function createNewMappingVersionFromMDTMapping_givenNoPredecessorAndAuthenticatedUserOnFacadeDisabledGameVersion_stillInheritsFacadeGeometryAndTimerFromDungeonPhysicalFact(): void
    {
        // Arrange
        $mappingService = $this->app->make(MappingServiceInterface::class);

        /** @var GameVersion $targetGameVersion */
        $targetGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_BETA)->firstOrFail();
        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_RETAIL)->firstOrFail();
        /** @var GameVersion $facadeDisabledGameVersion */
        $facadeDisabledGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_LEGION_REMIX)->firstOrFail();

        $dungeon = $this->getFacadeDungeonWithFacadeDisabledOtherGameVersionAndNoMappingForGameVersion(
            $targetGameVersion,
            $retailGameVersion,
            $facadeDisabledGameVersion,
        );

        $retailMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($retailGameVersion);

        // An authenticated user browsing under $facadeDisabledGameVersion. Dungeon::getCurrentMappingVersion()'s
        // ambient fallback resolves through THIS user's own game_version_id - proving the old ambient-resolution
        // bug is reachable through any caller of createNewMappingVersionFromMDTMapping(), not just the specific
        // CLI/no-auth context the pipeline happens to run under today.
        $user = User::factory()->create(['game_version_id' => $facadeDisabledGameVersion->id]);
        $this->actingAs($user);

        $newMappingVersion = null;

        try {
            // Act - a genuinely first-ever import for $targetGameVersion: $currentMappingVersion is null.
            $newMappingVersion = $mappingService->createNewMappingVersionFromMDTMapping(
                $dungeon,
                $targetGameVersion,
                null,
            );

            // Assert
            $this->assertSame($targetGameVersion->id, $newMappingVersion->game_version_id);
            $this->assertSame(1, $newMappingVersion->version);

            $this->assertSame(
                1,
                $newMappingVersion->facade_enabled,
                'facade_enabled is a physical fact about the dungeon (Dungeon::getFacadeFloor() !== null), independent of which mapping version the acting user\'s ambient game version happens to resolve to.',
            );
            $this->assertSame(
                $retailMappingVersion->floorUnions()->count(),
                $newMappingVersion->floorUnions()->count(),
                'FloorUnions must be cloned from a facade-ENABLED mapping version, not from whichever mapping version the acting user\'s ambient/facade-DISABLED game version happens to resolve to.',
            );
            $this->assertGreaterThan(
                0,
                $newMappingVersion->floorUnions()->count(),
                'Sanity check on the fixture: the retail mapping version this is supposed to clone from must actually have FloorUnions to prove the clone happened.',
            );
            $this->assertSame(
                $retailMappingVersion->timer_max_seconds,
                $newMappingVersion->timer_max_seconds,
                'timer_max_seconds must be carried over from the resolved facade-source mapping version, not left at its NOT NULL DEFAULT 0 column default - CombatLogEventFilter/CombatLogEvent divide by it unguarded.',
            );
        } finally {
            $newMappingVersion?->delete();
            $user->delete();
        }
    }

    /**
     * #3762 review follow-up: Wotuu asked why the null-predecessor path only clones FloorUnions and not every
     * physical property (floor switches, map icons, mountable areas). The answer differs per model:
     *
     * - MountableAreas ARE the same class of physical geometry as FloorUnions (fixed dungeon terrain, e.g. a
     *   mount-speed zone) and MDT has no concept of them at all, so nothing else ever (re)creates them - they
     *   need the same explicit clone FloorUnions get, or a first-ever import silently loses all mount-speed data.
     * - MapIcons and DungeonFloorSwitchMarkers correctly stay empty here, but not because they're curated: MDT's
     *   own map POI/MapLink data DOES describe them (graveyards, dungeon entrances, staircases, ...), and
     *   MDTMappingImportService::importMapPOIs() - called right after this method returns, as part of the same
     *   import - already (re)creates both from that data whenever $currentMappingVersion is null. Cloning them
     *   here too would just create duplicate rows moments later in the same import.
     */
    #[Test]
    public function createNewMappingVersionFromMDTMapping_givenNoPredecessor_clonesMountableAreasButLeavesMapIconsAndDungeonFloorSwitchMarkersEmpty(): void
    {
        // Arrange
        $mappingService = $this->app->make(MappingServiceInterface::class);

        /** @var GameVersion $targetGameVersion */
        $targetGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_BETA)->firstOrFail();
        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_RETAIL)->firstOrFail();
        /** @var GameVersion $facadeDisabledGameVersion */
        $facadeDisabledGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_LEGION_REMIX)->firstOrFail();

        $dungeon = $this->getFacadeDungeonWithPhysicalAndCuratedContentAndNoMappingForGameVersion(
            $targetGameVersion,
            $retailGameVersion,
        );

        $retailMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($retailGameVersion);

        // Same immunity-to-ambient-resolution setup as the sibling test above.
        $user = User::factory()->create(['game_version_id' => $facadeDisabledGameVersion->id]);
        $this->actingAs($user);

        $newMappingVersion = null;

        try {
            // Act - a genuinely first-ever import for $targetGameVersion: $currentMappingVersion is null.
            $newMappingVersion = $mappingService->createNewMappingVersionFromMDTMapping(
                $dungeon,
                $targetGameVersion,
                null,
            );

            // Assert
            $this->assertSame(
                $retailMappingVersion->mountableAreas()->count(),
                $newMappingVersion->mountableAreas()->count(),
                'MountableAreas are physical dungeon geometry with no other source (MDT has no mount-speed-zone concept), so they must be cloned from the facade source mapping version just like FloorUnions.',
            );
            $this->assertGreaterThan(
                0,
                $newMappingVersion->mountableAreas()->count(),
                'Sanity check on the fixture: the retail mapping version this is supposed to clone from must actually have MountableAreas to prove the clone happened.',
            );

            $this->assertGreaterThan(
                0,
                $retailMappingVersion->mapIcons()->count(),
                'Sanity check on the fixture: the retail mapping version must have MapIcons, otherwise leaving the new mapping version empty would prove nothing.',
            );
            $this->assertSame(
                0,
                $newMappingVersion->mapIcons()->count(),
                'MapIcons must NOT be cloned here - MDTMappingImportService::importMapPOIs() (re)creates the physical ones from MDT\'s own map POI data right after this method returns, so cloning them too would create duplicates.',
            );

            $this->assertGreaterThan(
                0,
                $retailMappingVersion->dungeonFloorSwitchMarkers()->count(),
                'Sanity check on the fixture: the retail mapping version must have DungeonFloorSwitchMarkers, otherwise leaving the new mapping version empty would prove nothing.',
            );
            $this->assertSame(
                0,
                $newMappingVersion->dungeonFloorSwitchMarkers()->count(),
                'DungeonFloorSwitchMarkers must NOT be cloned here - MDTMappingImportService::importMapPOIs() (re)creates them from MDT\'s own MapLink POI data right after this method returns, so cloning them too would create duplicates.',
            );
        } finally {
            $newMappingVersion?->delete();
            $user->delete();
        }
    }

    /**
     * A dungeon that (a) physically has a facade floor, (b) has a facade-ENABLED retail mapping version with at
     * least one FloorUnion - the CORRECT source this should clone from, (c) has a facade-DISABLED mapping
     * version for another game version - what the old ambient resolution would have picked up instead if the
     * acting user's game version happened to point at it, and (d) has zero mapping versions for the target game
     * version, so importing it is a genuine "first ever" case.
     */
    private function getFacadeDungeonWithFacadeDisabledOtherGameVersionAndNoMappingForGameVersion(
        GameVersion $gameVersion,
        GameVersion $retailGameVersion,
        GameVersion $facadeDisabledGameVersion,
    ): Dungeon {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()
            ->with(['mappingVersions', 'floors'])
            ->get()
            ->first(static function (Dungeon $dungeon) use ($gameVersion, $retailGameVersion, $facadeDisabledGameVersion): bool {
                if ($dungeon->mappingVersions->where('game_version_id', $gameVersion->id)->isNotEmpty()) {
                    return false;
                }

                if ($dungeon->getFacadeFloor() === null) {
                    return false;
                }

                $retailMappingVersion = $dungeon->mappingVersions
                    ->where('game_version_id', $retailGameVersion->id)
                    ->sortByDesc('version')
                    ->first();

                if ($retailMappingVersion === null
                    || !$retailMappingVersion->facade_enabled
                    || $retailMappingVersion->floorUnions()->count() === 0
                    || $retailMappingVersion->timer_max_seconds <= 0
                ) {
                    return false;
                }

                $facadeDisabledMappingVersion = $dungeon->mappingVersions
                    ->where('game_version_id', $facadeDisabledGameVersion->id)
                    ->sortByDesc('version')
                    ->first();

                return $facadeDisabledMappingVersion !== null && !$facadeDisabledMappingVersion->facade_enabled;
            });

        if ($dungeon === null) {
            $this->fail(
                'No dungeon found with a physical facade floor, a facade-enabled retail mapping version ' .
                '(with FloorUnions and a timer), a facade-DISABLED mapping version for another game version, ' .
                'and no mapping version for the target game version.',
            );
        }

        return $dungeon;
    }

    /**
     * A dungeon that (a) physically has a facade floor, (b) has a facade-ENABLED retail mapping version that is
     * ALSO the deterministically-pinned "newest facade-enabled mapping version of any game version" (so the
     * source under test is unambiguous), with at least one MountableArea, MapIcon and DungeonFloorSwitchMarker -
     * so the clone/no-clone assertions on all three are non-vacuous - and (c) has zero mapping versions for the
     * target game version, so importing it is a genuine "first ever" case.
     */
    private function getFacadeDungeonWithPhysicalAndCuratedContentAndNoMappingForGameVersion(
        GameVersion $gameVersion,
        GameVersion $retailGameVersion,
    ): Dungeon {
        /** @var Dungeon|null $dungeon */
        $dungeon = Dungeon::query()
            ->with(['mappingVersions', 'floors'])
            ->get()
            ->first(static function (Dungeon $dungeon) use ($gameVersion, $retailGameVersion): bool {
                if ($dungeon->mappingVersions->where('game_version_id', $gameVersion->id)->isNotEmpty()) {
                    return false;
                }

                if ($dungeon->getFacadeFloor() === null) {
                    return false;
                }

                $retailMappingVersion = $dungeon->mappingVersions
                    ->where('game_version_id', $retailGameVersion->id)
                    ->sortByDesc('version')
                    ->first();

                if ($retailMappingVersion === null
                    || !$retailMappingVersion->facade_enabled
                    || $retailMappingVersion->timer_max_seconds <= 0
                    || $retailMappingVersion->mountableAreas()->count() === 0
                    || $retailMappingVersion->mapIcons()->count() === 0
                    || $retailMappingVersion->dungeonFloorSwitchMarkers()->count() === 0
                ) {
                    return false;
                }

                // The deterministic pin picks the newest facade-enabled mapping version of ANY game version -
                // require it to be $retailMappingVersion itself so the fixture is unambiguous.
                $pinnedMappingVersion = $dungeon->mappingVersions()
                    ->where('facade_enabled', true)
                    ->orderByDesc('id')
                    ->first();

                return $pinnedMappingVersion !== null && $pinnedMappingVersion->id === $retailMappingVersion->id;
            });

        if ($dungeon === null) {
            $this->fail(
                'No dungeon found with a physical facade floor, a facade-enabled retail mapping version ' .
                '(with a timer, MountableAreas, MapIcons and DungeonFloorSwitchMarkers) that is also the ' .
                'deterministically-pinned facade source, and no mapping version for the target game version.',
            );
        }

        return $dungeon;
    }
}
