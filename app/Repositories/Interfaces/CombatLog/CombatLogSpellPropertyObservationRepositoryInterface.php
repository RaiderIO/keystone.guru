<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use Illuminate\Support\Collection;

/**
 * The tuple is `(spell_id, property)`, so the history is keyed by the property's enum value.
 *
 * @method CombatLogSpellPropertyObservation                  create(array<string, mixed> $attributes)
 * @method CombatLogSpellPropertyObservation|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogSpellPropertyObservation                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogSpellPropertyObservation                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                               save(CombatLogSpellPropertyObservation $model)
 * @method bool                                               update(CombatLogSpellPropertyObservation $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                               delete(CombatLogSpellPropertyObservation $model)
 * @method Collection<int, CombatLogSpellPropertyObservation> all()
 * @method bool                                               exists(array<int, string> $columns)
 *
 * @extends CombatLogObservationRepositoryInterface<CombatLogSpellPropertyObservation, string>
 */
interface CombatLogSpellPropertyObservationRepositoryInterface extends CombatLogObservationRepositoryInterface
{
}
