<?php

use App\Models\Team;
use App\Models\Tags\TagCategory;

/**
 * @var Team $team
 */
?>
<div class="tab-pane fade" id="team_tags" role="tabpanel" aria-labelledby="team-tags-tab">
    <h4>
        {{ __('view_team.edittabs.tags.title') }}
    </h4>
    <p>
        {{ __('view_team.edittabs.tags.description') }}
    </p>

    @include('common.tag.manager', ['category' => TagCategory::DUNGEON_ROUTE_TEAM])
</div>
