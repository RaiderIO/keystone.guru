<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\MDT\Entity\MDTNpc;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Npc\NpcDungeon;
use App\Models\Npc\NpcHealth;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDungeon;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\MDTMappingImportServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * #3989: which spells an NPC casts, and which spells belong to a dungeon, is data we gather ourselves from
 * parsed combat logs (NpcSpellAssignmentCollector and friends). The MDT import used to write that same data
 * from MDT's per-NPC spell list - inserting NpcSpell rows, coupling spells to dungeons via SpellDungeon, and
 * even creating empty placeholder Spell rows for spell IDs the spells table didn't know yet. That is not
 * curated data and must no longer drive any of those three tables.
 *
 * Runs the real import pipeline (real Lua parsing via MDTDungeon) because MDTMappingImportService constructs
 * its MDTDungeon with `new` - there is no DI seam to hand it a stub NPC list. Scoped to importNpcsDataFromMDT()
 * alone rather than the full importMappingVersionFromMDT(): the spell coupling lived there, and calling the
 * whole pipeline would create a mapping version and clone its entire contents into the shared seeded test DB
 * for no added coverage.
 */
#[Group('UsesLua')]
#[Group('MDT')]
final class MDTMappingImportNoSpellCouplingTest extends PublicTestCase
{
    #[Test]
    public function importNpcsDataFromMDT_givenMDTNpcCastingAnUncoupledSpell_doesNotCoupleTheSpellToTheNpcOrDungeon(): void
    {
        // Arrange
        $mappingImportService = $this->app->make(MDTMappingImportServiceInterface::class);

        /** @var GameVersion $retailGameVersion */
        $retailGameVersion = GameVersion::query()->where('key', GameVersion::GAME_VERSION_RETAIL)->firstOrFail();

        // The precondition that makes this test non-vacuous: a dungeon whose MDT export lists a spell for one
        // of its NPCs that npc_spells does NOT already hold. Without it the pre-#3989 code would have had
        // nothing to insert either, and every assertion below would pass no matter what the service does.
        [$dungeon, $uncoupledNpcId, $uncoupledSpellId] = $this->getMdtDungeonWithUncoupledNpcSpell();

        $mdtDungeon = app(MDTDungeon::class, [
            'cacheService'       => app(CacheServiceInterface::class),
            'coordinatesService' => app(CoordinatesServiceInterface::class),
            'dungeon'            => $dungeon,
        ]);
        $mdtNpcIds = $mdtDungeon->getMDTNPCs()->map(static fn(MDTNpc $mdtNpc) => $mdtNpc->getId())->all();

        $npcSpellCountBefore     = NpcSpell::query()->count();
        $spellCountBefore        = Spell::query()->count();
        $spellDungeonCountBefore = SpellDungeon::query()->count();

        // Unlike the spell tables, NpcHealth/NpcDungeon rows are a legitimate product of this import and are not
        // scoped to (nor cascade-deleted by) a mapping version, so snapshot what exists to clean up anything new.
        $preExistingNpcHealthIds = NpcHealth::query()
            ->where('game_version_id', $retailGameVersion->id)
            ->whereIn('npc_id', $mdtNpcIds)
            ->pluck('id')
            ->all();
        $preExistingNpcDungeonIds = NpcDungeon::query()->whereIn('npc_id', $mdtNpcIds)->pluck('id')->all();

        try {
            // Act
            $failures = [];
            $mappingImportService->importNpcsDataFromMDT($mdtDungeon, $dungeon, $retailGameVersion, $failures);

            // Assert
            $this->assertSame([], $failures, 'The import itself must not have failed for any NPC.');

            $this->assertFalse(
                NpcSpell::query()
                    ->where('npc_id', $uncoupledNpcId)
                    ->where('spell_id', $uncoupledSpellId)
                    ->exists(),
                'A spell MDT lists for an NPC must not be coupled to that NPC - only parsed combat log data may do that.',
            );

            $this->assertSame(
                $npcSpellCountBefore,
                NpcSpell::query()->count(),
                'The MDT import must not create any npc_spells rows.',
            );
            $this->assertSame(
                $spellCountBefore,
                Spell::query()->count(),
                'The MDT import must not create placeholder spells rows for spell IDs it does not know.',
            );
            $this->assertSame(
                $spellDungeonCountBefore,
                SpellDungeon::query()->count(),
                'The MDT import must not couple any spell to a dungeon.',
            );
        } finally {
            NpcHealth::query()
                ->where('game_version_id', $retailGameVersion->id)
                ->whereIn('npc_id', $mdtNpcIds)
                ->whereNotIn('id', $preExistingNpcHealthIds)
                ->delete();

            NpcDungeon::query()
                ->whereIn('npc_id', $mdtNpcIds)
                ->whereNotIn('id', $preExistingNpcDungeonIds)
                ->delete();
        }
    }

    /**
     * The first MDT-importable dungeon whose MDT export lists a spell for one of its NPCs that npc_spells does
     * not already hold, as [$dungeon, $npcId, $spellId].
     *
     * @return array{0: Dungeon, 1: int, 2: int}
     */
    private function getMdtDungeonWithUncoupledNpcSpell(): array
    {
        $dungeons = Dungeon::query()
            ->whereNotNull('challenge_mode_id')
            ->with(['floors'])
            ->get()
            ->filter(static fn(Dungeon $dungeon) => Conversion::hasMDTDungeonName($dungeon->key));

        foreach ($dungeons as $dungeon) {
            /** @var Dungeon $dungeon */
            $mdtDungeon = app(MDTDungeon::class, [
                'cacheService'       => app(CacheServiceInterface::class),
                'coordinatesService' => app(CoordinatesServiceInterface::class),
                'dungeon'            => $dungeon,
            ]);

            $mdtNpcs = $mdtDungeon->getMDTNPCs();

            $existingNpcSpellIds = NpcSpell::query()
                ->whereIn('npc_id', $mdtNpcs->map(static fn(MDTNpc $mdtNpc) => $mdtNpc->getId())->all())
                ->get()
                ->map(static fn(NpcSpell $npcSpell) => sprintf('%d-%d', $npcSpell->npc_id, $npcSpell->spell_id))
                ->flip();

            /** @var MDTNpc $mdtNpc */
            foreach ($mdtNpcs as $mdtNpc) {
                foreach (array_keys($mdtNpc->getSpells()) as $spellId) {
                    if (!$existingNpcSpellIds->has(sprintf('%d-%d', $mdtNpc->getId(), $spellId))) {
                        return [$dungeon, $mdtNpc->getId(), (int)$spellId];
                    }
                }
            }
        }

        $this->fail('No MDT-importable dungeon found whose MDT export lists a spell that is not already coupled to the NPC casting it - without one this test cannot prove anything.');
    }
}
