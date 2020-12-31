<?php
/** @var \App\Models\DungeonRoute $model */
/** @var \App\Models\Dungeon $dungeon */
?>
@include('common.general.inline', ['path' => 'common/maps/killzonessidebar', 'options' => [
    'dependencies' => ['common/maps/map'],
    'sidebarSelector' => '#killzonesidebar',
    'sidebarScrollSelector' => '#killzonesidebar .sidebar-content',
    'sidebarToggleSelector' => '#killzonesidebarToggle',
    'anchor' => 'right',
    'newKillZoneSelector' => '#new_pull_btn',
    'killZonesContainerSelector' => '#killzones_container',
    'edit' => $edit
]])

@section('sidebar-content')
@endsection

@component('common.maps.sidebar', [
    'dungeon' => $dungeon,
    'header' => __('Pulls'),
    'anchor' => 'right',
    'id' => 'killzonesidebar',
    /* Draw controls are injected here through drawcontrols.js */
    'customSubHeader' => '<div class="mt-4"><div id="edit_route_enemy_forces_container"></div></div>'
])
    @if($edit)
        <div class="row mb-2">
            <div class="col">
                <div id="killzones_new_pull" class="btn btn-success w-100">
                    <i class="fas fa-plus"></i> {{__('New pull')}}
                </div>
            </div>
        </div>
    @endif
    <div id="killzones_loading" class="row">
        <div class="col text-center">
            <h5>{{ __('Loading...') }}</h5>
        </div>
    </div>
    <div id="killzones_no_pulls" class="row" style="display: none;">
        <div class="col text-center">
            @if($edit)
                <h5>{{ __('No pulls created. Click on the button above or on an enemy to add them to your first pull.') }}</h5>
            @else
                <h5>{{ __('No pulls created.') }}</h5>
            @endif
        </div>
    </div>
    <div id="killzones_container">
    </div>
@endcomponent