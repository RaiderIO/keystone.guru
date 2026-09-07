<?php

namespace App\Service\Compendium;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Repositories\Interfaces\CombatLog\CombatLogNpcEventRepositoryInterface;
use App\Repositories\Interfaces\CombatLog\CombatLogSpellEventRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcDungeonRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcSpellRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NpcCompendiumService implements NpcCompendiumServiceInterface
{
    private const int FEED_LIMIT = 50;

    /** @var array<int, array{npcIds: Collection<int, int>, spellIds: Collection<int, int>}> */
    private array $dungeonIdCache = [];

    /** @var Collection<int, int>|null */
    private ?Collection $hiddenSpellIds = null;

    public function __construct(
        private readonly CombatLogNpcEventRepositoryInterface   $combatLogNpcEventRepository,
        private readonly CombatLogSpellEventRepositoryInterface $combatLogSpellEventRepository,
        private readonly NpcRepositoryInterface                 $npcRepository,
        private readonly NpcDungeonRepositoryInterface          $npcDungeonRepository,
        private readonly NpcSpellRepositoryInterface            $npcSpellRepository,
        private readonly SpellRepositoryInterface               $spellRepository,
    ) {
    }

    public function buildEventFeed(Npc $npc): Collection
    {
        $hiddenSpellIds = $this->getHiddenSpellIds();

        $npcEvents = $this->combatLogNpcEventRepository->getLatestByNpcId($npc->id, $hiddenSpellIds, self::FEED_LIMIT);
        $this->hydrateModelRelation($npcEvents);

        $spellEvents = $this->combatLogSpellEventRepository->getLatestBySpellIds(
            $npc->npcSpells->pluck('spell_id'),
            $hiddenSpellIds,
            self::FEED_LIMIT,
        );
        $this->hydrateSpellRelation($spellEvents);

        return $npcEvents->concat($spellEvents)
            ->sortByDesc('created_at')
            ->take(self::FEED_LIMIT)
            ->values();
    }

    /**
     * @return LengthAwarePaginator<int, string>
     */
    public function getActivityDates(int $perPage = 10, ?Dungeon $dungeon = null): LengthAwarePaginator
    {
        $hiddenSpellIds = $this->getHiddenSpellIds();

        $npcDates = $this->combatLogNpcEventRepository->getDistinctEventDates(
            $hiddenSpellIds,
            $dungeon === null ? null : $this->getNpcIdsForDungeon($dungeon),
        );

        $spellDates = $this->combatLogSpellEventRepository->getDistinctEventDates(
            $hiddenSpellIds,
            $dungeon === null ? null : $this->getDungeonSpellIds($dungeon),
        );

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
        $hiddenSpellIds = $this->getHiddenSpellIds();

        $npcEvents = $this->combatLogNpcEventRepository->getByDate(
            $date,
            $hiddenSpellIds,
            $dungeon === null ? null : $this->getNpcIdsForDungeon($dungeon),
        );
        $this->hydrateModelRelation($npcEvents);
        $this->hydrateNpcRelation($npcEvents);

        $spellEvents = $this->combatLogSpellEventRepository->getByDate(
            $date,
            $hiddenSpellIds,
            $dungeon === null ? null : $this->getDungeonSpellIds($dungeon),
        );
        $this->hydrateSpellRelation($spellEvents);

        return $npcEvents->concat($spellEvents)
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * The ids of every spell flagged `hidden_on_map`. Lives on the main DB while the event tables
     * live on the `combatlog` connection, so it cannot be joined - it is fetched once and handed to
     * the event repositories as an exclusion list instead (#4356).
     *
     * @return Collection<int, int>
     */
    private function getHiddenSpellIds(): Collection
    {
        return $this->hiddenSpellIds ??= $this->spellRepository->getHiddenOnMapSpellIds();
    }

    /**
     * Sets the polymorphic `model` relation on NPC events. The target class varies per row, so no single
     * repository can resolve it - the rows are grouped by class and each group is loaded through its own model.
     *
     * @param Collection<int, CombatLogNpcEvent> $npcEvents
     */
    private function hydrateModelRelation(Collection $npcEvents): void
    {
        $npcEvents->groupBy('model_class')->each(function (Collection $group, string $class): void {
            /** @var class-string<Model> $class */
            $models = $class::query()->whereIn('id', $group->pluck('model_id'))->get()->keyBy('id');
            $group->each(fn(CombatLogNpcEvent $event) => $event->setRelation('model', $models->get($event->model_id)));
        });
    }

    /**
     * @param Collection<int, CombatLogNpcEvent> $npcEvents
     */
    private function hydrateNpcRelation(Collection $npcEvents): void
    {
        if ($npcEvents->isEmpty()) {
            return;
        }

        $npcs = $this->npcRepository->findAllByIdWithTooltipRelations($npcEvents->pluck('npc_id')->unique());

        $npcEvents->each(fn(CombatLogNpcEvent $event) => $event->setRelation('npc', $npcs->get($event->npc_id)));
    }

    /**
     * @param Collection<int, CombatLogSpellEvent> $spellEvents
     */
    private function hydrateSpellRelation(Collection $spellEvents): void
    {
        if ($spellEvents->isEmpty()) {
            return;
        }

        $spells = $this->spellRepository->findAllById($spellEvents->pluck('spell_id')->unique());

        $spellEvents->each(fn(CombatLogSpellEvent $event) => $event->setRelation('spell', $spells->get($event->spell_id)));
    }

    /**
     * @return Collection<int, int>
     */
    private function getNpcIdsForDungeon(Dungeon $dungeon): Collection
    {
        return $this->dungeonIdCache[$dungeon->id]['npcIds'] ??= $this->npcDungeonRepository->getNpcIdsByDungeon($dungeon);
    }

    /**
     * @return Collection<int, int>
     */
    private function getDungeonSpellIds(Dungeon $dungeon): Collection
    {
        return $this->dungeonIdCache[$dungeon->id]['spellIds'] ??= $this->npcSpellRepository->getSpellIdsByNpcIds(
            $this->getNpcIdsForDungeon($dungeon),
        );
    }
}
