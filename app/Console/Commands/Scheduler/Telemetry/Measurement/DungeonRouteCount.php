<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\Telemetry\TelemetryMetric;
use App\Service\Telemetry\Dtos\TelemetryDataPoint;
use Illuminate\Support\Collection;

class DungeonRouteCount extends Measurement
{
    /**
     * {@inheritDoc}
     */
    public function getDataPoints(): array
    {
        /** @var Collection<int, PublishedState> $publishedStates */
        $publishedStates = PublishedState::all();

        $result = [
            new TelemetryDataPoint(TelemetryMetric::MEASUREMENT_DUNGEON_ROUTE_COUNT, 'all', DungeonRoute::count()),
            new TelemetryDataPoint(TelemetryMetric::MEASUREMENT_DUNGEON_ROUTE_COUNT, 'temporary', DungeonRoute::where('author_id', '<=', 0)->count()),
        ];

        // Get a count of routes by published state
        foreach ($publishedStates as $publishedState) {
            $result[] = new TelemetryDataPoint(
                TelemetryMetric::MEASUREMENT_DUNGEON_ROUTE_COUNT,
                sprintf('published_%s', $publishedState->name),
                DungeonRoute::where('published_state_id', $publishedState->id)
                    ->where('author_id', '>', 0)
                    ->count(),
            );
        }

        return $result;
    }
}
