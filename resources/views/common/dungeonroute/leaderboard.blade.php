<?php

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, DungeonRoute> $dungeonroutes
 * @var int  $startRank
 * @var bool $cache
 */

$startRank ??= 1;
$cache     ??= true;
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
            ])
        @endforeach
    </div>
@endif
