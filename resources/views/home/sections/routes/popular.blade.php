<?php

use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;
use App\Models\DungeonRoute\DungeonRoute;

/**
 * @var Collection<DungeonRoute> $dungeonRoutes
 */
?>
<div class="row my-4">
    <div class="col-12">
        <h4>{{ __('view_home.sections.routes.popular.title') }}</h4>
    </div>
    <?php
        // w-100 is required to make the cardlist show up properly - to limit the width of the container to just the screen
    ?>
    <div id="category_route_list" class="w-100">
        @include('common.dungeonroute.cardlist', [
            'cols' => 4,
            'currentAffixGroup' => null, // @TODO
            'affixgroup' => null,
            'dungeonroutes' => $dungeonRoutes,
            'showDungeonImage' => true,
            'cardHeaders' => $dungeonRoutes->mapWithKeys(function(DungeonRoute $dungeonRoute){
                return [
                    $dungeonRoute->id => [
                        'text' => __($dungeonRoute->dungeon->name),
                        'link' => route('dungeonroutes.discoverdungeon', [
                            'gameVersion' => GameVersion::getUserOrDefaultGameVersion(),
                            'dungeon' => $dungeonRoute->dungeon,
                        ])
                    ],
                ];
            }),
            'cache' => true,
        ])
    </div>
</div>
