<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\CombatLogNpcEvent;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @method CombatLogNpcEvent                  create(array<string, mixed> $attributes)
 * @method CombatLogNpcEvent|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogNpcEvent                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CombatLogNpcEvent                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                               save(CombatLogNpcEvent $model)
 * @method bool                               update(CombatLogNpcEvent $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                               delete(CombatLogNpcEvent $model)
 * @method Collection<int, CombatLogNpcEvent> all()
 * @method bool                               exists(array<int, string> $columns)
 */
interface CombatLogNpcEventRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * The most recent events for a single NPC, excluding any event pointing at a hidden spell.
     *
     * @param  Collection<int, int>               $hiddenSpellIds
     * @return Collection<int, CombatLogNpcEvent>
     */
    public function getLatestByNpcId(int $npcId, Collection $hiddenSpellIds, int $limit): Collection;

    /**
     * The distinct calendar days (as `Y-m-d` strings) on which NPC events occurred.
     *
     * @param  Collection<int, int>      $hiddenSpellIds
     * @param  Collection<int, int>|null $npcIds         when given, only these NPCs are considered
     * @return Collection<int, string>
     */
    public function getDistinctEventDates(Collection $hiddenSpellIds, ?Collection $npcIds = null): Collection;

    /**
     * @param  Collection<int, int>               $hiddenSpellIds
     * @param  Collection<int, int>|null          $npcIds         when given, only these NPCs are considered
     * @return Collection<int, CombatLogNpcEvent>
     */
    public function getByDate(Carbon $date, Collection $hiddenSpellIds, ?Collection $npcIds = null): Collection;
}
