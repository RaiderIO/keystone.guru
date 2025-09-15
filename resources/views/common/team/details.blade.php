<?php

use App\Models\Team;
use App\Models\TeamUser;

/**
 * @var Team|null $model
 */
?>

@isset($model)
    {{ html()->modelForm($model, 'PATCH', route('team.update', $model->public_key))->acceptsFiles()->open() }}
@else
    {{ html()->form('POST', route('team.savenew'))->acceptsFiles()->open() }}
@endisset

@if(!isset($model))
    <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
        {{ html()->label(__('view_common.team.details.name') . '<span class="form-required">*</span>', 'name') }}
        {{ html()->text('name')->class('form-control') }}
    </div>
@endif

<div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
    {{ html()->label(__('view_common.team.details.description'), 'description') }}
    {{ html()->text('description')->class('form-control') }}
</div>

<div class="form-group{{ $errors->has('logo') ? ' has-error' : '' }}">
    {{ html()->label(__('view_common.team.details.logo'), 'logo') }}
    {{ html()->file('logo')->class('form-control') }}
</div>

@if(isset($model) && isset($model->iconfile))
    <div class="form-group">
        {{__('view_common.team.details.current_logo') }}: <img src="{{ $model->iconfile->getURL() }}"
                                                               alt="{{ __('view_common.team.details.team_logo_title') }}"
                                                               style="max-width: 48px"/>
    </div>
@endif

<div class="row">
    <div class="col">
        {{ html()->input('submit')->value(isset($model) ? __('view_common.team.details.save') : __('view_common.team.details.submit'))->class('btn btn-info') }}
    </div>
    <div class="col">
        @if(isset($model) && $model->getUserRole(Auth::user()) === TeamUser::ROLE_ADMIN)
            <button id="delete_team" class="btn btn-danger float-right">
                <i class="fas fa-trash"></i> {{ __('view_common.team.details.disband_team') }}
            </button>
        @endif
    </div>
</div>

{{ html()->closeModelForm() }}
