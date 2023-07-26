<?php
/** @var $dungeonRoute App\Models\DungeonRoute|null */
$challengeModeRun = $dungeonRoute->getChallengeModeRun();
?>
<h3 class="card-title">{{ __('views/common.modal.routeadminsettings.title') }}</h3>

<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active"
           id="dungeon_route_info_tab" data-toggle="tab" href="#route-info" role="tab"
           aria-controls="dungeon_route_info_tab" aria-selected="false">
            {{ __('views/common.modal.routeadminsettings.dungeon_route_info') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="combatlog_info_tab" data-toggle="tab" href="#combatlog-info"
           role="tab"
           aria-controls="combatlog_info_tab" aria-selected="false">
            {{ __('views/common.modal.routeadminsettings.combatlog_info') }}
        </a>
    </li>
</ul>

<div class="tab-content">
    <div id="route-info" class="tab-pane fade show active mt-3"
         role="tabpanel" aria-labelledby="dungeon_route_info_tab">
        @dump($dungeonRoute)
    </div>
    <div id="combatlog-info" class="tab-pane fade mt-3" role="tabpanel"
         aria-labelledby="combatlog_info_tab">
        <div class="form-group">
            <h5>
                {{ __('views/common.modal.routeadminsettings.challenge_mode_run') }}
            </h5>
            @include('common.general.modeltable', ['model' => $challengeModeRun])
        </div>
        <div class="form-group">
            <h5>
                {{ __('views/common.modal.routeadminsettings.challenge_mode_run_data') }}
            </h5>
            @include('common.general.modeltable', ['model' => $challengeModeRun->challengeModeRunData, 'exclude' => ['post_body']])
            {{ Form::textarea('post_body', json_encode(json_decode($challengeModeRun->challengeModeRunData->post_body), JSON_PRETTY_PRINT), ['class' => 'form-control w-100', 'readonly' => 'readonly']) }}
        </div>
    </div>
</div>