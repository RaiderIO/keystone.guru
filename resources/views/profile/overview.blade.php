@extends('layouts.sitepage', ['rootClass' => 'col-xl-10 offset-xl-1', 'wide' => true, 'title' => __('view_profile.overview.title'), 'showAds' => false])

<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;

/**
 * @var string                        $newRouteStyle
 * @var GameVersion                   $currentUserGameVersion
 * @var Collection<int, DungeonRoute> $dungeonRoutes
 */
?>

@section('content')
    <div class="px-4">
        @if( $currentUserGameVersion->has_seasons )
            <div class="row">
                <div class="col">
                    <h3>
                        {{ __('view_profile.overview.route_coverage') }}
                    </h3>
                </div>
                <div class="col-auto">
                    <div class="btn-group" role="group">
                        <input type="radio" id="new_route_style_create" class="btn-check new_route_style_switch_btn"
                               name="new_route_style" autocomplete="off"
                               data-new-route-style="create" {{ $newRouteStyle === 'create' ? 'checked' : '' }}>
                        <label class="btn btn-dark" for="new_route_style_create">
                            <i class="fas fa-plus"></i>
                        </label>
                        <input type="radio" id="new_route_style_search" class="btn-check new_route_style_switch_btn"
                               name="new_route_style" autocomplete="off"
                               data-new-route-style="search" {{ $newRouteStyle === 'create' ? '' : 'checked' }}>
                        <label class="btn btn-dark" for="new_route_style_search">
                            <i class="fas fa-search"></i>
                        </label>
                    </div>
                </div>
            </div>
            <div class="row mb-3 mb-4">
                <div class="col">
                    @include('common.dungeonroute.coverage.affixgroup')
                </div>
            </div>
        @endif

        <h3>
            {{ __('view_profile.overview.route_overview') }}
        </h3>
        @include('common.dungeonroute.table', ['view' => 'profile'])
    </div>
@endsection
