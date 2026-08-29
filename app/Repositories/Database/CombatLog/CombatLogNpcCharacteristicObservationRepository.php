<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogNpcCharacteristicObservation;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogNpcCharacteristicObservationRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CombatLogNpcCharacteristicObservationRepository extends DatabaseRepository implements CombatLogNpcCharacteristicObservationRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogNpcCharacteristicObservation::class);
    }

    public function countAll(): int
    {
        return CombatLogNpcCharacteristicObservation::query()->count();
    }

    public function getObservedOnDateRange(): array
    {
        /** @var object{min_date: ?string, max_date: ?string, date_count: int}|null $row */
        $row = CombatLogNpcCharacteristicObservation::query()
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
        /** @var Collection<int, CombatLogNpcCharacteristicObservation> $result */
        $result = CombatLogNpcCharacteristicObservation::query()
            ->selectRaw('npc_id, characteristic_id, COUNT(DISTINCT observed_on) as days_observed')
            ->groupBy('npc_id', 'characteristic_id')
            ->orderBy('npc_id')
            ->orderBy('characteristic_id')
            ->get();

        return $result;
    }

    public function getHistoryForNpc(int $npcId): Collection
    {
        /** @var Collection<int, CombatLogNpcCharacteristicObservation> $rows */
        $rows = CombatLogNpcCharacteristicObservation::query()
            ->where('npc_id', $npcId)
            ->orderBy('characteristic_id')
            ->orderByDesc('observed_on')
            ->get(['characteristic_id', 'observed_on']);

        /** @var Collection<int, Collection<int, Carbon>> $result */
        $result = $rows
            ->groupBy('characteristic_id')
            ->map(static fn(Collection $rows) => $rows->pluck('observed_on')->values());

        return $result;
    }
}
