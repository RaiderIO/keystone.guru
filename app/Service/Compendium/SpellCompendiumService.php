<?php

namespace App\Service\Compendium;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

class SpellCompendiumService implements SpellCompendiumServiceInterface
{
    public function buildEventFeed(Spell $spell): Collection
    {
        $spellEvents = CombatLogSpellEvent::query()
            ->where('spell_id', $spell->id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        // Load the spell relation - cannot do that directly due to the different DB connection
        $spellEvents->each(fn(CombatLogSpellEvent $event) => $event->setRelation('spell', $spell));

        $npcEvents = CombatLogNpcEvent::query()
            ->where('model_class', Spell::class)
            ->where('model_id', $spell->id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        if ($npcEvents->isNotEmpty()) {
            // All NPC events reference the same spell - set the model relation directly
            $npcEvents->each(fn(CombatLogNpcEvent $event) => $event->setRelation('model', $spell));

            // Load the NPC relation - cannot do that directly due to the different DB connection
            $npcs = Npc::whereIn('id', $npcEvents->pluck('npc_id')->unique())->get()->keyBy('id');
            $npcEvents->each(fn(CombatLogNpcEvent $event) => $event->setRelation('npc', $npcs->get($event->npc_id)));
        }

        /** @var Collection<int, CombatLogNpcEvent|CombatLogSpellEvent> */
        return $spellEvents->merge($npcEvents) // @phpstan-ignore argument.type (intentional merge of related combat log event collections sharing the same DB table)
            ->sortByDesc('created_at')
            ->take(50)
            ->values();
    }
}
