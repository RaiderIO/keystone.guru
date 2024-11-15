<?php

use App\Models\Patreon\PatreonBenefit;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;

/**
 * @var Team $team
 * @var bool $userIsModerator
 * @var bool $userHasAdFreeTeamMembersPatreonBenefit
 * @var int  $userAdFreeTeamMembersRemaining
 * @var int  $userAdFreeTeamMembersMax
 */

$title                        = sprintf(__('view_team.edit.title'), $team->name);
$routesTableInlineId          = 'team_edit_routes_table';
$routePublishingTableInlineId = 'team_edit_route_publishing_table';

/** @var User $user */
$user      = Auth::user();
$menuItems = [
    ['icon' => 'far fa-list-alt', 'text' => __('view_team.edittabs.overview.title'), 'target' => '#overview'],
    ['icon' => 'fa-route', 'text' => __('view_team.edittabs.routes.title'), 'target' => '#routes'],
    ['icon' => 'fa-users', 'text' => __('view_team.edittabs.members.title'), 'target' => '#members'],
];
// May only edit details when member is a moderator
if ($userIsModerator) {
    $menuItems[] = ['icon' => 'fa-clock', 'text' => __('view_team.edittabs.routepublishing.title'), 'target' => '#route_publishing'];
    $menuItems[] = ['icon' => 'fa-tag', 'text' => __('view_team.edittabs.tags.title'), 'target' => '#team_tags'];
    $menuItems[] = ['icon' => 'fa-edit', 'text' => __('view_team.edittabs.details.title'), 'target' => '#details'];
}

$data = [];
foreach ($team->teamUsers as $teamUser) {
    /** @var TeamUser $teamUser */
    $hasAdFreeGiveaway = $teamUser->user->hasAdFreeGiveaway();

    $data[] = [
        'user_id'                              => $teamUser->user->id,
        'name'                                 => $teamUser->user->name,
        'join_date'                            => $teamUser->created_at->toDateTimeString(),
        'role'                                 => $teamUser->role,
        // Any and all roles that the user may assign to other users
        'assignable_roles'                     => $team->getAssignableRoles($user, $teamUser->user),
        'has_ad_free'                          => $teamUser->user->hasPatreonBenefit(PatreonBenefit::AD_FREE),
        'has_ad_free_giveaway'                 => $hasAdFreeGiveaway,
        'has_ad_free_giveaway_by_current_user' => $hasAdFreeGiveaway && $teamUser->user->getAdFreeGiveawayUser()->id === $user->id,
    ];
}
?>
@extends('layouts.sitepage', [
    'title' => $title,
    'menuTitle' => __('view_team.edit.menu_title'),
    'menuItems' => $menuItems,
    'breadcrumbsParams' => [$team],
    // The models to display as an option in the menu, plus the route to take when selecting them
    'menuModels' => $user->teams,
    'menuModelsRoute' => 'team.edit',
    'menuModelsRouteParameterName' => 'team',
    'menuModelEdit' => $team,
])
@section('header-title', $title)
@section('header-addition')
    <!--suppress HtmlDeprecatedAttribute -->
    <a href="{{ route('team.list') }}" class="btn btn-info text-white float-right" role="button">
        <i class="fas fa-backward"></i> {{ __('view_team.edit.to_team_list') }}
    </a>
@endsection
@include('common.general.inline', ['path' => 'team/edit', 'options' => [
    'dependenciesById' => [$routesTableInlineId, $routePublishingTableInlineId],
    'routesTableInlineId' => $routesTableInlineId,

    'routePublishingEnabledSelector' => '#route_publishing_enabled_checkbox',

    'data' => $data,
    'teamName' => $team->name,
    'teamPublicKey' => $team->public_key,
    'userIsModerator' => $userIsModerator,
    'currentUserId' => $user->id,
    'currentUserName' => $user->name,
    'currentUserRole' => $team->getUserRole($user),
    'adFreeGiveawayLeft' => $userAdFreeTeamMembersRemaining,
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
        @include('team.edittabs.overview', ['team' => $team])
        @include('team.edittabs.routes', [
            'inlineId' => $routesTableInlineId,
            'team' => $team,
            'userIsModerator' => $userIsModerator,
        ])
        @include('team.edittabs.members', [
            'team' => $team,
            'userIsModerator' => $userIsModerator,
            'userHasAdFreeTeamMembersPatreonBenefit' => $userHasAdFreeTeamMembersPatreonBenefit,
            'userAdFreeTeamMembersRemaining' => $userAdFreeTeamMembersRemaining,
            'userAdFreeTeamMembersMax' => $userAdFreeTeamMembersMax,
        ])
        @include('team.edittabs.teamtags', ['team' => $team])

        @if($userIsModerator)
            @include('team.edittabs.routepublishing', [
                'inlineId' => $routePublishingTableInlineId,
                'team' => $team,
            ])
            @include('team.edittabs.details', ['team' => $team])
        @endif
    </div>
@endsection
