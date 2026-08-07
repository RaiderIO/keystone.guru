<?php

namespace Tests\Feature\App\Console\Commands\Mapping;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #3720 follow-up: `mapping:copy`'s "move the freshly created mapping version onto the target
 * dungeon" step recomputed `version` via $targetDungeon->getCurrentMappingVersion() - the target's
 * AMBIENT current mapping version (resolved through the acting user's/default game version), not
 * scoped by the command's own $gameVersion argument. `version` is only unique per game_version_id,
 * so that ambient lookup could pick an entirely unrelated game version's counter.
 */
#[Group('MappingVersion')]
final class CopyGameVersionScopingTest extends PublicTestCase
{
    #[Test]
    public function handle_givenDungeonHasAHighVersionMappingOnTheAmbientDefaultGameVersion_scopesNewVersionNumberToTheGivenGameVersion(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->firstOrFail();
        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);

        /** @var GameVersion $targetGameVersion */
        $targetGameVersion = GameVersion::query()
            ->whereNotIn('id', $dungeon->mappingVersions()->pluck('game_version_id'))
            ->where('id', '!=', $retailGameVersion->id)
            ->firstOrFail();

        // A decoy on the ambient/guest-default game version (retail), with a huge `version` number -
        // this is what the pre-fix code picked up instead of the correct (nonexistent) predecessor for
        // $targetGameVersion. Inserted via insertGetId(), not MappingVersion::create(), so creating it
        // doesn't itself trigger the clone-on-create hook.
        $decoyId = MappingVersion::insertGetId([
            'game_version_id'                 => $retailGameVersion->id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => 999000,
            'enemy_forces_required'           => 0,
            'enemy_forces_required_teeming'   => 0,
            'enemy_forces_shrouded'           => 0,
            'enemy_forces_shrouded_zul_gamux' => 0,
            'timer_max_seconds'               => 0,
            'facade_enabled'                  => false,
            'created_at'                      => now(),
            'updated_at'                      => now(),
        ]);

        $createdMappingVersion = null;

        try {
            // Act - a same-dungeon "copy" so the cross-dungeon floor-remapping branch never runs; this
            // isolates the version-number computation this test targets.
            Artisan::call('mapping:copy', [
                'gameVersion'   => $targetGameVersion->key,
                'sourceDungeon' => $dungeon->key,
                'targetDungeon' => $dungeon->key,
            ]);

            // Assert
            /** @var MappingVersion|null $createdMappingVersion */
            $createdMappingVersion = MappingVersion::where('dungeon_id', $dungeon->id)
                ->where('game_version_id', $targetGameVersion->id)
                ->orderByDesc('id')
                ->first();

            $this->assertNotNull($createdMappingVersion, 'mapping:copy must create a new mapping version for the target game version.');
            $this->assertLessThan(
                999000,
                $createdMappingVersion->version,
                'The new version number must be scoped to $targetGameVersion, not derived from the ambient decoy mapping version on a different game version.',
            );
        } finally {
            if ($createdMappingVersion !== null) {
                // An Eloquent delete: MappingVersion::boot()'s `deleting` cascade removes what was cloned.
                $createdMappingVersion->delete();
            }
            MappingVersion::query()->where('id', $decoyId)->delete();
        }
    }
}
