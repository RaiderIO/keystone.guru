<?php
/** @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection */

$cols = isset($cols) ? $cols : 1;
$i = 0;
for ($i = 0; $i < (int)ceil($dungeonroutes->count() / $cols); $i++) { ?>
<div class="row">
    <?php for ($j = 0; $j < $cols; $j++) { ?>
    <div class="col">
        @include('common.dungeonroute.card', ['dungeonroute' => $dungeonroutes->get(($i * $cols) + $j)])
    </div>
    <?php } ?>
</div>
<?php } ?>