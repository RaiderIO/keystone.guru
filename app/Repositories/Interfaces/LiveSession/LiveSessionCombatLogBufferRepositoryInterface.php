<?php

namespace App\Repositories\Interfaces\LiveSession;

use App\Models\LiveSession\LiveSessionCombatLogBuffer;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method LiveSessionCombatLogBuffer                  create(array<string, mixed> $attributes)
 * @method LiveSessionCombatLogBuffer|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionCombatLogBuffer                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method LiveSessionCombatLogBuffer                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                        save(LiveSessionCombatLogBuffer $model)
 * @method bool                                        update(LiveSessionCombatLogBuffer $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                        delete(LiveSessionCombatLogBuffer $model)
 * @method Collection<int, LiveSessionCombatLogBuffer> all()
 * @method bool                                        exists(array<int, string> $columns)
 */
interface LiveSessionCombatLogBufferRepositoryInterface extends BaseRepositoryInterface
{
}
