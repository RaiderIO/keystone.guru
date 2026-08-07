<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\DungeonRouteEnemyForcesPageResolver;
use App\Service\DungeonRoute\DungeonRouteKillZoneServiceInterface;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, DungeonRoute>            $dungeonroutes
 * @var int                                       $startRank
 * @var bool                                      $cache
 * @var DungeonRouteEnemyForcesPageResolver|null  $pullForcesResolver Batches the per-pull enemy
 *      forces query across every card on the page, including any hero cards rendered alongside this
 *      leaderboard - see overview.blade.php. Built locally, scoped to just this leaderboard, when
 *      the caller doesn't already have one.
 */

$startRank ??= 1;
$cache     ??= true;
$pullForcesResolver ??= new DungeonRouteEnemyForcesPageResolver(
    app(DungeonRouteKillZoneServiceInterface::class),
    $dungeonroutes,
);
?>
@if($dungeonroutes->isEmpty())
    <div class="row g-0">
        <div class="col-xl text-center">
            {{ __('view_common.dungeonroute.cardlist.no_dungeonroutes') }}
        </div>
    </div>
@else
    <div class="dungeonroute_leaderboard">
        @foreach($dungeonroutes as $dungeonroute)
            @include('common.dungeonroute.cardrow', [
                'dungeonroute' => $dungeonroute,
                'rank' => $startRank + $loop->index,
                'cache' => $cache,
                'pullForcesResolver' => $pullForcesResolver,
            ])
        @endforeach
    </div>
@endif
