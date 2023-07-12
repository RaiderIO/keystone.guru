<?php
/** @var \Illuminate\Support\Collection $floors */
?>
<nav class="route_manipulation_tools left h-100 row no-gutters map_fade_out">
    <div class="bg-header">
        <div id="view_route_map_actions_container" class="mb-2">
            @include('common.maps.controls.elements.floorswitch', ['floors' => $floors, 'selectedFloorId' => $selectedFloorId])

            @include('common.maps.controls.elements.enemyvisualtype')

            @include('common.maps.controls.elements.mapobjectgroupvisibility', ['floors' => $floors])
        </div>

        <div id="view_route_misc_actions_container">
            @include('common.maps.controls.elements.labeltoggle')
        </div>
    </div>
</nav>
