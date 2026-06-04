<?php

use App\Models\User;

$isFacade = User::getCurrentUserMapFacadeStyle() === User::MAP_FACADE_STYLE_FACADE;
?>
    <!-- Facade toggle -->
<div class="row no-gutters">
    <div id="map_controls_element_facade_toggle_btn"
         class="col btn btn-info"
         data-current="{{ $isFacade ? 'facade' : 'split_floors' }}">
        <i class="fa fa-layer-group"></i>
        <span class="map_controls_element_label_toggle" style="display: none;">
            {{ $isFacade
                ? __('view_common.maps.controls.elements.facadetoggle.split_floors')
                : __('view_common.maps.controls.elements.facadetoggle.facade') }}
        </span>
    </div>
</div>
