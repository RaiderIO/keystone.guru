<?php

namespace App\Console\Commands\Scheduler\Telemetry\Measurement;


use App\Models\DungeonRoute;
use App\Models\PublishedState;
use Illuminate\Support\Collection;
use InfluxDB\Point;

class DungeonRouteCount extends Measurement
{
    /**
     * @inheritDoc
     */
    function getPoints(): array
    {
        /** @var PublishedState[]|Collection $publishedStates */
        $publishedStates = PublishedState::all();

        // Get a count of routes by published state
        $publishedStateFields = [];
        foreach ($publishedStates as $publishedState) {
            $publishedStateFields[sprintf('published_%s', $publishedState->name)] =
                DungeonRoute::where('published_state_id', $publishedState->id)
                    ->where('author_id', '>', 0)
                    ->count();
        }

        return [
            new Point(
                'dungeon_route_count',
                null,
                $this->getTags(),
                // Merge the published states with the 'all' and 'temporary' fields
                array_merge([
                    'all'       => DungeonRoute::count(),
                    'temporary' => DungeonRoute::where('author_id', '<=', 0)->count()
                ], $publishedStateFields),
                time()
            )
        ];
    }
}
