<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use Illuminate\Support\Collection;

/**
 * @var DungeonRouteCollection        $dungeonRouteCollection
 * @var Collection<int, DungeonRoute> $dungeonRoutes
 */

$title = sprintf(__('view_collection.view.title'), $dungeonRouteCollection->name);
?>
@extends('layouts.sitepage', [
    'wide' => true,
    'title' => $title,
    'showAds' => false,
    'breadcrumbsParams' => [$dungeonRouteCollection],
])

@section('header-title')
    {{ $dungeonRouteCollection->name }}
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <div class="text-body-secondary small mb-2">
                <a href="{{ route('profile.view', ['user' => $dungeonRouteCollection->user]) }}">
                    {{ __('view_collection.view.by_author', ['author' => $dungeonRouteCollection->user->name]) }}
                </a>
                &middot;
                {{ trans_choice('view_collection.view.route_count', $dungeonRoutes->count(), ['count' => $dungeonRoutes->count()]) }}
                @if($dungeonRouteCollection->dungeonRouteCollectionCategory !== null)
                    &middot;
                    <span class="badge bg-info">
                        {{ $dungeonRouteCollection->dungeonRouteCollectionCategory->getTranslatedName() }}
                    </span>
                @endif
            </div>

            @if(!empty($dungeonRouteCollection->description))
                <p class="mb-0">
                    {{ $dungeonRouteCollection->description }}
                </p>
            @endif
        </div>
    </div>

    @if($dungeonRoutes->isEmpty())
        <div class="card">
            <div class="card-body text-center">
                {{ __('view_collection.view.no_routes') }}
            </div>
        </div>
    @else
        @include('common.dungeonroute.cardlist', [
            'cols' => 3,
            'currentAffixGroup' => null,
            'dungeonroutes' => $dungeonRoutes,
            'showDungeonImage' => true,
        ])
    @endif
@endsection
