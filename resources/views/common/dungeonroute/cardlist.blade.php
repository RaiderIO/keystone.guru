<?php
/** @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection */

$cols = isset($cols) ? $cols : 1;
$showDungeonImage = isset($showDungeonImage) ? $showDungeonImage : false;

$i = 0;
for ($i = 0; $i < (int)ceil($dungeonroutes->count() / $cols); $i++) { ?>
<div class="row no-gutters">
    <?php for ($j = 0; $j < $cols; $j++) {
    $dungeonroute = $dungeonroutes->get(($i * $cols) + $j);
    ?>
    <div class="col-lg">
        @if($dungeonroute !== null)
            @include('common.dungeonroute.card', ['dungeonroute' => $dungeonroute, 'showDungeonImage' => $showDungeonImage])
        @endif
    </div>
    <?php } ?>
</div>
<?php } ?>