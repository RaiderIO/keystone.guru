<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use Illuminate\Support\Collection;

/**
 * @var boolean           $isAdmin
 * @var Collection<Floor> $floors
 * @var DungeonRoute      $dungeonroute
 */
?>
<nav
    class="route_sidebar route_manipulation_tools left h-100 row no-gutters map_fade_out {{ $isMobile ? 'mobile' : '' }}">
    <div class="bg-header" style="background-color: unset !important;">
        <!-- Draw controls are injected here through drawcontrols.js -->
        <div id="edit_route_draw_container" class="mb-2">


        </div>

        @isset($dungeonroute)
            <div id="edit_route_actions_container" class="mb-2">
                <div class="row no-gutters">
                    <div class="col">
                        <a href="{{ route('dungeonroute.view', ['dungeon' => $dungeonroute->dungeon, 'dungeonroute' => $dungeonroute, 'title' => $dungeonroute->getTitleSlug()]) }}"
                           class="btn btn-info">
                            <i class="fas fa-eye"></i>
                            <span class="map_controls_element_label_toggle" style="display: none;">
                                {{ __('view_common.maps.controls.draw.view_this_route') }}
                            </span>
                        </a>
                    </div>
                </div>

                @include('common.maps.controls.elements.dungeonroute.info', ['dungeonroute' => $dungeonroute])
            </div>
        @endisset

        <!-- Draw actions are injected here through drawcontrols.js -->
        <div id="edit_route_draw_actions_container" class="mb-2">

        </div>

        <div id="edit_route_draw_map_actions_container" class="mb-2">
            @include('common.maps.controls.elements.floorswitch', ['floors' => $floors])

            @include('common.maps.controls.elements.enemyvisualtype')

            @include('common.maps.controls.elements.mapobjectgroupvisibility', ['floors' => $floors])

            @include('common.maps.controls.elements.mapzoom')
        </div>

        @if( $isAdmin )
            <div id="edit_route_draw_admin_map_actions_container" class="mb-2">
                <div class="row">
                    <div class="col">
                        {{ __('view_common.maps.controls.draw.admin') }}
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
