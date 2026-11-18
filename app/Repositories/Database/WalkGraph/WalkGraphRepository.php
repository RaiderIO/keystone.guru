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

    public function allSummariesWithFloors(): Collection
    {
        return WalkGraph::query()
            ->select([
                'id',
                'ui_map_id',
                'cell_size',
                'run_count',
                'node_count',
                'edge_count',
                'largest_component_node_count',
                'built_at',
            ])
            ->with(['floors.dungeon.expansion'])
            ->orderBy('ui_map_id')
            ->get();
    }

    public function findByUiMapIdWithFloors(int $uiMapId): ?WalkGraph
    {
        return WalkGraph::query()
            ->with(['floors.dungeon.expansion'])
            ->where('ui_map_id', $uiMapId)
            ->first();
    }

    public function saveForUiMapId(int $uiMapId, array $attributes): WalkGraph
    {
        return WalkGraph::query()->updateOrCreate(['ui_map_id' => $uiMapId], $attributes);
    }
}
