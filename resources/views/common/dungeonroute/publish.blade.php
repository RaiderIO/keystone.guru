<?php
$publishStates = \App\Models\PublishedState::all()->pluck('name', 'id');
?>
{!! Form::select('map_route_publish', $publishStates, 1, ['id' => 'map_route_publish', 'class' => 'form-control selectpicker', 'size' => count($publishStates)]) !!}
