<?php
/** @var \App\Models\Team $team */
$title = sprintf(__('Team %s'), $team->name);
/** @var \App\User $user */
$user = Auth::user();
$userIsModerator = $team->isUserModerator($user);
$menuItems = [
    ['icon' => 'far fa-list-alt', 'text' => __('Overview'), 'target' => '#overview'],
    ['icon' => 'fa-route', 'text' => __('Routes'), 'target' => '#routes'],
    ['icon' => 'fa-users', 'text' => __('Members'), 'target' => '#members']
];
// May only edit details when member is a moderator
if ($userIsModerator) {
    $menuItems[] = ['icon' => 'fa-tag', 'text' => __('Team tags'), 'target' => '#tags'];
    $menuItems[] = ['icon' => 'fa-edit', 'text' => __('Team details'), 'target' => '#details'];
}

$data = [];
foreach ($team->teamusers as $teamuser) {
    /** @var $teamuser \App\Models\TeamUser */
    $data[] = [
        'user_id' => $teamuser->user->id,
        'name' => $teamuser->user->name,
        'join_date' => $teamuser->created_at->toDateTimeString(),
        'role' => $teamuser->role,
        // Any and all roles that the user may assign to other users
        'assignable_roles' => $team->getAssignableRoles($user, $teamuser->user)
    ];
}
?>
@extends('layouts.sitepage', [
    'title' => $title,
    'menuTitle' => __('Teams'),
    'menuItems' => $menuItems,
    // The models to display as an option in the menu, plus the route to take when selecting them
    'menuModels' => $user->teams,
    'menuModelsRoute' => 'team.edit',
    'menuModelsRouteParameterName' => 'team',
    'menuModelEdit' => $team
])
@section('header-title', $title)
@section('header-addition')
    <a href="{{ route('team.list') }}" class="btn btn-info text-white float-right" role="button">
        <i class="fas fa-backward"></i> {{ __('Team list') }}
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
                    {{ sprintf(__('Team %s'), $team->name) }}
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
                                                 alt="{{ __('No image') }}"
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
                                {{ __('Routes') }}
                            </div>
                            <div class="card-body">
                                <h1>{{ $team->dungeonroutes->count() }}</h1>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg mt-2">
                        <div class="card text-center">
                            <div class="card-header">
                                {{ __('Members') }}
                            </div>
                            <div class="card-body">
                                <h1>{{ $team->members->count() }}</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="routes" role="tabpanel" aria-labelledby="routes-tab">
            <div class="form-group">
                <div class="row">
                    <div class="col-8">
                        <h4>
                            {{ __('Route list') }}
                        </h4>
                    </div>
                    <div class="col-4">
                        @if($userIsModerator)
                            <button id="add_route_btn" class="btn btn-success float-right">
                                <i class="fas fa-plus"></i> {{ __('Add route') }}
                            </button>
                            <button id="view_existing_routes" class="btn btn-warning float-right"
                                    style="display: none;">
                                <i class="fas fa-backward"></i> {{ __('Stop adding routes') }}
                            </button>
                        @endif
                    </div>
                </div>

                @include('common.dungeonroute.table', ['view' => 'team', 'team' => $team])
            </div>
        </div>

        <div class="tab-pane fade" id="members" role="tabpanel" aria-labelledby="members-tab">
            <h4>
                {{ __('Members') }}
            </h4>
            <div class="form-group">
                <h5>
                    {{ __('Invite new members') }}
                </h5>
                <div class="row">
                    @if(!isAlertDismissed('team-invite-info'))
                        <div class="col">
                            <div class="alert alert-info alert-dismissible">
                                <a href="#" class="close" data-dismiss="alert" aria-label="close"
                                   data-alert-dismiss-id="team-invite-info">
                                    <i class="fas fa-times"></i>
                                </a>
                                <i class="fas fa-info-circle"></i>
                                {{ __('Be careful who you share the invite link with, everyone with the link can join your team!') }}
                            </div>
                        </div>
                    @endif
                </div>
                <div class="row">
                    <div class="col-xl-5">
                        <div class="input-group-append">
                            {!! Form::text('team_members_invite_link', route('team.invite', ['invitecode' => $team->invite_code]),
                                ['id' => 'team_members_invite_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                            <div class="input-group-append">
                                <button id="team_invite_link_copy_to_clipboard" class="btn btn-info"
                                        data-toggle="tooltip" title="{{ __('Copy to clipboard') }}">
                                    <i class="far fa-copy"></i>
                                </button>
                                @if($team->isUserModerator(\App\User::findOrFail(Auth::id())))
                                    <button id="team_invite_link_refresh" class="btn btn-info"
                                            data-toggle="tooltip" title="{{ __('Refresh invite link') }}">
                                        <i class="fa fa-sync"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
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
                {{ __('Team tags') }}
            </h4>
            <p>
                {{ __('You can manage tags for the team\'s routes here. Everyone that is a member of this team may view the tags attached to the routes.
                        The personal tags that may or may not have been attached by the route owner will not be visible.') }}
            </p>

            @include('common.tag.manager', ['category' => \App\Models\Tags\TagCategory::DUNGEON_ROUTE_TEAM])
        </div>

        @if($userIsModerator)
            <div class="tab-pane fade" id="details" role="tabpanel"
                 aria-labelledby="details-tab">
                <div class="">
                    <h4>
                        {{ __('Team details') }}
                    </h4>

                    @include('common.team.details', ['model' => $team])
                </div>
            </div>
        @endif
    </div>
@endsection
