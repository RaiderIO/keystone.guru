<?php

namespace App\Repositories\Database;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PageView;
use App\Models\PageViewCount;
use App\Repositories\Interfaces\PageViewCountRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PageViewCountRepository extends DatabaseRepository implements PageViewCountRepositoryInterface
{
    private const int UPSERT_CHUNK_SIZE = 1000;

    public function __construct()
    {
        parent::__construct(PageViewCount::class);
    }

    public function aggregateDay(Carbon $day): int
    {
        $dayStart = $day->copy()->startOfDay();
        $viewedOn = $dayStart->toDateString();

        $rows = PageView::query()
            ->toBase()
            ->selectRaw('`model_class`, `model_id`, COALESCE(`source`, 0) AS `view_source`, COUNT(*) AS `views`')
            ->where('created_at', '>=', $dayStart)
            ->where('created_at', '<', $dayStart->copy()->addDay())
            ->groupByRaw('`model_class`, `model_id`, COALESCE(`source`, 0)')
            ->get()
            ->map(static fn(object $row): array => [
                'model_class' => $row->model_class,
                'model_id'    => (int)$row->model_id,
                'source'      => (int)$row->view_source,
                'viewed_on'   => $viewedOn,
                'views'       => (int)$row->views,
            ]);

        // All or nothing: the prune resumes after the latest aggregated day, so a half-written day would never be completed.
        DB::transaction(static function () use ($rows): void {
            foreach ($rows->chunk(self::UPSERT_CHUNK_SIZE) as $chunk) {
                PageViewCount::query()->upsert(
                    $chunk->values()->all(),
                    ['model_class', 'model_id', 'viewed_on', 'source'],
                    ['views'],
                );
            }
        });

        return $rows->count();
    }

    public function getLatestViewedOn(): ?Carbon
    {
        $latest = PageViewCount::query()->max('viewed_on');

        return $latest === null ? null : Carbon::parse($latest);
    }

    public function getViewsPerDungeon(Carbon $since): Collection
    {
        $viewedOn = $since->toDateString();

        $dungeonViews = PageViewCount::query()
            ->where('model_class', Dungeon::class)
            ->where('source', Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON)
            ->where('viewed_on', '>=', $viewedOn)
            ->groupBy('model_id')
            ->selectRaw('`model_id` AS `dungeon_id`, SUM(`views`) AS `views`')
            ->toBase()
            ->get();

        $routeViews = PageViewCount::query()
            ->join('dungeon_routes', 'dungeon_routes.id', '=', 'page_view_counts.model_id')
            ->where('page_view_counts.model_class', DungeonRoute::class)
            ->whereIn('page_view_counts.source', [
                DungeonRoute::PAGE_VIEW_SOURCE_VIEW_ROUTE,
                DungeonRoute::PAGE_VIEW_SOURCE_PRESENT_ROUTE,
            ])
            ->where('page_view_counts.viewed_on', '>=', $viewedOn)
            ->groupBy('dungeon_routes.dungeon_id')
            ->selectRaw('`dungeon_routes`.`dungeon_id`, SUM(`page_view_counts`.`views`) AS `views`')
            ->toBase()
            ->get();

        return $dungeonViews->concat($routeViews)
            ->groupBy(static fn(object $row): int => (int)$row->dungeon_id)
            ->map(static fn(Collection $rows): int => (int)$rows->sum('views'));
    }
}
