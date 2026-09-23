<?php

use App\Http\Requests\DungeonRoute\DungeonRouteCollectionIndexFormRequest;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, DungeonRouteCollection> $dungeonRouteCollections
 * @var Collection<int, int>                    $coveredDungeonCounts  Keyed by collection id.
 * @var Collection<int, GameVersion>            $gameVersions
 * @var GameVersion                             $selectedGameVersion
 * @var Collection<int, Season>                 $seasons
 * @var int|string|null                         $selectedSeasonFilter
 * @var bool                                    $mayCreateCollection
 */

$gameVersionOptions = $gameVersions->mapWithKeys(static fn(GameVersion $gameVersion): array => [
    $gameVersion->id => __($gameVersion->name),
])->all();

$seasonOptions = ['' => __('view_collection.index.filter_season_all')]
    + $seasons->mapWithKeys(static fn(Season $season): array => [$season->id => $season->name_long])->all()
    + [DungeonRouteCollectionIndexFormRequest::SEASON_NONE => __('view_collection.index.filter_season_none')];
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_collection.index.title')])

@section('header-title', __('view_collection.index.header'))
@section('header-addition')
    @if($mayCreateCollection)
        <a href="{{ route('collections.new') }}" class="btn btn-success text-white float-end" role="button">
            <i class="fas fa-plus"></i> {{ __('view_collection.index.create_collection') }}
        </a>
    @else
        <button type="button" class="btn btn-success float-end" disabled aria-describedby="collections_max_collections">
            <i class="fas fa-plus"></i> {{ __('view_collection.index.create_collection') }}
        </button>
    @endif
@endsection

@section('content')

    <p class="text-body-secondary">
        {{ __('view_collection.index.description') }}
    </p>
    @if(!$mayCreateCollection)
        <p id="collections_max_collections" class="text-warning">
            {{ __('view_collection.index.max_collections', ['max' => DungeonRouteCollection::MAX_COLLECTIONS]) }}
        </p>
    @endif

    {{ html()->form('GET', route('collections.index'))->class('row g-2 align-items-end mb-3')->open() }}
    <div class="col-auto">
        {{ html()->label(__('view_collection.index.filter_game_version'), 'game_version_id')->class('form-label') }}
        {{ html()->select('game_version_id', $gameVersionOptions, $selectedGameVersion->id)->class('form-select') }}
    </div>
    @if($selectedGameVersion->has_seasons)
        <div class="col-auto">
            {{ html()->label(__('view_collection.index.filter_season'), 'season')->class('form-label') }}
            {{ html()->select('season', $seasonOptions, $selectedSeasonFilter === null ? '' : (string)$selectedSeasonFilter)->class('form-select') }}
        </div>
    @endif
    <div class="col-auto">
        {{ html()->input('submit')->value(__('view_collection.index.filter_submit'))->class('btn btn-primary') }}
    </div>
    {{ html()->form()->close() }}

    @if($dungeonRouteCollections->isEmpty())
        <div class="card">
            <div class="card-body text-center">
                {{ request()->hasAny(['game_version_id', 'season']) ? __('view_collection.index.no_collections_filtered') : __('view_collection.index.no_collections') }}
            </div>
        </div>
    @else
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th width="30%">{{ __('view_collection.index.table_header_name') }}</th>
                <th width="20%">{{ __('view_collection.index.table_header_kind') }}</th>
                <th width="15%">{{ __('view_collection.index.table_header_category') }}</th>
                <th width="10%">{{ __('view_collection.index.table_header_visibility') }}</th>
                <th width="10%">{{ __('view_collection.index.table_header_routes') }}</th>
                <th width="15%"></th>
            </tr>
            </thead>

            <tbody>
            @foreach($dungeonRouteCollections as $dungeonRouteCollection)
                <tr>
                    <td>
                        <a href="{{ route('collections.edit', ['dungeonRouteCollection' => $dungeonRouteCollection]) }}">
                            {{ $dungeonRouteCollection->name }}
                        </a>
                    </td>
                    <td>
                        @include('common.collection.kind', [
                            'dungeonRouteCollection' => $dungeonRouteCollection,
                            'coveredDungeonCount' => $coveredDungeonCounts->get($dungeonRouteCollection->id, 0),
                        ])
                    </td>
                    <td>
                        @if($dungeonRouteCollection->dungeonRouteCollectionCategory !== null)
                            {{ $dungeonRouteCollection->dungeonRouteCollectionCategory->getTranslatedName() }}
                        @else
                            <span class="text-body-secondary">
                                {{ __('view_collection.index.no_category') }}
                            </span>
                        @endif
                    </td>
                    <td>
                        {{ __(sprintf('view_collection.index.published_state.%s', $dungeonRouteCollection->getPublishedStateName())) }}
                        @if($dungeonRouteCollection->team !== null)
                            ({{ $dungeonRouteCollection->team->name }})
                        @endif
                    </td>
                    <td>
                        {{ $dungeonRouteCollection->dungeon_route_collection_routes_count }}
                    </td>
                    <td>
                        <a href="{{ route('collection.view', ['dungeonRouteCollection' => $dungeonRouteCollection]) }}"
                           class="float-end">
                            <i class="fas fa-external-link-alt"></i> {{ __('view_collection.index.view') }}
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endsection
