<?php
/** @var boolean $isAdmin */
/** @var \Illuminate\Support\Collection $floors */
/** @var \App\Models\Dungeonroute|null $dungeonroute */
/** @var bool $isMobile */
?>
<nav class="route_sidebar route_manipulation_tools left h-100 row no-gutters map_fade_out {{ $isMobile ? 'mobile' : '' }}">
    <div class="bg-header">
        @isset($dungeonroute)
        <div id="view_route_actions_container" class="mb-2">
            @auth
                @if($dungeonroute->mayUserEdit(Auth::user()))
                    @include('common.maps.controls.elements.dungeonroute.edit', ['dungeonroute' => $dungeonroute])
                @endif
                @if($dungeonroute->dungeon->active)
                    @include('common.maps.controls.elements.dungeonroute.clone', ['dungeonroute' => $dungeonroute])
                @endif

                @include('common.maps.controls.elements.dungeonroute.report', ['dungeonroute' => $dungeonroute])

                @include('common.maps.controls.elements.rating', ['dungeonroute' => $dungeonroute])
            @endauth

                @include('common.maps.controls.elements.dungeonroute.info', ['dungeonroute' => $dungeonroute])
        </div>
        @endisset

        <div id="view_route_map_actions_container" class="mb-2">
            @include('common.maps.controls.elements.floorswitch', ['floors' => $floors])

            @include('common.maps.controls.elements.enemyvisualtype')

            @include('common.maps.controls.elements.mapobjectgroupvisibility', ['floors' => $floors])
        </div>

        <div id="view_route_misc_actions_container">
            @include('common.maps.controls.elements.labeltoggle')
        </div>
    </div>
</nav>
