<?php

namespace App\Repositories\Interfaces;

use App\Models\PageViewCount;
use App\Repositories\BaseRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @method PageViewCount                  create(array<string, mixed> $attributes)
 * @method PageViewCount|null             find(int $id, array<int, string>|string $columns = ['*'])
 * @method PageViewCount                  findOrFail(int $id, array<int, string>|string $columns = ['*'])
 * @method PageViewCount                  findOrNew(int $id, array<int, string>|string $columns = ['*'])
 * @method bool                           save(PageViewCount $model)
 * @method bool                           update(PageViewCount $model, array<string, mixed> $attributes = [], array<string, mixed> $options = [])
 * @method bool                           delete(PageViewCount $model)
 * @method Collection<int, PageViewCount> all()
 * @method bool                           exists(array<int, string> $columns)
 */
interface PageViewCountRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Counts the page views of $day per model and source into page_view_counts, replacing any earlier count of
     * that day.
     *
     * @return int The number of (model, source) counts written
     */
    public function aggregateDay(Carbon $day): int;

    /**
     * The most recent day that has been aggregated, or null when nothing has been aggregated yet.
     */
    public function getLatestViewedOn(): ?Carbon;
}
