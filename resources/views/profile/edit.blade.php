<?php
/** @var \App\User $user */
/** @var \App\Models\CharacterClass[]|\Illuminate\Support\Collection $allClasses */

$user = Auth::getUser();
$isOAuth = $user->password === '';
$menuItems = [
    ['icon' => 'fa-user', 'text' => __('views/profile.edit.profile'), 'target' => '#profile'],
    ['icon' => 'fa-cog', 'text' => __('views/profile.edit.account'), 'target' => '#account'],
    ['icon' => 'fab fa-patreon', 'text' => __('views/profile.edit.patreon'), 'target' => '#patreon'],
];
// Optionally add this menu item
if (!$isOAuth) {
    $menuItems[] = ['icon' => 'fa-key', 'text' => __('views/profile.edit.change_password'), 'target' => '#change-password'];
}
$menuItems[] = ['icon' => 'fa-user-secret', 'text' => __('views/profile.edit.privacy'), 'target' => '#privacy'];
$menuItems[] = ['icon' => 'fa-flag', 'text' => __('views/profile.edit.reports'), 'target' => '#reports'];

$menuTitle = sprintf(__('views/profile.edit.menu_title'), $user->name);
$deleteConsequences = $user->getDeleteConsequences();
?>
@extends('layouts.sitepage', ['wide' => true,
    'title' => __('views/profile.edit.title'),
    'menuTitle' => $menuTitle,
    'menuItems' => $menuItems,
    'menuModelEdit' => $user
])

@include('common.general.inline', ['path' => 'profile/edit', 'options' => [

]])

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            // Code for base app
            var appCode = _inlineManager.getInlineCode('layouts/app');
            appCode._newPassword('#new_password');

            // Disabled since it's not shown by default and causes a JS error otherwise
            // $('#user_reports_table').DataTable({});
        });
    </script>
@endsection

