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
    'customSubHeader' => '&nbsp;'
])
    <div id="killzones_no_pulls" class="row">
        <div class="col text-center">
            <h5>{{ __('No pulls created. Click on an enemy to add them to your first pull!') }}</h5>
        </div>
    </div>
    <div id="killzones_container">
    </div>
@endcomponent