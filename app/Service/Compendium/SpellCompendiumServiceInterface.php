<?php

namespace App\Service\Compendium;

use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

interface SpellCompendiumServiceInterface
{
    /**
     * Build the merged, sorted event feed for the Spell compendium detail page.
     * Combines CombatLogSpellEvents and CombatLogNpcEvents (SpellAssigned), sorted by created_at descending.
     *
     * @return Collection<int, mixed>
     */
    public function buildEventFeed(Spell $spell): Collection;
}
