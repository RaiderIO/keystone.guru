<?php

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @var LengthAwarePaginator<int, DungeonRouteCollection> $dungeonRouteCollections
 * @var Collection<int, Collection<int, DungeonRoute>>    $tileDungeonRoutes       Keyed by collection id.
 * @var Collection<int, int>                              $coveredDungeonCounts    Keyed by collection id.
 * @var string|null                                       $search
 * @var Collection<int, DungeonRouteCollectionCategory>   $categories
 * @var DungeonRouteCollectionCategory|null               $selectedCategory
 * @var Collection<int, Dungeon>                          $dungeons
 * @var Dungeon|null                                      $selectedDungeon
 */

$isFiltered = $search !== null || $selectedCategory !== null || $selectedDungeon !== null;
?>
@extends('layouts.sitepage', [
    'wide'  => true,
    'title' => __('view_collection.browse.title'),
])

@section('header-title')
    {{ __('view_collection.browse.header') }}
@endsection

@section('content')
    <p class="text-body-secondary">
        {{ __('view_collection.browse.description') }}
    </p>

    <form method="GET" action="{{ route('collection.browse') }}" class="row g-2 mb-4" role="search">
        <div class="col-12 col-md-6 col-lg-4">
            <label for="collection_search" class="visually-hidden">
                {{ __('view_collection.browse.search_label') }}
            </label>
            <div class="input-group">
                <input type="search"
                       id="collection_search"
                       name="search"
                       class="form-control"
                       maxlength="{{ DungeonRouteCollection::MAX_NAME_LENGTH }}"
                       value="{{ $search }}"
                       placeholder="{{ __('view_collection.browse.search_placeholder') }}"/>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    {{ __('view_collection.browse.search_submit') }}
                </button>
            </div>
            @include('common.forms.form-error', ['key' => 'search'])
        </div>

        @if($categories->isNotEmpty())
            <div class="col-12 col-md-6 col-lg-3">
                <div class="input-group">
                    <label for="collection_category" class="input-group-text">
                        {{ __('view_collection.browse.category_label') }}
                    </label>
                    {{-- Submitted by the search button, so this needs no JS of its own --}}
                    <select id="collection_category" name="category_id" class="form-select">
                        <option value="">{{ __('view_collection.browse.category_any') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}"
                                    @if($selectedCategory?->id === $category->id) selected @endif>
                                {{ $category->getTranslatedName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @include('common.forms.form-error', ['key' => 'category_id'])
            </div>
        @endif

        <div class="col-12 col-md-6 col-lg-3">
            <div class="input-group">
                <label for="collection_dungeon" class="input-group-text">
                    {{ __('view_collection.browse.dungeon_label') }}
                </label>
                <select id="collection_dungeon" name="dungeon" class="form-select">
                    <option value="">{{ __('view_collection.browse.dungeon_any') }}</option>
                    @foreach($dungeons as $dungeon)
                        <option value="{{ $dungeon->slug }}"
                                @if($selectedDungeon?->id === $dungeon->id) selected @endif>
                            {{ __($dungeon->name) }}
                        </option>
                    @endforeach
                </select>
            </div>
            @include('common.forms.form-error', ['key' => 'dungeon'])
        </div>
    </form>

    @if($dungeonRouteCollections->isEmpty())
        <p class="text-body-secondary">
            {{ $isFiltered ? __('view_collection.browse.empty_filtered') : __('view_collection.browse.empty') }}
        </p>
    @else
        <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3">
            @foreach($dungeonRouteCollections as $dungeonRouteCollection)
                <div class="col">
                    @include('common.collection.card', [
                        'dungeonRouteCollection' => $dungeonRouteCollection,
                        'dungeonRoutes'          => $tileDungeonRoutes->get($dungeonRouteCollection->id, collect()),
                        'coveredDungeonCount'    => $coveredDungeonCounts->get($dungeonRouteCollection->id, 0),
                        'showAuthor'             => true,
                    ])
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $dungeonRouteCollections->links() }}
        </div>
    @endif
@endsection
