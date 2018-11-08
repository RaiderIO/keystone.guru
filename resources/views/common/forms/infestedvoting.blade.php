{{ Form::open(['route' => 'dungeonroute.infestedvoting.post']) }}
<h3>
    {{ __('Infested voting') }}
</h3>
<div class="form-group">
    {!! Form::label('dungeon_id', __('Select dungeon') . "*") !!}
    {!! Form::select('dungeon_id', \App\Models\Dungeon::active()->pluck('name', 'id'), null, ['class' => 'form-control']) !!}
</div>

<div class="form-group">
    {!! Form::label('teeming', __('Teeming week')) !!}
    {!! Form::checkbox('teeming', 1, 0, ['class' => 'form-control left_checkbox']) !!}
</div>

<div class="form-group">
    {!! Form::submit(__('Start voting'), ['class' => 'btn btn-primary col-md-auto']) !!}
</div>

{!! Form::close() !!}