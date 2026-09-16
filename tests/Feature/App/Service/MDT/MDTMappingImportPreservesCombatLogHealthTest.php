<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Data\MDTDungeon;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcHealth;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('UsesLua')]
#[Group('MDT')]
final class MDTMappingImportPreservesCombatLogHealthTest extends PublicTestCase
{
    // Xathuux the Annihilator, Murder Row - #4208 corrected the seeded 23,648,733 (MDT's value, 4.17% high) to the
    // true, combat-log-measured base of 22,702,784.
    private const int XATHUUX_NPC_ID = 234647;

    private const int XATHUUX_CORRECTED_HEALTH = 22_702_784;

    #[Test]
    public function importNpcsDataFromMDT_givenNpcWithExistingHealth_leavesItsHealthAlone(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->where('key', 'murder_row')->firstOrFail();

        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_RETAIL)->firstOrFail();

        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        $mdtDungeon = app(MDTDungeon::class, [
            'cacheService'       => app(CacheServiceInterface::class),
            'coordinatesService' => app(CoordinatesServiceInterface::class),
            'dungeon'            => $dungeon,
        ]);

        // Don't assume what MDT currently reports (it drifts as the package's bundled data changes, e.g. #4211
        // itself) - fetch it first and arrange a sentinel health guaranteed to differ from it, so the assertion
        // below actually proves the import left the stored health alone rather than merely matching by luck.
        $mdtHealth = collect($mdtDungeon->getMDTNPCs())
            ->first(static fn($mdtNpc) => $mdtNpc->getId() === self::XATHUUX_NPC_ID)
            ?->getHealth();
        $this->assertIsInt($mdtHealth);
        $sentinelHealth = $mdtHealth + 1;

        $xathuux   = Npc::query()->with('npcHealths')->findOrFail(self::XATHUUX_NPC_ID);
        $npcHealth = $xathuux->getHealthByGameVersion($retailGameVersion);
        $this->assertNotNull($npcHealth, 'The seeder must ship a health row, or this test proves nothing.');

        try {
            NpcHealth::query()->whereKey($npcHealth->id)->update(['health' => $sentinelHealth]);

            // Act
            $failures = [];
            $mappingImportService->importNpcsDataFromMDT($mdtDungeon, $dungeon, $retailGameVersion, $failures);

            // Assert
            $this->assertSame([], $failures, 'The import itself must not have failed for any NPC.');

            $this->assertSame(
                $sentinelHealth,
                Npc::query()->with('npcHealths')->findOrFail(self::XATHUUX_NPC_ID)->getHealthByGameVersion($retailGameVersion)?->health,
                sprintf('The import must not have replaced the existing health with MDT\'s %s.', var_export($mdtHealth, true)),
            );
        } finally {
            NpcHealth::query()->whereKey($npcHealth->id)->update(['health' => self::XATHUUX_CORRECTED_HEALTH]);
        }
    }

    #[Test]
    public function importNpcsDataFromMDT_givenNpcWithPlaceholderHealth_leavesThePlaceholder(): void
    {
        // Arrange
        [$dungeon, $retailGameVersion, $npcHealth] = $this->arrangeXathuux();

        try {
            NpcHealth::query()->whereKey($npcHealth->id)->update(['health' => NpcHealth::HEALTH_PLACEHOLDER]);

            // Act
            $failures = $this->importMurderRow($dungeon, $retailGameVersion);

            // Assert
            $this->assertSame([], $failures, 'The import itself must not have failed for any NPC.');

            $this->assertSame(
                NpcHealth::HEALTH_PLACEHOLDER,
                $this->findXathuuxHealth($retailGameVersion)?->health,
                'A placeholder is left for combatlog:extractnpchealth to fill, never filled from MDT.',
            );
        } finally {
            NpcHealth::query()->whereKey($npcHealth->id)->update(['health' => self::XATHUUX_CORRECTED_HEALTH]);
        }
    }

    #[Test]
    public function importNpcsDataFromMDT_givenNpcWithoutHealthRow_createsAPlaceholderRow(): void
    {
        // Arrange
        [$dungeon, $retailGameVersion, $npcHealth] = $this->arrangeXathuux();
        $originalAttributes                        = $npcHealth->getAttributes();

        try {
            NpcHealth::query()->whereKey($npcHealth->id)->delete();

            // Act
            $failures = $this->importMurderRow($dungeon, $retailGameVersion);

            // Assert
            $this->assertSame([], $failures, 'The import itself must not have failed for any NPC.');

            $createdNpcHealths = NpcHealth::query()
                ->where('npc_id', self::XATHUUX_NPC_ID)
                ->where('game_version_id', $retailGameVersion->id)
                ->get();
            $this->assertCount(1, $createdNpcHealths);
            $this->assertSame(NpcHealth::HEALTH_PLACEHOLDER, $createdNpcHealths->first()->health);
            $this->assertNull($createdNpcHealths->first()->percentage);
        } finally {
            NpcHealth::query()
                ->where('npc_id', self::XATHUUX_NPC_ID)
                ->where('game_version_id', $retailGameVersion->id)
                ->delete();
            NpcHealth::query()->insert($originalAttributes);
        }
    }

    #[Test]
    public function importNpcsDataFromMDT_givenNpcHealthWithPercentage_leavesThePercentageAlone(): void
    {
        // Arrange
        [$dungeon, $retailGameVersion, $npcHealth] = $this->arrangeXathuux();
        $originalPercentage                        = $npcHealth->percentage;

        try {
            NpcHealth::query()->whereKey($npcHealth->id)->update(['percentage' => 50]);

            // Act
            $failures = $this->importMurderRow($dungeon, $retailGameVersion);

            // Assert
            $this->assertSame([], $failures, 'The import itself must not have failed for any NPC.');

            $this->assertSame(50, $this->findXathuuxHealth($retailGameVersion)?->percentage);
        } finally {
            NpcHealth::query()->whereKey($npcHealth->id)->update(['percentage' => $originalPercentage]);
        }
    }

    /**
     * @return array{Dungeon, GameVersion, NpcHealth}
     */
    private function arrangeXathuux(): array
    {
        $dungeon = Dungeon::query()->where('key', 'murder_row')->firstOrFail();

        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_RETAIL)->firstOrFail();

        $npcHealth = $this->findXathuuxHealth($retailGameVersion);
        $this->assertNotNull($npcHealth, 'The seeder must ship a health row, or this test proves nothing.');

        return [$dungeon, $retailGameVersion, $npcHealth];
    }

    private function findXathuuxHealth(GameVersion $gameVersion): ?NpcHealth
    {
        return Npc::query()->with('npcHealths')->findOrFail(self::XATHUUX_NPC_ID)->getHealthByGameVersion($gameVersion);
    }

    /**
     * @return array<int, Exception>
     */
    private function importMurderRow(Dungeon $dungeon, GameVersion $gameVersion): array
    {
        $mdtDungeon = app(MDTDungeon::class, [
            'cacheService'       => app(CacheServiceInterface::class),
            'coordinatesService' => app(CoordinatesServiceInterface::class),
            'dungeon'            => $dungeon,
        ]);

        $failures = [];
        $this->app->make(MDTMappingImportServiceInterface::class)->importNpcsDataFromMDT($mdtDungeon, $dungeon, $gameVersion, $failures);

        return $failures;
    }
}
