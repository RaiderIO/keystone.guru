<?php
/** @var boolean $isAdmin */
/** @var \Illuminate\Support\Collection $floors */
?>
<nav class="route_manipulation_tools left h-100 row no-gutters map_fade_out">
    <div class="bg-header">
        <!-- Draw controls are injected here through drawcontrols.js -->
        <div id="edit_route_draw_container" class="mb-2">


        </div>

        @isset($dungeonroute)
            <div id="view_route_actions_container" class="mb-2">
                @include('common.maps.controls.elements.dungeonrouteinfo', ['dungeonroute' => $dungeonroute])
            </div>
    @endisset

    <!-- Draw actions are injected here through drawcontrols.js -->
        <div id="edit_route_draw_actions_container" class="mb-2">

        </div>

        <div id="edit_route_draw_map_actions_container">
            @include('common.maps.controls.elements.floorswitch', ['floors' => $floors])

            @include('common.maps.controls.elements.enemyvisualtype')

            @include('common.maps.controls.elements.mapobjectgroupvisibility', ['floors' => $floors])

            @if( $isAdmin )
                @include('common.maps.controls.elements.mdtclones')
            @endif

            @include('common.maps.controls.elements.labeltoggle', ['floors' => $floors])
        </div>
    </div>
</nav>
