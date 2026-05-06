<?php

namespace App\Repositories\Interfaces;

use App\Models\CharacterClassSpecialization;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CharacterClassSpecialization             create(array $attributes)
 * @method CharacterClassSpecialization|null        find(int $id, array|string $columns = ['*'])
 * @method CharacterClassSpecialization             findOrFail(int $id, array|string $columns = ['*'])
 * @method CharacterClassSpecialization             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                                     save(CharacterClassSpecialization $model)
 * @method bool                                     update(CharacterClassSpecialization $model, array $attributes = [], array $options = [])
 * @method bool                                     delete(CharacterClassSpecialization $model)
 * @method Collection<CharacterClassSpecialization> all()
 */
interface CharacterClassSpecializationRepositoryInterface extends BaseRepositoryInterface
{
}
