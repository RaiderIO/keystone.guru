<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Models\Spell\Spell;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogNpcEventRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CombatLogNpcEventRepository extends DatabaseRepository implements CombatLogNpcEventRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogNpcEvent::class);
    }

    public function getLatestByNpcId(int $npcId, Collection $hiddenSpellIds, int $limit): Collection
    {
        return $this->rejectHiddenSpellEvents(CombatLogNpcEvent::query(), $hiddenSpellIds)
            ->where('npc_id', $npcId)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function getDistinctEventDates(Collection $hiddenSpellIds, ?Collection $npcIds = null): Collection
    {
        return $this->rejectHiddenSpellEvents(CombatLogNpcEvent::query(), $hiddenSpellIds)
            ->selectRaw('DATE(created_at) as event_date')
            ->when($npcIds !== null, static fn(Builder $builder) => $builder->whereIn('npc_id', $npcIds))
            ->groupByRaw('DATE(created_at)')
            ->pluck('event_date');
    }

    public function getByDate(Carbon $date, Collection $hiddenSpellIds, ?Collection $npcIds = null): Collection
    {
        return $this->rejectHiddenSpellEvents(CombatLogNpcEvent::query(), $hiddenSpellIds)
            ->whereDate('created_at', $date)
            ->when($npcIds !== null, static fn(Builder $builder) => $builder->whereIn('npc_id', $npcIds))
            ->latest('created_at')
            ->get();
    }

    /**
     * The NPC-event equivalent of {@see CombatLogSpellEventRepository::rejectHiddenSpellEvents()}: an NPC event whose
     * polymorphic target is a hidden spell (a `SpellAssigned`, in practice) is excluded. NPC events pointing at any
     * other model class are untouched. Applied to the query rather than to the result, so that a burst of
     * hidden-spell events cannot consume a feed's limit budget, and so that a day whose only events are hidden does
     * not surface as an empty day in the activity dates (#4356).
     *
     * @param Builder<CombatLogNpcEvent> $query
     * @param Collection<int, int>       $hiddenSpellIds
     *
     * @return Builder<CombatLogNpcEvent>
     */
    private function rejectHiddenSpellEvents(Builder $query, Collection $hiddenSpellIds): Builder
    {
        if ($hiddenSpellIds->isEmpty()) {
            return $query;
        }

        return $query->whereNot(static function (Builder $builder) use ($hiddenSpellIds): void {
            $builder->where('model_class', Spell::class)->whereIn('model_id', $hiddenSpellIds);
        });
    }
}
