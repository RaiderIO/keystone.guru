<?php

use App\Models\GameVersion\GameVersion;
use App\Models\Season;

/**
 * @var Season|null $currentSeason
 * @var bool        $isPayingPatron
 */

$currentUserGameVersion = GameVersion::getUserOrDefaultGameVersion();
$findRouteLink          = route('dungeonroutes.gameVersion', ['gameVersion' => $currentUserGameVersion]);
?>
<div class="row my-4 px-2">
    <div class="col-12">
        <h4>{{ __('view_home.sections.featured.title') }}</h4>
    </div>
    <div class="col-12">
        <div class="row g-0">
            <div class="col-md-4 mb-3 mb-md-0 mt-4">
                <a href="{{ route('dungeon.dungeonroute.search') }}" class="d-block text-center">
                    <img src="{{ ksgAssetImage('home/featured/revamped_search.png') }}" alt="{{ __('view_home.sections.featured.revamped_search_alt') }}"
                         class="img-fluid rounded shadow-sm">
                </a>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <a href="{{ $findRouteLink }}"
                   class="d-block text-center">
                    <img src="{{ ksgAssetImage('home/featured/find_a_route.png') }}" alt="{{ __('view_home.sections.featured.weekly_route_alt') }}"
                         class="img-fluid rounded shadow-sm border border-accent p-1" style="border-width: 2px !important;">
                </a>
            </div>
            <div class="col-md-4 mt-4">
                @if($isPayingPatron)
                    <a href="{{ sprintf('%s#patreon', route('profile.edit')) }}" class="d-block text-center text-decoration-none">
                        <div class="card ratio rounded shadow-sm" style="--bs-aspect-ratio: 87.2%;">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                                <i class="fab fa-patreon text-patreon mb-3" style="font-size: 3rem;"></i>
                                <h5 class="card-title">{{ __('view_home.sections.featured.patreon_thank_you_title') }}</h5>
                                <p class="card-text mb-0">{{ __('view_home.sections.featured.patreon_thank_you_body') }}</p>
                            </div>
                        </div>
                    </a>
                @else
                    <a href="https://www.patreon.com/c/keystoneguru" class="d-block text-center">
                        <img src="{{ ksgAssetImage('home/featured/patreon.png') }}" alt="{{ __('view_home.sections.featured.patreon_alt') }}"
                             class="img-fluid rounded shadow-sm">
                    </a>
                @endif
            </div>

        </div>
    </div>
</div>
