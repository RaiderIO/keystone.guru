<?php

namespace App\Service\Compendium;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Npc\Npc;
use App\Models\Spell\Spell;
use Illuminate\Support\Collection;

class NpcCompendiumService implements NpcCompendiumServiceInterface
{
    public function buildEventFeed(Npc $npc): Collection
    {
        $npcEvents = CombatLogNpcEvent::query()
            ->where('npc_id', $npc->id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        // Eager-load related models grouped by class to avoid N+1
        $npcEvents->groupBy('model_class')->each(function (Collection $group, string $class): void {
            /** @var class-string $class */
            $models = $class::whereIn('id', $group->pluck('model_id'))->get()->keyBy('id');
            $group->each(fn(CombatLogNpcEvent $event) => $event->setRelation('model', $models->get($event->model_id)));
        });

        // Reject events that are related to hidden spells, as they are not relevant for the compendium feed
        $npcEvents = $npcEvents->reject(
            fn(CombatLogNpcEvent $event) => $event->model instanceof Spell && $event->model->hidden_on_map,
        );

        $spellIds = $npc->npcSpells->pluck('spell_id');

        $spellEvents = $spellIds->isNotEmpty()
            ? CombatLogSpellEvent::query()
                ->whereIn('spell_id', $spellIds)
                ->with('spell')
                ->latest('created_at')
                ->limit(50)
                ->get()
            : collect();

        return $npcEvents->merge($spellEvents)
            ->sortByDesc('created_at')
            ->take(50)
            ->values();
    }
}
