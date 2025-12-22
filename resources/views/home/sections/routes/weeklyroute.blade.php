<?php

use App\Models\Dungeon;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use Illuminate\Support\Collection;

/**
 * @var Collection<Dungeon>                         $weeklyRouteDungeons
 * @var Collection<string, Collection<WeeklyRoute>> $weeklyRoutes
 */
?>
<div class="row my-4">
    <div class="col-12">
        <h4>Raider.IO weekly route</h4>

        @include('common.dungeon.gridcards', [
            'dungeons' => $weeklyRouteDungeons,
            'links' => $weeklyRoutes->mapWithKeys(function(Collection $weeklyRoutes, string $dungeonKey) {
                return [
                    $dungeonKey => $weeklyRoutes->map(function(WeeklyRoute $weeklyRoute) {
                        $dungeonRoute = $weeklyRoute->dungeonRoute;
                        return [
                            'text' => __(sprintf('view_home.sections.routes.weeklyroute.%s', $weeklyRoute->type)),
                            'href' => route('dungeonroute.view', [
                                'dungeon' => $dungeonRoute->dungeon,
                                'dungeonroute' => $dungeonRoute,
                                'title' => $dungeonRoute->getTitleSlug()
                            ])
                        ];
                    })
                ];
            })
        ])
    </div>
</div>
