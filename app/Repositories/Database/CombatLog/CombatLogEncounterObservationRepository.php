<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogEncounterObservation;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogEncounterObservationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CombatLogEncounterObservationRepository extends DatabaseRepository implements CombatLogEncounterObservationRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(CombatLogEncounterObservation::class);
    }

    public function deleteCreatedBefore(Carbon $cutoff, int $limit): int
    {
        return CombatLogEncounterObservation::query()
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->limit($limit)
            ->toBase()
            ->delete();
    }

    public function getEncounters(?int $dungeonId = null): Collection
    {
        return CombatLogEncounterObservation::query()
            ->when($dungeonId !== null, static fn(Builder $query) => $query->where('dungeon_id', $dungeonId))
            ->distinct()
            ->orderBy('dungeon_id')
            ->orderBy('encounter_id')
            ->get(['dungeon_id', 'encounter_id'])
            ->map(static fn(CombatLogEncounterObservation $observation) => [
                'dungeon_id'   => $observation->dungeon_id,
                'encounter_id' => $observation->encounter_id,
            ])
            ->toBase();
    }

    public function getLatestKillPerRun(int $dungeonId, int $encounterId, int $limit): Collection
    {
        $ids = CombatLogEncounterObservation::query()
            ->where('dungeon_id', $dungeonId)
            ->where('encounter_id', $encounterId)
            ->where('success', true)
            ->groupBy('run_key')
            ->selectRaw('MAX(id) AS id')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('id');

        return CombatLogEncounterObservation::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get()
            ->toBase();
    }
}
