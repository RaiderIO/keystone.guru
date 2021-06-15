<?php
/** @var boolean $isAdmin */
/** @var \Illuminate\Support\Collection $floors */
/** @var \App\Models\Dungeonroute|null $dungeonroute */
?>
<nav class="route_manipulation_tools left h-100 row no-gutters map_fade_out">
    <div class="bg-header">
        <div id="view_route_actions_container" class="mb-2">
            @auth
                @if($dungeonroute->mayUserEdit(Auth::user()))
                    <div class="row no-gutters">
                        <div class="col" data-toggle="tooltip" data-placement="right"
                             title="{{ __('Edit this route') }}">
                            <a href="{{ route('dungeonroute.edit', ['dungeonroute' => $dungeonroute]) }}"
                               class="btn btn-info">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                @endif
                @if($dungeonroute->dungeon->active)
                    <div class="row no-gutters">
                        <div class="col" data-toggle="tooltip" data-placement="right"
                             title="{{ __('Clone this route') }}">
                            <a href="{{ route('dungeonroute.clone', ['dungeonroute' => $dungeonroute]) }}"
                               class="btn btn-info">
                                <i class="fas fa-clone"></i>
                            </a>
                        </div>
                    </div>
                @endif

                <div class="row no-gutters">
                    <div class="col" data-toggle="tooltip" data-placement="right"
                         title="{{ isset($current_report) ? __('You have reported this route for moderation.') : __('Report for moderation') }}">
                        <a href="#" data-toggle="modal" data-target="#userreport_dungeonroute_modal"
                           class="btn btn-info {{ isset($current_report) ? 'disabled' : '' }}">
                            <i class="fas fa-flag"></i>
                        </a>
                    </div>
                </div>

                @isset($dungeonroute)
                    @include('common.maps.controls.elements.rating', ['dungeonroute' => $dungeonroute])
                @endisset
            @endauth

            @isset($dungeonroute)
                @include('common.maps.controls.elements.dungeonrouteinfo', ['dungeonroute' => $dungeonroute])
            @endisset
        </div>

        <div id="view_route_map_actions_container">
            @include('common.maps.controls.elements.floorswitch', ['floors' => $floors])

            @include('common.maps.controls.elements.enemyvisualtype')

            @include('common.maps.controls.elements.mapobjectgroupvisibility', ['floors' => $floors])
        </div>
    </div>
</nav>