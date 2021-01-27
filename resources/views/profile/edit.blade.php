<?php
/** @var \App\User $user */
$user = Auth::getUser();
$isOAuth = $user->password === '';
$menuItems = [
    ['icon' => 'fa-route', 'text' => __('Routes'), 'target' => '#routes'],
    ['icon' => 'fa-user', 'text' => __('Profile'), 'target' => '#profile'],
    ['icon' => 'fa-cog', 'text' => __('Account'), 'target' => '#account'],
    ['icon' => 'fa-tag', 'text' => __('Personal tags'), 'target' => '#tags'],
    ['icon' => 'fab fa-patreon', 'text' => __('Patreon'), 'target' => '#patreon'],
];
// Optionally add this menu item
if (!$isOAuth) {
    $menuItems[] = ['icon' => 'fa-key', 'text' => __('Change password'), 'target' => '#change-password'];
}
$menuItems[] = ['icon' => 'fa-user-secret', 'text' => __('Privacy'), 'target' => '#privacy'];
$menuItems[] = ['icon' => 'fa-flag', 'text' => __('Reports'), 'target' => '#reports'];

$menuTitle = sprintf(__('%s\'s profile'), $user->name);
$deleteConsequences = $user->getDeleteConsequences();
?>
@extends('layouts.app', ['wide' => true, 'title' => __('Profile'),
    'menuTitle' => $menuTitle,
    'menuItems' => $menuItems,
    'model' => $user
])

@include('common.general.inline', ['path' => 'profile/edit', 'options' => [
    'test' => 'test'
]])

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            // Code for base app
            var appCode = _inlineManager.getInlineCode('layouts/app');
            appCode._newPassword('#new_password');
        });
    </script>
@endsection

