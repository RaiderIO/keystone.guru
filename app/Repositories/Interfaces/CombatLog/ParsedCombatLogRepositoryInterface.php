<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\ParsedCombatLog;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ParsedCombatLog             create(array $attributes)
 * @method ParsedCombatLog|null        find(int $id, array|string $columns = ['*'])
 * @method ParsedCombatLog             findOrFail(int $id, array|string $columns = ['*'])
 * @method ParsedCombatLog             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                        save(ParsedCombatLog $model)
 * @method bool                        update(ParsedCombatLog $model, array $attributes = [], array $options = [])
 * @method bool                        delete(ParsedCombatLog $model)
 * @method Collection<ParsedCombatLog> all()
 * @method bool                        exists(array $columns)
 */
interface ParsedCombatLogRepositoryInterface extends BaseRepositoryInterface
{
}
