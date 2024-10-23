<?php

use App\Models\DungeonRoute\DungeonRoute;

/**
 * @var DungeonRoute|null $dungeonroute
 */
?>
<h3 class="card-title">{{ __('view_common.modal.routesettings.title') }}</h3>

@include('common.forms.createroute', ['dungeonroute' => $dungeonroute])