@section('content')
    <div class="tab-content">

        <div class="tab-pane fade show active" id="routes" role="tabpanel" aria-labelledby="routes-tab">
            <h3>{{ __('My routes') }}</h3>

            @include('common.general.messages')

            @include('common.dungeonroute.table', ['view' => 'profile'])
        </div>

        <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            {{ Form::model($user, ['route' => ['profile.update', $user->id], 'method' => 'patch']) }}
            <h4>
                {{ $menuTitle }}
            </h4>

            @if($isOAuth && !$user->changed_username)
                <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                    <label for="name">
                        {{ __('Username') }}
                        <i class="fas fa-info-circle" data-toggle="tooltip"
                           title="{{ __('Since you logged in using an external Authentication service, you may change your username once.') }}"></i>
                    </label>
                    {!! Form::text('name', null, ['class' => 'form-control']) !!}
                    @include('common.forms.form-error', ['key' => 'name'])
                </div>
            @endif
            @if(!$isOAuth)
                <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                    {!! Form::label('email', __('Email')) !!}
                    {!! Form::text('email', null, ['class' => 'form-control']) !!}
                    @include('common.forms.form-error', ['key' => 'email'])
                </div>
            @endif
            <div class="form-group{{ $errors->has('game_server_region_id') ? ' has-error' : '' }}">
                {!! Form::label('game_server_region_id', __('Region')) !!}
                {!! Form::select('game_server_region_id', array_merge(['-1' => __('Select region')], \App\Models\GameServerRegion::all()->pluck('name', 'id')->toArray()), null, ['class' => 'form-control']) !!}
                @include('common.forms.form-error', ['key' => 'game_server_region_id'])
            </div>
            <div class="form-group{{ $errors->has('timezone') ? ' has-error' : '' }}">
                @include('common.forms.timezoneselect', ['selected' => $user->timezone])
            </div>
            <div class="form-group{{ $errors->has('echo_anonymous') ? ' has-error' : '' }}">
                <label for="echo_anonymous">
                    {{ __('Show as Anonymous') }}
                    <i class="fas fa-info-circle" data-toggle="tooltip"
                       title="{{ __('Enabling this option will show you as \'Anonymous\' when viewing routes that are not part of any teams you are a part of.
                            For your own routes and for routes part of your teams, your name will always be visible.') }}"></i>
                </label>
                {!! Form::checkbox('echo_anonymous', 1, $user->echo_anonymous, ['class' => 'form-control left_checkbox']) !!}
            </div>
            <div class="form-group{{ $errors->has('echo_color') ? ' has-error' : '' }}">
                <label for="echo_color">
                    {{ __('Synchronized route edit color') }}
                    <i class="fas fa-info-circle" data-toggle="tooltip"
                       title="{{ __('When editing a route cooperatively with a team member, this color will uniquely identify you.') }}"></i>
                </label>
                {!! Form::color('echo_color', null, ['id' => 'echo_color', 'class' => 'form-control']) !!}

                @php($classes = \App\Models\CharacterClass::all())
                @php($half = ($classes->count() / 2))
                @for($i = 0; $i < $classes->count(); $i++)
                    @php($class = $classes->get($i))
                    @if($i % $half === 0)
                        <div class="row no-gutters pt-1">
                            @endif
                            <div class="col profile_class_color border-dark"
                                 data-color="{{ $class->color }}"
                                 style="background-color: {{ $class->color }};">
                            </div>
                            @if($i % $half === $half - 1)
                        </div>
                    @endif
                @endfor
            </div>

            {!! Form::submit(__('Save'), ['class' => 'btn btn-info']) !!}
            {!! Form::close() !!}
        </div>

        <div class="tab-pane fade" id="account" role="tabpanel" aria-labelledby="account-tab">
            <h4>
                {{ __('Account') }}
            </h4>
            <div class="form-group">
                {{ __('If you delete your Keystone.guru account the following will happen:') }}
            </div>
            @if( !empty($deleteConsequences['dungeonroutes']) && $deleteConsequences['dungeonroutes']['delete_count'] > 0 )
                <div class="form-group">
                    <h5>
                        {{ __('Routes') }}
                    </h5>
                    <ul>
                        <li>
                            {{ __(sprintf('Your %s route(s) will be deleted.', $deleteConsequences['dungeonroutes']['delete_count'])) }}
                        </li>
                    </ul>
                </div>
            @endif
            @if( !empty($deleteConsequences['teams']) )
                <div class="form-group">
                    <h5>
                        {{ __('Teams') }}
                    </h5>
                    <ul>
                        <?php foreach($deleteConsequences['teams'] as $teamName => $consequence) { ?>
                        <li>
                            <?php
                            $consequenceText = '';
                            if ($consequence['result'] === 'new_owner') {
                                if ($consequence['new_owner'] === null) {
                                    $consequenceText = __('You will be removed from this team.');
                                } else {
                                    $consequenceText = sprintf(__('%s will be appointed Admin of this team.'),
                                        $consequence['new_owner']->name);
                                }
                            } elseif ($consequence['result'] === 'deleted') {
                                $consequenceText = __('This team will be deleted (you are the only user in this team).');
                            }
                            ?>
                            {{ sprintf(__('%s: %s'), $teamName, $consequenceText) }}
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            @endif
            @if( !empty($deleteConsequences['patreon']) && $deleteConsequences['patreon']['unlinked'] )
                <div class="form-group">
                    <h5>
                        {{ __('Patreon') }}
                    </h5>
                    <ul>
                        <li>
                            {{ __('The connection between Patreon and Keystone.guru will be terminated. You will no longer receive
                            Patreon rewards.') }}
                        </li>
                    </ul>
                </div>
            @endif
            @if( !empty($deleteConsequences['reports']))
                <div class="form-group">
                    <h5>
                        {{ __('Reports') }}
                    </h5>
                    <ul>
                        <li>
                            {{ sprintf(__('Your %s unresolved report(s) will be deleted'), $deleteConsequences['reports']['delete_count']) }}
                        </li>
                    </ul>
                </div>
            @endif
            <div class="text-danger font-weight-bold">
                {{ __('Your account will be permanently deleted. There is no turning back.') }}
            </div>
            {{ Form::open(['route' => 'profile.delete']) }}
            {!! Form::hidden('_method', 'delete') !!}
            {!! Form::submit(__('Delete my Keystone.guru account'), ['class' => 'btn btn-danger', 'name' => 'submit']) !!}
            {!! Form::close() !!}
        </div>

        <div class="tab-pane fade" id="tags" role="tabpanel" aria-labelledby="personal-tags-tab">
            <h4>
                {{ __('Personal tags') }}
            </h4>
            <p>
                {{ __('You can manage tags for your own routes here. Nobody else will be able to view your tags - for routes attached to a team
                        you can manage a separate set of tags for just that team.') }}
            </p>

            @include('common.tag.manager', ['category' => \App\Models\Tags\TagCategory::DUNGEON_ROUTE_PERSONAL])
        </div>

        <div class="tab-pane fade" id="patreon" role="tabpanel" aria-labelledby="patreon-tab">
            <h4>
                {{ __('Patreon') }}
            </h4>
            @isset($user->patreondata)
                <a class="btn patreon-color text-white" href="{{ route('patreon.unlink') }}" target="_blank">
                    {{ __('Unlink from Patreon') }}
                </a>

                <p class="mt-2">
                    <span class="text-info"><i class="fa fa-check-circle"></i></span>
                    {{ __('Your account is linked to Patreon. Thank you!') }}
                </p>
            @else
                <a class="btn patreon-color text-white" href="{{
                        'https://patreon.com/oauth2/authorize?' . http_build_query(
                            ['response_type' => 'code',
                            'client_id' => env('PATREON_CLIENT_ID'),
                            'redirect_uri' => route('patreon.link'),
                            'state' => csrf_token()
                            ])
                        }}" target="_blank">{{ __('Link to Patreon') }}</a>

                <p class="mt-2">
                    <span class="text-info"><i class="fa fa-info-circle"></i></span>
                    {{ __('In order to claim your Patreon rewards, you need to link your Patreon account') }}
                </p>
            @endisset
            <p class="text-warning mt-2">
                <i class="fa fa-exclamation-triangle"></i>
                {{ __('Patreon implementation is experimental. If your rewards are not available after linking with your Patreon, please contact me directly on Discord or Patreon and I will fix it for you.') }}
            </p>
        </div>

        @if(!$isOAuth)
            <div class="tab-pane fade" id="change-password" role="tabpanel" aria-labelledby="change-password-tab">
                <h4>
                    {{ __('Change password') }}
                </h4>
                {{--$user->email is intended, since that is the actual username--}}
                {{ Form::model($user, ['route' => ['profile.changepassword', $user->name], 'method' => 'patch']) }}
                {!! Form::hidden('username', $user->email) !!}
                <div class="form-group{{ $errors->has('current_password') ? ' has-error' : '' }}">
                    {!! Form::label('current_password', __('Current password')) !!}
                    {!! Form::password('current_password', ['class' => 'form-control', 'autocomplete' => 'current-password']) !!}
                    @include('common.forms.form-error', ['key' => 'current_password'])
                </div>

                <div class="form-group{{ $errors->has('new_password') ? ' has-error' : '' }}">
                    {!! Form::label('new_password', __('New password')) !!}
                    {!! Form::password('new_password', ['id' => 'new_password', 'class' => 'form-control', 'autocomplete' => 'new-password']) !!}
                    @include('common.forms.form-error', ['key' => 'new_password'])
                </div>


                <div class="form-group{{ $errors->has('new_password-confirm') ? ' has-error' : '' }}">
                    {!! Form::label('new_password-confirm', __('New password (confirm)')) !!}
                    {!! Form::password('new_password-confirm', ['class' => 'form-control', 'autocomplete' => 'new-password']) !!}
                    @include('common.forms.form-error', ['key' => 'new_password-confirm'])
                </div>

                {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}

                {!! Form::close() !!}
            </div>
        @endif

        <div class="tab-pane fade" id="privacy" role="tabpanel" aria-labelledby="privacy-tab">
            <h4>
                {{ __('Privacy') }}
            </h4>
            {{ Form::model($user, ['route' => ['profile.updateprivacy', $user->id], 'method' => 'patch']) }}
            <div class="form-group{{ $errors->has('analytics_cookie_opt_out') ? ' has-error' : '' }}">
                {!! Form::label('analytics_cookie_opt_out', __('Google Analytics cookies opt-out')) !!}
                {!! Form::checkbox('analytics_cookie_opt_out', 1, $user->analytics_cookie_opt_out, ['class' => 'form-control left_checkbox']) !!}
            </div>
            <div class="form-group{{ $errors->has('adsense_no_personalized_ads') ? ' has-error' : '' }}">
                {!! Form::label('adsense_no_personalized_ads', __('Google Adsense no personalized ads')) !!}
                {!! Form::checkbox('adsense_no_personalized_ads', 1, $user->adsense_no_personalized_ads, ['class' => 'form-control left_checkbox']) !!}
            </div>
            {!! Form::submit(__('Submit'), ['class' => 'btn btn-info']) !!}
            {!! Form::close() !!}
        </div>

        <div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
            <h4>
                {{ __('Reports') }}
            </h4>
            <p>
                {{ __('All routes, enemies and other reports you have made on the site will be listed here.') }}
            </p>

        </div>
    </div>
@endsection