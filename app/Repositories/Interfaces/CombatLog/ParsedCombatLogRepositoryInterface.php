<?php

namespace App\Repositories\Interfaces\CombatLog;

use App\Models\CombatLog\ParsedCombatLog;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method ParsedCombatLog                  create(array<string, mixed> $attributes)
 * @method ParsedCombatLog|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method ParsedCombatLog                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method ParsedCombatLog                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                             save(ParsedCombatLog $model)
 * @method bool                             update(ParsedCombatLog $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                             delete(ParsedCombatLog $model)
 * @method Collection<int, ParsedCombatLog> all()
 * @method bool                             exists(array<string, mixed> $columns)
 */
interface ParsedCombatLogRepositoryInterface extends BaseRepositoryInterface
{
}
