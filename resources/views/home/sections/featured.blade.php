<?php

?>
<div class="row my-4">
    <h4>Featured</h4>
</div>
<div class="row">
    <div class="col-md-4 mb-3 mb-md-0">
        <a href="https://raider.io/weekly-routes" class="d-block text-center">
            <img src="{{ ksgAssetImage('home/featured_weekly_route_tww_s3.png') }}" alt="Weekly route" class="img-fluid rounded shadow-sm">
        </a>
    </div>
    <div class="col-md-4 mb-3 mb-md-0">
        <a href="{{ route('dungeon.heatmaps.list') }}" class="d-block text-center">
            <img src="{{ ksgAssetImage('home/featured_heatmaps_text.png') }}" alt="Heatmaps" class="img-fluid rounded shadow-sm">
        </a>
    </div>
    <div class="col-md-4">
        <a href="https://www.patreon.com/c/keystoneguru" class="d-block text-center">
            <img src="{{ ksgAssetImage('home/featured_patreon.png') }}" alt="Patreon" class="img-fluid rounded shadow-sm">
        </a>
    </div>

</div>
