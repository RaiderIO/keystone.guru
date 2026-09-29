<?php

use App\Http\Requests\Team\TeamAddRoutesFormRequest;
use App\Models\GameVersion\GameVersion;
use App\Models\Team;

/**
 * @var Team        $team
 * @var bool        $userIsModerator
 * @var string      $inlineId
 * @var string      $routePickerId
 * @var GameVersion $currentUserGameVersion
 */
?>
<div class="tab-pane fade" id="routes" role="tabpanel" aria-labelledby="routes-tab">
    <div class="mb-3">
        <div class="row">
            <div class="col">
                <h4>
                    {{ __('view_team.edittabs.routes.title') }}
                </h4>
            </div>
            <div class="col-auto">
                @if($userIsModerator)
                    <button id="add_route_btn" class="btn btn-success">
                        <i class="fas fa-plus"></i> {{ __('view_team.edittabs.routes.add_route') }}
                    </button>
                @else
                    <button id="add_route_btn" class="btn btn-success" disabled
                            data-bs-toggle="tooltip" title="{{ __('view_team.edit.add_route_no_moderator') }}">
                        <i class="fas fa-plus"></i> {{ __('view_team.edittabs.routes.add_route') }}
                    </button>
                @endif
            </div>
        </div>

        @include('common.dungeonroute.table', ['inlineId' => $inlineId, 'view' => 'team', 'team' => $team])
    </div>
</div>

@if($userIsModerator)
    @include('common.dungeonroute.picker', [
        'id' => $routePickerId,
        'title' => sprintf(__('view_team.edittabs.routes.picker_title'), $team->name),
        'sourceScope' => 'unassigned_by_members',
        'sourceTeam' => $team,
        'lockedGameVersion' => $currentUserGameVersion,
        'max' => TeamAddRoutesFormRequest::MAX_ROUTES_PER_REQUEST,
        'actionUrl' => sprintf('/ajax/team/%s/route', $team->public_key),
        'openButtonSelector' => '#add_route_btn',
    ])
@endif
