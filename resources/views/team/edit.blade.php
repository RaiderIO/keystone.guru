<?php
/** @var \App\Models\Team $model */
$title = __('Edit team');
/** @var \App\User $user */
$user = Auth::user();
$userRole = $model->getUserRole($user);
$userIsModerator = $userRole === 'moderator' || $userRole === 'admin';
$menuItems = [
    ['icon' => 'far fa-list-alt', 'text' => __('Overview'), 'target' => '#overview'],
    ['icon' => 'fa-route', 'text' => __('Routes'), 'target' => '#routes'],
    ['icon' => 'fa-users', 'text' => __('Members'), 'target' => '#members'],
    ['icon' => 'fa-edit', 'text' => __('Team details'), 'target' => '#details']
];

$data = [];
foreach ($model->teamusers as $teamuser) {
    /** @var $teamuser \App\Models\TeamUser */
    $data[] = [
        'user_id' => $teamuser->user->id,
        'name' => $teamuser->user->name,
        'join_date' => $teamuser->created_at->toDateTimeString(),
        'role' => $teamuser->role,
        // Any and all roles that the user may assign to other users
        'assignable_roles' => $model->getAssignableRoles($user, $teamuser->user)
    ];
}
?>
@extends('layouts.app', ['title' => $title,
    'menuTitle' => __('Teams'), 'menuItems' => $menuItems,
    // The models to display as an option in the menu, plus the route to take when selecting them
    'menuModels' => $user->teams, 'menuModelsRoute' => 'team.edit',
    'model' => $model])
@section('header-title', $title)
@section('header-addition')
    <a href="{{ route('team.list') }}" class="btn btn-info text-white float-right" role="button">
        <i class="fas fa-backward"></i> {{ __('Team list') }}
    </a>
@endsection
@include('common.general.inline', ['path' => 'team/edit', 'options' => [
    'data' => $data,
    'teamId' => $model->id,
    'userIsModerator' => $userIsModerator,
    'currentUserId' => $user->id,
    'currentUserName' => $user->name,
    'currentUserRole' => $userRole,
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
                    {{ sprintf(__('Team %s'), $model->name) }}
                </h4>

                <div class="row">
                    <div class="col">
                        <div class="card" style="width: 18rem;">
                            @isset($model->iconfile)
                                <img class="card-img-top" src="{{ url('storage/' . $model->iconfile->getUrl()) }}"
                                     alt="{{ __('No image') }}">
                            @endisset
                            <div class="card-body">
                                <p class="card-text">
                                    {{ $model->description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card" style="width: 18rem;">
                            <div class="card-header">
                                {{ __('Routes') }}
                            </div>
                            <div class="card-body text-center">
                                <h1>{{ $model->dungeonroutes->count() }}</h1>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card" style="width: 18rem;">
                            <div class="card-header">
                                {{ __('Members') }}
                            </div>
                            <div class="card-body text-center">
                                <h1>{{ $model->members->count() }}</h1>
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

                @include('common.dungeonroute.table', ['view' => 'team', 'team' => $model])
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
                <div class="col-xl-5">
                    <div class="input-group-append">
                        {!! Form::text('team_members_invite_link', route('team.invite', ['invitecode' => $model->invite_code]),
                            ['id' => 'team_members_invite_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                        <div class="input-group-append">
                            <button id="team_invite_link_copy_to_clipboard" class="btn btn-info"
                                    data-toggle="tooltip" title="{{ __('Copy to clipboard') }}">
                                <i class="far fa-copy"></i>
                            </button>
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

        <div class="tab-pane fade" id="details" role="tabpanel"
             aria-labelledby="details-tab">
            <div class="">
                <h4>
                    {{ __('Team details') }}
                </h4>

                @include('team.details', ['model' => $model])
            </div>
        </div>
    </div>
@endsection
