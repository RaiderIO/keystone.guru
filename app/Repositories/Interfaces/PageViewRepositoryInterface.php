<?php

namespace App\Repositories\Interfaces;

use App\Models\PageView;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @method PageView                  create(array<string, mixed> $attributes)
 * @method PageView|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method PageView                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method PageView                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                      save(PageView $model)
 * @method bool                      update(PageView $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                      delete(PageView $model)
 * @method Collection<int, PageView> all()
 * @method bool                      exists(array<int, string> $columns)
 */
interface PageViewRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Collection<int, int> The number of page views of each model of the given class since the given moment, keyed by model id
     */
    public function getViewCountsByModelIdSince(string $modelClass, Carbon $since): Collection;
}
