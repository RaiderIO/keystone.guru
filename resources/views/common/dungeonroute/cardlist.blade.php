<?php
/** @var $dungeonroutes \App\Models\DungeonRoute[]|\Illuminate\Support\Collection */
/** @var $affixgroup \App\Models\AffixGroup\AffixGroup|null */
/** @var $currentAffixGroup \App\Models\AffixGroup\AffixGroup */
/** @var $__env array */


$cols             = $cols ?? 1;
$showDungeonImage = $showDungeonImage ?? false;
$affixgroup       = $affixgroup ?? null;
$cache            = $cache ?? true;

$i = 0;

// @formatter:off
$renderDungeonRouteCollection = function(\Illuminate\Support\Collection $collection, string $header = null)
    use($cols, $affixgroup, $currentAffixGroup, $showDungeonImage, $cache, $__env) {
    $count = $collection->count();
    if( $header !== null ) { ?>
    <div class="row no-gutters mt-2">
        <h4 class="col text-center">
            {{ $header }}
        </h4>
    </div>
   <?php
    }
    for ($i = 0; $i < (int)ceil($count / $cols); $i++) { ?>
    <div class="row no-gutters">
        <?php for ($j = 0; $j < $cols; $j++) {
        $dungeonroute = $collection->get(($i * $cols) + $j);
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
<?php }; ?>

@if($dungeonroutes->isEmpty())
    <div class="row no-gutters">
        <div class="col-xl text-center">
            {{ __('views/common.dungeonroute.cardlist.no_dungeonroutes') }}
        </div>
    </div>
@else
    <?php
    // If it's grouped by something, add a loop
    if( $dungeonroutes->first() instanceof \Illuminate\Support\Collection ){
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
