<?php

use App\Models\Team;

/**
 * @var Team $team
 */
?>
<div class="tab-pane fade" id="details" role="tabpanel"
     aria-labelledby="details-tab">
    <div class="">
        <h4>
            {{ __('view_team.edittabs.details.title') }}
        </h4>

        @include('common.team.details', ['model' => $team])
    </div>
</div>
