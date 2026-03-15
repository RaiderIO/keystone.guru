<?php

use App\Models\GameVersion\GameVersion;
use App\Models\Season;

/**
 * @var Season|null $currentSeason
 */

$currentUserGameVersion = GameVersion::getUserOrDefaultGameVersion();
$findRouteLink          = $currentUserGameVersion->has_seasons ?
    route('dungeonroutes.season', ['gameVersion' => $currentUserGameVersion, 'season' => $currentSeason->index]) :
    route('dungeonroutes.gameVersion', ['gameVersion' => $currentUserGameVersion]);
?>
<div class="row my-4 px-2">
    <div class="col-12">
        <h4>{{ __('view_home.sections.featured.title') }}</h4>
    </div>
    <div class="col-12">
        <div class="row no-gutters">
            <div class="col-md-4 mb-3 mb-md-0 mt-4">
                <a href="{{ route('dungeon.heatmaps.list') }}" class="d-block text-center">
                    <img src="{{ ksgAssetImage('home/featured_heatmaps_text.png') }}" alt="{{ __('view_home.sections.featured.heatmaps_alt') }}"
                         class="img-fluid rounded shadow-sm">
                </a>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <a href="{{ $findRouteLink }}"
                   class="d-block text-center">
                    <img src="{{ ksgAssetImage('home/featured_find_your_route.png') }}" alt="{{ __('view_home.sections.featured.weekly_route_alt') }}"
                         class="img-fluid rounded shadow-sm border border-accent" style="border-width: 3px !important;">
                </a>
            </div>
            <div class="col-md-4 mt-4">
                <a href="https://www.patreon.com/c/keystoneguru" class="d-block text-center">
                    <img src="{{ ksgAssetImage('home/featured_patreon.png') }}" alt="{{ __('view_home.sections.featured.patreon_alt') }}"
                         class="img-fluid rounded shadow-sm">
                </a>
            </div>

        </div>
    </div>
</div>
