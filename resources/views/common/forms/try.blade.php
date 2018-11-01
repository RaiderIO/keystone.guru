<?php
/**
 * Created by PhpStorm.
 * User: Wouter
 * Date: 01/11/2018
 * Time: 23:18
 */
?>

{{ Form::open(['route' => 'dungeonroute.try.post']) }}
<div class="container">
    <h3>
        {{ __('General') }}
    </h3>
    <div class="form-group">
        {!! Form::label('dungeon_id', __('Select dungeon') . "*") !!}
        {!! Form::select('dungeon_id', \App\Models\Dungeon::active()->pluck('name', 'id'), null, ['id' => 'dungeon_id_select', 'class' => 'form-control']) !!}
        <div id="siege_of_boralus_faction_warning" class="text-warning" style="display: none;">
            {{ __('Due to differences between the Horde and the Alliance version of Siege of Boralus, you are required to select a faction in the group composition.') }}
        </div>
    </div>

    <div class="form-group">
        {!! Form::label('teeming', __('Teeming (check to change the dungeon to resemble Teeming week)')) !!}
        {!! Form::checkbox('teeming', 1, 0, ['class' => 'form-control left_checkbox']) !!}
    </div>

    <div class="form-group">
        {!! Form::submit(__('Try it!'), ['class' => 'btn btn-info col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
</div>

{!! Form::close() !!}
