<?php

namespace Tests\Feature\App\Service\Mapping;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #3720 follow-up: MappingService::copyMappingVersionToDungeon() derived its target's game_version_id
 * and next `version` number from $dungeon->getCurrentMappingVersion() - the dungeon's AMBIENT current
 * mapping version, resolved through the acting user's/default game version - instead of from
 * $sourceMappingVersion's own game_version_id. For a dungeon that has mapping versions on more than one
 * game version, that ambient lookup can land on a completely different game_version_id than the one
 * actually being copied, silently mislabeling the clone and computing its `version` from an unrelated
 * game version's counter.
 */
#[Group('MappingVersion')]
final class CopyMappingVersionToDungeonGameVersionScopingTest extends PublicTestCase
{
    #[Test]
    public function copyMappingVersionToDungeon_givenSourceOnGameVersionTheDungeonHasNoAmbientMappingFor_preservesSourceGameVersionAndScopesVersionNumber(): void
    {
        // Arrange
        $mappingService = $this->app->make(MappingServiceInterface::class);

        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->firstOrFail();
        // The ambient/ guest-default resolution this bug used to fall back on - captured before any
        // changes so the assertions below don't have to guess what it resolves to.
        $ambientMappingVersion = $dungeon->getCurrentMappingVersion();

        /** @var GameVersion $otherGameVersion */
        $otherGameVersion = GameVersion::query()
            ->whereNotIn('id', $dungeon->mappingVersions()->pluck('game_version_id'))
            ->firstOrFail();

        $decoySourceId     = null;
        $newMappingVersion = null;

        try {
            // The "source" being copied: a mapping version on a game version the dungeon has zero other
            // rows for. Inserted via insertGetId(), not MappingVersion::create(), so creating it doesn't
            // itself trigger the clone-on-create hook (matching MappingService's own "needs to happen
            // quietly" idiom for exactly this reason).
            $decoySourceId = MappingVersion::insertGetId([
                'game_version_id'                 => $otherGameVersion->id,
                'dungeon_id'                      => $dungeon->id,
                'version'                         => 42,
                'enemy_forces_required'           => 0,
                'enemy_forces_required_teeming'   => 0,
                'enemy_forces_shrouded'           => 0,
                'enemy_forces_shrouded_zul_gamux' => 0,
                'timer_max_seconds'               => 0,
                'facade_enabled'                  => false,
                'created_at'                      => now(),
                'updated_at'                      => now(),
            ]);
            /** @var MappingVersion $decoySource */
            $decoySource = MappingVersion::findOrFail($decoySourceId);

            // Act
            $newMappingVersion = $mappingService->copyMappingVersionToDungeon($decoySource, $dungeon);

            // Assert
            $this->assertSame(
                $otherGameVersion->id,
                $newMappingVersion->game_version_id,
                'The clone must preserve the SOURCE mapping version\'s game_version_id, not the dungeon\'s ambient current game version.',
            );
            $this->assertNotSame(
                $ambientMappingVersion?->game_version_id,
                $newMappingVersion->game_version_id,
                'The clone must not be mislabeled with the dungeon\'s ambient current game version.',
            );
            $this->assertSame(
                43,
                $newMappingVersion->version,
                'The next version number must be computed from the SAME game version\'s predecessor (the decoy, version 42), not the ambient current mapping version.',
            );
        } finally {
            if ($newMappingVersion !== null) {
                // An Eloquent delete: MappingVersion::boot()'s `deleting` cascade removes what was cloned.
                $newMappingVersion->delete();
            }
            if ($decoySourceId !== null) {
                MappingVersion::query()->where('id', $decoySourceId)->delete();
            }
        }
    }
}
