<?php
/** @var $dungeonroute \App\Models\DungeonRoute\DungeonRoute|null */

?>
<h3 class="card-title">{{ __('views/common.modal.routesettings.title') }}</h3>

@include('common.forms.createroute', ['dungeonroute' => $dungeonroute])
