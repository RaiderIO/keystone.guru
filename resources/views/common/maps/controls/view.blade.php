<?php
/** @var boolean $isAdmin */
/** @var \Illuminate\Support\Collection $floors */
/** @var \App\Models\Dungeonroute $dungeonroute */

?>
<nav class="route_manipulation_tools h-100 row no-gutters align-items-center">
    <div class="p-2 bg-header">
        <div id="view_route_actions_container" class="mb-3">
            @auth
                @if($model->mayUserEdit(Auth::user()))
                    <div class="row no-gutters" data-toggle="tooltip" data-placement="right"
                         title="{{ __('Edit this route') }}">
                        <div class="col">
                            <a href="{{ route('dungeonroute.edit', ['dungeonroute' => $model->public_key]) }}"
                               class="btn btn-info" target="_blank">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                @endif
                @if($model->dungeon->active)
                    <div class="row no-gutters" data-toggle="tooltip" data-placement="right"
                         title="{{ __('Clone this route') }}">
                        <div class="col">
                            <a href="{{ route('dungeonroute.clone', ['dungeonroute' => $model->public_key]) }}"
                               class="btn btn-info" target="_blank">
                                <i class="fas fa-clone"></i>
                            </a>
                        </div>
                    </div>
                @endif

                <div class="row no-gutters" data-toggle="tooltip" data-placement="right"
                     title="{{ isset($current_report) ? __('You have reported this route for moderation.') : __('Report for moderation') }}">
                    <div class="col">
                        <a href="#" data-toggle="modal" data-target="#userreport_dungeonroute_modal" target="_blank"
                           class="btn btn-info {{ isset($current_report) ? 'disabled' : '' }}">
                            <i class="fas fa-flag"></i>
                        </a>
                    </div>
                </div>

                @include('common.maps.controls.elements.rating', ['dungeonroute' => $dungeonroute])
            @endauth

            @include('common.maps.controls.elements.dungeonrouteinfo', ['dungeonroute' => $dungeonroute])
        </div>

        <div id="view_route_map_actions_container">
            @include('common.maps.controls.elements.floorswitch', ['floors' => $floors])

            @include('common.maps.controls.elements.enemyvisualtype')

            @include('common.maps.controls.elements.mapobjectgroupvisibility', ['floors' => $floors])
        </div>
    </div>
</nav>