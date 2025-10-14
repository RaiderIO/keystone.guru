<?php

namespace App\Repositories\Interfaces;

use App\Models\Release;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Release             create(array $attributes)
 * @method Release|null        find(int $id, array|string $columns = ['*'])
 * @method Release             findOrFail(int $id, array|string $columns = ['*'])
 * @method Release             findOrNew(int $id, array|string $columns = ['*'])
 * @method bool                save(Release $model)
 * @method bool                update(Release $model, array $attributes = [], array $options = [])
 * @method bool                delete(Release $model)
 * @method Collection<Release> all()
 */
interface ReleaseRepositoryInterface extends BaseRepositoryInterface
{
    public function getLatestUnreleasedRelease(): ?Release;

    public function releaseSuccessful(): void;

    public function findReleaseByVersion(?string $version): ?Release;
}
