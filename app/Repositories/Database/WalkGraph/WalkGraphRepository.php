<?php

namespace App\Repositories\Database\WalkGraph;

use App\Models\WalkGraph\WalkGraph;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\WalkGraph\WalkGraphRepositoryInterface;
use Illuminate\Support\Collection;

class WalkGraphRepository extends DatabaseRepository implements WalkGraphRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(WalkGraph::class);
    }

    public function findByUiMapId(int $uiMapId): ?WalkGraph
    {
        return WalkGraph::query()->where('ui_map_id', $uiMapId)->first();
    }

    public function getUiMapIds(): Collection
    {
        return WalkGraph::query()->pluck('ui_map_id');
    }

    public function saveForUiMapId(int $uiMapId, array $attributes): WalkGraph
    {
        return WalkGraph::query()->updateOrCreate(['ui_map_id' => $uiMapId], $attributes);
    }
}
