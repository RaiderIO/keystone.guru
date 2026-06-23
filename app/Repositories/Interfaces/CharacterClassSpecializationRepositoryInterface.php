<?php

namespace App\Repositories\Interfaces;

use App\Models\CharacterClassSpecialization;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CharacterClassSpecialization                  create(array<string, mixed> $attributes)
 * @method CharacterClassSpecialization|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CharacterClassSpecialization                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CharacterClassSpecialization                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                          save(CharacterClassSpecialization $model)
 * @method bool                                          update(CharacterClassSpecialization $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                          delete(CharacterClassSpecialization $model)
 * @method Collection<int, CharacterClassSpecialization> all()
 * @method bool                                          exists(array<int, string> $columns)
 */
interface CharacterClassSpecializationRepositoryInterface extends BaseRepositoryInterface
{
}
