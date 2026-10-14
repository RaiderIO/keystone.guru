<?php

namespace App\Repositories\Database;

use App\Models\PageView;
use App\Repositories\Interfaces\PageViewRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PageViewRepository extends DatabaseRepository implements PageViewRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(PageView::class);
    }

    public function getViewCountsByModelIdSince(string $modelClass, Carbon $since): Collection
    {
        return PageView::query()
            ->toBase()
            ->selectRaw('`model_id`, COUNT(*) AS `views`')
            ->where('model_class', $modelClass)
            ->where('created_at', '>', $since)
            ->groupBy('model_id')
            ->pluck('views', 'model_id')
            ->map(static fn(int|string $views): int => (int)$views);
    }
}
