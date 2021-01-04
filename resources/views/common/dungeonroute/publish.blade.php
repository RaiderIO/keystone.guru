<?php
$publishStates = \App\Models\PublishedState::all()->pluck('name', 'id');
?>

@include('common.general.inline', ['path' => 'common/dungeonroute/publish', 'options' => [
    'publishSelector' => '#map_route_publish',
    'publishStates' => $publishStates,
    'publishStateSelected' => $model->publishedstate->name
    ]])

{!! Form::select('map_route_publish', [], 1, ['id' => 'map_route_publish', 'class' => 'form-control selectpicker', 'size' => count($publishStates)]) !!}
