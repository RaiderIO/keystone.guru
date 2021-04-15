@isset($model)
    {{ Form::model($model, ['route' => ['team.update', $model->public_key], 'method' => 'patch', 'files' => true]) }}
@else
    {{ Form::open(['route' => 'team.savenew', 'files' => true]) }}
@endisset

@if(!isset($model))
<div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
    {!! Form::label('name', __('Name') . '<span class="form-required">*</span>', [], false) !!}
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
</div>
@endif

<div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
    {!! Form::label('description', __('Description')) !!}
    {!! Form::text('description', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group{{ $errors->has('logo') ? ' has-error' : '' }}">
    {!! Form::label('logo', __('Logo')) !!}
    {!! Form::file('logo', ['class' => 'form-control']) !!}
</div>

@if(isset($model) && isset($model->iconfile))
    <div class="form-group">
        {{__('Current logo:')}} <img src="{{ $model->iconfile->getURL() }}"
                                     alt="{{ __('Team logo') }}" style="max-width: 48px"/>
    </div>
@endif

<div class="row">
    <div class="col">
        {!! Form::submit(isset($model) ? __('Save') : __('Submit'), ['class' => 'btn btn-info']) !!}
    </div>
    <div class="col">
        @if(isset($model) && $model->getUserRole(Auth::user()) === 'admin')
            <button id="delete_team" class="btn btn-danger float-right">
                <i class="fas fa-trash"></i> {{ __('Disband team') }}
            </button>
        @endif
    </div>
</div>

{!! Form::close() !!}