<?php

namespace Tests\Feature\App\Model\Mapping;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
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
            // Inserted via insertGetId(), not MappingVersion::create(): going through Eloquent
            // would fire this very clone-on-create hook for the decoy itself, and - since it's the
            // dungeon's globally-highest version at that point - the (buggy) hook would clone
            // $existingMappingVersion's real values into it, overwriting the 111111 sentinel this
            // test relies on to detect a wrong pick. This needs to happen quietly as to not trigger
            // MappingVersion events defined in its class, matching MappingService's own usage.
            $decoyMappingVersionId = MappingVersion::insertGetId([
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
                MappingVersion::query()->where('id', $decoyMappingVersionId)->delete();
            }
        }
    }

    /**
     * #3720 review follow-up: scoping the clone-on-create lookup by game_version_id also scopes its
     * `count() < 2` guard, so creating the FIRST mapping version for a game version the dungeon doesn't
     * have yet now hits the early-return branch instead of the clone. This pins that branch down: the new
     * mapping version must come out with raw column defaults (matching what
     * MappingService::createNewBareMappingVersion() already produces for the same scenario) and no
     * content cloned in from an unrelated game version - not the previous, pre-#3720 behaviour of
     * grabbing whatever mapping version happened to sort second in the dungeon's globally-ordered list.
     */
    #[Test]
    public function create_givenDungeonWithNoMappingVersionOnThisGameVersionYet_earlyReturnsWithRawDefaultsAndNoContent(): void
    {
        // Arrange
        $mappingService = $this->app->make(MappingServiceInterface::class);

        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::whereNotNull('challenge_mode_id')->firstOrFail();
        /** @var MappingVersion $existingMappingVersion */
        $existingMappingVersion = $dungeon->mappingVersions()->firstOrFail();

        /** @var GameVersion $newGameVersion */
        $newGameVersion = GameVersion::query()
            ->whereNotIn('id', $dungeon->mappingVersions()->pluck('game_version_id'))
            ->firstOrFail();

        $newMappingVersion = null;

        try {
            // Act
            $newMappingVersion = $mappingService->createNewMappingVersionFromPreviousMapping($dungeon, $newGameVersion);

            // Assert
            $freshNewMappingVersion = $newMappingVersion->fresh();
            $this->assertNotNull($freshNewMappingVersion);
            $this->assertSame(
                0,
                $freshNewMappingVersion->enemy_forces_required,
                'A dungeon\'s first mapping version for a game version has nothing to clone scalars from and must land on the raw column default.',
            );
            $this->assertSame(
                0,
                $freshNewMappingVersion->timer_max_seconds,
                'A dungeon\'s first mapping version for a game version has nothing to clone scalars from and must land on the raw column default.',
            );
            $this->assertNotSame(
                $existingMappingVersion->enemy_forces_required,
                $freshNewMappingVersion->enemy_forces_required,
                'The early-return branch must not fall back to cloning scalars from a mapping version on a different game_version_id.',
            );
            $this->assertSame(
                0,
                $freshNewMappingVersion->enemies()->count(),
                'The early-return branch must not clone content from a mapping version on a different game_version_id.',
            );
        } finally {
            if ($newMappingVersion !== null) {
                $newMappingVersion->delete();
            }
        }
    }
}
