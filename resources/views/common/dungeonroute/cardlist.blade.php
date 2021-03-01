<?php
/** @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection */

$cols = isset($cols) ? $cols : 1;
$i = 0;
foreach ($dungeonroutes as $dungeonroute) {
    $newRow = $i % $cols === 0;
?>
@if($newRow )
    <div class="row">
        @endif
        <div class="col">
            @include('common.dungeonroute.card', ['dungeonroute' => $dungeonroute])
        </div>
        @if($newRow )
    </div>
@endif
<?php
    $i++;
}
?>