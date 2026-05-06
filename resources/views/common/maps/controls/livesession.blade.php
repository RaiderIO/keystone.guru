<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use Illuminate\Support\Collection;

/**
 * @var boolean           $isAdmin
 * @var Collection<Floor> $floors
 * @var bool              $isMobile
 */
?>
<nav
    class="route_sidebar route_manipulation_tools left row no-gutters map_fade_out {{ $isMobile ? 'mobile' : '' }}">
    <div class="bg-header">
        <div id="view_route_map_actions_container" class="mb-2">
            @include('common.maps.controls.elements.floorswitch', ['floors' => $floors])

            @include('common.maps.controls.elements.enemyvisualtype')

            @include('common.maps.controls.elements.mapobjectgroupvisibility', ['floors' => $floors])

            @include('common.maps.controls.elements.mapzoom')
        </div>

        <div id="view_route_misc_actions_container">
            @include('common.maps.controls.elements.labeltoggle')
        </div>
    </div>
</nav>
