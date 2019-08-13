<?php
/** @var \App\Models\DungeonRoute $model */
/** @var \App\Models\Dungeon $dungeon */
?>
@include('common.general.inline', ['path' => 'common/maps/killzonessidebar'])

@section('sidebar-content')
@endsection

@component('common.maps.sidebar', [
'header' => __('Toolbox'),
'anchor' => 'right',
'id' => 'killzonesidebar',
'selectedFloorId' => 0
])
    <div class="bg-danger h-100">
        Killzone sidebar!
    </div>
@endcomponent