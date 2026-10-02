<?php

namespace App\Repositories\Database\GameVersion;

use App\Models\GameVersion\GameVersion;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\GameVersion\GameVersionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class GameVersionRepository extends DatabaseRepository implements GameVersionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(GameVersion::class);
    }

    public function whereMappingVersionIsUsable(GameVersion $gameVersion, Builder $query): Builder
    {
        return $query->where(static function (Builder $query) use ($gameVersion) {
            $query->where('mapping_versions.game_version_id', $gameVersion->id);

            if ($gameVersion->parent_game_version_id !== null) {
                $query->orWhere(static function (Builder $query) use ($gameVersion) {
                    $query->where('mapping_versions.game_version_id', $gameVersion->parent_game_version_id)
                        ->whereNotExists(static function (QueryBuilder $query) use ($gameVersion) {
                            $query->selectRaw('1')
                                ->from('mapping_versions as own_mapping_versions')
                                ->whereColumn('own_mapping_versions.dungeon_id', 'mapping_versions.dungeon_id')
                                ->where('own_mapping_versions.game_version_id', $gameVersion->id);
                        });
                });
            }
        });
    }

    public function findFirstInDisplayOrder(array $gameVersionIds): ?GameVersion
    {
        return GameVersion::query()
            ->whereIn('id', $gameVersionIds)
            ->orderBy('display_order')
            ->first();
    }
}
