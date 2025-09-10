<?php

namespace App\Repositories\Interfaces;

use App\Models\CharacterClass;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CharacterClass             create(array $attributes)
 * @method CharacterClass|null        find(int $id, array|string $columns = ['*'])
 * @method CharacterClass             findOrFail(int $id, array|string $columns = ['*'])
 * @method CharacterClass             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                       save(CharacterClass $model)
 * @method bool                       update(CharacterClass $model, array $attributes = [], array $options = [])
 * @method bool                       delete(CharacterClass $model)
 * @method Collection<CharacterClass> all()
 */
interface CharacterClassRepositoryInterface extends BaseRepositoryInterface
{
}
