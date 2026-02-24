<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

/**
 * @var Collection<DungeonRoute>                   $dungeonroutes
 * @var AffixGroup|null                            $affixgroup
 * @var AffixGroup|null                            $currentAffixGroup
 * @var array                                      $__env
 * @var bool                                       $showThumbnails
 * @var bool|null                                  $showDungeonImage
 * @var bool|null                                  $cache
 * @var string                                     $orientation
 * @var Collection<integer, array<string, string>> $headers
 */

$cols             ??= 1;
$showThumbnails   ??= true;
$showDungeonImage ??= false;
$affixgroup       ??= null;
$cache            ??= true;
$orientation      ??= 'vertical';
$cardHeaders      ??= collect();

$renderedDungeonRouteCount = 0;
$i                         = 0;

// @formatter:off
$renderDungeonRouteCollection = static function (Collection $collection, ?string $header = null) use ($cols, $affixgroup, $currentAffixGroup, $showThumbnails, $showDungeonImage, $cache, $orientation, $__env, &$renderedDungeonRouteCount, $cardHeaders) {
    /** @var Collection<DungeonRoute> $collection */
    $count = $collection->count();
    if( $count > 0 && $header !== null ) { ?>
    <div class="row no-gutters">
        <h4 class="col text-center">
            {{ $header }}
        </h4>
    </div>
   <?php
    }

    for ($i = 0; $i < (int)ceil($count / $cols); ++$i) { ?>
    <div class="row no-gutters">
        <?php for ($j = 0; $j < $cols; ++$j) {
        $dungeonRouteIndex = ($i * $cols) + $j;
        /** @var DungeonRoute $dungeonroute */
        $dungeonroute = $collection->get($dungeonRouteIndex);
        ?>
        <div class="col-xl-{{ 12 / $cols }}">
            @if($dungeonroute !== null)
                @if($cardHeaders->isNotEmpty())
                    @php($cardHeader = $cardHeaders->get($dungeonroute->id))
                    <a href="{{ $cardHeader['link'] }}" class="d-block mb-2 mt-3">
                        <h5>
                            {{ $cardHeader['text'] }}
                        </h5>
                    </a>
                @endif
                <?php
                    $view = match($orientation) {
                        'horizontal_row' => 'common.dungeonroute.cardhorizontalrow',
                        'vertical' => 'common.dungeonroute.cardvertical',
                        'horizontal' => 'common.dungeonroute.cardhorizontal',
                        default => throw new InvalidArgumentException("Invalid orientation: $orientation")
                    }
                ?>
                @include($view, [
                    'dungeonroute' => $dungeonroute,
                    'currentAffixGroup' => $currentAffixGroup,
                    'tierAffixGroup' => $affixgroup,
                    'showThumbnails' => $showThumbnails,
                    'showDungeonImage' => $showDungeonImage,
                    'cache' => $cache
                ])
                @php($renderedDungeonRouteCount++)
            @endif
        </div>
        <?php }
     ?>
    </div>
<?php }
}; ?>

@if(!$dungeonroutes->isEmpty())
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

@if($renderedDungeonRouteCount === 0)
    <div class="row no-gutters">
        <div class="col-xl text-center">
            {{ __('view_common.dungeonroute.cardlist.no_dungeonroutes') }}
        </div>
    </div>
@endif

<?php
// @formatter:om
?>
