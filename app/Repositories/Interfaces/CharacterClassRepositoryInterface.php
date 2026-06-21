<?php

namespace App\Repositories\Interfaces;

use App\Models\CharacterClass;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CharacterClass                  create(array<string, mixed> $attributes)
 * @method CharacterClass|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CharacterClass                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CharacterClass                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                            save(CharacterClass $model)
 * @method bool                            update(CharacterClass $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                            delete(CharacterClass $model)
 * @method Collection<int, CharacterClass> all()
 * @method bool                            exists(array<int, string> $columns)
 */
interface CharacterClassRepositoryInterface extends BaseRepositoryInterface
{
}
