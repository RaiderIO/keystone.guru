{{ Form::open(['route' => 'dungeonroute.try.post']) }}
<div class="container">
    <h3>
        {{ __('Try') }} {{ config('app.name') }}
    </h3>
    <div class="form-group">
        {!! Form::label('dungeon_id', __('Dungeon') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::select('dungeon_id', \App\Models\Dungeon::active()->pluck('name', 'id'), null, ['class' => 'form-control']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('teeming', __('Teeming')) !!}
        {!! Form::checkbox('teeming', 1, 0, ['class' => 'form-control left_checkbox']) !!}
    </div>

    <div class="form-group">
        {!! Form::submit(__('Try it!'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
</div>

{!! Form::close() !!}
