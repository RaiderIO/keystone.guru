<?php
/** @var $dungeonroute \App\Models\DungeonRoute\DungeonRoute */
/** @var $edit bool */
?>
<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active"
           id="map_settings_tab" data-toggle="tab" href="#map-settings" role="tab"
           aria-controls="map_settings" aria-selected="false">
            {{ __('view_common.modal.mapsettings.map_settings') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="pull_settings_tab" data-toggle="tab" href="#pull-settings"
           role="tab"
           aria-controls="pull_settings" aria-selected="false">
            {{ __('view_common.modal.mapsettings.pull_settings') }}
        </a>
    </li>
</ul>

<div class="tab-content">
    <div id="map-settings" class="tab-pane fade show active mt-3"
         role="tabpanel" aria-labelledby="map_settings_tab">
        @include('common.forms.mapsettings', ['dungeonroute' => $dungeonroute, 'edit' => $edit])
    </div>
    <div id="pull-settings" class="tab-pane fade mt-3" role="tabpanel"
         aria-labelledby="pull_settings_tab">
        @include('common.forms.pullsettings', ['dungeonroute' => $dungeonroute, 'edit' => $edit])
    </div>
</div>
