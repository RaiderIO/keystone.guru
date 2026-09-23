<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Service\DungeonRoute\Dtos\DungeonRouteCollectionGroup;
use Illuminate\Support\Collection;

/**
 * @var DungeonRouteCollection                       $dungeonRouteCollection
 * @var Collection<int, DungeonRoute>                $dungeonRoutes
 * @var Collection<int, DungeonRouteCollectionGroup> $dungeonRouteGroups
 * @var int                                          $coveredDungeonCount
 */

$author = $dungeonRouteCollection->user;
$title  = __('view_collection.view.title_by_author', [
    'name'   => $dungeonRouteCollection->name,
    'author' => $author->name,
]);

$groupAnchor = static fn(DungeonRouteCollectionGroup $dungeonRouteGroup): string => sprintf(
    'collection_dungeon_%d',
    $dungeonRouteGroup->dungeon->id ?? 0,
);

$isEmpty = $dungeonRoutes->isEmpty();
?>
@extends('layouts.sitepage', [
    'title' => $title,
    'breadcrumbsParams' => [$dungeonRouteCollection],
])

@include('common.general.inline', ['path' => 'collection/view', 'options' => [
    'copyLinkButtonSelector' => '#collection_copy_link',
]])

@section('content')
    <header class="card collection_header my-4">
        <div class="card-body collection_header_body">
            <div class="collection_header_text">
                <h1 class="collection_header_name h3">
                    {{ $dungeonRouteCollection->name }}
                </h1>

                <div class="collection_header_meta text-body-secondary small">
                    <a href="{{ route('profile.view', ['user' => $author]) }}" class="collection_header_author">
                        @if($author->iconfile !== null)
                            <img src="{{ $author->iconfile->getURL() }}" alt="" class="collection_header_avatar"/>
                        @else
                            <span class="collection_header_avatar collection_header_initials" aria-hidden="true">
                                {{ $author->initials }}
                            </span>
                        @endif
                        {{ __('view_collection.view.by_author', ['author' => $author->name]) }}
                    </a>
                    <span aria-hidden="true">&middot;</span>
                    <span>
                        @include('common.collection.kind', [
                            'dungeonRouteCollection' => $dungeonRouteCollection,
                            'coveredDungeonCount' => $coveredDungeonCount,
                        ])
                    </span>
                    <span aria-hidden="true">&middot;</span>
                    <span>
                        {{ trans_choice('view_collection.view.route_count', $dungeonRoutes->count(), ['count' => $dungeonRoutes->count()]) }}
                    </span>
                    @if($dungeonRouteCollection->dungeonRouteCollectionCategory !== null)
                        <span class="badge bg-info">
                            {{ $dungeonRouteCollection->dungeonRouteCollectionCategory->getTranslatedName() }}
                        </span>
                    @endif
                </div>

                @if(!empty($dungeonRouteCollection->description))
                    <p class="collection_header_description">
                        {{ $dungeonRouteCollection->description }}
                    </p>
                @endif
            </div>

            <div class="collection_header_actions">
                <button type="button" id="collection_copy_link" class="btn btn-primary"
                        data-url="{{ route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]) }}">
                    <i class="fas fa-link" aria-hidden="true"></i> {{ __('view_collection.view.copy_link') }}
                </button>
                @can('edit', $dungeonRouteCollection)
                    <a href="{{ route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]) }}"
                       class="btn btn-primary">
                        <i class="fas fa-pencil-alt" aria-hidden="true"></i> {{ __('view_collection.view.edit') }}
                    </a>
                @endcan
            </div>
        </div>
    </header>

    @if($isEmpty)
        <div class="card">
            <div class="card-body text-center">
                @if($dungeonRouteCollection->mayUserEdit(Auth::user()))
                    <p>{{ __('view_collection.view.no_routes_owner') }}</p>
                    <a href="{{ route('collections.edit', $dungeonRouteCollection) }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> {{ __('view_collection.view.add_routes') }}
                    </a>
                @else
                    {{ __('view_collection.view.no_routes') }}
                @endif
            </div>
        </div>
    @else
        <div class="row row-cols-1 row-cols-lg-2 g-3">
            @foreach($dungeonRouteGroups as $dungeonRouteGroup)
                <?php
                $dungeonName       = __($dungeonRouteGroup->dungeon->name ?? '');
                $dungeonRouteCount = $dungeonRouteGroup->dungeonRoutes->count();
                $headingId         = sprintf('%s_heading', $groupAnchor($dungeonRouteGroup));
                ?>
                <div class="col">
                    <section id="{{ $groupAnchor($dungeonRouteGroup) }}" class="collection_slot h-100"
                             style="background-image: url('{{ $dungeonRouteGroup->dungeon?->getImageUrl() }}')"
                             aria-labelledby="{{ $headingId }}">
                        <div class="collection_slot_scrim h-100 p-3">
                            <div class="collection_slot_header">
                                <h2 id="{{ $headingId }}" class="h6 fw-bold mb-0">{{ $dungeonName }}</h2>
                                @if($dungeonRouteCount > 0)
                                    <span class="small">
                                        {{ trans_choice('view_collection.view.route_count', $dungeonRouteCount, ['count' => $dungeonRouteCount]) }}
                                    </span>
                                @endif
                            </div>

                            @if($dungeonRouteCount === 0)
                                <p class="collection_slot_empty mb-0">
                                    {{ __('view_collection.view.slot_empty', ['dungeon' => $dungeonName]) }}
                                </p>
                            @else
                                <ol class="collection_route_grid list-unstyled mb-0">
                                    @foreach($dungeonRouteGroup->dungeonRoutes as $dungeonRoute)
                                        <li class="collection_route_item{{ $dungeonRouteCount > 1 ? ' collection_route_item_numbered' : '' }}">
                                            @if($dungeonRouteCount > 1)
                                                <span class="collection_route_position" aria-hidden="true">{{ $loop->iteration }}</span>
                                            @endif
                                            @include('common.dungeonroute.cardposter', [
                                                'dungeonroute' => $dungeonRoute,
                                                'currentAffixGroup' => null,
                                                'tierAffixGroup' => null,
                                                'showDungeonImage' => true,
                                                'cache' => true,
                                            ])
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                    </section>
                </div>
            @endforeach
        </div>
    @endif
@endsection
