<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

/**
 * @var Collection<DungeonRoute> $dungeonroutes
 * @var AffixGroup|null          $affixgroup
 * @var AffixGroup|null          $currentAffixGroup
 * @var array                    $__env
 * @var string                   $orientation
 */

$cols             ??= 1;
$showDungeonImage ??= false;
$affixgroup       ??= null;
$cache            ??= true;
$orientation      ??= 'vertical';

$i = 0;

// @formatter:off
$renderDungeonRouteCollection = static function (Collection $collection, string $header = null) use ($cols, $affixgroup, $currentAffixGroup, $showDungeonImage, $cache, $orientation, $__env) {
    $count = $collection->count();
    if( $count > 0 && $header !== null ) { ?>
    <div class="row no-gutters mt-2">
        <h4 class="col text-center">
            {{ $header }}
        </h4>
    </div>
   <?php
    }

    for ($i = 0; $i < (int)ceil($count / $cols); ++$i) { ?>
    <div class="row no-gutters">
        <?php for ($j = 0; $j < $cols; ++$j) {
        $dungeonroute = $collection->get(($i * $cols) + $j);
        ?>
        <div class="col-xl-{{ 12 / $cols }}">
            @if($dungeonroute !== null)
                @include($orientation === 'horizontal' ? 'common.dungeonroute.cardhorizontal' : 'common.dungeonroute.cardvertical', [
                    'dungeonroute' => $dungeonroute,
                    'currentAffixGroup' => $currentAffixGroup,
                    'tierAffixGroup' => $affixgroup,
                    'showDungeonImage' => $showDungeonImage,
                    'cache' => $cache
                ])
            @endif
        </div>
        <?php }
     ?>
    </div>
<?php }
}; ?>

@if($dungeonroutes->isEmpty())
    <div class="row no-gutters">
        <div class="col-xl text-center">
            {{ __('view_common.dungeonroute.cardlist.no_dungeonroutes') }}
        </div>
    </div>
@else
    <?php
    // If it's grouped by something, add a loop
    if( $dungeonroutes->first() instanceof Collection ){
        foreach($dungeonroutes as $header => $groupedDungeonRoutes ) {
            $renderDungeonRouteCollection($groupedDungeonRoutes, $header);
        }
    } else {
        $renderDungeonRouteCollection($dungeonroutes);
    }
    ?>
@endif

<?php
// @formatter:om
?>
