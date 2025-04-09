<?php
/** @var bool $isMobile */
?>
@if(!$isMobile)
    <!-- Map object group visibility -->
    <div class="row no-gutters">
        <div class="col">
            <button type="button" id="map_map_zoom_out_btn" class="btn btn-accent px-1 text-center">
                <i class="fa fa-search-minus w-100"></i>
            </button>
        </div>
        <div class="col">
            <button type="button" id="map_map_zoom_in_btn" class="btn btn-accent px-1 text-center">
                <i class="fa fa-search-plus w-100"></i>
            </button>
        </div>
    </div>
@endif
