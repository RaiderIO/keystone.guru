<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use Illuminate\Support\Collection;

/**
 * @var DungeonRouteCollection                       $dungeonRouteCollection
 * @var Collection<int, DungeonRoute>                $dungeonRoutes
 * @var Collection<int, DungeonRouteCollectionGroup> $dungeonRouteGroups
 * @var string                                       $kindLabel
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
                {{ $kindLabel }}
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

    @if($dungeonRoutes->isEmpty() && !$dungeonRouteCollection->isSeasonSet())
        <div class="card">
            <div class="card-body text-center">
                {{ __('view_collection.view.no_routes') }}
            </div>
        </div>
    @else
        @foreach($dungeonRouteGroups as $dungeonRouteGroup)
            <section class="collection_group mb-4">
                <h2 class="h5">
                    @if(!$dungeonRouteGroup->matchesCollection)
                        {{ __('view_collection.view.foreign') }}
                    @else
                        {{ __($dungeonRouteGroup->dungeon?->name ?? '') }}
                    @endif
                </h2>

                @if($dungeonRouteGroup->dungeonRoutes->isEmpty())
                    <p class="text-body-secondary mb-0">
                        {{ __('view_collection.view.slot_empty', ['dungeon' => __($dungeonRouteGroup->dungeon?->name ?? '')]) }}
                    </p>
                @else
                    @include('common.dungeonroute.cardlist', [
                        'cols' => 3,
                        'currentAffixGroup' => null,
                        'dungeonroutes' => $dungeonRouteGroup->dungeonRoutes,
                        'showDungeonImage' => true,
                    ])
                @endif
            </section>
        @endforeach
    @endif
@endsection
