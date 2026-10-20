<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\GameVersion\GameVersion;
use App\Models\PublishedState;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteCollectionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DungeonRouteCollectionRepository extends DatabaseRepository implements DungeonRouteCollectionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRouteCollection::class);
    }

    public function lockForUpdate(DungeonRouteCollection $dungeonRouteCollection): void
    {
        DungeonRouteCollection::query()->whereKey($dungeonRouteCollection->id)->lockForUpdate()->first();
    }

    public function touchUpdatedAt(DungeonRouteCollection $dungeonRouteCollection): void
    {
        DungeonRouteCollection::query()->whereKey($dungeonRouteCollection->id)->update(['updated_at' => now()]);
    }

    public function paginateWorldPublished(
        GameVersion                     $gameVersion,
        ?string                         $search,
        ?DungeonRouteCollectionCategory $dungeonRouteCollectionCategory,
        ?Dungeon                        $dungeon,
        int                             $perPage,
    ): LengthAwarePaginator {
        // A route inside a public collection keeps its own published state - only the ones anyone may open count
        // towards the collection being worth listing, or towards it matching a dungeon
        $openablePublishedStateIds = [
            PublishedState::ALL[PublishedState::WORLD],
            PublishedState::ALL[PublishedState::WORLD_WITH_LINK],
        ];

        return DungeonRouteCollection::query()
            ->withTileRelations()
            ->with(['user'])
            ->where('published_state_id', PublishedState::ALL[PublishedState::WORLD])
            ->where('game_version_id', $gameVersion->id)
            ->whereHas('dungeonRoutes', static fn(Builder $builder): Builder => $builder
                ->whereIn('dungeon_routes.published_state_id', $openablePublishedStateIds)
                ->when($dungeon !== null, static fn(Builder $builder): Builder => $builder->where('dungeon_routes.dungeon_id', $dungeon?->id)))
            ->when($search !== null, static fn(Builder $builder): Builder => $builder->where(
                'name',
                'like',
                sprintf('%%%s%%', addcslashes((string)$search, '%_\\')),
            ))
            ->when($dungeonRouteCollectionCategory !== null, static fn(Builder $builder): Builder => $builder->where(
                'dungeon_route_collection_category_id',
                $dungeonRouteCollectionCategory?->id,
            ))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
