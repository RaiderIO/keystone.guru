<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\CombatLogMappingVersionServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #3720 follow-up: CombatLogMappingVersionService::createMappingVersionFromCombatLog()'s "first mapping
 * version for a newly-discovered dungeon" branch computed the new `version` number via an UNSCOPED
 * `MappingVersion::where('dungeon_id', ...)->orderByDesc('version')->first()`, ignoring
 * game_version_id entirely - even though the mapping version being created is already correctly
 * tagged with a specific game_version_id.
 */
#[Group('MappingVersion')]
#[Group('CombatLog')]
final class CombatLogMappingVersionServiceGameVersionScopingTest extends PublicTestCase
{
    #[Test]
    public function createMappingVersionFromChallengeMode_givenDungeonHasAHighVersionMappingOnAnotherGameVersion_scopesNewVersionNumberByGameVersion(): void
    {
        // Arrange
        $service = $this->app->make(CombatLogMappingVersionServiceInterface::class);

        // The Arcway - a real seeded dungeon whose floors already carry ingame bounding boxes
        // (required by this code path) and has a default floor.
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::where('challenge_mode_id', 209)->firstOrFail();

        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);

        /** @var GameVersion $otherGameVersion */
        $otherGameVersion = GameVersion::query()
            ->where('id', '!=', $retailGameVersion->id)
            ->firstOrFail();

        // A decoy on a DIFFERENT game version, with a huge `version` number - this is what the pre-fix
        // code picked up instead of the correct, retail-scoped predecessor. Inserted via insertGetId(),
        // not MappingVersion::create(), so creating it doesn't itself trigger the clone-on-create hook.
        $decoyId = MappingVersion::insertGetId([
            'game_version_id'                 => $otherGameVersion->id,
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

        // A minimal synthetic combat log: a single CHALLENGE_MODE_START line is enough to reach the
        // "dungeon found" branch this test targets; CombatLogService::parseCombatLog() reads a plain
        // (non-.zip) file line by line with no header requirement.
        $combatLogPath = tempnam(sys_get_temp_dir(), 'combatlog_test_');
        file_put_contents($combatLogPath, "5/15 21:20:10.941  CHALLENGE_MODE_START,\"The Arcway\",1841,209,2,[9]\n");

        $createdMappingVersion = null;

        try {
            // Act
            $createdMappingVersion = $service->createMappingVersionFromChallengeMode($combatLogPath, $retailGameVersion);

            // Assert
            $this->assertNotNull($createdMappingVersion);
            $this->assertSame($dungeon->id, $createdMappingVersion->dungeon_id);
            $this->assertSame($retailGameVersion->id, $createdMappingVersion->game_version_id);
            $this->assertLessThan(
                999000,
                $createdMappingVersion->version,
                'The new version number must be scoped to $retailGameVersion, not derived from the unrelated decoy mapping version on another game version.',
            );
        } finally {
            if (file_exists($combatLogPath)) {
                unlink($combatLogPath);
            }
            if ($createdMappingVersion !== null) {
                // An Eloquent delete: MappingVersion::boot()'s `deleting` cascade removes anything cloned.
                $createdMappingVersion->delete();
            }
            MappingVersion::query()->where('id', $decoyId)->delete();
        }
    }
}
