<?php

namespace Tests\Traits;

use App\Models\Spell\SpellDescriptionImportState;

/**
 * Snapshots the seeded spell_description_import_states row of a game version and puts it back after
 * the test. The row is seeded from database/data/spell_description/import_state.json, and the test
 * database is persistent - a test that deletes or rewrites it without restoring leaves every later
 * test running against a database that records no import at all (#4501).
 */
trait RestoresSpellDescriptionImportState
{
    /** @var array<string, mixed>|null */
    private ?array $spellDescriptionImportStateSnapshot = null;

    private ?int $spellDescriptionImportStateGameVersionId = null;

    protected function captureSpellDescriptionImportState(int $gameVersionId): void
    {
        $this->spellDescriptionImportStateGameVersionId = $gameVersionId;
        $this->spellDescriptionImportStateSnapshot      = SpellDescriptionImportState::query()
            ->find($gameVersionId)?->getAttributes();
    }

    protected function restoreSpellDescriptionImportState(): void
    {
        if ($this->spellDescriptionImportStateGameVersionId === null) {
            return;
        }

        SpellDescriptionImportState::query()
            ->where('game_version_id', $this->spellDescriptionImportStateGameVersionId)
            ->delete();

        if ($this->spellDescriptionImportStateSnapshot !== null) {
            SpellDescriptionImportState::query()->insert($this->spellDescriptionImportStateSnapshot);
        }

        $this->spellDescriptionImportStateGameVersionId = null;
        $this->spellDescriptionImportStateSnapshot      = null;
    }
}
