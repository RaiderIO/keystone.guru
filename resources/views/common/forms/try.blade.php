<?php
$modal = isset($modal) ? $modal : false;
$modalClass = $modal ? 'modal-' : '';
?>

{{ Form::open(['route' => 'dungeonroute.try.post']) }}
<div class="container">
    <h3>
        {{ __('Try') }} {{ config('app.name') }}
    </h3>
    <div class="form-group">
        {!! Form::label($modalClass . 'dungeon_id', __('Select dungeon') . "*") !!}
        {!! Form::select($modalClass . 'dungeon_id', \App\Models\Dungeon::active()->pluck('name', 'id'), null, ['class' => 'form-control']) !!}
        <div id="siege_of_boralus_faction_warning" class="text-warning" style="display: none;">
            {{ __('Due to differences between the Horde and the Alliance version of Siege of Boralus, you are required to select a faction in the group composition.') }}
        </div>
    </div>

    <div class="form-group">
        {!! Form::label($modalClass . 'teeming', __('Teeming week')) !!}
        {!! Form::checkbox($modalClass . 'teeming', 1, 0, ['class' => 'form-control left_checkbox']) !!}
    </div>

    <div class="form-group">
        {!! Form::submit(__('Try it!'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
</div>

{!! Form::close() !!}
