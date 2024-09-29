@extends('layouts.sitepage', ['rootClass' => 'col-xl-10 offset-xl-1', 'wide' => true, 'title' => __('view_profile.overview.title')])

<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;

/**
 * @var string                   $newRouteStyle
 * @var GameVersion              $currentUserGameVersion
 * @var Collection<DungeonRoute> $dungeonRoutes
 */
?>

@section('content')
    @include('common.general.messages')

    @auth
        @if( Auth::user()->dungeonRoutes()->count() === 0)
            <div class="row form-group text-center">
                <div class="col">
                    {{ __('view_profile.overview.welcome_text') }}
                </div>
            </div>
        @endif
    @endauth

    <div class="row form-group mb-4">
        <div class="col text-center">
            <a class="btn btn-info" href="{{ route('profile.favorites') }}">
                <i class="fa fa-star"></i> {{ __('view_profile.overview.favorites') }}
            </a>
        </div>
        <div class="col text-center">
            <a class="btn btn-info" href="{{ route('profile.tags') }}">
                <i class="fa fa-tag"></i> {{ __('view_profile.overview.tags') }}
            </a>
        </div>
        <div class="col text-center">
            <a class="btn btn-info" href="{{ route('team.list') }}">
                <i class="fa fa-users"></i> {{ __('view_profile.overview.teams') }}
            </a>
        </div>
        <div class="col text-center">
            <a class="btn btn-info" href="{{ route('profile.edit') }}">
                <i class="fa fa-user"></i> {{ __('view_profile.overview.profile') }}
            </a>
        </div>
    </div>

    @if( $currentUserGameVersion->has_seasons )
        <div class="row">
            <div class="col">
                <h3>
                    {{ __('view_profile.overview.route_coverage') }}
                </h3>
            </div>
            <div class="col-auto">
                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-dark {{ $newRouteStyle === 'create' ? 'active' : '' }}">
                        <input type="radio" id="new_route_style_create" class="new_route_style_switch_btn" name="theme"
                               autocomplete="off"
                               data-new-route-style="create" {{ $newRouteStyle === 'create' ? 'checked' : '' }}>
                        <i class="fas fa-plus"></i>
                    </label>
                    <label class="btn btn-dark {{ $newRouteStyle === 'create' ? '' : 'active' }}">
                        <input type="radio" id="new_route_style_search" class="new_route_style_switch_btn" name="theme"
                               autocomplete="off"
                               data-new-route-style="search" {{ $newRouteStyle === 'create' ? '' : 'checked' }}>
                        <i class="fas fa-search"></i>
                    </label>
                </div>
            </div>
        </div>
        <div class="row form-group mb-4">
            <div class="col">
                @include('common.dungeonroute.coverage.affixgroup')
            </div>
        </div>
    @endif

    <h3>
        {{ __('view_profile.overview.route_overview') }}
    </h3>
    @include('common.dungeonroute.table', ['view' => 'profile'])
@endsection
