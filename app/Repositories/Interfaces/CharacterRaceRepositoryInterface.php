<?php

namespace App\Repositories\Interfaces;

use App\Models\CharacterRace;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CharacterRace create(array $attributes)
 * @method CharacterRace|null find(int $id, array|string $columns = ['*'])
 * @method CharacterRace findOrFail(int $id, array|string $columns = ['*'])
 * @method CharacterRace findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(CharacterRace $model)
 * @method bool update(CharacterRace $model, array $attributes = [], array $options = [])
 * @method bool delete(CharacterRace $model)
 * @method Collection<CharacterRace> all()
 */
interface CharacterRaceRepositoryInterface extends BaseRepositoryInterface
{

}
