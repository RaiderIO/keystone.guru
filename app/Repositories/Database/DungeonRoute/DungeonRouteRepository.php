<?php

namespace App\Repositories\Database\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Random\RandomException;

class DungeonRouteRepository extends DatabaseRepository implements DungeonRouteRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(DungeonRoute::class);
    }

    public function generateRandomPublicKey(): string
    {
        try {
            return DungeonRoute::generateRandomPublicKey();
        } catch (RandomException $e) {
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
}
