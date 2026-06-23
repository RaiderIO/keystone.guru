<?php

namespace App\Service\Compendium;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

interface SpellCompendiumServiceInterface
{
    /**
     * Build the merged, sorted event feed for the Spell compendium detail page.
     * Combines CombatLogSpellEvents and CombatLogNpcEvents (SpellAssigned), sorted by created_at descending.
     *
     * @return Collection<int, CombatLogNpcEvent|CombatLogSpellEvent>
     */
    public function buildEventFeed(Spell $spell): Collection;
}
