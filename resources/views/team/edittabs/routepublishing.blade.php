<?php

use App\Models\Team;

/**
 * @var Team $team
 */
?>
<div class="tab-pane fade" id="route_publishing" role="tabpanel" aria-labelledby="route-publishing-tab">
    <h4>
        {{ __('view_team.edittabs.routepublishing.title') }}
    </h4>
    <div class="form-group">
        {{ __('view_team.edittabs.routepublishing.description') }}
    </div>
    <div class="form-group">
        @include('common.dungeonroute.table', [
            'view' => 'team',
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
