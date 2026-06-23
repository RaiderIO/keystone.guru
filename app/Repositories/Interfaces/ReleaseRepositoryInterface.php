<?php

namespace App\Repositories\Interfaces;

use App\Models\Release;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @method Release                  create(array<string, mixed> $attributes)
 * @method Release|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method Release                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method Release                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                     save(Release $model)
 * @method bool                     update(Release $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                     delete(Release $model)
 * @method Collection<int, Release> all()
 * @method bool                     exists(array<int, string> $columns)
 */
interface ReleaseRepositoryInterface extends BaseRepositoryInterface
{
    public function getLatestUnreleasedRelease(): ?Release;

    public function releaseSuccessful(): void;

    public function findReleaseByVersion(?string $version): ?Release;
}
