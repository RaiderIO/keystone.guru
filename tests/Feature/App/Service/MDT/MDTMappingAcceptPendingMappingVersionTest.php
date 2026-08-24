<?php

namespace Tests\Feature\App\Service\MDT;

use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
use App\Service\MDT\Exceptions\MDTMappingPendingAcceptanceException;
use App\Service\MDT\MDTMappingImportServiceInterface;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #4281: once we correct a mapping ourselves the mapping version is flagged `mdt_changes_pending` (#4280),
 * and the usual reason MDT's mapping changes afterwards is that they accepted our correction. Importing that
 * as a new mapping version would take a dungeon from v7 (MDT) to v8 (ours) to v9 (MDT, identical to v8),
 * invalidating every route on v8 for no actual mapping change. The import therefore refuses, and accepting
 * MDT's mapping onto the pending mapping version is an explicit operator step.
 *
 * Real Lua is only needed for the mapping hash both paths compare; nothing here runs the import pipeline
 * except the explicit `--force` case, which must keep being able to create a real new mapping version.
 */
#[Group('UsesLua')]
#[Group('MDT')]
#[Group('MappingVersion')]
final class MDTMappingAcceptPendingMappingVersionTest extends PublicTestCase
{
    private const string OUTDATED_MAPPING_HASH = 'outdated-hash-4281';

    #[Test]
    public function importMappingVersionFromMDT_givenPendingMappingVersionAndChangedMdtMapping_refusesAndCreatesNoMappingVersion(): void
    {
        // Arrange
        $dungeon     = $this->getDungeon();
        $gameVersion = $this->getGameVersion();

        $mappingVersion = $this->makeCurrentMappingVersionPending($dungeon, $gameVersion, self::OUTDATED_MAPPING_HASH);
        $countBefore    = $dungeon->mappingVersions()->count();

        try {
            // Assert
            $this->expectException(MDTMappingPendingAcceptanceException::class);

            // Act
            $this->app->make(MDTMappingImportServiceInterface::class)->importMappingVersionFromMDT(
                $this->app->make(MappingServiceInterface::class),
                $this->reloadDungeon($dungeon),
                $gameVersion,
            );
        } finally {
            $this->assertSame(
                $countBefore,
                $dungeon->mappingVersions()->count(),
                'The refusal must happen before anything is created.',
            );

            $this->restoreMappingVersion($mappingVersion);
        }
    }

    #[Test]
    public function importMappingVersionFromMDT_givenPendingMappingVersionAndForce_createsANewMappingVersionAnyway(): void
    {
        // Arrange - MDT shipping changes of their own on top of ours does warrant a real new mapping version,
        // so --force must stay able to push one through
        $dungeon     = $this->getDungeon();
        $gameVersion = $this->getGameVersion();

        $mappingVersion = $this->makeCurrentMappingVersionPending($dungeon, $gameVersion, self::OUTDATED_MAPPING_HASH);

        $newMappingVersion = null;

        try {
            // Act
            $newMappingVersion = $this->app->make(MDTMappingImportServiceInterface::class)->importMappingVersionFromMDT(
                $this->app->make(MappingServiceInterface::class),
                $this->reloadDungeon($dungeon),
                $gameVersion,
                true,
            );

            // Assert
            $this->assertGreaterThan($mappingVersion->version, $newMappingVersion->version);
            $this->assertFalse(
                $newMappingVersion->mdt_changes_pending,
                'A mapping version imported from MDT is MDT-matching, whatever its predecessor was.',
            );
        } finally {
            // An Eloquent delete: MappingVersion::boot()'s `deleting` cascade removes what was imported
            $newMappingVersion?->delete();

            $this->restoreMappingVersion($mappingVersion);
        }
    }

    #[Test]
    public function acceptMDTMappingForPendingMappingVersion_givenPendingMappingVersionAndChangedMdtMapping_stampsItInPlace(): void
    {
        // Arrange
        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);
        $dungeon              = $this->getDungeon();
        $gameVersion          = $this->getGameVersion();

        $mappingVersion = $this->makeCurrentMappingVersionPending($dungeon, $gameVersion, self::OUTDATED_MAPPING_HASH);
        $countBefore    = $dungeon->mappingVersions()->count();

        $latestMdtMappingHash = $mappingImportService->getMDTMappingHash($dungeon);

