<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogSpellPropertyObservationRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CombatLogSpellPropertyObservationRepository extends DatabaseRepository implements CombatLogSpellPropertyObservationRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogSpellPropertyObservation::class);
    }

    public function countAll(): int
    {
        return CombatLogSpellPropertyObservation::query()->count();
    }

    public function getObservedOnDateRange(): array
    {
        /** @var object{min_date: ?string, max_date: ?string, date_count: int}|null $row */
        $row = CombatLogSpellPropertyObservation::query()
            ->selectRaw('MIN(observed_on) as min_date, MAX(observed_on) as max_date, COUNT(DISTINCT observed_on) as date_count')
            ->first();

        return [
            'min'   => $row?->min_date === null ? null : Carbon::parse($row->min_date),
            'max'   => $row?->max_date === null ? null : Carbon::parse($row->max_date),
            'count' => (int)($row->date_count ?? 0),
        ];
    }

    public function getTupleDensity(): Collection
    {
        /** @var Collection<int, CombatLogSpellPropertyObservation> $result */
        $result = CombatLogSpellPropertyObservation::query()
            ->selectRaw('spell_id, property, COUNT(DISTINCT observed_on) as days_observed')
            ->groupBy('spell_id', 'property')
            ->orderBy('spell_id')
            ->orderBy('property')
            ->get();

        return $result;
    }

    public function getHistoryForSpell(int $spellId): Collection
    {
        /** @var Collection<int, CombatLogSpellPropertyObservation> $rows */
        $rows = CombatLogSpellPropertyObservation::query()
            ->where('spell_id', $spellId)
            ->orderBy('property')
            ->orderByDesc('observed_on')
            ->get(['property', 'observed_on']);

        /** @var Collection<string, Collection<int, Carbon>> $result */
        $result = $rows
            ->groupBy(static fn(CombatLogSpellPropertyObservation $row) => $row->property->value)
            ->map(static fn(Collection $rows) => $rows->pluck('observed_on')->values());

        return $result;
    }
}
