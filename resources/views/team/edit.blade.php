<?php
/** @var \App\Models\Team $team */
$title = sprintf(__('views/team.edit.title'), $team->name);
/** @var \App\User $user */
$user            = Auth::user();
$userIsModerator = $team->isUserModerator($user);
$menuItems       = [
    ['icon' => 'far fa-list-alt', 'text' => __('views/team.edit.overview'), 'target' => '#overview'],
    ['icon' => 'fa-route', 'text' => __('views/team.edit.routes'), 'target' => '#routes'],
    ['icon' => 'fa-users', 'text' => __('views/team.edit.members'), 'target' => '#members']
];
// May only edit details when member is a moderator
if ($userIsModerator) {
    $menuItems[] = ['icon' => 'fa-tag', 'text' => __('views/team.edit.team_tags'), 'target' => '#tags'];
    $menuItems[] = ['icon' => 'fa-edit', 'text' => __('views/team.edit.team_details'), 'target' => '#details'];
}

$data = [];
foreach ($team->teamusers as $teamuser) {
    /** @var $teamuser \App\Models\TeamUser */
    $data[] = [
        'user_id'          => $teamuser->user->id,
        'name'             => $teamuser->user->name,
        'join_date'        => $teamuser->created_at->toDateTimeString(),
        'role'             => $teamuser->role,
        // Any and all roles that the user may assign to other users
        'assignable_roles' => $team->getAssignableRoles($user, $teamuser->user)
    ];
}
?>
@extends('layouts.sitepage', [
    'title' => $title,
    'menuTitle' => __('views/team.edit.menu_title'),
    'menuItems' => $menuItems,
    'breadcrumbsParams' => [$team],
    // The models to display as an option in the menu, plus the route to take when selecting them
    'menuModels' => $user->teams,
    'menuModelsRoute' => 'team.edit',
    'menuModelsRouteParameterName' => 'team',
    'menuModelEdit' => $team
])
@section('header-title', $title)
@section('header-addition')
    <a href="{{ route('team.list') }}" class="btn btn-info text-white float-right" role="button">
        <i class="fas fa-backward"></i> {{ __('views/team.edit.to_team_list') }}
    </a>
@endsection
@include('common.general.inline', ['path' => 'team/edit', 'options' => [
    'data' => $data,
    'teamName' => $team->name,
    'teamPublicKey' => $team->public_key,
    'userIsModerator' => $userIsModerator,
    'currentUserId' => $user->id,
    'currentUserName' => $user->name,
    'currentUserRole' => $team->getUserRole($user),
]])

@section('scripts')
    @parent

    <script type="text/javascript">
        var _currentUserId = {{ $user->id }};
        var _currentUserName = "{{ $user->name }}";
    </script>
@endsection

