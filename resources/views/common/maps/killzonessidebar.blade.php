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
    'header' => __('Pulls'),
    'anchor' => 'right',
    'id' => 'killzonesidebar',
    /* Draw controls are injected here through drawcontrols.js */
    'customSubHeader' => '<div class="mt-4"><div id="edit_route_enemy_forces_container"></div></div>'
])
    <div id="killzones_loading" class="row">
        <div class="col text-center">
            <h5>{{ __('Loading...') }}</h5>
        </div>
    </div>
    <div id="killzones_no_pulls" class="row" style="display: none;">
        <div class="col text-center">
            <h5>{{ __('No pulls created. Click on an enemy to add them to your first pull!') }}</h5>
        </div>
    </div>
    <div id="killzones_container">
    </div>
@endcomponent