        try {
            // Act
            $accepted = $mappingImportService->acceptMDTMappingForPendingMappingVersion(
                $this->reloadDungeon($dungeon),
                $gameVersion,
            );

            // Assert - same mapping version, now MDT-matching, and no new one alongside it
            $this->assertSame($mappingVersion->id, $accepted->id);
            $this->assertSame($latestMdtMappingHash, $accepted->mdt_mapping_hash);
            $this->assertFalse($accepted->mdt_changes_pending);
            $this->assertSame($countBefore, $dungeon->mappingVersions()->count());
        } finally {
            $this->restoreMappingVersion($mappingVersion);
        }
    }

    #[Test]
    public function acceptMDTMappingForPendingMappingVersion_givenAcceptedMappingVersion_makesItAnMdtImportTarget(): void
    {
        // Arrange - the point of accepting: MDT strings resolve onto it again (#4280)
        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);
        $mappingService       = $this->app->make(MappingServiceInterface::class);
        $dungeon              = $this->getDungeon();
        $gameVersion          = $this->getGameVersion();

        $mappingVersion = $this->makeCurrentMappingVersionPending($dungeon, $gameVersion, self::OUTDATED_MAPPING_HASH);

        try {
            $beforeAccepting = $mappingService->getMappingVersionForMdtAddonVersion($this->reloadDungeon($dungeon), null, $gameVersion);
            $this->assertNotNull($beforeAccepting);
            $this->assertNotSame($mappingVersion->id, $beforeAccepting->id);

            // Act
            $mappingImportService->acceptMDTMappingForPendingMappingVersion($this->reloadDungeon($dungeon), $gameVersion);

            // Assert
            $afterAccepting = $mappingService->getMappingVersionForMdtAddonVersion($this->reloadDungeon($dungeon), null, $gameVersion);
            $this->assertNotNull($afterAccepting);
            $this->assertSame($mappingVersion->id, $afterAccepting->id);
        } finally {
            $this->restoreMappingVersion($mappingVersion);
        }
    }

    #[Test]
    public function acceptMDTMappingForPendingMappingVersion_givenMappingVersionThatIsNotPending_throws(): void
    {
        // Arrange - explicitly not pending, rather than relying on how the seeded data happens to have this
        // dungeon flagged today
        $dungeon     = $this->getDungeon();
        $gameVersion = $this->getGameVersion();

        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);
        MappingVersion::query()->whereKey($mappingVersion->id)->update(['mdt_changes_pending' => false]);

        try {
            // Assert
            $this->expectException(Exception::class);
            $this->expectExceptionMessageMatches('/not awaiting MDT acceptance/');

            // Act
            $this->app->make(MDTMappingImportServiceInterface::class)
                ->acceptMDTMappingForPendingMappingVersion($this->reloadDungeon($dungeon), $gameVersion);
        } finally {
            $this->restoreMappingVersion($mappingVersion);
        }
    }

    #[Test]
    public function acceptMDTMappingForPendingMappingVersion_givenUnchangedMdtMapping_throwsRatherThanClearingTheFlag(): void
    {
        // Arrange - MDT has shipped nothing since, so it cannot have accepted our changes: clearing the flag
        // would hand MDT string imports a mapping that still diverges
        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);
        $dungeon              = $this->getDungeon();
        $gameVersion          = $this->getGameVersion();

        $mappingVersion = $this->makeCurrentMappingVersionPending(
            $dungeon,
            $gameVersion,
            $mappingImportService->getMDTMappingHash($dungeon),
        );

        try {
            // Assert
            $this->expectException(Exception::class);
            $this->expectExceptionMessageMatches('/has not changed/');

            // Act
            $mappingImportService->acceptMDTMappingForPendingMappingVersion($this->reloadDungeon($dungeon), $gameVersion);
        } finally {
            $this->assertTrue(
                MappingVersion::query()->findOrFail($mappingVersion->id)->mdt_changes_pending,
                'The flag must survive a refused acceptance.',
            );

            $this->restoreMappingVersion($mappingVersion);
        }
    }

    #[Test]
    public function acceptMDTMappingForPendingMappingVersion_givenUnchangedMdtMappingAndForce_acceptsAnyway(): void
    {
        // Arrange
        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);
        $dungeon              = $this->getDungeon();
        $gameVersion          = $this->getGameVersion();

        $mappingVersion = $this->makeCurrentMappingVersionPending(
            $dungeon,
            $gameVersion,
            $mappingImportService->getMDTMappingHash($dungeon),
        );

        try {
            // Act
            $accepted = $mappingImportService->acceptMDTMappingForPendingMappingVersion(
                $this->reloadDungeon($dungeon),
                $gameVersion,
                true,
            );

            // Assert
            $this->assertSame($mappingVersion->id, $accepted->id);
            $this->assertFalse($accepted->mdt_changes_pending);
        } finally {
            $this->restoreMappingVersion($mappingVersion);
        }
    }

    private function getDungeon(): Dungeon
    {
        return Dungeon::query()->where('key', DungeonKey::MURDER_ROW->value)->firstOrFail();
    }

    private function getGameVersion(): GameVersion
    {
        return GameVersion::query()->where('key', GameVersion::GAME_VERSION_RETAIL)->firstOrFail();
    }

    /**
     * Flags the dungeon's current mapping version as awaiting MDT acceptance, with $mdtMappingHash standing
     * in for how MDT's mapping compared at the time it was created. Written through the query builder so the
     * clone-on-create/update hooks stay out of it; {@see restoreMappingVersion()} puts the row back.
     */
    private function makeCurrentMappingVersionPending(Dungeon $dungeon, GameVersion $gameVersion, string $mdtMappingHash): MappingVersion
    {
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);

        MappingVersion::query()->whereKey($mappingVersion->id)->update([
            'mdt_mapping_hash'    => $mdtMappingHash,
            'mdt_changes_pending' => true,
        ]);

        // The pre-change row, for restoreMappingVersion() to write back
        return $mappingVersion;
    }

    private function restoreMappingVersion(MappingVersion $mappingVersion): void
    {
        MappingVersion::query()->whereKey($mappingVersion->id)->update([
            'mdt_mapping_hash'    => $mappingVersion->mdt_mapping_hash,
            'mdt_addon_version'   => $mappingVersion->mdt_addon_version,
            'mdt_changes_pending' => $mappingVersion->mdt_changes_pending,
        ]);
    }

    private function reloadDungeon(Dungeon $dungeon): Dungeon
    {
        // Fresh instance so the per-request current-mapping-version cache does not carry over
        return Dungeon::query()->findOrFail($dungeon->id);
    }
}
