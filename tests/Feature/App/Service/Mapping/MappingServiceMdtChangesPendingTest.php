<?php

namespace Tests\Feature\App\Service\Mapping;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\CombatLogMappingVersionServiceInterface;
use App\Service\Mapping\MappingServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #4280: a mapping version created by us (mapping editor, copy) diverges from what MDT ships and must be
 * marked `mdt_changes_pending`, so MDT string imports never resolve onto it - the enemies such a string
 * references are MDT's. Only the MDT import pipeline may produce a mapping version with the flag off.
 */
#[Group('MDT')]
#[Group('MappingVersion')]
#[Group('CombatLog')]
final class MappingServiceMdtChangesPendingTest extends PublicTestCase
{
    #[Test]
    public function createNewMappingVersionFromPreviousMapping_givenMdtDerivedPredecessor_marksTheNewMappingVersionAsPending(): void
    {
        // Arrange
        $mappingService = $this->app->make(MappingServiceInterface::class);
        $dungeon        = $this->getDungeon();
        $gameVersion    = $this->getGameVersionOf($dungeon);

        $newMappingVersion = null;

        try {
            // Act
            $newMappingVersion = $mappingService->createNewMappingVersionFromPreviousMapping($dungeon, $gameVersion);

            // Assert - even though it inherits the predecessor's mdt_addon_version to keep the MDT era it
            // descends from, its mapping is ours to edit and no longer matches MDT's.
            $this->assertTrue($newMappingVersion->mdt_changes_pending);
        } finally {
            $newMappingVersion?->delete();
        }
    }

    #[Test]
    public function createNewBareMappingVersion_givenAnyDungeon_marksTheNewMappingVersionAsPending(): void
    {
        // Arrange
        $mappingService = $this->app->make(MappingServiceInterface::class);
        $dungeon        = $this->getDungeon();
        $gameVersion    = $this->getGameVersionOf($dungeon);

        $newMappingVersion = null;

        try {
            // Act
            $newMappingVersion = $mappingService->createNewBareMappingVersion($dungeon, $gameVersion);

            // Assert
            $this->assertTrue($newMappingVersion->mdt_changes_pending);
        } finally {
            $newMappingVersion?->delete();
        }
    }

    #[Test]
    public function copyMappingVersionToDungeon_givenMdtDerivedSource_marksTheCopyAsPending(): void
    {
        // Arrange
        $mappingService       = $this->app->make(MappingServiceInterface::class);
        $dungeon              = $this->getDungeon();
        $sourceMappingVersion = $dungeon->getCurrentMappingVersion();
        $this->assertNotNull($sourceMappingVersion);

        $newMappingVersion = null;

        try {
            // Act
            $newMappingVersion = $mappingService->copyMappingVersionToDungeon($sourceMappingVersion, $dungeon);

            // Assert
            $this->assertTrue($newMappingVersion->mdt_changes_pending);
        } finally {
            $newMappingVersion?->delete();
        }
    }

    #[Test]
    public function createNewMappingVersionFromMDTMapping_givenMdtDerivedPredecessor_leavesTheNewMappingVersionNotPending(): void
    {
        // Arrange
        $mappingService = $this->app->make(MappingServiceInterface::class);
        $dungeon        = $this->getDungeon();
        $gameVersion    = $this->getGameVersionOf($dungeon);

        $currentMappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        $newMappingVersion = null;

        try {
            // Act
            $newMappingVersion = $mappingService->createNewMappingVersionFromMDTMapping($dungeon, $gameVersion, $currentMappingVersion);

            // Assert - imported straight from MDT, so MDT strings may resolve onto it
            $this->assertFalse($newMappingVersion->mdt_changes_pending);
        } finally {
            $newMappingVersion?->delete();
        }
    }

    #[Test]
    public function createNewMappingVersionFromMDTMapping_givenPendingPredecessor_doesNotInheritThePendingFlag(): void
    {
        // Arrange - the dungeon's current mapping version is one of our own corrections, awaiting MDT
        $mappingService = $this->app->make(MappingServiceInterface::class);
        $dungeon        = $this->getDungeon();
        $gameVersion    = $this->getGameVersionOf($dungeon);

        $pendingMappingVersion = null;
        $newMappingVersion     = null;

        try {
            $pendingMappingVersion = $mappingService->createNewMappingVersionFromPreviousMapping($dungeon, $gameVersion);

            // Act - MDT accepted the change and shipped it, so the next import is MDT-derived again
            $newMappingVersion = $mappingService->createNewMappingVersionFromMDTMapping(
                $dungeon->fresh(),
                $gameVersion,
                $pendingMappingVersion,
            );

            // Assert
            $this->assertFalse($newMappingVersion->mdt_changes_pending);
        } finally {
            $newMappingVersion?->delete();
            $pendingMappingVersion?->delete();
        }
    }

    #[Test]
    public function createMappingVersionFromChallengeMode_givenCombatLog_marksTheNewMappingVersionAsPending(): void
    {
        // Arrange - a combat-log-derived mapping version starts on dungeon_id -1 but is attached to the real
        // dungeon as soon as the log reveals it, so it becomes a regular mapping version of that dungeon.
        $service = $this->app->make(CombatLogMappingVersionServiceInterface::class);

        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::query()->where('challenge_mode_id', 209)->firstOrFail();
        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::query()->firstWhere('key', GameVersion::GAME_VERSION_RETAIL);

        // A single CHALLENGE_MODE_START line is enough to reach the "dungeon found" branch.
        $combatLogPath = tempnam(sys_get_temp_dir(), 'combatlog_test_');
        file_put_contents($combatLogPath, "5/15 21:20:10.941  CHALLENGE_MODE_START,\"The Arcway\",1841,209,2,[9]\n");

        $newMappingVersion = null;

        try {
            // Act
            $newMappingVersion = $service->createMappingVersionFromChallengeMode($combatLogPath, $retailGameVersion);

            // Assert
            $this->assertNotNull($newMappingVersion);
            $this->assertSame($dungeon->id, $newMappingVersion->dungeon_id);
            $this->assertTrue($newMappingVersion->mdt_changes_pending);
        } finally {
            if (file_exists($combatLogPath)) {
                unlink($combatLogPath);
            }
            $newMappingVersion?->delete();
        }
    }

    private function getDungeon(): Dungeon
    {
        return Dungeon::query()->whereNotNull('challenge_mode_id')->firstOrFail();
    }

    private function getGameVersionOf(Dungeon $dungeon): GameVersion
    {
        /** @var MappingVersion $currentMappingVersion */
        $currentMappingVersion = $dungeon->getCurrentMappingVersion();

        return $currentMappingVersion->gameVersion;
    }
}
