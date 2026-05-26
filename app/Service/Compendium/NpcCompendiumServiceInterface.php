<?php

namespace App\Service\Compendium;

use App\Models\Npc\Npc;
use Illuminate\Support\Collection;

interface NpcCompendiumServiceInterface
{
    /**
     * Build the merged, sorted event feed for the NPC compendium detail page.
     * Combines CombatLogNpcEvents and CombatLogSpellEvents, sorted by created_at descending.
     *
     * @return Collection<int, \App\Models\CombatLog\CombatLogNpcEvent|\App\Models\CombatLog\CombatLogSpellEvent>
     */
    public function buildEventFeed(Npc $npc): Collection;
}
