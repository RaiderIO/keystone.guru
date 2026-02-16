<?php

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use Illuminate\Support\Collection;

/**
 * @var Collection<Dungeon>                         $weeklyRouteDungeons
 * @var Collection<string, Collection<WeeklyRoute>> $weeklyRoutes
 */
?>
<div class="row my-4">
    <div class="col-12">
        <h4>{{ __('view_home.sections.routes.weeklyroute.header') }}</h4>

        @include('common.dungeon.gridcards', [
            'colCount' => 12,
            'useAbbreviation' => true,
            'dungeons' => $weeklyRouteDungeons,
            'cardBodyClass' => 'p-0 py-2',
            'imageLinks' => $weeklyRoutes->mapWithKeys(function(Collection $weeklyRoutes, string $dungeonKey) {
                /** @var WeeklyRoute $weeklyRoute */
                $weeklyRoute = $weeklyRoutes->first();
                return [
                    $dungeonKey => route('dungeonroutes.discoverdungeon', [
                        'gameVersion' => GameVersion::getUserOrDefaultGameVersion(),
                        'dungeon' => $weeklyRoute->dungeonRoute->dungeon,
                    ])
                ];
            }),
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
