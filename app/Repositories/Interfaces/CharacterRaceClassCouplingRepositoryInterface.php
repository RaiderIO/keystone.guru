<?php

namespace App\Repositories\Interfaces;

use App\Models\CharacterRaceClassCoupling;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CharacterRaceClassCoupling create(array $attributes)
 * @method CharacterRaceClassCoupling|null find(int $id, array|string $columns = ['*'])
 * @method CharacterRaceClassCoupling findOrFail(int $id, array|string $columns = ['*'])
 * @method CharacterRaceClassCoupling findOrNew(int $id, array|string $columns = ['*'])
 * @method bool save(CharacterRaceClassCoupling $model)
 * @method bool update(CharacterRaceClassCoupling $model, array $attributes = [], array $options = [])
 * @method bool delete(CharacterRaceClassCoupling $model)
 * @method Collection<CharacterRaceClassCoupling> all()
 */
interface CharacterRaceClassCouplingRepositoryInterface extends BaseRepositoryInterface
{

}
