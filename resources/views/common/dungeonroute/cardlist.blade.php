<?php
/** @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection */
/** @var $affixgroup \App\Models\AffixGroup|null */
/** @var $currentAffixGroup \App\Models\AffixGroup */


$cols = $cols ?? 1;
$showDungeonImage = $showDungeonImage ?? false;
$affixgroup = $affixgroup ?? null;
$cache = $cache ?? true;

$i = 0;
$count = $dungeonroutes->count(); ?>
@if($dungeonroutes->isEmpty())
    <div class="row no-gutters">
        <div class="col-xl text-center">
            {{ __('views/common.dungeonroute.cardlist.no_dungeonroutes') }}
        </div>
    </div>
@else
    <?php for ($i = 0; $i < (int)ceil($count / $cols); $i++) { ?>
    <div class="row no-gutters">
        <?php for ($j = 0; $j < $cols; $j++) {
        $dungeonroute = $dungeonroutes->get(($i * $cols) + $j);
        ?>
        <div class="col-xl">
            @if($dungeonroute !== null)
                @include('common.dungeonroute.card', [
                    'dungeonroute' => $dungeonroute,
                    'currentAffixGroup' => $currentAffixGroup,
                    'tierAffixGroup' => $affixgroup,
                    'showDungeonImage' => $showDungeonImage,
                    'cache' => $cache
                ])
            @endif
        </div>
        <?php } ?>
    </div>
    <?php } ?>
@endif
