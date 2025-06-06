<?php

use App\Models\CharacterClass;
use App\Models\GameServerRegion;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * @var User                       $user
 * @var Collection<CharacterClass> $allClasses
 */
?>
<div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
    {{ Form::model($user, ['route' => ['profile.update', $user->id], 'method' => 'patch', 'files' => true]) }}
    <h4>
        {{ $menuTitle }}
    </h4>

    @csrf
    @method('PATCH')

    <div class="form-group{{ $errors->has('avatar') ? ' has-error' : '' }}">
        {!! Form::label('avatar', __('view_profile.edit.avatar')) !!}
        {!! Form::file('avatar', ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'avatar'])
    </div>

    @if(isset($user->iconfile))
        <div class="form-group">
            {{__('view_profile.edit.avatar')}}: <img src="{{ $user->iconfile->getURL() }}"
                                                     alt="{{ __('view_profile.edit.avatar_title') }}"
                                                     style="max-width: 48px"/>
        </div>
    @endif

    @if($isOAuth && !$user->changed_username)
        <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
            <label for="name">
                {{ __('view_profile.edit.username') }}
                <i class="fas fa-info-circle" data-toggle="tooltip"
                   title="{{ __('view_profile.edit.username_title') }}"></i>
            </label>
            {!! Form::text('name', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'name'])
        </div>
    @endif

    @if(!$isOAuth)
        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
            {!! Form::label('email', __('view_profile.edit.email')) !!}
            {!! Form::text('email', null, ['class' => 'form-control']) !!}
            @include('common.forms.form-error', ['key' => 'email'])
        </div>
    @endif

    <div class="form-group{{ $errors->has('game_server_region_id') ? ' has-error' : '' }}">
        {!! Form::label('game_server_region_id', __('view_profile.edit.region')) !!}
        {!! Form::select('game_server_region_id', array_merge(['0' => __('view_profile.edit.select_region')],
            $allRegions->mapWithKeys(function (GameServerRegion $region){
                return [$region->id => __($region->name)];
            })->toArray()), null, ['class' => 'form-control']) !!}
        @include('common.forms.form-error', ['key' => 'game_server_region_id'])
    </div>

    <div class="form-group{{ $errors->has('timezone') ? ' has-error' : '' }}">
        @include('common.forms.timezoneselect', ['selected' => $user->timezone])
    </div>

    <div class="form-group{{ $errors->has('echo_anonymous') ? ' has-error' : '' }}">
        <label for="echo_anonymous">
            {{ __('view_profile.edit.show_as_anonymous') }}
            <i class="fas fa-info-circle" data-toggle="tooltip"
               title="{{ __('view_profile.edit.show_as_anonymous_title') }}"></i>
        </label>
        {!! Form::checkbox('echo_anonymous', 1, $user->echo_anonymous, ['class' => 'form-control left_checkbox']) !!}
    </div>

    <div class="form-group{{ $errors->has('echo_color') ? ' has-error' : '' }}">
        <label for="echo_color">
            {{ __('view_profile.edit.echo_color') }}
            <i class="fas fa-info-circle" data-toggle="tooltip"
               title="{{ __('view_profile.edit.echo_color_title') }}"></i>
        </label>
        {!! Form::color('echo_color', null, ['id' => 'echo_color', 'class' => 'form-control']) !!}

        <?php
        foreach ($allClasses->chunk(13) as $chunk) { ?>
        <div class="row no-gutters pt-1">
                <?php foreach ($chunk as $class) { ?>
            <div class="col-md profile_class_color border-dark"
                 data-color="{{ $class->color }}"
                 style="background-color: {{ $class->color }};">
            </div>
            <?php }
                ?>
        </div>
        <?php }
        ?>
    </div>

    {!! Form::submit(__('view_profile.edit.save'), ['class' => 'btn btn-info']) !!}
    {!! Form::close() !!}
</div>
