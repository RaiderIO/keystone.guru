<?php
/** @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection */
/** @var $affixgroup \App\Models\AffixGroup|null */

$cols = $cols ?? 1;
$showDungeonImage = $showDungeonImage ?? false;
$affixgroup = $affixgroup ?? null;

$i = 0;
for ($i = 0; $i < (int)ceil($dungeonroutes->count() / $cols); $i++) { ?>
<div class="row no-gutters">
    <?php for ($j = 0; $j < $cols; $j++) {
    $dungeonroute = $dungeonroutes->get(($i * $cols) + $j);
    ?>
    <div class="col-xl">
        @if($dungeonroute !== null)
            @include('common.dungeonroute.card', ['dungeonroute' => $dungeonroute, 'tierAffixGroup' => $affixgroup, 'showDungeonImage' => $showDungeonImage])
        @endif
    </div>
    <?php } ?>
</div>
<?php } ?>