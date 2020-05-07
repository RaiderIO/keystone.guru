<?php
/** @var \App\Models\DungeonRoute $model */
/** @var \App\Models\Dungeon $dungeon */
?>
@include('common.general.inline', ['path' => 'common/maps/killzonessidebar', 'options' => [
    'dependencies' => ['common/maps/map'],
    'sidebarSelector' => '#killzonesidebar',
    'sidebarToggleSelector' => '#killzonesidebarToggle',
    'anchor' => 'right',
    'newKillZoneSelector' => '#new_pull_btn',
    'killZonesContainerSelector' => '#killzones_container'
]])

@section('sidebar-content')
@endsection

@component('common.maps.sidebar', [
    'header' => __('Pulls'),
    'anchor' => 'right',
    'id' => 'killzonesidebar',
    'customSubHeader' => '<div class="sidebar-header-pulls-spacer"></div><button id="new_pull_btn" class="btn btn-primary w-100"><i class="fas fa-plus"></i> ' . __('New Pull') . '</button>'
])
    <div id="killzones_container">

    </div>
@endcomponent