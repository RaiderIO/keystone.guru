<?php

use App\Models\DungeonRoute\DungeonRoute;

/**
 * @var  DungeonRoute|null $dungeonRoute
 */

$challengeModeRun = $dungeonRoute->getChallengeModeRun();
?>
<h3 class="card-title">{{ __('view_common.modal.routeadminsettings.title') }}</h3>

<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active"
           id="dungeon_route_info_tab" data-toggle="tab" href="#route-info" role="tab"
           aria-controls="dungeon_route_info_tab" aria-selected="false">
            {{ __('view_common.modal.routeadminsettings.dungeon_route_info') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="combatlog_info_tab" data-toggle="tab" href="#combatlog-info"
           role="tab"
           aria-controls="combatlog_info_tab" aria-selected="false">
            {{ __('view_common.modal.routeadminsettings.combatlog_info') }}
        </a>
    </li>
</ul>

<div class="tab-content">
    <div id="route-info" class="tab-pane fade show active mt-3"
         role="tabpanel" aria-labelledby="dungeon_route_info_tab">
        <div class="form-group">
            <h5>
                {{ __('view_common.modal.routeadminsettings.links') }}
            </h5>
            <a href="{{ route('admin.floor.edit.mapping', [
                'dungeon' => $dungeonRoute->dungeon,
                'floor' => $dungeonRoute->dungeon->floors->first(),
                'mapping_version' => $dungeonRoute->mapping_version_id,
            ]) }}">
                {{ __('view_common.modal.routeadminsettings.edit_mapping_version') }}
            </a>
        </div>
        <div class="form-group">
            @dump($dungeonRoute)
        </div>
    </div>
    <div id="combatlog-info" class="tab-pane fade mt-3" role="tabpanel"
         aria-labelledby="combatlog_info_tab">
        <div class="form-group">
            <h5>
                {{ __('view_common.modal.routeadminsettings.challenge_mode_run') }}
            </h5>
            @if($challengeModeRun !== null)
                @include('common.general.modeltable', ['model' => $challengeModeRun])
            @else
                {{ __('view_common.modal.routeadminsettings.route_not_created_from_combat_log') }}
            @endif
        </div>
        <div class="form-group">
            <h5>
                {{ __('view_common.modal.routeadminsettings.challenge_mode_run_data') }}
            </h5>
            @if($challengeModeRun?->challengeModeRunData !== null)
                @include('common.general.modeltable', ['model' => $challengeModeRun->challengeModeRunData, 'exclude' => ['post_body']])
                {{ html()->textarea('post_body', json_encode(json_decode($challengeModeRun->challengeModeRunData->post_body), JSON_PRETTY_PRINT))->class('form-control w-100')->isReadonly() }}
            @else
                {{ __('view_common.modal.routeadminsettings.route_not_created_through_api') }}
            @endif
        </div>
    </div>
</div>
