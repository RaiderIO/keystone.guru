<?php
/**
 * @var \App\User                                                 $user
 * @var \App\Logic\MapContext\MapContextDungeonRouteCompare       $mapContext
 * @var \App\Models\Dungeon                                       $dungeon
 * @var \App\Models\Floor                                         $floor
 * @var \App\Models\Mapping\MappingVersion                        $mappingVersion
 * @var \Illuminate\Support\Collection|\App\Models\DungeonRoute[] $dungeonRoutes
 * @var array                                                     $show
 * @var bool                                                      $adFree
 */

$user           = Auth::user();
$isAdmin        = isset($admin) && $admin;
$mapClasses     = $mapClasses ?? '';
$mappingVersion = $mappingVersion ?? null;

// Set the key to 'sandbox' if sandbox mode is enabled
$enemyVisualType                  = $_COOKIE['enemy_display_type'] ?? 'enemy_portrait';
$unkilledEnemyOpacity             = $_COOKIE['map_unkilled_enemy_opacity'] ?? '50';
$unkilledImportantEnemyOpacity    = $_COOKIE['map_unkilled_important_enemy_opacity'] ?? '80';
$defaultEnemyAggressivenessBorder = (int)($_COOKIE['map_enemy_aggressiveness_border'] ?? 0);

// Allow echo to be overridden
$echo           = $echo ?? Auth::check();
$zoomToContents = $zoomToContents ?? false;

// Show ads or not
$showAds = $showAds ?? true;
// No UI on the map
$noUI            = isset($noUI) && $noUI;
$gestureHandling = isset($gestureHandling) && $gestureHandling;
// Default zoom for the map
$defaultZoom = $defaultZoom ?? 2;
// By default hidden elements
$hiddenMapObjectGroups = $hiddenMapObjectGroups ?? [];
// Show the attribution
$showAttribution = isset($showAttribution) && $showAttribution;
?>
@include('common.general.inline', ['path' => 'common/maps/map', 'options' => [
    'embed' => false,
    'edit' => false,
    'readonly' => false, // May be set to true in the code though - but set a default here
    'sandbox' => false,
    'defaultEnemyVisualType' => $enemyVisualType,
    'defaultUnkilledEnemyOpacity' => $unkilledEnemyOpacity,
    'defaultUnkilledImportantEnemyOpacity' => $unkilledImportantEnemyOpacity,
    'defaultEnemyAggressivenessBorder' => $defaultEnemyAggressivenessBorder,
    'noUI' => false,
    'showControls' => [],
    'gestureHandling' => $gestureHandling,
    'zoomToContents' => $zoomToContents,
    'hiddenMapObjectGroups' => $hiddenMapObjectGroups,
    'defaultZoom' => $defaultZoom,
    'showAttribution' => $showAttribution,
    // @TODO Temp fix
    'npcsMinHealth' => $mapContext['npcsMinHealth'],
    'npcsMaxHealth' => $mapContext['npcsMaxHealth'],
]])

@section('scripts')
    {{-- Make sure we don't override the scripts of the page this thing is included in --}}
    @parent

    @include('common.general.statemanager', [
        'echo' => $echo,
        'patreonBenefits' => Auth::check() ? $user->getPatreonBenefits() : collect(),
        'userData' => $user,
        'mapContext' => $mapContext,
    ])
    <script>
        var dungeonMap;

        $(function () {
            let code = _inlineManager.getInlineCode('common/maps/map');

            // Expose the dungeon map in a global variable
            dungeonMap = code.getDungeonMap();
        });
    </script>
@endsection

@if(!$noUI)
    @include('common.maps.controls.header', [
        'title' => 'Compare routes',
        'echo' => $echo,
        'edit' => $edit,
        'dungeonroute' => null,
        'livesession' => null,
        'mappingVersion' => $mappingVersion,
        'dungeon' => $dungeon,
        'floor' => $floor,
    ])

    @if(isset($show['controls']['enemyInfo']) && $show['controls']['enemyInfo'])
        @include('common.maps.controls.enemyinfo')
    @endif
@endif


<div id="map" class="virtual-tour-element {{$mapClasses}}" data-position="auto">

</div>
@if(!$noUI)

    @if(!$adFree && $showAds)
        @if($isMobile)
            @include('common.thirdparty.adunit', ['id' => 'map_footer', 'type' => 'footer'])
        @endif
    @endif
    <footer class="fixed-bottom container p-0" style="width: 728px">
        <div id="snackbar_container">

        </div>
        @if(!$adFree && $showAds)
            @include('common.thirdparty.adunit', ['id' => 'map_footer', 'type' => 'footer', 'class' => 'map_ad_background', 'map' => true])
        @endif
    </footer>

        <?php
        /*
        So speedrun dungeons are such low traffic that this doesn't really matter anyways. But those routes already
        have to fight for height in the sidebar. This will only make it worse, so don't render this ad
        */ ?>
    @if(!$adFree && $showAds && !$dungeon->speedrun_enabled)
        <footer class="fixed-bottom container p-0 m-0 mr-2 map_ad_unit_footer_right">
            @include('common.thirdparty.adunit', ['id' => 'map_footer_right', 'type' => 'footer_map_right', 'class' => 'map_ad_background', 'map' => true])
        </footer>
    @endif
@endif
