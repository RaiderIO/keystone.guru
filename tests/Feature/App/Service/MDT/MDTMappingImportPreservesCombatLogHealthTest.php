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

        $xathuux = Npc::query()->with('npcHealths')->findOrFail(self::XATHUUX_NPC_ID);
        $this->assertSame(
            self::XATHUUX_CORRECTED_HEALTH,
            $xathuux->getHealthByGameVersion($retailGameVersion)?->health,
            'The seeder must ship the corrected health, or this test proves nothing.',
        );

        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        $mdtDungeon = app(MDTDungeon::class, [
            'cacheService'       => app(CacheServiceInterface::class),
            'coordinatesService' => app(CoordinatesServiceInterface::class),
            'dungeon'            => $dungeon,
        ]);

        $mdtHealth = collect($mdtDungeon->getMDTNPCs())
            ->first(static fn($mdtNpc) => $mdtNpc->getId() === self::XATHUUX_NPC_ID)
            ?->getHealth();
        $this->assertNotSame(
            self::XATHUUX_CORRECTED_HEALTH,
            $mdtHealth,
            'MDT must still disagree with the corrected health, or the import has no value to accidentally restore.',
        );

        // Act
        $failures = [];
        $mappingImportService->importNpcsDataFromMDT($mdtDungeon, $dungeon, $retailGameVersion, $failures);

        // Assert
        $this->assertSame([], $failures, 'The import itself must not have failed for any NPC.');

        // Model caching is on in CI: the eager-loaded npcHealths cache under the Npc model, so both need flushing
        new Npc()->flushCache();
        new NpcHealth()->flushCache();
        $this->assertSame(
            self::XATHUUX_CORRECTED_HEALTH,
            Npc::query()->with('npcHealths')->findOrFail(self::XATHUUX_NPC_ID)->getHealthByGameVersion($retailGameVersion)?->health,
            sprintf('The import must not have replaced the corrected health with MDT\'s %s.', var_export($mdtHealth, true)),
        );
    }

    #[Test]
    public function importNpcsDataFromMDT_givenNpcWithPlaceholderHealth_fillsItFromMDT(): void
    {
        // Arrange
        $dungeon = Dungeon::query()->where('key', 'murder_row')->firstOrFail();

        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_RETAIL)->firstOrFail();

        $xathuux   = Npc::query()->with('npcHealths')->findOrFail(self::XATHUUX_NPC_ID);
        $npcHealth = $xathuux->getHealthByGameVersion($retailGameVersion);
        $this->assertNotNull($npcHealth, 'The seeder must ship a health row, or this test proves nothing.');

        try {
            NpcHealth::query()->whereKey($npcHealth->id)->update(['health' => NpcHealth::HEALTH_PLACEHOLDER]);
            Npc::query()->findOrFail(self::XATHUUX_NPC_ID)->flushCache();
            new NpcHealth()->flushCache();

            $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

            $mdtDungeon = app(MDTDungeon::class, [
                'cacheService'       => app(CacheServiceInterface::class),
                'coordinatesService' => app(CoordinatesServiceInterface::class),
                'dungeon'            => $dungeon,
            ]);

            $mdtHealth = collect($mdtDungeon->getMDTNPCs())
                ->first(static fn($mdtNpc) => $mdtNpc->getId() === self::XATHUUX_NPC_ID)
                ?->getHealth();
            $this->assertIsInt($mdtHealth);

            // Act
            $failures = [];
            $mappingImportService->importNpcsDataFromMDT($mdtDungeon, $dungeon, $retailGameVersion, $failures);

            // Assert
            $this->assertSame([], $failures, 'The import itself must not have failed for any NPC.');

            new Npc()->flushCache();
            new NpcHealth()->flushCache();
            $this->assertSame(
                $mdtHealth,
                Npc::query()->with('npcHealths')->findOrFail(self::XATHUUX_NPC_ID)->getHealthByGameVersion($retailGameVersion)?->health,
                'A placeholder health must still be filled from MDT.',
            );
        } finally {
            NpcHealth::query()->whereKey($npcHealth->id)->update(['health' => self::XATHUUX_CORRECTED_HEALTH]);
            Npc::query()->findOrFail(self::XATHUUX_NPC_ID)->flushCache();
            new NpcHealth()->flushCache();
        }
    }
}
