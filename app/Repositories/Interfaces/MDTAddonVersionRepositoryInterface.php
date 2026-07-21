<?php

namespace App\Repositories\Interfaces;

use App\Models\MDTAddonVersion;
use App\Repositories\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * @method MDTAddonVersion                  create(array<string, mixed> $attributes)
 * @method MDTAddonVersion|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method MDTAddonVersion                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method MDTAddonVersion                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                             save(MDTAddonVersion $model)
 * @method bool                             update(MDTAddonVersion $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                             delete(MDTAddonVersion $model)
 * @method Collection<int, MDTAddonVersion> all()
 * @method bool                             exists(array<int, string> $columns)
 */
interface MDTAddonVersionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * The date the given MDT addonVersion's upstream release was published, or null when the version
     * is unknown (e.g. newer than what has been synced, or a value with no release).
     */
    public function findReleaseDate(int $addonVersion): ?CarbonInterface;

    /**
     * The addonVersion whose release most recently precedes (or equals) the given date — i.e. the MDT
     * version that was live at that moment. Null when the date predates every known release.
     */
    public function findLatestAddonVersionAtDate(CarbonInterface $date): ?int;
}
