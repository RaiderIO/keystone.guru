<?php

use App\Models\Team;

/**
 * @var Team   $team
 * @var bool   $userIsModerator
 * @var string $inlineId
 */
?>
<div class="tab-pane fade" id="routes" role="tabpanel" aria-labelledby="routes-tab">
    <div class="form-group">
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
                            data-toggle="tooltip" title="{{ __('view_team.edit.add_route_no_moderator') }}">
                        <i class="fas fa-plus"></i> {{ __('view_team.edittabs.routes.add_route') }}
                    </button>
                @endif
                <button id="view_existing_routes" class="btn btn-warning"
                        style="display: none;">
                    <i class="fas fa-backward"></i> {{ __('view_team.edittabs.routes.stop_adding_routes') }}
                </button>
            </div>
        </div>

        @include('common.dungeonroute.table', ['inlineId' => $inlineId, 'view' => 'team', 'team' => $team])
    </div>
</div>
