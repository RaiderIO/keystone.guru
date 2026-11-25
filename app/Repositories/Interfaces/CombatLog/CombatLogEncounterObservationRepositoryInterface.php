<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogEncounterObservation;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @method CombatLogEncounterObservation                  create(array<string, mixed> $attributes)
 * @method CombatLogEncounterObservation|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogEncounterObservation                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogEncounterObservation                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                           save(CombatLogEncounterObservation $model)
 * @method bool                                           update(CombatLogEncounterObservation $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                           delete(CombatLogEncounterObservation $model)
 * @method Collection<int, CombatLogEncounterObservation> all()
 * @method bool                                           exists(array<string, mixed> $columns)
 */
interface CombatLogEncounterObservationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Deletes up to $limit observations created before the cutoff.
     *
     * @return int the number of deleted rows
     */
    public function deleteCreatedBefore(Carbon $cutoff, int $limit): int;
}
