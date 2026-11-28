<?php

use App\Models\Patreon\PatreonBenefit;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
    <div class="mb-3{{ $errors->has('name') ? ' has-error' : '' }}">
        {{ html()->label(__('view_common.team.details.name') . '<span class="form-required">*</span>', 'name') }}
        {{ html()->text('name')->class('form-control') }}
    </div>
@endif

<div class="mb-3{{ $errors->has('name') ? ' has-error' : '' }}">
    {{ html()->label(__('view_common.team.details.description'), 'description') }}
    {{ html()->text('description')->class('form-control') }}
</div>

@if(isset($model) && Auth::check() && Auth::user()->hasPatreonBenefit(PatreonBenefit::CUSTOM_URLS))
    <div class="mb-3{{ $errors->has('vanity_key') ? ' has-error' : '' }}">
        {{ html()->label(__('view_common.team.details.vanity_key'), 'vanity_key') }}
        <i class="fas fa-info-circle" data-bs-toggle="tooltip"
           title="{{ __('view_common.team.details.vanity_key_title') }}"></i>
        <div class="input-group">
            <span class="input-group-text">{{ url('team') }}/</span>
            {{ html()->text('vanity_key')->class('form-control')->placeholder($model->public_key) }}
        </div>
    </div>
@endif

<div class="mb-3{{ $errors->has('logo') ? ' has-error' : '' }}">
    {{ html()->label(__('view_common.team.details.logo'), 'logo') }}
    {{ html()->file('logo')->class('form-control') }}
</div>

@if(isset($model) && isset($model->iconfile))
    <div class="mb-3">
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
        @if(isset($model) && Gate::allows('delete', $model))
            <button id="delete_team" class="btn btn-danger float-end">
                <i class="fas fa-trash"></i> {{ __('view_common.team.details.disband_team') }}
            </button>
        @endif
    </div>
</div>

{{ html()->closeModelForm() }}
