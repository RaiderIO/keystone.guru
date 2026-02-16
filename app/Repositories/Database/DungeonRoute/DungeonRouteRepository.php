<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\TagCategory;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Random\RandomException;

class DungeonRouteRepository extends DatabaseRepository implements DungeonRouteRepositoryInterface
{
    public function __construct(
        private readonly SeasonServiceInterface $seasonService,
    ) {
        parent::__construct(DungeonRoute::class);
    }

    public function generateRandomPublicKey(): string
    {
        try {
            return DungeonRoute::generateRandomPublicKey();
        } catch (RandomException) {
            return 'RandomException!';
        }
    }

    public function getDungeonRoutesWithExpiredThumbnails(?Collection $dungeonRoutes = null): Collection
    {
        /** @var Collection<DungeonRoute> $routes */
        return DungeonRoute::where('author_id', '>', '0')
            // Check if in queue, if so skip, unless the queue age is longer than keystoneguru.thumbnail.refresh_requeue_hours
            ->where(static function (Builder $builder) {
                $builder->whereColumn('thumbnail_refresh_queued_at', '<', 'thumbnail_updated_at')
                    ->orWhere(static function (Builder $builder) {
                        // If it is in the queue to be refreshed
                        $builder->whereColumn('thumbnail_refresh_queued_at', '>', 'thumbnail_updated_at')
                            ->whereDate('thumbnail_refresh_queued_at', '<', now()->subHours(config('keystoneguru.thumbnail.refresh_requeue_hours'))->toDateTimeString());
                    });
            })
            ->where(static function (Builder $builder) {
                // Only if it's not already queued!
                $builder->whereColumn('updated_at', '>', 'thumbnail_updated_at')
                    ->whereDate('updated_at', '<', now()->subMinutes(config('keystoneguru.thumbnail.refresh_min'))->toDateTimeString());
            })
            ->when($dungeonRoutes, function (Builder $builder) use ($dungeonRoutes) {
                // If we have a specific set of routes to refresh, only select those
                $builder->whereIn('id', $dungeonRoutes->pluck('id'));
            })->when(!$dungeonRoutes, function (Builder $builder) {
                // Otherwise, only select routes that have been recently updated/viewed/accessed
                $builder->where('popularity', '>', 0);
            })
            // Published routes get priority! This is only really relevant initially while processing the thumbnail queue
            ->orderBy('published_state_id', 'desc')
            // Newest first
            ->orderBy('id', 'desc')
            // Limit the amount of routes at a time, do not overflow the queue since we cannot process more anyway
            ->limit(config('keystoneguru.thumbnail.refresh_outdated_count'))
            ->get();
    }

    /**
     * @return Collection<string, Collection<WeeklyRoute>>
     */
    public function getWeeklyRoutes(?Dungeon $dungeon = null): Collection
    {
        $currentSeason = $this->seasonService->getCurrentSeason();

        $weeklyRouteTags = config('keystoneguru.raider_io.weekly_route.tags');
        $tagCategoryId   = TagCategory::ALL[TagCategory::DUNGEON_ROUTE_TEAM];
        $raiderIOTeamId  = config('keystoneguru.raider_io.team_id');
        $tagsFilterFn    = function (HasMany|Builder $query) use (
            $weeklyRouteTags,
            $tagCategoryId,
            $raiderIOTeamId
        ) {
            $query->whereIn('name', $weeklyRouteTags)
                ->where('tag_category_id', $tagCategoryId)
                ->where('context_id', $raiderIOTeamId);
        };

        return DungeonRoute::where('team_id', config('keystoneguru.raider_io.team_id'))
            ->with([
                'author',
                'dungeon',
                'tags' => $tagsFilterFn,
            ])
            ->when($dungeon, fn(Builder $query) => $query->where('dungeon_id', $dungeon->id))
            ->whereRelation(
                'dungeon.seasonDungeons',
                'season_id',
                $currentSeason->id,
            )
            ->whereRelation('tags', $tagsFilterFn)
            ->orderBy('dungeon_id')
            ->get()
            ->groupBy(fn(DungeonRoute $route) => $route->dungeon->key)
            ->map(function (Collection $dungeonRoutes) use ($weeklyRouteTags) {
                $result = collect();
                foreach ($weeklyRouteTags as $key => $value) {
                    /** @var DungeonRoute $dungeonRoute */
                    $dungeonRoute = $dungeonRoutes->first(fn(DungeonRoute $route) => $route->tags->first()?->name === $value);
                    if ($dungeonRoute) {
                        $result->push(new WeeklyRoute($key, $dungeonRoute));
                    }
                }

                return $result;
            });
    }
}
