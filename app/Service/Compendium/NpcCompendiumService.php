<?php

namespace App\Service\Compendium;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcSpell;
use App\Models\Spell\Spell;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NpcCompendiumService implements NpcCompendiumServiceInterface
{
    /** @var array<int, array{npcIds: Collection<int, int>, spellIds: Collection<int, int>}> */
    private array $dungeonIdCache = [];

    /** @var Collection<int, int>|null */
    private ?Collection $hiddenSpellIds = null;

    public function buildEventFeed(Npc $npc): Collection
    {
        $npcEvents = $this->rejectHiddenSpellNpcEvents(CombatLogNpcEvent::query())
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

        $spellIds = $npc->npcSpells->pluck('spell_id');

        $spellEvents = $spellIds->isNotEmpty()
            ? $this->rejectHiddenSpellEvents(CombatLogSpellEvent::query())
                ->whereIn('spell_id', $spellIds)
                ->latest('created_at')
                ->limit(50)
                ->get()
            : collect();

        // Load the spells relation - cannot do that directly due the different DB connection
        if ($spellEvents->isNotEmpty()) {
            $spells = Spell::whereIn('id', $spellEvents->pluck('spell_id')->unique())->get()->keyBy('id');
            $spellEvents->each(fn(CombatLogSpellEvent $event) => $event->setRelation('spell', $spells->get($event->spell_id)));
        }

        return $npcEvents->concat($spellEvents)
            ->sortByDesc('created_at')
            ->take(50)
            ->values();
    }

    /**
     * @return LengthAwarePaginator<int, string>
     */
    public function getActivityDates(int $perPage = 10, ?Dungeon $dungeon = null): LengthAwarePaginator
    {
        $npcDates = $this->rejectHiddenSpellNpcEvents(CombatLogNpcEvent::query())
            ->selectRaw('DATE(created_at) as event_date')
            ->when($dungeon, fn($q) => $q->whereIn('npc_id', $this->getNpcIdsForDungeon($dungeon)))
            ->groupByRaw('DATE(created_at)')
            ->pluck('event_date');

        $spellDates = $this->rejectHiddenSpellEvents(CombatLogSpellEvent::query())
            ->selectRaw('DATE(created_at) as event_date')
            ->when($dungeon, fn($q) => $q->whereIn('spell_id', $this->getDungeonSpellIds($dungeon)))
            ->groupByRaw('DATE(created_at)')
            ->pluck('event_date');

        $allDates = $npcDates->merge($spellDates)
            ->unique()
            ->sortDesc()
            ->values();

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $items       = $allDates->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($items, $allDates->count(), $perPage, $currentPage, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);
    }

    public function getEventsForDate(Carbon $date, ?Dungeon $dungeon = null): Collection
    {
        $npcEvents = $this->rejectHiddenSpellNpcEvents(CombatLogNpcEvent::query())
            ->whereDate('created_at', $date)
            ->when($dungeon, fn($q) => $q->whereIn('npc_id', $this->getNpcIdsForDungeon($dungeon)))
            ->latest('created_at')
            ->get();

        // Eager-load model relations (cross-DB: manual setRelation)
        $npcEvents->groupBy('model_class')->each(function (Collection $group, string $class): void {
            /** @var class-string $class */
            $models = $class::whereIn('id', $group->pluck('model_id'))->get()->keyBy('id');
            $group->each(fn(CombatLogNpcEvent $event) => $event->setRelation('model', $models->get($event->model_id)));
        });

        // Eager-load NPC relation (cross-DB: manual setRelation)
        $npcs = Npc::whereIn('id', $npcEvents->pluck('npc_id')->unique())
            // What the NPC links' hover tooltips read (#4096)
            ->with(['classification', 'type', 'characteristics', 'npcHealths'])
            ->get()
            ->keyBy('id');
        $npcEvents->each(fn(CombatLogNpcEvent $event) => $event->setRelation('npc', $npcs->get($event->npc_id)));

        $spellEvents = $this->rejectHiddenSpellEvents(CombatLogSpellEvent::query())
            ->whereDate('created_at', $date)
            ->when($dungeon, fn($q) => $q->whereIn('spell_id', $this->getDungeonSpellIds($dungeon)))
            ->latest('created_at')
            ->get();

        // Load the spells relation - cannot do that directly due the different DB connection
        if ($spellEvents->isNotEmpty()) {
            $spells = Spell::whereIn('id', $spellEvents->pluck('spell_id')->unique())->get()->keyBy('id');
            $spellEvents->each(fn(CombatLogSpellEvent $event) => $event->setRelation('spell', $spells->get($event->spell_id)));
        }

        return $npcEvents->concat($spellEvents)
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * The ids of every spell flagged `hidden_on_map`. Lives on the main DB while the event tables
     * live on the `combatlog` connection, so it cannot be joined - it is fetched once and applied as
     * a `whereNotIn` instead (#4356).
     *
     * @return Collection<int, int>
     */
    private function getHiddenSpellIds(): Collection
    {
        return $this->hiddenSpellIds ??= Spell::query()->where('hidden_on_map', true)->pluck('id');
    }

    /**
     * Excludes spell events on hidden spells. Applied to the query rather than to the result, so that
     * a burst of hidden-spell events cannot consume the feed's `limit(50)` budget, and so that a day
     * whose only events are hidden does not surface as an empty day in `getActivityDates()`.
     *
     * @param Builder<CombatLogSpellEvent> $query
     *
     * @return Builder<CombatLogSpellEvent>
     */
    private function rejectHiddenSpellEvents(Builder $query): Builder
    {
        return $query->whereNotIn('spell_id', $this->getHiddenSpellIds());
    }

    /**
     * The NPC-event equivalent of {@see self::rejectHiddenSpellEvents()}: an NPC event whose
     * polymorphic target is a hidden spell (a `SpellAssigned`, in practice) is excluded. NPC events
     * pointing at any other model class are untouched.
     *
     * @param Builder<CombatLogNpcEvent> $query
     *
     * @return Builder<CombatLogNpcEvent>
     */
    private function rejectHiddenSpellNpcEvents(Builder $query): Builder
    {
        $hiddenSpellIds = $this->getHiddenSpellIds();

        if ($hiddenSpellIds->isEmpty()) {
            return $query;
        }

        return $query->whereNot(static function (Builder $q) use ($hiddenSpellIds): void {
            $q->where('model_class', Spell::class)->whereIn('model_id', $hiddenSpellIds);
        });
    }

    /**
     * @return Collection<int, int>
     */
    private function getNpcIdsForDungeon(Dungeon $dungeon): Collection
    {
        return $this->dungeonIdCache[$dungeon->id]['npcIds'] ??= DB::table('npc_dungeons')
            ->where('dungeon_id', $dungeon->id)
            ->pluck('npc_id');
    }

    /**
     * @return Collection<int, int>
     */
    private function getDungeonSpellIds(Dungeon $dungeon): Collection
    {
        return $this->dungeonIdCache[$dungeon->id]['spellIds'] ??= NpcSpell::whereIn('npc_id', $this->getNpcIdsForDungeon($dungeon))
            ->pluck('spell_id')
            ->unique();
    }
}
