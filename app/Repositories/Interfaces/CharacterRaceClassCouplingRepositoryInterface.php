<?php

namespace App\Repositories\Interfaces;

use App\Models\CharacterRaceClassCoupling;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method CharacterRaceClassCoupling                  create(array<string, mixed> $attributes)
 * @method CharacterRaceClassCoupling|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method CharacterRaceClassCoupling                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method CharacterRaceClassCoupling                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                                        save(CharacterRaceClassCoupling $model)
 * @method bool                                        update(CharacterRaceClassCoupling $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                                        delete(CharacterRaceClassCoupling $model)
 * @method Collection<int, CharacterRaceClassCoupling> all()
 * @method bool                                        exists(array<int, string> $columns)
 */
interface CharacterRaceClassCouplingRepositoryInterface extends BaseRepositoryInterface
{
}
