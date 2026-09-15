<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogSpellEvent;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogSpellEventRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CombatLogSpellEventRepository extends DatabaseRepository implements CombatLogSpellEventRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogSpellEvent::class);
    }

    public function getLatestBySpellIds(Collection $spellIds, Collection $hiddenSpellIds, int $limit): Collection
    {
        if ($spellIds->isEmpty()) {
            return collect();
        }

        return $this->rejectHiddenSpellEvents(CombatLogSpellEvent::query(), $hiddenSpellIds)
            ->whereIn('spell_id', $spellIds)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function getDistinctEventDates(Collection $hiddenSpellIds, ?Collection $spellIds = null): Collection
    {
        return $this->rejectHiddenSpellEvents(CombatLogSpellEvent::query(), $hiddenSpellIds)
            ->selectRaw('DATE(created_at) as event_date')
            ->when($spellIds !== null, static fn(Builder $builder) => $builder->whereIn('spell_id', $spellIds))
            ->groupByRaw('DATE(created_at)')
            ->pluck('event_date');
    }

    public function getByDate(Carbon $date, Collection $hiddenSpellIds, ?Collection $spellIds = null): Collection
    {
        return $this->rejectHiddenSpellEvents(CombatLogSpellEvent::query(), $hiddenSpellIds)
            ->whereDate('created_at', $date)
            ->when($spellIds !== null, static fn(Builder $builder) => $builder->whereIn('spell_id', $spellIds))
            ->latest('created_at')
            ->get();
    }

    /**
     * Excludes spell events on hidden spells. Applied to the query rather than to the result, so that a burst of
     * hidden-spell events cannot consume a feed's limit budget, and so that a day whose only events are hidden does
     * not surface as an empty day in the activity dates (#4356).
     *
     * @param Builder<CombatLogSpellEvent> $query
     * @param Collection<int, int>         $hiddenSpellIds
     *
     * @return Builder<CombatLogSpellEvent>
     */
    private function rejectHiddenSpellEvents(Builder $query, Collection $hiddenSpellIds): Builder
    {
        if ($hiddenSpellIds->isEmpty()) {
            return $query;
        }

        return $query->whereNotIn('spell_id', $hiddenSpellIds);
    }
}
