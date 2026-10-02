<?php

namespace App\Repositories\Database;

use App\Models\PageView;
use App\Models\PageViewCount;
use App\Repositories\Interfaces\PageViewCountRepositoryInterface;
use Illuminate\Support\Carbon;
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
}
