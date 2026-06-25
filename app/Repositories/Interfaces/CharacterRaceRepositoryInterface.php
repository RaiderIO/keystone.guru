<?php

namespace App\Repositories\Interfaces;

use App\Models\CharacterRace;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CharacterRace                  create(array<string, mixed> $attributes)
 * @method CharacterRace|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CharacterRace                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CharacterRace                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                           save(CharacterRace $model)
 * @method bool                           update(CharacterRace $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                           delete(CharacterRace $model)
 * @method Collection<int, CharacterRace> all()
 * @method bool                           exists(array<int, string> $columns)
 */
interface CharacterRaceRepositoryInterface extends BaseRepositoryInterface
{
}
