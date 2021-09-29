@isset($model)
    {{ Form::model($model, ['route' => ['team.update', $model->public_key], 'method' => 'patch', 'files' => true]) }}
@else
    {{ Form::open(['route' => 'team.savenew', 'files' => true]) }}
@endisset

@if(!isset($model))
    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {!! Form::label('name', __('views/common.team.details.name') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::text('name', null, ['class' => 'form-control']) !!}
    </div>
@endif

<div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
    {!! Form::label('description', __('views/common.team.details.description')) !!}
    {!! Form::text('description', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group{{ $errors->has('logo') ? ' has-error' : '' }}">
    {!! Form::label('logo', __('views/common.team.details.logo')) !!}
    {!! Form::file('logo', ['class' => 'form-control']) !!}
</div>

@if(isset($model) && isset($model->iconfile))
    <div class="form-group">
        {{__('views/common.team.details.current_logo') }}: <img src="{{ $model->iconfile->getURL() }}"
                                                                alt="{{ __('views/common.team.details.team_logo_title') }}"
                                                                style="max-width: 48px"/>
    </div>
@endif

<div class="row">
    <div class="col">
        {!! Form::submit(isset($model) ?
            __('views/common.team.details.save') :
            __('views/common.team.details.submit'), ['class' => 'btn btn-info']) !!}
    </div>
    <div class="col">
        @if(isset($model) && $model->getUserRole(Auth::user()) === 'admin')
            <button id="delete_team" class="btn btn-danger float-right">
                <i class="fas fa-trash"></i> {{ __('views/common.team.details.disband_team') }}
            </button>
        @endif
    </div>
</div>

{!! Form::close() !!}