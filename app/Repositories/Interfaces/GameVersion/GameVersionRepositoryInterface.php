<?php

namespace App\Repositories\Interfaces\GameVersion;

use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @method GameVersion                  create(array<string, mixed> $attributes)
 * @method GameVersion|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method GameVersion                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method GameVersion                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                         save(GameVersion $model)
 * @method bool                         update(GameVersion $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                         delete(GameVersion $model)
 * @method Collection<int, GameVersion> all()
 * @method bool                         exists(array<int, string> $columns)
 */
interface GameVersionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Whether routes on the mapping version belong to the game version: it is the game version's own, or the
     * parent's for a dungeon the game version has no mapping version of its own for.
     */
    public function canUseMappingVersion(GameVersion $gameVersion, MappingVersion $mappingVersion): bool;

    /**
     * Limits a query that joins mapping_versions to the routes the game version shows: those on its own mapping
     * versions, and those on the parent's mapping versions of dungeons it has no mapping version of its own for.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel> $query
     * @return Builder<TModel>
     */
    public function whereMappingVersionIsUsable(GameVersion $gameVersion, Builder $query): Builder;
}