@section('content')

    <div class="tab-content">
        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
            <div class="form-group">
                <h4>
                    {{ sprintf(__('views/team.edit.team_header'), $team->name) }}
                </h4>
                @include('common.general.messages')

                <div class="row">
                    <div class="col-lg mt-2">
                        <div class="card text-center">
                            <div class="card-header">
                                {{ $team->name }}
                            </div>
                            @isset($team->iconfile)
                                <div class="card-body p-0">
                                    <div class="row">
                                        <div class="col" style="max-width: 128px">
                                            <img class="card-img-top d-block"
                                                 src="{{ $team->iconfile->getURL() }}"
                                                 alt="{{ __('views/team.edit.icon_image_alt') }}"
                                                 style="max-width: 128px; max-height: 128px;">
                                        </div>
                                        <div class="col text-left pl-0">
                                            {{ $team->description }}
                                        </div>
                                        <div class="col">
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="card-body">
                                    @isset($team->description)
                                        {{ $team->description }}
                                    @else
                                        <h1>&nbsp;</h1>
                                    @endisset
                                </div>
                            @endisset
                        </div>
                    </div>

                    <div class="col-lg mt-2">
                        <div class="card text-center">
                            <div class="card-header">
                                {{ __('views/team.edit.routes') }}
                            </div>
                            <div class="card-body">
                                <h1>{{ $team->getVisibleRouteCount() }}</h1>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg mt-2">
                        <div class="card text-center">
                            <div class="card-header">
                                {{ __('views/team.edit.members') }}
                            </div>
                            <div class="card-body">
                                <h1>{{ $team->members()->count() }}</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="routes" role="tabpanel" aria-labelledby="routes-tab">
            <div class="form-group">
                <div class="row">
                    <div class="col">
                        <h4>
                            {{ __('views/team.edit.route_list') }}
                        </h4>
                    </div>
                    <div class="col-auto">
                        @if($userIsModerator)
                            <button id="add_route_btn" class="btn btn-success">
                                <i class="fas fa-plus"></i> {{ __('views/team.edit.add_route') }}
                            </button>
                        @else
                            <button id="add_route_btn" class="btn btn-success" disabled
                                    data-toggle="tooltip" title="{{ __('views/team.edit.add_route_no_moderator') }}">
                                <i class="fas fa-plus"></i> {{ __('views/team.edit.add_route') }}
                            </button>
                        @endif
                        <button id="view_existing_routes" class="btn btn-warning"
                                style="display: none;">
                            <i class="fas fa-backward"></i> {{ __('views/team.edit.stop_adding_routes') }}
                        </button>
                    </div>
                </div>

                @include('common.dungeonroute.table', ['view' => 'team', 'team' => $team])
            </div>
        </div>

        <div class="tab-pane fade" id="members" role="tabpanel" aria-labelledby="members-tab">
            <h4>
                {{ __('views/team.edit.members') }}
            </h4>
            <div class="form-group">
                @component('common.general.alert', ['type' => 'info', 'name' => 'team-invite-info'])
                    {{ __('views/team.edit.invite_code_share_warning') }}
                @endcomponent

                <div class="row">
                    <div class="col-xl-6">
                        {!! Form::label('team_members_invite_link', __('views/team.edit.invite_new_members'), [], false) !!}
                        <div class="input-group-append">
                            {!! Form::text('team_members_invite_link', route('team.invite', ['invitecode' => $team->invite_code]),
                                ['id' => 'team_members_invite_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                            <div class="input-group-append">
                                <button id="team_invite_link_copy_to_clipboard" class="btn btn-info"
                                        data-toggle="tooltip"
                                        title="{{ __('views/team.edit.copy_to_clipboard_title') }}">
                                    <i class="far fa-copy"></i>
                                </button>
                                @if($userIsModerator)
                                    <button id="team_invite_link_refresh" class="btn btn-info"
                                            data-toggle="tooltip"
                                            title="{{ __('views/team.edit.refresh_invite_link_title') }}">
                                        <i class="fa fa-sync"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        {!! Form::label('default_role', __('views/team.edit.default_role'), [], false) !!}
                        <?php $keys = array_keys(\App\Models\TeamUser::ALL_ROLES); ?>
                        {!! Form::select('default_role', array_map(function($role){
                                return __(sprintf('teamroles.%s', $role));
                            }, array_combine($keys, $keys)), $team->default_role, ['class' => 'form-control selectpicker']) !!}
                    </div>
                </div>
            </div>

            <div class="form-group">
                <table id="team_members_table" class="tablesorter default_table table-striped w-100" width="100%">
                    <thead>

                    </thead>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="tags" role="tabpanel" aria-labelledby="team-tags-tab">
            <h4>
                {{ __('views/team.edit.team_tags') }}
            </h4>
            <p>
                {{ __('views/team.edit.team_tags_description') }}
            </p>

            @include('common.tag.manager', ['category' => \App\Models\Tags\TagCategory::DUNGEON_ROUTE_TEAM])
        </div>

        @if($userIsModerator)
            <div class="tab-pane fade" id="details" role="tabpanel"
                 aria-labelledby="details-tab">
                <div class="">
                    <h4>
                        {{ __('views/team.edit.team_details') }}
                    </h4>

                    @include('common.team.details', ['model' => $team])
                </div>
            </div>
        @endif
    </div>
@endsection
