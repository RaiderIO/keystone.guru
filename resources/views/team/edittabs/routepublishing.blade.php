<?php

use App\Models\Team;

/**
 * @var Team   $team
 * @var string $inlineId
 */
?>
<div class="tab-pane fade" id="route_publishing" role="tabpanel" aria-labelledby="route-publishing-tab">
    <h4>
        {{ __('view_team.edittabs.routepublishing.title') }}
    </h4>
    @component('common.general.alert', ['type' => 'warning', 'name' => 'team-route-publishing-warning'])
        {{ __('view_team.edittabs.routepublishing.warning') }}
    @endcomponent

    <div class="form-group">
        {{ __('view_team.edittabs.routepublishing.description') }}
    </div>
    <div class="form-group">
        {!! Form::label('route_publishing_enabled', __('view_team.edittabs.routepublishing.enabled')) !!}
        {!! Form::checkbox('route_publishing_enabled', 1, $team->route_publishing_enabled ?? 0, [
            'id' => 'route_publishing_enabled_checkbox',
            'class' => 'form-control left_checkbox'
        ]) !!}
    </div>
    <div class="form-group">
        @include('common.dungeonroute.table', [
            'inlineId' => $inlineId,
            'view' => 'team_route_publishing',
            'team' => $team,
            'tableId' => 'route_publishing_table',
            'filterButtonId' => 'route_publishing_filter_button',
            'dungeonSelectId' => 'route_publishing_dungeon',
            'affixSelectId' => 'route_publishing_affixes',
            'attributesSelectId' => 'route_publishing_attributes',
            'requirementsSelectId' => 'route_publishing_requirements',
            'tagsSelectId' => 'route_publishing_tags',
        ])
    </div>
</div>
