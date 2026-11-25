<?php

namespace App\Repositories\Database\CombatLog;

use App\Models\CombatLog\CombatLogEncounterObservation;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\CombatLog\CombatLogEncounterObservationRepositoryInterface;
use Illuminate\Support\Carbon;

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
}