@section('content')
    <div class="tab-content">
        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            {{ Form::model($user, ['route' => ['profile.update', $user->id], 'method' => 'patch', 'files' => true]) }}
            <h4>
                {{ $menuTitle }}
            </h4>

            <div class="form-group{{ $errors->has('avatar') ? ' has-error' : '' }}">
                {!! Form::label('avatar', __('views/profile.edit.avatar')) !!}
                {!! Form::file('avatar', ['class' => 'form-control']) !!}
            </div>

            @if(isset($user->iconfile))
                <div class="form-group">
                    {{__('views/profile.edit.avatar')}}: <img src="{{ $user->iconfile->getURL() }}"
                                           alt="{{ __('views/profile.edit.avatar_title') }}" style="max-width: 48px"/>
                </div>
            @endif

            @if($isOAuth && !$user->changed_username)
                <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                    <label for="name">
                        {{ __('views/profile.edit.username') }}
                        <i class="fas fa-info-circle" data-toggle="tooltip"
                           title="{{ __('views/profile.edit.username_title') }}"></i>
                    </label>
                    {!! Form::text('name', null, ['class' => 'form-control']) !!}
                    @include('common.forms.form-error', ['key' => 'name'])
                </div>
            @endif
            {{--            <div class="form-group{{ $errors->has('theme') ? ' has-error' : '' }}">--}}
            {{--                {!! Form::label('theme', __('Theme')) !!}--}}
            {{--                {!! Form::select('theme', config('keystoneguru.themes'), null, ['class' => 'form-control']) !!}--}}
            {{--                @include('common.forms.form-error', ['key' => 'theme'])--}}
            {{--            </div>--}}
            @if(!$isOAuth)
                <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                    {!! Form::label('email', __('views/profile.edit.email')) !!}
                    {!! Form::text('email', null, ['class' => 'form-control']) !!}
                    @include('common.forms.form-error', ['key' => 'email'])
                </div>
            @endif
            <div class="form-group{{ $errors->has('game_server_region_id') ? ' has-error' : '' }}">
                {!! Form::label('game_server_region_id', __('views/profile.edit.region')) !!}
                {!! Form::select('game_server_region_id', array_merge(['-1' => __('views/profile.edit.select_region')], \App\Models\GameServerRegion::all()->pluck('name', 'id')->toArray()), null, ['class' => 'form-control']) !!}
                @include('common.forms.form-error', ['key' => 'game_server_region_id'])
            </div>
            <div class="form-group{{ $errors->has('timezone') ? ' has-error' : '' }}">
                @include('common.forms.timezoneselect', ['selected' => $user->timezone])
            </div>
            <div class="form-group{{ $errors->has('echo_anonymous') ? ' has-error' : '' }}">
                <label for="echo_anonymous">
                    {{ __('views/profile.edit.show_as_anonymous') }}
                    <i class="fas fa-info-circle" data-toggle="tooltip"
                       title="{{ __('views/profile.edit.show_as_anonymous_title') }}"></i>
                </label>
                {!! Form::checkbox('echo_anonymous', 1, $user->echo_anonymous, ['class' => 'form-control left_checkbox']) !!}
            </div>
            <div class="form-group{{ $errors->has('echo_color') ? ' has-error' : '' }}">
                <label for="echo_color">
                    {{ __('views/profile.edit.echo_color') }}
                    <i class="fas fa-info-circle" data-toggle="tooltip"
                       title="{{ __('views/profile.edit.echo_color_title') }}"></i>
                </label>
                {!! Form::color('echo_color', null, ['id' => 'echo_color', 'class' => 'form-control']) !!}

                <?php
                $half = ($allClasses->count() / 2);
                for($i = 0; $i < $allClasses->count(); $i++){
                $class = $allClasses->get($i)
                ?>
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
                <?php
                }
                ?>
            </div>

            {!! Form::submit(__('views/profile.edit.save'), ['class' => 'btn btn-info']) !!}
            {!! Form::close() !!}
        </div>

        <div class="tab-pane fade" id="account" role="tabpanel" aria-labelledby="account-tab">
            <h4>
                {{ __('views/profile.edit.account') }}
            </h4>
            <div class="form-group">
                {{ __('views/profile.edit.account_delete_consequences') }}:
            </div>
            @if( !empty($deleteConsequences['dungeonroutes']) && $deleteConsequences['dungeonroutes']['delete_count'] > 0 )
                <div class="form-group">
                    <h5>
                        {{ __('views/profile.edit.account_delete_consequence_routes') }}
                    </h5>
                    <ul>
                        <li>
                            {{ sprintf(__('views/profile.edit.account_delete_consequence_routes_delete'), $deleteConsequences['dungeonroutes']['delete_count']) }}
                        </li>
                    </ul>
                </div>
            @endif
            @if( !empty($deleteConsequences['teams']) )
                <div class="form-group">
                    <h5>
                        {{ __('views/profile.edit.account_delete_consequence_teams') }}
                    </h5>
                    <ul>
                        <?php foreach($deleteConsequences['teams'] as $teamName => $consequence) { ?>
                        <li>
                            <?php
                            $consequenceText = '';
                            if ($consequence['result'] === 'new_owner') {
                            if ($consequence['new_owner'] === null) {
                            $consequenceText = __('views/profile.edit.account_delete_consequence_teams_you_are_removed');
                            } else {
                            $consequenceText = sprintf(__('views/profile.edit.account_delete_consequence_teams_new_admin'),
                            $consequence['new_owner']->name);
                            }
                            } elseif ($consequence['result'] === 'deleted') {
                            $consequenceText = __('views/profile.edit.account_delete_consequence_teams_team_deleted');
                            }
                            ?>
                            {{ sprintf('%s: %s', $teamName, $consequenceText) }}
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            @endif
            @if( !empty($deleteConsequences['patreon']) && $deleteConsequences['patreon']['unlinked'] )
                <div class="form-group">
                    <h5>
                        {{ __('views/profile.edit.patreon') }}
                    </h5>
                    <ul>
                        <li>
                            {{ __('views/profile.edit.account_delete_consequence_patreon') }}
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

        <div class="tab-pane fade" id="patreon" role="tabpanel" aria-labelledby="patreon-tab">
            <h4>
                {{ __('Patreon') }}
            </h4>
            @isset($user->patreondata)
                <a class="btn patreon-color text-white" href="{{ route('patreon.unlink') }}" target="_blank"
                   rel="noopener noreferrer">
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
                            'client_id' => config('keystoneguru.patreon.oauth.client_id'),
                            'redirect_uri' => route('patreon.link'),
                            'state' => csrf_token()
                            ])
                        }}" target="_blank" rel="noopener noreferrer">
                    {{ __('Link to Patreon') }}
                </a>

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

            <table id="user_reports_table" class="tablesorter default_table table-striped">
                <thead>
                <tr>
                    <th width="5%">{{ __('Id') }}</th>
                    <th width="10%">{{ __('Category') }}</th>
                    <th width="60%">{{ __('Message') }}</th>
                    <th width="15%">{{ __('Created at') }}</th>
                    <th width="10%">{{ __('Status') }}</th>
                </tr>
                </thead>

                <tbody>
                @foreach ($user->reports()->orderByDesc('id')->get() as $report)
                    <?php /** @var $user \App\Models\UserReport */?>
                    <tr>
                        <td>{{ $report->id }}</td>
                        <td>{{ $report->category }}</td>
                        <td>{{ $report->message }}</td>
                        <td>{{ $report->contact_ok ? $report->user->email : '-' }}</td>
                        <td>{{ $report->created_at }}</td>
                        <td>
                            <button class="btn btn-success mark_as_handled_btn" data-id="{{$report->id}}">
                                <i class="fas fa-check-circle"></i> {{ __('Handled') }}
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>

            </table>
        </div>
    </div>
@endsection