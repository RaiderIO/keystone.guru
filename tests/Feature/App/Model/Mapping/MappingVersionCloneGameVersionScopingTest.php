<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MappingVersion')]
final class MappingVersionCloneGameVersionScopingTest extends PublicTestCase
{
    #[Test]
    public function create_givenDungeonWithMappingVersionOnAnotherGameVersion_clonesFromSameGameVersionPredecessor(): void
    {
        // Arrange
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->firstOrFail();
        /** @var MappingVersion $existingMappingVersion */
        $existingMappingVersion = $dungeon->mappingVersions()->firstOrFail();

        /** @var GameVersion $otherGameVersion */
        $otherGameVersion = GameVersion::query()
            ->where('id', '!=', $existingMappingVersion->game_version_id)
            ->firstOrFail();

        $decoyMappingVersionId = null;
        $newMappingVersion     = null;

        try {
            // A mapping version on a DIFFERENT game_version_id, with a raw `version` number that sits
            // between $existingMappingVersion's and the new one's. `version` is only unique per
            // game_version_id (see MappingService::createNewBareMappingVersion()), so an unscoped,
            // globally-ordered list of the dungeon's mapping versions can put this decoy where the
            // real same-game-version predecessor belongs - the exact interleaving #3720 describes.
            //
            // Inserted via the query builder, not MappingVersion::create(): going through Eloquent
            // would fire this very clone-on-create hook for the decoy itself, and - since it's the
            // dungeon's globally-highest version at that point - the (buggy) hook would clone
            // $existingMappingVersion's real values into it, overwriting the 111111 sentinel this
            // test relies on to detect a wrong pick.
            $decoyMappingVersionId = DB::table('mapping_versions')->insertGetId([
                'game_version_id'                 => $otherGameVersion->id,
                'dungeon_id'                      => $dungeon->id,
                'version'                         => $existingMappingVersion->version + 500000,
                'enemy_forces_required'           => 111111,
                'enemy_forces_required_teeming'   => 111111,
                'enemy_forces_shrouded'           => 111111,
                'enemy_forces_shrouded_zul_gamux' => 111111,
                'timer_max_seconds'               => 111111,
                'facade_enabled'                  => false,
                'created_at'                      => now(),
                'updated_at'                      => now(),
            ]);

            // Act
            $newMappingVersion = MappingVersion::create([
                'game_version_id'                 => $existingMappingVersion->game_version_id,
                'dungeon_id'                      => $dungeon->id,
                'version'                         => $existingMappingVersion->version + 1000000,
                'enemy_forces_required'           => $existingMappingVersion->enemy_forces_required,
                'enemy_forces_required_teeming'   => $existingMappingVersion->enemy_forces_required_teeming,
                'enemy_forces_shrouded'           => $existingMappingVersion->enemy_forces_shrouded,
                'enemy_forces_shrouded_zul_gamux' => $existingMappingVersion->enemy_forces_shrouded_zul_gamux,
                'timer_max_seconds'               => $existingMappingVersion->timer_max_seconds,
                'facade_enabled'                  => false,
            ]);

            // Assert
            $freshNewMappingVersion = $newMappingVersion->fresh();
            $this->assertNotNull($freshNewMappingVersion);
            $this->assertSame(
                $existingMappingVersion->enemy_forces_required,
                $freshNewMappingVersion->enemy_forces_required,
                'The clone-on-create hook must clone from the previous mapping version of the SAME game_version_id.',
            );
            $this->assertNotSame(
                111111,
                $freshNewMappingVersion->enemy_forces_required,
                'The clone-on-create hook must not pick a mapping version from a different game_version_id as its clone source.',
            );
        } finally {
            if ($newMappingVersion !== null) {
                $newMappingVersion->delete();
            }
            if ($decoyMappingVersionId !== null) {
                DB::table('mapping_versions')->where('id', $decoyMappingVersionId)->delete();
            }
        }
    }
}
