<?php

namespace App\Repositories\Interfaces;

use App\Models\NpcClassification;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method NpcClassification create(array $attributes)
 * @method NpcClassification find(int $id, array $columns = [])
 * @method NpcClassification findOrFail(int $id, array $columns = [])
 * @method NpcClassification findOrNew(int $id, array $columns = [])
 * @method bool save(NpcClassification $model)
 * @method bool update(NpcClassification $model, array $attributes = [], array $options = [])
 * @method bool delete(NpcClassification $model)
 * @method Collection<NpcClassification> all()
 */
interface NpcClassificationRepositoryInterface extends BaseRepositoryInterface
{

}
