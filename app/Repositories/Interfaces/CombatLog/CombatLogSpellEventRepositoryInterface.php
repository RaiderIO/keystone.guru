<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogSpellEvent;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @method CombatLogSpellEvent                  create(array<string, mixed> $attributes)
 * @method CombatLogSpellEvent|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogSpellEvent                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogSpellEvent                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                 save(CombatLogSpellEvent $model)
 * @method bool                                 update(CombatLogSpellEvent $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                 delete(CombatLogSpellEvent $model)
 * @method Collection<int, CombatLogSpellEvent> all()
 * @method bool                                 exists(array<int, string> $columns)
 */
interface CombatLogSpellEventRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * The most recent events for the given spells, excluding any event on a hidden spell.
     *
     * @param  Collection<int, int>                 $spellIds
     * @param  Collection<int, int>                 $hiddenSpellIds
     * @return Collection<int, CombatLogSpellEvent>
     */
    public function getLatestBySpellIds(Collection $spellIds, Collection $hiddenSpellIds, int $limit): Collection;

    /**
     * The distinct calendar days (as `Y-m-d` strings) on which spell events occurred.
     *
     * @param  Collection<int, int>      $hiddenSpellIds
     * @param  Collection<int, int>|null $spellIds       when given, only these spells are considered
     * @return Collection<int, string>
     */
    public function getDistinctEventDates(Collection $hiddenSpellIds, ?Collection $spellIds = null): Collection;

    /**
     * @param  Collection<int, int>                 $hiddenSpellIds
     * @param  Collection<int, int>|null            $spellIds       when given, only these spells are considered
     * @return Collection<int, CombatLogSpellEvent>
     */
    public function getByDate(Carbon $date, Collection $hiddenSpellIds, ?Collection $spellIds = null): Collection;
}
