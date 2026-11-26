<?php

namespace App\Service\Compendium;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\CombatLog\CombatLogSpellEvent;
use App\Models\Dungeon;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcEncounterMechanic;
use App\Models\Npc\NpcEncounterMechanicType;
use App\Models\Spell\Spell;
use App\Repositories\Interfaces\CombatLog\CombatLogNpcEventRepositoryInterface;
use App\Repositories\Interfaces\CombatLog\CombatLogSpellEventRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcDungeonRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcEncounterMechanicRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcSpellRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\Compendium\Dtos\NpcEncounterMechanics;
use Illuminate\Database\Eloquent\Builder;
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
        private readonly CombatLogNpcEventRepositoryInterface    $combatLogNpcEventRepository,
        private readonly CombatLogSpellEventRepositoryInterface  $combatLogSpellEventRepository,
        private readonly NpcRepositoryInterface                  $npcRepository,
        private readonly NpcDungeonRepositoryInterface           $npcDungeonRepository,
        private readonly NpcSpellRepositoryInterface             $npcSpellRepository,
        private readonly SpellRepositoryInterface                $spellRepository,
        private readonly NpcEncounterMechanicRepositoryInterface $npcEncounterMechanicRepository,
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

    public function getEncounterMechanics(Npc $npc): NpcEncounterMechanics
    {
        $rows = $this->npcEncounterMechanicRepository->getForEncountersOfNpc($npc->id);

        /** @var Collection<int, NpcEncounterMechanic> $encounterMechanics */
        $encounterMechanics = collect();
        /** @var Collection<int, NpcEncounterMechanic> $npcMechanics */
        $npcMechanics          = collect();
        $bossNpcByEncounterKey = [];
        $typeOrder             = array_flip(array_map(static fn(NpcEncounterMechanicType $type) => $type->value, NpcEncounterMechanicType::cases()));

        foreach ($rows->groupBy(NpcEncounterMechanics::getEncounterKey(...)) as $encounterKey => $encounterRows) {
            $bossRows = $encounterRows->filter(static fn(NpcEncounterMechanic $row) => $row->type === NpcEncounterMechanicType::Boss);
            $bossNpc  = $bossRows->sortBy('npc_id')->first()?->npc;
            if ($bossNpc !== null) {
                $bossNpcByEncounterKey[$encounterKey] = $bossNpc;
            }

            if ($bossRows->contains('npc_id', $npc->id)) {
                $encounterMechanics = $encounterMechanics->concat(
                    $encounterRows->reject(static fn(NpcEncounterMechanic $row) => $row->type === NpcEncounterMechanicType::Boss),
                );
            } else {
                $npcMechanics = $npcMechanics->concat($encounterRows->filter(static fn(NpcEncounterMechanic $row) => $row->npc_id === $npc->id && in_array($row->type, [
                    NpcEncounterMechanicType::Adds,
                    NpcEncounterMechanicType::Friendly,
                    NpcEncounterMechanicType::AffixExempt,
                ], true)));
            }
        }

        /** @var Collection<int, Npc> $npcsById */
        $npcsById      = $rows->pluck('npc')->filter()->keyBy('id');
        $missingNpcIds = $encounterMechanics
            ->flatMap(static fn(NpcEncounterMechanic $row) => $row->getSharedHealthNpcIds())
            ->unique()
            ->reject(static fn(int $npcId) => $npcsById->has($npcId))
            ->values();
        if ($missingNpcIds->isNotEmpty()) {
            $npcsById = $npcsById->union($this->npcRepository->findAllById($missingNpcIds));
        }

        $sortByType = static fn(Collection $rows) => $rows
            ->sortBy(static fn(NpcEncounterMechanic $row) => sprintf('%02d %010d', $typeOrder[$row->type->value], $row->id))
            ->values();

        return new NpcEncounterMechanics(
            $sortByType($encounterMechanics),
            $sortByType($npcMechanics),
            $npcsById,
            $bossNpcByEncounterKey,
        );
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
        $dateString = $date->format('Y-m-d');
        /** @var Collection<int, string> $dates */
        $dates = collect([$dateString]);

        return $this->getEventsForDates($dates, $dungeon)->get($dateString);
    }

    public function getEventsForDates(Collection $dates, ?Dungeon $dungeon = null): Collection
    {
        if ($dates->isEmpty()) {
            return collect();
        }

        $hiddenSpellIds = $this->getHiddenSpellIds();

        $npcEvents = $this->combatLogNpcEventRepository->getByDates(
            $dates,
            $hiddenSpellIds,
            $dungeon === null ? null : $this->getNpcIdsForDungeon($dungeon),
        );
        $this->hydrateModelRelation($npcEvents);
        $this->hydrateNpcRelation($npcEvents);

        $spellEvents = $this->combatLogSpellEventRepository->getByDates(
            $dates,
            $hiddenSpellIds,
            $dungeon === null ? null : $this->getDungeonSpellIds($dungeon),
        );
        $this->hydrateSpellRelation($spellEvents);

        $eventsByDate = $npcEvents->concat($spellEvents)
            ->sortByDesc('created_at')
            ->groupBy(static fn(CombatLogNpcEvent|CombatLogSpellEvent $event): string => $event->created_at->format('Y-m-d'));

        return $dates->mapWithKeys(static fn(string $date): array => [
            $date => ($eventsByDate->get($date) ?? new Collection())->values(),
        ]);
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
            // A spell model is rendered as a spell link, whose tooltip reads the description of the
            // locale being viewed
            $models = $class::query()
                ->when($class === Spell::class, static fn(Builder $builder) => $builder->with('descriptionTranslation'))
                ->whereIn('id', $group->pluck('model_id'))
                ->get()
                ->keyBy('id');
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

        $spells = $this->spellRepository->findAllByIdWithTooltipRelations($spellEvents->pluck('spell_id')->unique());

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
