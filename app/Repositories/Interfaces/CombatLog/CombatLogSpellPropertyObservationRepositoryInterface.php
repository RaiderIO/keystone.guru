<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @method CombatLogSpellPropertyObservation                  create(array<string, mixed> $attributes)
 * @method CombatLogSpellPropertyObservation|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogSpellPropertyObservation                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogSpellPropertyObservation                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                               save(CombatLogSpellPropertyObservation $model)
 * @method bool                                               update(CombatLogSpellPropertyObservation $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                               delete(CombatLogSpellPropertyObservation $model)
 * @method Collection<int, CombatLogSpellPropertyObservation> all()
 * @method bool                                               exists(array<int, string> $columns)
 */
interface CombatLogSpellPropertyObservationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Total row count of the table.
     */
    public function countAll(): int;

    /**
     * The distinct `observed_on` dates present across the whole table.
     *
     * @return array{min: ?Carbon, max: ?Carbon, count: int}
     */
    public function getObservedOnDateRange(): array;

    /**
     * Per `(spell_id, property)` tuple, the number of distinct `observed_on` dates recorded - i.e. how many days
     * that fact has been seen. Ordered deterministically by spell_id, property.
     *
     * @return Collection<int, CombatLogSpellPropertyObservation> partial models exposing spell_id, property, days_observed
     */
    public function getTupleDensity(): Collection;

    /**
     * For one spell, every observed property's list of `observed_on` dates (newest first), keyed by the
     * property's enum value.
     *
     * @return Collection<string, Collection<int, Carbon>>
     */
    public function getHistoryForSpell(int $spellId): Collection;
}
