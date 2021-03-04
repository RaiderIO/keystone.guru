<?php
/** @var $title string */
/** @var $cols int */
/** @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection */

$cols = isset($cols) ? $cols : 2;
?>
<div class="discover_panel">
    <div class="row mt-4">
        <div class="col-xl">
            <h2 class="text-center">
                {{ $title }}
            </h2>
            @include('common.dungeonroute.cardlist', ['cols' => $cols, 'dungeonroutes' => $dungeonroutes])
        </div>
    </div>
</div>