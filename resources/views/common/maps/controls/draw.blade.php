<?php
/** @var boolean $isAdmin */
/** @var \Illuminate\Support\Collection $floors */
?>
<nav class="route_manipulation_tools left h-100 row no-gutters map_fade_out">
    <div class="bg-header" style="background-color: unset !important;">
        <!-- Draw controls are injected here through drawcontrols.js -->
        <div id="edit_route_draw_container" class="mb-2">


        </div>

        @isset($dungeonroute)
            <div id="view_route_actions_container" class="mb-2">
                <div class="row no-gutters">
                    <div class="col">
                        <a href="{{ route('dungeonroute.view', ['dungeonroute' => $dungeonroute]) }}"
                           class="btn btn-info">
                            <i class="fas fa-eye"></i>
                            <span class="map_controls_element_label_toggle" style="display: none;">
                                {{ __('views/common.maps.controls.draw.view_this_route') }}
                            </span>
                        </a>
                    </div>
                </div>

                @include('common.maps.controls.elements.dungeonrouteinfo', ['dungeonroute' => $dungeonroute])
            </div>
        @endisset

    <!-- Draw actions are injected here through drawcontrols.js -->
        <div id="edit_route_draw_actions_container" class="mb-2">

        </div>

        <div id="edit_route_draw_map_actions_container" class="mb-2">
            @include('common.maps.controls.elements.floorswitch', ['floors' => $floors])

            @include('common.maps.controls.elements.enemyvisualtype')

            @include('common.maps.controls.elements.mapobjectgroupvisibility', ['floors' => $floors])
        </div>

        @if( $isAdmin )
            <div id="edit_route_draw_admin_map_actions_container" class="mb-2">
                <div class="row">
                    <div class="col">
                        {{ __('views/common.maps.controls.draw.admin') }}
                    </div>
                </div>
                @include('common.maps.controls.elements.mdtclones')
            </div>
        @endif

        <div id="edit_route_misc_actions_container">
            @include('common.maps.controls.elements.labeltoggle')
        </div>
    </div>
</nav>
